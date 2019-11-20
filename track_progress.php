<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * track_progress.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/workshop/locallib.php');

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\entities\providers\user_provider;
use plagiarism_unicheck\classes\helpers\unicheck_progress;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_core;

global $USER;

require_sesskey();

$action = required_param('action', PARAM_ALPHAEXT);
$cmid = required_param('cmid', PARAM_INT);
$fileids = required_param('fileids', PARAM_SEQUENCE);

$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);

require_login($course);

$context = context_module::instance($cmid);
$fileids = explode(',', $fileids);

$grader = false;
$member = false;
$userids = [$USER->id];
switch ($cm->modname) {
    case UNICHECK_MODNAME_ASSIGN:
        $assign = new \assign($context, null, null);
        if ($assign->can_grade($USER->id)) {
            $grader = true;
            break;
        }

        if ($assign->get_instance()->teamsubmission) {
            $submission = $assign->get_group_submission($USER->id, 0, false);
            if ($submission) {
                $userids = user_provider::get_users_by_group($submission->groupid);
                $member = true;

                break;
            }
        }

        $submission = $assign->get_user_submission($USER->id, false);
        if ($submission) {
            $member = true;
        }

        break;
    case UNICHECK_MODNAME_FORUM:
        if (has_capability('mod/forum:viewdiscussion', $context)) {
            $member = true;
            $userids = null;
        }
        break;
    case UNICHECK_MODNAME_WORKSHOP:
        $grader = has_capability('moodle/grade:viewall', $context);
        if ($grader) {
            break;
        }

        if (has_capability('mod/workshop:view', $context)) {
            $member = true;
        }

        break;
}

if (!in_array(true, [$member, $grader])) {
    header('HTTP/1.1 403 Forbidden');
    die;
}

// Show all files progress for grader.
if ($grader) {
    $userids = null;
}

$records = unicheck_file_provider::find_by_ids_for_context($fileids, $cmid, $userids);
if (empty($records)) {
    header('HTTP/1.0 404 not found');
    die;
}

$response = [];
try {
    foreach ($records as $record) {
        switch ($record->state) {
            case unicheck_file_state::UPLOADING:
                unicheck_progress::track_upload($record);
                break;
            case unicheck_file_state::HAS_ERROR:
                $response[$record->id] = [
                    'file_id' => $record->id,
                    'state'   => $record->state,
                    'content' => unicheck_progress::gen_row_content_score($cmid, $record),
                ];
                break;
            default:
                $trackedfiles = [];
                $progressinfo = unicheck_progress::get_check_progress_info($record, $cmid, $trackedfiles);
                if ($progressinfo) {
                    $response[$record->id] = $progressinfo;
                }

                if (!empty($trackedfiles[$record->id]['checks'])) {
                    unicheck_progress::get_real_check_progress($cmid, $trackedfiles, $response);
                }

                break;
        }
    }
} catch (\Exception $ex) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
    $response['error'] = $ex->getMessage();
}

echo unicheck_core::json_response($response);

die;