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

use plagiarism_unicheck\classes\unicheck_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once(dirname(__FILE__) . '/../constants.php');
require_once(dirname(__FILE__) . '/../autoloader.php');

/**
 * @param $oldversion
 *
 * @return bool
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_plagiarism_unicheck_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016041600) {
        // Define field external_file_id to be added to plagiarism_unplag_files.
        $table = new xmldb_table(UNICHECK_FILES_TABLE);

        $field = new xmldb_field('external_file_id', XMLDB_TYPE_INTEGER, '10', true, null, null, null);
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('reportediturl', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('similarityscore', XMLDB_TYPE_NUMBER, '5,2', null, null, null, null, null);
        $dbman->change_field_type($table, $field);

        // Unicheck savepoint reached.
        xmldb_plagiarism_unicheck_savepoint(2016041600);
    }

    if ($oldversion < 2016100500) {
        // Define field external_file_id to be added to plagiarism_unplag_files.
        $table = new xmldb_table(UNICHECK_FILES_TABLE);

        $field = new xmldb_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        // Conditionally launch add field parent_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL, null, 'document', 'filename');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Unicheck savepoint reached.
        xmldb_plagiarism_unicheck_savepoint(2016100500);
    }

    if ($oldversion < 2016112200) {
        if ($dbman->table_exists(UNICHECK_USER_DATA_TABLE)) {
            $dbman->drop_table(UNICHECK_USER_DATA_TABLE);
        }

        $table = new xmldb_table(UNICHECK_USER_DATA_TABLE);

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('external_user_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('external_token', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, array('user_id'), 'user', 'id');

        $dbman->create_table($table);

        xmldb_plagiarism_unicheck_savepoint(2016112200);
    }

    if ($oldversion < 2017012300) {
        unicheck_core::migrate_users_access();
        xmldb_plagiarism_unicheck_savepoint(2017012300);
    }

    return true;
}

function xmldb_plagiarism_unicheck_savepoint($version) {
    upgrade_plugin_savepoint(true, $version, 'plagiarism', 'unicheck');
}
