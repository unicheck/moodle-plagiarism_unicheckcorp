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
 * user_provider.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   2019 UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\entities\providers;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;

require_once("$CFG->dirroot/mod/assign/locallib.php");

/**
 * Class user_provider
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   2019 UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_provider {

    /**
     * Find plagiarism user by id
     *
     * @param int $id
     *
     * @return mixed
     */
    public static function find_by_id($id) {
        global $DB;

        return $DB->get_record(UNICHECK_USER_DATA_TABLE, ['id' => $id]);
    }

    /**
     * Find plagiarism user by moodle user id and current Unicheck API key
     *
     * @param int    $userid
     * @param string $apikey
     *
     * @return mixed
     */
    public static function find_by_user_id_and_api_key($userid, $apikey) {
        global $DB;

        return $DB->get_record(UNICHECK_USER_DATA_TABLE, ['user_id' => $userid, 'api_key' => $apikey]);
    }

    /**
     * Create plagiarism user
     *
     * @param object $user
     *
     * @return bool|int
     */
    public static function create($user) {
        global $DB;

        return $DB->insert_record(UNICHECK_USER_DATA_TABLE, $user);
    }

    /**
     * get_users_by_group
     *
     * @param int $groupid
     *
     * @return array
     */
    public static function get_users_by_group($groupid) {
        global $DB;

        $groupmembers = $DB->get_fieldset_select(
            'groups_members',
            'userid',
            'groupid = :groupid',
            ['groupid' => $groupid]
        );

        return $groupmembers;
    }
}
