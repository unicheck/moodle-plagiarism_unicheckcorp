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
 * check.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

use plagiarism_unicheck\classes\permissions\capability;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once(dirname(__FILE__) . '/lib.php');

$cmid = required_param('cmid', PARAM_INT); // Course Module ID
$uid = required_param('uid', PARAM_INT); // User ID
$submissiontype = required_param('submissiontype', PARAM_TEXT); // submission type.
$pf = optional_param('pf', null, PARAM_INT); // plagiarism file id.

require_sesskey();
require_login();

$url = new moodle_url(dirname(__FILE__) . '/check.php');
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);

$PAGE->set_url($url);
require_login($cm->course, true, $cm);

$modulecontext = context_module::instance($cmid);
require_capability(capability::CHECK_FILE, $modulecontext);

$ucore = new unicheck_core($cmid, $uid, $cm->modname);
$fs = get_file_storage();

switch ($cm->modname) {
    case UNICHECK_MODNAME_ASSIGN:
        $redirect = new moodle_url('/mod/assign/view.php', ['id' => $cmid, 'action' => 'grading']);
        if ($submissiontype == 'onlinetext' && null == $pf) {
            $assign = unicheck_assign::get_assign_by_cm($modulecontext);
            $submission = $assign->get_user_submission($uid, false);
            $onlinetextsubmission = unicheck_assign::get_onlinetext_submission($submission->id);
            $user = $DB->get_record("user", ["id" => $uid], '*', MUST_EXIST);

            $storedfile = $ucore->create_file_from_content(
                trim($onlinetextsubmission->onlinetext),
                'assign_submission',
                $modulecontext->id,
                $submission->id
            );

            $internalfile = $ucore->get_plagiarism_entity($storedfile)->get_internal_file();
            $pf = $internalfile->id;
        }
        break;
    case 'assignment':
        $redirect = new moodle_url('/mod/assignment/submissions.php', ['id' => $cmid]);
        break;
    default:
        $redirect = $CFG->wwwroot;
}

if ($pf) {
    unicheck_assign::check_submitted_assignment($pf);
}

redirect($redirect, plagiarism_unicheck::trans('check_start'));