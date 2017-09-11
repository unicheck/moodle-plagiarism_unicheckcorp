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
 * unicheck_event_group_submition.class.php
 *
 * @package     plagiarism_unicheck
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\event;

use core\event\base;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_event_group_submition
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_event_group_submition extends unicheck_abstract_event {
    /**
     * handle_event
     *
     * @param unicheck_core $core
     * @param base          $event
     */
    public function handle_event(unicheck_core $core, base $event) {

        $submission = unicheck_assign::get_user_submission_by_cmid($event->contextinstanceid);
        if (!$submission) {
            return;
        }

        $assign = unicheck_assign::get($submission->assignment);

        /* Only for team submission */
        if ($submission->status == unicheck_event_submission_updated::DRAFT_STATUS || !(bool) $assign->teamsubmission) {
            return;
        }

        /* All users of group must confirm submission */
        if ((bool) $assign->requireallteammemberssubmit && !$this->all_users_confirm_submition($assign)) {
            return;
        }

        $core->enable_teamsubmission();

        $assignfiles = unicheck_assign::get_area_files($event->contextid);
        foreach ($assignfiles as $assignfile) {
            $plagiarismentity = $core->get_plagiarism_entity($assignfile);
            $internalfile = $plagiarismentity->get_internal_file();

            if ($internalfile->statuscode == UNICHECK_STATUSCODE_PROCESSED) {
                continue;
            }

            if (\plagiarism_unicheck::is_archive($assignfile)) {
                $archive = new unicheck_archive($assignfile, $core);
                $archive->run_checks();

                continue;
            }

            if ($internalfile->check_id) {
                continue;
            }

            if ($internalfile->external_file_id == null) {
                $plagiarismentity->upload_file_on_server();
                $this->add_after_handle_task($plagiarismentity);
            }
        }

        $this->after_handle_event();
    }

    /**
     * all_users_confirm_submition
     *
     * @param mixed $assign
     *
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