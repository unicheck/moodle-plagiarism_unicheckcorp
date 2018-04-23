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
 * submission_observer.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\observers;

use core\event\base;
use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class submission_observer
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_observer extends abstract_observer {
    /**
     * DRAFT_STATUS
     */
    const DRAFT_STATUS = 'draft';

    /**
     * handle_event
     *
     * @param unicheck_core $core
     * @param base          $event
     *
     * @return bool
     */
    public function status_updated(unicheck_core $core, base $event) {

        global $DB;
        if (!isset($event->other['newstatus'])) {
            return false;
        }
        $newstatus = $event->other['newstatus'];
        if (!$event->relateduserid) {
            $core->enable_teamsubmission();
        } else {
            $core->userid = $event->relateduserid;
        }

        if ($newstatus == self::DRAFT_STATUS) {
            $unfiles = \plagiarism_unicheck::get_area_files($event->contextid, UNICHECK_DEFAULT_FILES_AREA, $event->objectid);
            $assignfiles = unicheck_assign::get_area_files($event->contextid, $event->objectid);

            $files = array_merge($unfiles, $assignfiles);

            $ids = [];
            foreach ($files as $file) {
                $plagiarismentity = $core->get_plagiarism_entity($file);
                $internalfile = $plagiarismentity->get_internal_file();
                $ids[] = $internalfile->id;
            }

            $allrecordssql = implode(',', $ids);
            $DB->delete_records_select(UNICHECK_FILES_TABLE, "id IN ($allrecordssql) OR parent_id IN ($allrecordssql)");
        }

        return true;
    }

    /**
     * status_viewed
     *
     * @param unicheck_core $core
     * @param base          $event
     */
    public function status_viewed(unicheck_core $core, base $event) {
        $submission = unicheck_assign::get_user_submission_by_cmid($event->contextinstanceid);
        if (!$submission) {
            return;
        }

        $assign = unicheck_assign::get($submission->assignment);

        /* Only for team submission */
        if ($submission->status == self::DRAFT_STATUS || !(bool)$assign->teamsubmission) {
            return;
        }

        /* All users of group must confirm submission */
        if ((bool)$assign->requireallteammemberssubmit && !$this->all_users_confirm_submition($assign)) {
            return;
        }

        $core->enable_teamsubmission();

        $assignfiles = unicheck_assign::get_area_files($event->contextid);
        foreach ($assignfiles as $assignfile) {
            $plagiarismentity = $core->get_plagiarism_entity($assignfile);
            $internalfile = $plagiarismentity->get_internal_file();

            if ($internalfile->state == unicheck_file_state::CHECKED || $internalfile->check_id) {
                continue;
            }

            try {
                if (\plagiarism_unicheck::is_archive($assignfile)) {
                    $archive = new unicheck_archive($assignfile, $core);
                    $archive->upload();

                    continue;
                }

                if ($internalfile->external_file_id == null) {
                    $this->add_after_handle_task($assignfile);
                }
            } catch (\Exception $exception) {
                unicheck_file_provider::to_error_state($internalfile, $exception->getMessage());
            }
        }

        $this->after_handle_event($core);
    }

    /**
     * all_users_confirm_submition
     *
     * @param \stdClass $assign
     * @return bool
     */
    private function all_users_confirm_submition($assign) {
        global $USER;

        list($course, $cm) = get_course_and_cm_from_instance($assign, 'assign');

        $assign = new \assign(\context_module::instance($cm->id), $cm, $course);

        $submgroup = $assign->get_submission_group($USER->id);
        if (!$submgroup) {
            return false;
        }

        $notsubmitted = $assign->get_submission_group_members_who_have_not_submitted($submgroup->id, true);

        return count($notsubmitted) == 0;
    }
}