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
 * unicheck_rar_extractor.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\entities\extractors;

use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\exception\unicheck_exception;
use plagiarism_unicheck\classes\services\storage\filesize_checker;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_rar_extractor
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_rar_extractor implements unicheck_extractor_interface {
    /**
     * @var \stored_file
     */
    private $file;
    /**
     * @var string
     */
    private $tmprarfile;
    /**
     * @var \RarArchive
     */
    private $rararch;

    /**
     * unicheck_rar_extractor constructor.
     *
     * @param \stored_file $file
     *
     * @throws unicheck_exception
     */
    public function __construct(\stored_file $file) {
        global $CFG;

        if (!extension_loaded('rar')) {
            throw new unicheck_exception(unicheck_exception::RAR_ARCHIVE_CANT_BE_OPEN);
        }

        $this->file = $file;

        $this->tmprarfile = tempnam($CFG->tempdir, 'unicheck_rar');

        $this->file->copy_content_to($this->tmprarfile);

        $this->rararch = \RarArchive::open($this->tmprarfile);

        if ($this->rararch === false) {
            throw new unicheck_exception(unicheck_exception::RAR_ARCHIVE_CANT_BE_OPEN);
        }
    }

    /**
     * Extract each file
     *
     * @return \Generator
     * @throws unicheck_exception
     */
    public function extract() {
        global $CFG;

        $entries = $this->rararch->getEntries();
        if ($entries === false) {
            throw new unicheck_exception(unicheck_exception::ARCHIVE_IS_EMPTY);
        }

        foreach ($entries as $entry) {
            if (!$entry->getUnpackedSize() || filesize_checker::is_too_large($entry->getUnpackedSize())) {
                continue;
            }

            $tmpfile = tempnam($CFG->tempdir, 'unicheck_unrar');

            if (!$entry->extract(false, $tmpfile)) {
                unicheck_archive::unlink($tmpfile);
                continue;
            }

            $name = $entry->getName();
            $format = pathinfo($entry->getName(), PATHINFO_EXTENSION);
            if (!\plagiarism_unicheck::is_supported_extension($format)) {
                unicheck_archive::unlink($tmpfile);
                continue;
            }

            yield [
                'path'     => $tmpfile,
                'filename' => $name,
                'format'   => $format,
            ];
        }
    }

    /**
     * Destruct
     */
    public function __destruct() {
        $this->rararch->close();
        unicheck_archive::unlink($this->tmprarfile);
    }
}
