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
 * plagiarism_unicheck_basic_testcase.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;

use plagiarism_unicheck\classes\unicheck_core;

/**
 * Class plagiarism_unicheck_generator
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_unicheck_generator extends testing_data_generator {

    /**
     * Create plagiarism core
     *
     * @param int $cmid
     * @param int $userid
     *
     * @return unicheck_core
     * @throws coding_exception
     */
    public function create_ucore($cmid, $userid) {
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);

        return new unicheck_core($cmid, $userid, $cm->modname);
    }

    /**
     * Create stored file from fixture
     *
     * @param string $filepath
     * @param object $owner
     *
     * @return stored_file
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    public function create_file_from_pathname($filepath, $owner) {
        $syscontext = context_system::instance();
        $filerecord = [
            'contextid' => $syscontext->id,
            'component' => 'assignsubmission_file',
            'filearea'  => 'submission_files',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => basename($filepath),
            'userid'    => $owner->id
        ];

        $fs = get_file_storage();

        return $fs->create_file_from_pathname($filerecord, $filepath);
    }

    /**
     * create_plagiarism_unicheck_file
     *
     * @param null             $user
     * @param stored_file|null $storedfile
     * @param null             $cm
     *
     * @return stdClass
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_plagiarism_unicheck_file(
        $user = null,
        stored_file $storedfile = null,
        $cm = null
    ) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

        if (!$cm) {
            $course = phpunit_util::get_data_generator()->create_course(['enablecompletion' => 1]);
            /** @var mod_assign_generator $assigngenerator */
            $assigngenerator = $this->get_plugin_generator('mod_assign');
            $assigninstance = $assigngenerator->create_instance(['course' => $course->id]);
            $cm = get_coursemodule_from_instance('assign', $assigninstance->id);
        }

        if (!$user) {
            $user = phpunit_util::get_data_generator()->create_user();
        }

        $plagiarismfile = new stdClass();
        $plagiarismfile->cm = $cm->id;
        $plagiarismfile->userid = $user->id;
        $plagiarismfile->identifier = $storedfile ? $storedfile->get_pathnamehash() : "cbde71b364debd86673e357e67ccd164de9936ba";
        $plagiarismfile->check_id = 321;
        $plagiarismfile->filename = $storedfile ? $storedfile->get_filename() : "User's file";
        $plagiarismfile->type = "document";
        $plagiarismfile->progress = 100;
        $plagiarismfile->statuscode = 200;
        $plagiarismfile->similarityscore = 77.7;
        $plagiarismfile->attempt = 1;
        $plagiarismfile->errorresponse = null;
        $plagiarismfile->timesubmitted = strtotime("25 May 2018");
        $plagiarismfile->external_file_id = 123;
        $plagiarismfile->state = "CHECKED";
        $plagiarismfile->external_file_uuid = "5d2db4f1f45d49f5b0fd754a55884057";
        $plagiarismfile->metadata = json_encode([
            'char_count'                             => 333,
            'cheating_char_replacements_count'       => 33,
            'cheating_char_replacements_words_count' => 3
        ]);

        $id = $DB->insert_record('plagiarism_unicheck_files', $plagiarismfile);
        $plagiarismfile->id = $id;

        return $plagiarismfile;
    }
}
