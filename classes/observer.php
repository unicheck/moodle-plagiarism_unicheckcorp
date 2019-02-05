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
 * plagiarism_unicheck_observer.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot . '/plagiarism/unicheck/lib.php');

use core\event\base;
use plagiarism_unicheck\classes\observers\assessable_observer;
use plagiarism_unicheck\classes\observers\event_validator;
use plagiarism_unicheck\classes\observers\file_observer;
use plagiarism_unicheck\classes\observers\online_text_observer;
use plagiarism_unicheck\classes\observers\submission_observer;
use plagiarism_unicheck\classes\observers\workshop_observer;
use plagiarism_unicheck\classes\services\storage\pluginfile_url;
use plagiarism_unicheck\classes\unicheck_core;

/**
 * Class plagiarism_unicheck_observer
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_unicheck_observer {

    /**
     * assignsubmission_file_submission_updated
     *
     * @param base $event
     */
    public static function assignsubmission_file_submission_updated(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        file_observer::instance()->file_submitted(self::get_core($event), $event);
    }

    /**
     * assignsubmission_file_assessable_uploaded
     *
     * @param base $event
     */
    public static function assignsubmission_file_assessable_uploaded(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        file_observer::instance()->file_submitted(self::get_core($event), $event);
    }

    /**
     * assignsubmission_onlinetext_assessable_uploaded
     *
     * @param base $event
     */
    public static function assignsubmission_onlinetext_assessable_uploaded(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        online_text_observer::instance()->assessable_uploaded(self::get_core($event), $event);
    }

    /**
     * mod_forum_assessable_uploaded
     *
     * @param base $event
     */
    public static function mod_forum_assessable_uploaded(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        $core = self::get_core($event);

        $pluginfileurl = new pluginfile_url();
        $pluginfileurl->set_component($event->component);
        $pluginfileurl->set_filearea('post');

        online_text_observer::instance()->assessable_uploaded($core, $event, $pluginfileurl);
        file_observer::instance()->file_submitted($core, $event);
    }

    /**
     * mod_workshop_assessable_uploaded
     *
     * @param base $event
     */
    public static function mod_workshop_assessable_uploaded(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        self::get_core($event)->create_file_from_onlinetext_event($event);
    }

    /**
     * mod_assign_assessable_submitted
     *
     * @param base $event
     */
    public static function mod_assign_assessable_submitted(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        assessable_observer::instance()->submitted(self::get_core($event), $event);
    }

    /**
     * mod_workshop_phase_switched
     *
     * @param base $event
     */
    public static function mod_workshop_phase_switched(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        workshop_observer::instance()->phase_switched(self::get_core($event), $event);
    }

    /**
     * mod_assign_submission_status_updated
     *
     * @param base $event
     */
    public static function mod_assign_submission_status_updated(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        submission_observer::instance()->status_updated(self::get_core($event), $event);
    }

    /**
     * mod_assign_submission_status_viewed
     *
     * @param base $event
     */
    public static function mod_assign_submission_status_viewed(base $event) {
        if (!self::can_observe($event)) {
            return;
        }

        submission_observer::instance()->status_viewed(self::get_core($event), $event);
    }

    /**
     * get_core
     *
     * @param base $event
     * @return unicheck_core
     */
    private static function get_core(base $event) {
        $cm = get_coursemodule_from_id('', $event->get_context()->instanceid);

        return new unicheck_core($event->get_context()->instanceid, $event->userid, $cm->modname);
    }

    /**
     * can_observe
     *
     * @param base $event
     * @return bool
     */
    private static function can_observe(base $event) {
        if (event_validator::validate_event($event)) {
            return true;
        }

        return false;
    }
}