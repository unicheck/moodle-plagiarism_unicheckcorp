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
 * api_user_updated.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   2019 UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\event;

use core\event\base;
use plagiarism_unicheck;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/plagiarism/unicheck/lib.php');

/**
 * Class api_user_updated
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 *
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   2019 UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_user_updated extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->context = \context_system::instance();
        $this->data['objecttable'] = UNICHECK_USER_DATA_TABLE;
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public static function get_name() {
        return plagiarism_unicheck::trans('event:api_user_updated');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $token = s($this->other['external_token']);

        return "API user with token '$token' updated";
    }

    /**
     * Create from api user
     *
     * @param object $apiuser
     *
     * @return base
     */
    public static function create_from_apiuser($apiuser) {
        return self::create([
            'relateduserid' => $apiuser->user_id,
            'objectid'      => $apiuser->id,
            'other'         => [
                'external_user_id' => $apiuser->external_user_id,
                'external_token'   => $apiuser->external_token
            ]
        ]);
    }
}