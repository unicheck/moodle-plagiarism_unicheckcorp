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
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\plagiarism;

use moodle_exception;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_plagiarism_entity;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_archive_item_file
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_archive_item_file extends unicheck_plagiarism_entity {
    /**
     * @var string
     */
    private $path;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $ext;
    /**
     * @var int
     */
    private $parentid;

    /**
     * unicheck_archive_item_file constructor.
     *
     * @param  unicheck_core  $core
     * @param  array          $item
     * @param  int|null       $parentid
     */
    public function __construct(unicheck_core $core, array $item, $parentid = null) {
        $this->core = $core;
        $this->path = $item['path'];
        $this->name = $item['filename'];
        $this->ext = isset($item['format']) && $item['format'] ? $item['format'] : 'html';
        $this->parentid = $parentid;
    }

    /**
     * Get internal file
     *
     * @return object
     * @throws moodle_exception
     */
    public function get_internal_file() {
        global $DB;

        if ($this->plagiarismfile) {
            return $this->plagiarismfile;
        }

        $plagiarismfile = null;
        try {
            $filedata = [
                'cm'         => $this->cmid(),
                'userid'     => $this->userid(),
                'identifier' => sha1($this->name . $this->cmid() . UNICHECK_DEFAULT_FILES_AREA . $this->parentid),
            ];

            if ($this->core->is_teamsubmission_mode()) {
                unset($filedata['userid']);
            }

            // Now update or insert record into unicheck_files.
            $plagiarismfile = $DB->get_record(UNICHECK_FILES_TABLE, $filedata);

            if (empty($plagiarismfile)) {
                $plagiarismfile = $this->new_plagiarismfile([
                    'cm'         => $this->cmid(),
                    'userid'     => $this->userid(),
                    'identifier' => $filedata['identifier'],
                    'filename'   => $this->name,
                ]);

                if ($this->parentid) {
                    $plagiarismfile->parent_id = $this->parentid;
                }

                if (!$pid = $DB->insert_record(UNICHECK_FILES_TABLE, $plagiarismfile)) {
                    debugging("INSERT INTO {UNICHECK_FILES_TABLE}");
                }

                $plagiarismfile->id = $pid;
            }
        } catch (\Exception $ex) {
            throw new moodle_exception($ex->getMessage());
        }

        $this->plagiarismfile = $plagiarismfile;

        return $this->plagiarismfile;
    }

    /**
     * Build upload data
     *
     * @return array
     */
    protected function build_upload_data() {
        return [
            $this->path,
            $this->name,
            $this->ext,
            $this->cmid(),
            unicheck_core::get_user($this->userid()),
        ];
    }
}
