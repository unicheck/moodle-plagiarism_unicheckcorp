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
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use plagiarism_unicheck\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Class plagiarism_unicheck_privacy_provider_testcase
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_unicheck_privacy_provider_testcase extends provider_testcase {
    /**
     * Test for _get_metadata shim.
     */
    public function test_get_metadata() {
        $this->resetAfterTest();
        $collection = new collection('plagiarism_unicheck');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();

        $this->assertCount(5, $itemcollection);

        // Verify core_plagiarism data is returned.
        $this->assertEquals('core_plagiarism', $itemcollection[0]->get_name());
        $this->assertEquals('privacy:metadata:core_plagiarism', $itemcollection[0]->get_summary());

        // Verify core_files data is returned.
        $this->assertEquals('core_files', $itemcollection[1]->get_name());
        $this->assertEquals('privacy:metadata:core_files', $itemcollection[1]->get_summary());

        // Verify plagiarism_unicheck_files data is returned.
        $this->assertEquals('plagiarism_unicheck_files', $itemcollection[2]->get_name());
        $privacyfields = $itemcollection[2]->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('identifier', $privacyfields);
        $this->assertArrayHasKey('check_id', $privacyfields);
        $this->assertArrayHasKey('filename', $privacyfields);
        $this->assertArrayHasKey('type', $privacyfields);
        $this->assertArrayHasKey('attempt', $privacyfields);
        $this->assertArrayHasKey('errorresponse', $privacyfields);
        $this->assertArrayHasKey('timesubmitted', $privacyfields);
        $this->assertArrayHasKey('external_file_id', $privacyfields);
        $this->assertArrayHasKey('state', $privacyfields);
        $this->assertArrayHasKey('external_file_uuid', $privacyfields);
        $this->assertArrayHasKey('metadata', $privacyfields);

        // Verify plagiarism_unicheck_users data is returned.
        $this->assertEquals('plagiarism_unicheck_users', $itemcollection[3]->get_name());
        $privacyfields = $itemcollection[3]->get_privacy_fields();
        $this->assertArrayHasKey('user_id', $privacyfields);
        $this->assertArrayHasKey('external_user_id', $privacyfields);
        $this->assertArrayHasKey('external_token', $privacyfields);

        // Verify plagiarism_external_unicheck_api data is returned.
        $this->assertEquals('External Unicheck API', $itemcollection[4]->get_name());
        $privacyfields = $itemcollection[4]->get_privacy_fields();
        $this->assertArrayHasKey('domain', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('useremail', $privacyfields);
        $this->assertArrayHasKey('userfirstname', $privacyfields);
        $this->assertArrayHasKey('userlastname', $privacyfields);
        $this->assertArrayHasKey('userscope', $privacyfields);
        $this->assertArrayHasKey('fileformat', $privacyfields);
        $this->assertArrayHasKey('filedata', $privacyfields);
        $this->assertArrayHasKey('filename', $privacyfields);
        $this->assertArrayHasKey('submissionid', $privacyfields);
    }

    /**
     * _get_contexts_for_userid
     */
    public function test_get_contexts_for_userid() {
        $this->resetAfterTest();
        global $DB;

        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $createdfile = $this->create_plagiarism_unicheck_file($student);

        $plagiarismfiles = $DB->get_records('plagiarism_unicheck_files', ['userid' => $student->id]);
        $this->assertEquals(1, count($plagiarismfiles));

        $contextlist = provider::get_contexts_for_userid($student->id);

        $this->assertCount(1, $contextlist);

        $selectedfile = $DB->get_record('plagiarism_unicheck_files', ['cm' => $contextlist->get_contexts()[0]->instanceid]);

        $this->assertEquals($createdfile->id, $selectedfile->id);
        $this->assertEquals($student->id, $selectedfile->userid);
    }

    /**
     * test_export_plagiarism_user_data
     */
    public function test_export_plagiarism_user_data() {
        $this->resetAfterTest();
        global $DB, $CFG;

        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);

        /** @var plagiarism_unicheck_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('plagiarism_unicheck');
        $storedfile = $generator->create_file_from_pathname(
            $CFG->dirroot . '/plagiarism/unicheck/tests/fixtures/sample.pdf',
            $student
        );
        $plagiarismfile = $this->create_plagiarism_unicheck_file($student, $storedfile);

        $plagiarismfiles = $DB->get_records('plagiarism_unicheck_files', ['userid' => $student->id]);
        $this->assertEquals(1, count($plagiarismfiles));

        $context = context_module::instance($plagiarismfile->cm);

        // Export all of the data for the user.
        provider::export_plagiarism_user_data($student->id, $context, [], ['file' => $storedfile]);

        $writer = writer::with_context($context);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * test_delete_plagiarism_for_context
     */
    public function test_delete_plagiarism_for_context() {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        /** @var mod_assign_generator $assigngenerator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assigninstance = $assigngenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $assigninstance->id);
        $context = context_module::instance($cm->id);

        for ($i = 0; $i < 3; $i++) {
            $this->create_plagiarism_unicheck_file(null, null, $cm);
        }

        $plagiarismfiles = $DB->get_records('plagiarism_unicheck_files', ['cm' => $cm->id]);
        $this->assertEquals(3, count($plagiarismfiles));
        // Delete all of the data for the user.
        provider::delete_plagiarism_for_context($context);
        $plagiarismfiles = $DB->get_records('plagiarism_unicheck_files', ['cm' => $cm->id]);
        $this->assertEquals(0, count($plagiarismfiles));
    }

    /**
     * test_delete_plagiarism_for_user
     */
    public function test_delete_plagiarism_for_user() {
        $this->resetAfterTest();
        global $DB;
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($student);
        $plagiarismfile = $this->create_plagiarism_unicheck_file($student);

        $context = context_module::instance($plagiarismfile->cm);

        $plagiarismfiles = $DB->get_records('plagiarism_unicheck_files', ['userid' => $student->id]);
        $this->assertEquals(1, count($plagiarismfiles));

        // Delete all of the data for the user.
        provider::delete_plagiarism_for_user($student->id, $context);
        $plagiarismfiles = $DB->get_records('plagiarism_unicheck_files', ['userid' => $student->id]);
        $this->assertEquals(0, count($plagiarismfiles));
    }

    /**
     * test_delete_plagiarism_for_users
     */
    public function test_delete_plagiarism_for_users() {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        /** @var mod_assign_generator $assigngenerator */
        $assigngenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $assigninstance = $assigngenerator->create_instance(['course' => $course->id]);
        $cm = get_coursemodule_from_instance('assign', $assigninstance->id);
        $context = context_module::instance($cm->id);

        $studentids = [];
        for ($i = 0; $i < 3; $i++) {
            $student = $this->getDataGenerator()->create_user();
            $studentids[] = $student->id;

            $this->create_plagiarism_unicheck_file($student, null, $cm);
        }

        $plagiarismfiles = $DB->get_records('plagiarism_unicheck_files', ['cm' => $cm->id]);
        $this->assertEquals(3, count($plagiarismfiles));

        // Delete all of the data for the users.
        provider::delete_plagiarism_for_users($studentids, $context);
        $plagiarismfiles = $DB->get_records('plagiarism_unicheck_files', ['cm' => $cm->id]);
        $this->assertEquals(0, count($plagiarismfiles));
    }

    /**
     * create_plagiarism_unicheck_file
     *
     * @param stdClass         $student
     * @param stored_file|null $storedfile
     * @param null             $cm
     *
     * @return stdClass
     */
    protected function create_plagiarism_unicheck_file($student, stored_file $storedfile = null, $cm = null) {
        /** @var plagiarism_unicheck_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('plagiarism_unicheck');

        return $generator->create_plagiarism_unicheck_file($student, $storedfile, $cm);
    }
}