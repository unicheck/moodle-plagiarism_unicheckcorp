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
 * abstract_observer.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\observers;

use core\event\base;
use plagiarism_unicheck\classes\exception\unicheck_exception;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_adhoc;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;
use stored_file;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Class unicheck_abstract_event
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class abstract_observer {
    /** @var */
    protected static $instance;
    /** @var stored_file[] */
    protected $tasks = [];

    /**
     * Get instance
     *
     * @return static
     */
    public static function instance() {
        $class = get_called_class();

        if (!isset(static::$instance[$class])) {
            static::$instance[$class] = new static;
        }

        return static::$instance[$class];
    }

    /**
     * is_submition_draft
     *
     * @param base $event
     *
     * @return bool
     */
    public static function is_submition_draft(base $event) {
        if ($event->objecttable != 'assign_submission') {
            return false;
        }

        $submission = unicheck_assign::get_user_submission_by_cmid($event->contextinstanceid);
        if (!$submission) {
            return true;
        }

        return ($submission->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED);
    }

    /**
     * after_handle_event
     *
     * @param unicheck_core $ucore
     *
     * @throws unicheck_exception
     */
    protected function after_handle_event(unicheck_core $ucore) {
        if (empty($this->tasks)) {
            // Skip this file check cause assign is draft.
            return;
        }

        foreach ($this->tasks as $storedfile) {
            if (!$storedfile instanceof stored_file) {
                continue;
            }
            $plagiarismentity = $ucore->get_plagiarism_entity($storedfile);
            if (null === $plagiarismentity) {
                continue;
            }

            $internalfile = $plagiarismentity->get_internal_file();
            if ($internalfile->state == unicheck_file_state::HAS_ERROR) {
                continue;
            }

            if (!isset($internalfile->external_file_uuid)) {
                unicheck_adhoc::upload($storedfile, $ucore);
                continue;
            }

            if (!isset($internalfile->check_id)) {
                unicheck_adhoc::check($internalfile);
            }
        }
    }

    /**
     * add_after_handle_task
     *
     * @param stored_file $file
     */
    protected function add_after_handle_task(stored_file $file) {
        array_push($this->tasks, $file);
    }

    /**
     * all_users_confirm_submition
     *
     * @param \stdClass $assign
     *
     * @return bool
     */
    protected function all_users_confirm_submition($assign) {
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