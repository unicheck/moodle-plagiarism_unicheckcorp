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
 * unicheck_archive.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\entities;

use plagiarism_unicheck\classes\entities\extractors\unicheck_extractor_interface;
use plagiarism_unicheck\classes\entities\extractors\unicheck_rar_extractor;
use plagiarism_unicheck\classes\entities\extractors\unicheck_zip_extractor;
use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\exception\unicheck_exception;
use plagiarism_unicheck\classes\unicheck_adhoc;
use plagiarism_unicheck\classes\unicheck_api;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_notification;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_archive
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_archive {

    /**
     * DEFAULT_SUPPORTED_FILES_COUNT
     */
    const DEFAULT_SUPPORTED_FILES_COUNT = 10;
    /**
     * MIN_SUPPORTED_FILES_COUNT
     */
    const MIN_SUPPORTED_FILES_COUNT = 1;
    /**
     * MAX_SUPPORTED_FILES_COUNT
     */
    const MAX_SUPPORTED_FILES_COUNT = 100;

    /**
     * ZIP_MIMETYPE
     */
    const ZIP_MIMETYPE = 'application/zip';
    /**
     * RAR_MIMETYPE
     */
    const RAR_MIMETYPE = 'application/x-rar-compressed';
    /**
     * @var \stored_file
     */
    private $file;
    /**
     * @var unicheck_core
     */
    private $core;
    /**
     * @var unicheck_extractor_interface
     */
    private $extractor;
    /**
     * @var object
     */
    private $archive;

    /**
     * unicheck_archive constructor.
     *
     * @param \stored_file  $file
     * @param unicheck_core $core
     *
     * @throws unicheck_exception
     */
    public function __construct(\stored_file $file, unicheck_core $core) {
        $this->file = $file;
        $this->core = $core;

        $this->archive = $this->core->get_plagiarism_entity($this->file)->get_internal_file();

        switch ($file->get_mimetype()) {
            case self::RAR_MIMETYPE:
                $this->extractor = new unicheck_rar_extractor($file);
                break;
            case self::ZIP_MIMETYPE:
                $this->extractor = new unicheck_zip_extractor($file);
                break;
            default:
                throw new unicheck_exception(unicheck_exception::UNSUPPORTED_MIMETYPE);
        }
    }

    /**
     * Extract each file
     *
     * @return array
     *
     * @throws unicheck_exception
     */
    public function extract() {
        try {
            return $this->extractor->extract();
        } catch (\Exception $ex) {
            throw new unicheck_exception($ex->getMessage());
        }
    }

    /**
     * Upload archive for check
     *
     * @return bool
     */
    public function upload() {
        return unicheck_adhoc::upload($this->file, $this->core);
    }

    /**
     * Restart check
     */
    public function restart_check() {
        $internalfile = $this->core->get_plagiarism_entity($this->file)->get_internal_file();
        $childs = unicheck_file_provider::get_file_list_by_parent_id($internalfile->id);
        if (count($childs)) {
            foreach ((object)$childs as $child) {
                if ($child->check_id) {
                    unicheck_api::instance()->delete_check($child);
                }
            }

            unicheck_notification::success('plagiarism_run_success', true);

            $this->upload();
        }
    }

    /**
     * Delete
     *
     * @param string $file
     */
    public static function unlink($file) {
        if (!unlink($file)) {
            mtrace('Error deleting ' . $file);
        }
    }
}
