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
 * @param int $oldversion
 *
 * @return bool
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
function xmldb_plagiarism_unicheck_upgrade($oldversion) {
    global $DB;

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

    $dbman = $DB->get_manager();

    if ($oldversion < 2020090102) {
        // Define field api_key to be added to plagiarism_unicheck_users.
        $table = new xmldb_table('plagiarism_unicheck_users');
        $field = new xmldb_field('api_data_hash', XMLDB_TYPE_CHAR, '32', null, null, null, null, 'api_key');
        $field->setComment('Data md5 hash after updating API information in Unicheck');

        // Conditionally launch add field api_data_hash.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020090102, 'plagiarism', 'unicheck');
    }

    return true;
}
