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
 * File upgrade.php
 *
 * @package     plagiarism_unicheck
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * db plagiarism unicheck upgrade
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param int $oldversion
 *
 * @return bool
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_plagiarism_unicheck_upgrade($oldversion) {
    global $DB, $CFG;

    if ($oldversion < 2020090100) {
        $configs = get_config('plagiarism');

        foreach ($configs as $field => $value) {
            if (strpos($field, 'unicheck') === 0) {
                if ($field === 'unicheck_use') {
                    $DB->delete_records('config_plugins', ['name' => $field, 'plugin' => 'plagiarism']);

                    $field = 'enabled';
                }

                set_config($field, $value, 'plagiarism_unicheck');
            }
        }

        upgrade_plugin_savepoint(true, 2020090100, 'plagiarism', 'unicheck');
    }

    if ($CFG->branch >= 39) {
        $configs = get_config('plagiarism');

        foreach ($configs as $field => $value) {
            if (strpos($field, 'unicheck') === 0) {
                if ($field === 'unicheck_use') {
                    $DB->delete_records('config_plugins', ['name' => $field, 'plugin' => 'plagiarism']);

                    $field = 'enabled';
                }

                set_config($field, $value, 'plagiarism_unicheck');
            }
        }
    }

    return true;
}
