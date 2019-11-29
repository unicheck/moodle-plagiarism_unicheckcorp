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
 * config_provider.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\entities\providers;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class config_provider
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class config_provider {

    /**
     * get_default_configs
     *
     * @param int $cmid he cmid(0) is the default list.
     *
     * @return array
     */
    public static function get_configs($cmid = 0) {
        global $DB;

        $configs = [];

        if ($records = $DB->get_records(UNICHECK_CONFIG_TABLE, ['cm' => $cmid], '', 'id,name,value,cm')) {
            foreach ($records as $record) {
                $record = (array) $record;
                $configs[$record['name']] = $record;
            }
        }

        return $configs;
    }

    /**
     * update_configs
     *
     * @param array $rows
     */
    public static function update_configs($rows) {
        global $DB;

        $ids = [];
        foreach ($rows as $row) {
            array_push($ids, $row->id);
        }

        $params = [];
        $table = UNICHECK_CONFIG_TABLE;

        $sql = "UPDATE {{$table}}
                SET value = (
                    CASE ";

        foreach ($rows as $row) {
            $sql .= "WHEN id = ? THEN ? ";
            $params[] = (int) $row->id;
            $params[] = s($row->value);
        }

        list($insql, $inparams) = $DB->get_in_or_equal($ids);
        $params = array_merge($params, $inparams);

        $sql .= "END)
                 WHERE id $insql";

        $DB->execute($sql, $params);
    }

    /**
     * insert_records
     *
     * @param array $rows
     */
    public static function insert_configs($rows) {
        global $DB;

        $DB->insert_records(UNICHECK_CONFIG_TABLE, $rows);
    }
}
