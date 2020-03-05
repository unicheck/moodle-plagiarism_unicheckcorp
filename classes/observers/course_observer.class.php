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
 * course_observer.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\observers;

use core\event\base;
use core\event\course_viewed;
use plagiarism_unicheck\classes\entities\providers\user_provider;
use plagiarism_unicheck\classes\unicheck_api;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_settings;
use plagiarism_unicheck\event\api_user_updated;
use plagiarism_unicheck\event\error_handled;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class course_observer
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_observer extends abstract_observer {
    /**
     * Handle user updated event
     *
     * @param base $event
     *
     * @return void
     */
    public function course_viewed(base $event) {
        if (!$event instanceof course_viewed) {
            return;
        }

        try {
            $moodleuser = unicheck_core::get_user($event->userid);
            if (!$moodleuser) {
                return;
            }

            $apikey = unicheck_settings::get_settings('client_id');
            $plagiarismuser = user_provider::find_by_user_id_and_api_key($event->userid, $apikey);
            if (!$plagiarismuser || empty($plagiarismuser->external_token)) {
                return;
            }

            $cangrade = unicheck_core::is_teacher($event->courseid, $event->userid, CONTEXT_COURSE);
            $response = unicheck_api::instance()->user_update(
                $plagiarismuser->external_token,
                $moodleuser,
                $cangrade ? unicheck_api::ACCESS_SCOPE_WRITE : unicheck_api::ACCESS_SCOPE_READ
            );

            if ($response && $response->result) {
                api_user_updated::create_from_apiuser($plagiarismuser)->trigger();
            }
        } catch (\Exception $exception) {
            error_handled::create_from_exception($exception)->trigger();
        }
    }
}