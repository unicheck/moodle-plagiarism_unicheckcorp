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
 * File install.php
 *
 * @package     plagiarism_unicheck
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Installer
 *
 * @return bool
 */
function xmldb_plagiarism_unicheck_install() {
    global $DB;

    $installed = $DB->get_record('config_plugins', ['plugin' => 'plagiarism_unplag', 'name' => 'version']);
    if (!$installed || $installed->value < 2017120100) {
        return true;
    }

    $dbman = $DB->get_manager();
    $oldtable = 'plagiarism_unplag_files';
    if ($dbman->table_exists($oldtable)) {
        $DB->insert_records('plagiarism_unicheck_files', $DB->get_records($oldtable));
    }

    $oldtable = 'plagiarism_unplag_user_data';
    if ($dbman->table_exists($oldtable)) {
        $DB->insert_records('plagiarism_unicheck_users', $DB->get_records($oldtable));
    }

    $oldtable = 'plagiarism_unplag_config';
    if ($dbman->table_exists($oldtable)) {
        $DB->insert_records('plagiarism_unicheck_config', $DB->get_records($oldtable));
    }

    return true;
}