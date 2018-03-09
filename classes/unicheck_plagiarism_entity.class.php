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

use plagiarism_unicheck\classes\helpers\unicheck_response;
use plagiarism_unicheck\classes\services\comments\commentable_interface;
use plagiarism_unicheck\classes\services\comments\commentable_type;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\event\file_upload_started;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_plagiarism_entity
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class unicheck_plagiarism_entity implements commentable_interface {
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
     * Create new plagiarismfile
     *
     * @param array $data
     *
     * @return null|\stdClass
     */
    public function new_plagiarismfile($data) {

        foreach (['cm', 'userid', 'identifier', 'filename'] as $key) {
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
        $plagiarismfile->state = unicheck_file_state::CREATED;
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
        if ($internalfile->state !== unicheck_file_state::UPLOADING) {
            return $internalfile;
        }

        file_upload_started::create_from_plagiarismfile($internalfile)->trigger();
        list($content, $name, $ext, $cmid, $owner) = $this->build_upload_data();
        $uploadresponse = unicheck_api::instance()->upload_file($content, $name, $ext, $cmid, $owner, $internalfile);

        // Increment attempt number.
        $internalfile->attempt++;

        unicheck_response::process_after_upload($uploadresponse, $internalfile);

        return $internalfile;
    }

    /**
     * Get commentable object id
     *
     * @return int
     */
    public function get_commentable_id() {
        return $this->get_internal_file()->id;
    }

    /**
     * Get Commentable object type
     *
     * @return string
     */
    public function get_commentable_type() {
        return commentable_type::FILE;
    }
}