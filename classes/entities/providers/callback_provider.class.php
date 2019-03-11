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
 * callback_provider.class.php
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

/**
 * Class callback_provider
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   2019 UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class callback_provider {

    /**
     * Find callback by event id
     *
     * @param int $eventid
     *
     * @return mixed
     */
    public static function find_by_event_id($eventid) {
        global $DB;

        return $DB->get_record(UNICHECK_CALLBACK_TABLE, ['event_id' => $eventid]);
    }

    /**
     * Create plagiarism callback
     *
     * @param \stdClass $callback
     *
     * @return bool|int
     */
    public static function create(\stdClass $callback) {
        global $DB;

        $callback->timecreated = time();
        $callback->timemodified = time();

        return $DB->insert_record(UNICHECK_CALLBACK_TABLE, $callback);
    }

    /**
     * Update plagiarism callback
     *
     * @param \stdClass $callback
     *
     * @return bool
     */
    public static function save(\stdClass $callback) {
        global $DB;

        $callback->timemodified = time();

        return $DB->update_record(UNICHECK_CALLBACK_TABLE, $callback);
    }
}
