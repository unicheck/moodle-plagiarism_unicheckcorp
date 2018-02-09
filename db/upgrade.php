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

require_once(dirname(__FILE__) . '/../constants.php');
require_once(dirname(__FILE__) . '/../autoloader.php');

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
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2017120100) {

        $table = new xmldb_table('plagiarism_unicheck_files');

        $field = new xmldb_field('state', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL, null, 'CREATED', 'reportediturl');
        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $files = $DB->get_recordset('plagiarism_unicheck_files', null, 'id asc', '*');
            foreach ($files as $file) {
                switch ($file->statuscode) {
                    case 200:
                        $file->state = 'CHECKED';
                        break;
                    case 202:
                        if ($file->check_id) {
                            $file->state = 'CHECKING';

                            break;
                        }

                        $file->state = 'UPLOADED';
                        break;
                    case 'pending':
                        $file->state = 'CREATED';
                        break;
                    default:
                        $file->state = 'HAS_ERROR';
                        break;
                }
                $DB->update_record('plagiarism_unicheck_files', $file);
            }
            $files->close(); // Don't forget to close the recordset!
        }

        $field = new xmldb_field('external_file_uuid', XMLDB_TYPE_CHAR, '63', null, null, null, null, 'state');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Unicheck savepoint reached.
        upgrade_plugin_savepoint(true, 2017120100, 'plagiarism', 'unicheck');
    }

    if ($oldversion < 2018020700) {
        $table = new xmldb_table('plagiarism_unicheck_comments');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('commentableid', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, null, null, null);
        $table->add_field('commentabletype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id'], null, null);
        $dbman->create_table($table);

        $table = new xmldb_table('plagiarism_unicheck_files');
        $field = new xmldb_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null, 'external_file_uuid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2018020700, 'plagiarism', 'unicheck');
    }

    return true;
}