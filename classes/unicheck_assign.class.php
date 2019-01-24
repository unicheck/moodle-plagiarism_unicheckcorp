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
 * unicheck_assign.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

use assign;
use coding_exception;
use context_module;
use plagiarism_unicheck;
use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\entities\unicheck_archive;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_assign
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_assign {
    /**
     * Mod name in DB
     */
    const DB_NAME = 'assign';

    /**
     * get_user_submission_by_cmid
     *
     * @param int  $cmid
     * @param null $userid
     *
     * @return bool|\stdClass
     */
    public static function get_user_submission_by_cmid($cmid, $userid = null) {
        global $USER;

        try {
            $modulecontext = context_module::instance($cmid);
            $assign = new assign($modulecontext, false, false);
        } catch (\Exception $ex) {
            return false;
        }

        return ($assign->get_user_submission(($userid !== null) ? $userid : $USER->id, false));
    }

    /**
     * check_submitted_assignment
     *
     * @param int $id
     *
     * @throws coding_exception
     */
    public static function check_submitted_assignment($id) {
        $plagiarismfile = unicheck_file_provider::get_by_id($id);
        if (!unicheck_file_provider::can_start_check($plagiarismfile)) {
            return;
        }

        $cm = get_coursemodule_from_id('', $plagiarismfile->cm);

        if (plagiarism_unicheck::is_support_mod($cm->modname)) {
            $file = get_file_storage()->get_file_by_hash($plagiarismfile->identifier);
            if ($file->is_directory()) {
                return;
            }

            $ucore = new unicheck_core($plagiarismfile->cm, $plagiarismfile->userid, $cm->modname);
            try {
                if (plagiarism_unicheck::is_archive($file)) {
                    (new unicheck_archive($file, $ucore))->upload();
                } else {
                    unicheck_adhoc::upload($file, $ucore);
                }
            } catch (\Exception $exception) {
                unicheck_file_provider::to_error_state($plagiarismfile, $exception->getMessage());
            }
        }
    }

    /**
     * get_area_files
     *
     * @param int  $contextid
     * @param bool $itemid
     *
     * @return \stored_file[]
     */
    public static function get_area_files($contextid, $itemid = false) {
        return get_file_storage()->get_area_files($contextid, 'assignsubmission_file', 'submission_files', $itemid, null, false);
    }

    /**
     * Check is draft
     *
     * @param int $id
     *
     * @return bool
     */
    public static function is_draft($id) {
        global $DB;

        $sql = 'SELECT COUNT(id) FROM {assign_submission} WHERE id = ? AND status = ?';

        return (bool)$DB->count_records_sql($sql, [$id, 'draft']);
    }

    /**
     * Get assign
     *
     * @param int $id
     *
     * @return \stdClass
     */
    public static function get($id) {
        global $DB;

        return $DB->get_record(self::DB_NAME, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Get assign by cmid
     *
     * @param integer $cmid
     *
     * @return \stdClass
     */
    public static function get_by_cmid($cmid) {
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);

        return self::get($cm->instance);
    }

    /**
     * get_assign_by_cm
     *
     * @param context_module $contextmodule
     *
     * @return assign|bool
     */
    public static function get_assign_by_cm(context_module $contextmodule) {
        try {
            return new assign($contextmodule, false, false);
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Get onlinetext submission information from the database
     *
     * @param  int $submissionid
     *
     * @return mixed
     */
    public static function get_onlinetext_submission($submissionid) {
        global $DB;

        return $DB->get_record('assignsubmission_onlinetext', ['submission' => $submissionid]);
    }
}