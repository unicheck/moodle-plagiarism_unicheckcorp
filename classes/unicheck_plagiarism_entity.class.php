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
 * unicheck_plagiarism_entity.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

use plagiarism_unicheck\classes\helpers\unicheck_stored_file;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_plagiarism_entity
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class unicheck_plagiarism_entity {
    /**
     * TYPE_ARCHIVE
     */
    const TYPE_ARCHIVE = 'archive';
    /**
     * TYPE_DOCUMENT
     */
    const TYPE_DOCUMENT = 'document';
    /** @var unicheck_core */
    protected $core;
    /** @var \stdClass */
    protected $plagiarismfile;

    /**
     * get_internal_file
     *
     * @return object
     */
    abstract public function get_internal_file();

    /**
     * build_upload_data
     *
     * @return array
     */
    abstract protected function build_upload_data();

    /**
     * Get cmid
     *
     * @return integer
     */
    protected function cmid() {
        return $this->core->cmid;
    }

    /**
     * Get userid
     *
     * @return integer
     */
    protected function userid() {
        return $this->core->userid;
    }

    /**
     * store_file_errors
     *
     * @param \stdClass $response
     *
     * @return bool
     */
    protected function store_file_errors(\stdClass $response) {
        global $DB;

        $plagiarismfile = $this->get_internal_file();
        $plagiarismfile->statuscode = UNICHECK_STATUSCODE_INVALID_RESPONSE;
        $plagiarismfile->errorresponse = json_encode($response->errors);

        $result = $DB->update_record(UNICHECK_FILES_TABLE, $plagiarismfile);

        if ($result && $plagiarismfile->parent_id) {
            $hasgoodchild = $DB->count_records_select(UNICHECK_FILES_TABLE, "parent_id = ? AND statuscode in (?,?,?)",
                array(
                    $plagiarismfile->parent_id, UNICHECK_STATUSCODE_PROCESSED, UNICHECK_STATUSCODE_ACCEPTED,
                    UNICHECK_STATUSCODE_PENDING,
                ));

            if (!$hasgoodchild) {
                $parentplagiarismfile = unicheck_stored_file::get_internal_file($plagiarismfile->parent_id);
                $parentplagiarismfile->statuscode = UNICHECK_STATUSCODE_INVALID_RESPONSE;
                $parentplagiarismfile->errorresponse = json_encode($response->errors);

                $DB->update_record(UNICHECK_FILES_TABLE, $parentplagiarismfile);
            }
        }

        return $result;
    }

    /**
     * handle_check_response
     *
     * @param \stdClass $checkresp
     */
    public function handle_check_response(\stdClass $checkresp) {
        if ($checkresp->result === true) {
            $this->update_file_accepted($checkresp->check);
        } else {
            $this->store_file_errors($checkresp);
        }
    }

    /**
     * update_file_accepted
     *
     * @param object $check
     *
     * @return bool
     */
    protected function update_file_accepted($check) {
        global $DB;

        $plagiarismfile = $this->get_internal_file();
        $plagiarismfile->attempt = 0; // Reset attempts for status checks.
        $plagiarismfile->check_id = $check->id;
        $plagiarismfile->statuscode = UNICHECK_STATUSCODE_ACCEPTED;
        $plagiarismfile->errorresponse = null;

        return $DB->update_record(UNICHECK_FILES_TABLE, $plagiarismfile);
    }

    /**
     * Create new plagiarismfile
     *
     * @param array $data
     *
     * @return null|\stdClass
     */
    public function new_plagiarismfile($data) {

        foreach (array('cm', 'userid', 'identifier', 'filename') as $key) {
            if (empty($data[$key])) {
                print_error($key . ' value is empty');

                return null;
            }
        }

        $plagiarismfile = new \stdClass();
        $plagiarismfile->cm = $data['cm'];
        $plagiarismfile->userid = $data['userid'];
        $plagiarismfile->identifier = $data['identifier'];
        $plagiarismfile->filename = $data['filename'];
        $plagiarismfile->statuscode = UNICHECK_STATUSCODE_PENDING;
        $plagiarismfile->attempt = 0;
        $plagiarismfile->progress = 0;
        $plagiarismfile->timesubmitted = time();
        $plagiarismfile->type = self::TYPE_DOCUMENT;

        return $plagiarismfile;
    }

    /**
     * Upload file on server
     *
     * @return object
     */
    public function upload_file_on_server() {

        $internalfile = $this->get_internal_file();

        if (isset($internalfile->external_file_id)) {
            return $internalfile;
        }

        // Check if $internalfile actually needs to be submitted.
        if ($internalfile->statuscode !== UNICHECK_STATUSCODE_PENDING) {
            return $internalfile;
        }

        list($content, $name, $ext, $cmid, $owner) = $this->build_upload_data();
        $uploadresponse = unicheck_api::instance()->upload_file($content, $name, $ext, $cmid, $owner);

        // Increment attempt number.
        $internalfile->attempt++;

        $this->process_after_upload($internalfile, $uploadresponse);

        return $internalfile;
    }

    /**
     * Trigger process after upload
     *
     * @param object $internalfile
     * @param object $uploadresponse
     */
    private function process_after_upload(&$internalfile, $uploadresponse) {
        global $DB;

        if ($uploadresponse->result) {
            $internalfile->external_file_id = $uploadresponse->file->id;
            $DB->update_record(UNICHECK_FILES_TABLE, $internalfile);
        } else {
            $this->store_file_errors($uploadresponse);
        }
    }
}