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
 * Class unicheck_check_helper
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\helpers;

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_api;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_notification;
use plagiarism_unicheck\classes\unicheck_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_check_helper
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_check_helper {
    /**
     * check_complete
     *
     * @param \stdClass $record
     * @param \stdClass $check
     * @param int       $progress
     * @return bool
     */
    public static function check_complete(\stdClass &$record, \stdClass $check, $progress = 100) {
        global $DB;

        if ($progress == 100) {
            $record->state = unicheck_file_state::CHECKED;
        }

        $record->similarityscore = (float)$check->report->similarity;
        $record->reporturl = $check->report->view_url;
        $record->reportediturl = $check->report->view_edit_url;
        $record->progress = round($progress, 0, PHP_ROUND_HALF_DOWN);

        $updated = unicheck_file_provider::save($record);

        $emailstudents = unicheck_settings::get_assign_settings($record->cm, 'unicheck_studentemail');
        if ($updated && !empty($emailstudents)) {
            unicheck_notification::send_student_email_notification($record);
        }

        if ($updated && $record->parent_id !== null) {
            $parentrecord = $DB->get_record(UNICHECK_FILES_TABLE, ['id' => $record->parent_id]);
            $childs = $DB->get_records_select(UNICHECK_FILES_TABLE, "parent_id = ? AND state not in (?)",
                [$record->parent_id, unicheck_file_state::HAS_ERROR]);

            $similarity = 0;
            $parentprogress = 0;
            foreach ($childs as $child) {
                $parentprogress += $child->progress;
                $similarity += $child->similarityscore;
            }

            $parentprogress = round($parentprogress / count($childs), 2, PHP_ROUND_HALF_DOWN);
            $reporturl = new \moodle_url('/plagiarism/unicheck/reports.php', [
                'cmid' => $parentrecord->cm,
                'pf'   => $parentrecord->id,
            ]);

            $parentcheck = [
                'report' => [
                    'similarity'    => round($similarity / count($childs), 2, PHP_ROUND_HALF_DOWN),
                    'view_url'      => (string)$reporturl->out_as_local_url(),
                    'view_edit_url' => (string)$reporturl->out_as_local_url(),
                ],
            ];

            $parentcheck = json_decode(json_encode($parentcheck));
            self::check_complete($parentrecord, $parentcheck, $parentprogress);
        }

        return $updated;
    }

    /**
     * run_plagiarism_detection
     *
     * @param \stdClass $plagiarismfile
     */
    public static function run_plagiarism_detection(\stdClass $plagiarismfile) {
        if (isset($plagiarismfile->external_file_id)) {
            if ($plagiarismfile->check_id) {
                unicheck_api::instance()->delete_check($plagiarismfile);
            }

            unicheck_notification::success('plagiarism_run_success', true);

            unicheck_response::handle_check_response(unicheck_api::instance()->run_check($plagiarismfile), $plagiarismfile);
        } else {
            $error = unicheck_core::parse_json($plagiarismfile->errorresponse);
            if (isset($error[0]) && is_object($error[0])) {
                unicheck_notification::error('Can\'t run check: ' . $error[0]->message, false);
            }
        }
    }
}