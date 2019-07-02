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
 * unicheck_upload_task.class.php
 *
 * @package     plagiarism_unicheck
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\task;

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\exception\unicheck_exception;
use plagiarism_unicheck\classes\plagiarism\unicheck_content;
use plagiarism_unicheck\classes\services\storage\filesize_checker;
use plagiarism_unicheck\classes\services\storage\unicheck_file_metadata;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_settings;
use plagiarism_unicheck\event\archive_files_unpacked;
use plagiarism_unicheck\event\error_handled;
use plagiarism_unicheck\event\file_upload_failed;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_upload_task
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_upload_task extends unicheck_abstract_task {

    /**
     * Key of pathname hash data parameter
     */
    const PATHNAME_HASH = 'pathnamehash';
    /**
     * Key of ucore data parameter
     */
    const UCORE_KEY = 'ucore';

    /**
     * @var unicheck_core
     */
    protected $ucore;

    /**
     * @var object
     */
    protected $internalfile;

    /**
     * Execute of adhoc task
     */
    public function execute() {
        $data = $this->get_custom_data();
        if (!is_object($data)) {
            return;
        }

        if (!property_exists($data, self::UCORE_KEY) || !property_exists($data, self::PATHNAME_HASH)) {
            return;
        }

        try {
            $modname = $this->get_modname($data->ucore);
            $this->ucore = new unicheck_core($data->ucore->cmid, $data->ucore->userid, $modname);
            if ($modname == UNICHECK_MODNAME_ASSIGN
                && (bool) unicheck_assign::get_by_cmid($this->ucore->cmid)->teamsubmission) {
                $this->ucore->enable_teamsubmission();
            }

            $storage = get_file_storage();
            $fs = $storage->get_file_system();

            $file = $storage->get_file_by_hash($data->pathnamehash);
            $plagiarismentity = $this->ucore->get_plagiarism_entity($file);
            $this->internalfile = $plagiarismentity->get_internal_file();

            if (!$fs->is_file_readable_remotely_by_storedfile($file)) {
                throw new unicheck_exception(unicheck_exception::CAN_NOT_READ_FILE);
            }

            if (!\plagiarism_unicheck::is_archive($file)) {
                $this->process_single_file($file);

                return;
            }

            $maxsupportedcount = unicheck_settings::get_activity_settings(
                $this->ucore->cmid,
                unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT
            );

            if ($maxsupportedcount < unicheck_archive::MIN_SUPPORTED_FILES_COUNT ||
                $maxsupportedcount > unicheck_archive::MAX_SUPPORTED_FILES_COUNT) {
                $maxsupportedcount = unicheck_archive::DEFAULT_SUPPORTED_FILES_COUNT;
            }

            $archivefiles = (new unicheck_archive($file, $this->ucore))->extract();
            if (!$archivefiles) {
                throw new unicheck_exception(unicheck_exception::ARCHIVE_IS_EMPTY);
            }

            $extractedcount = 0;
            $archivefilescount = 0;
            $fileforprocessing = [];
            foreach ($archivefiles as $archivefile) {
                $archivefilescount++;
                if ($extractedcount >= $maxsupportedcount) {
                    unicheck_archive::unlink($archivefile['path']);
                    continue;
                }

                $fileforprocessing[] = $archivefile;
                $extractedcount++;
            }

            if ($extractedcount < 1) {
                throw new unicheck_exception(unicheck_exception::ARCHIVE_IS_EMPTY);
            }

            if ($archivefilescount > $maxsupportedcount) {
                unicheck_file_provider::add_metadata($this->internalfile->id, [
                    unicheck_file_metadata::ARCHIVE_SUPPORTED_FILES_COUNT                => $archivefilescount,
                    unicheck_file_metadata::EXTRACTED_SUPPORTED_FILES_FROM_ARCHIVE_COUNT => $extractedcount
                ]);
            }

            archive_files_unpacked::create_from_plagiarismfile($this->internalfile)->trigger();

            foreach ($fileforprocessing as $item) {
                try {
                    $this->process_archive_item($item);
                } catch (\Exception $exception) {
                    mtrace("File " . $item['filename'] . " processing error: " . $exception->getMessage());
                    continue;
                } finally {
                    unicheck_archive::unlink($item['path']);
                }
            }
        } catch (\Exception $exception) {
            if ($this->internalfile) {
                unicheck_file_provider::to_error_state($this->internalfile, $exception->getMessage());
                try {
                    file_upload_failed::create_from_failed_plagiarismfile($this->internalfile, $exception->getMessage())->trigger();
                } catch (\Exception $exception) {
                    error_handled::create_from_exception($exception)->trigger();
                }
            } else {
                unicheck_file_provider::to_error_state_by_pathnamehash($data->pathnamehash, $exception->getMessage());
                error_handled::create_from_exception($exception)->trigger();
            }

            mtrace("File {$data->pathnamehash}(pathnamehash) processing error: " . $exception->getMessage());
        }
    }

    /**
     * Process archive item
     *
     * @param array $item
     */
    protected function process_archive_item(array $item) {
        $content = file_get_contents($item['path']);
        $plagiarismentity = new unicheck_content(
            $this->ucore,
            $content,
            $item['filename'],
            $item['format'],
            $this->internalfile->id
        );
        $internalfile = $plagiarismentity->get_internal_file();
        if ($internalfile->state == unicheck_file_state::CREATED) {
            $internalfile->state = unicheck_file_state::UPLOADING;
            unicheck_file_provider::save($internalfile);
            $plagiarismentity->upload_file_on_server();
        }

        unset($plagiarismentity, $content);
    }

    /**
     * Process single stored file
     *
     * @param \stored_file $file
     *
     * @throws unicheck_exception
     */
    protected function process_single_file(\stored_file $file) {
        if (filesize_checker::file_is_to_large($file)) {
            throw new unicheck_exception(unicheck_exception::FILE_IS_TOO_LARGE);
        }

        if ($this->internalfile->external_file_uuid) {
            mtrace("File already uploaded. Skipped. Plugin file id: {$this->internalfile->id}");

            return;
        }

        $plagiarismentity = $this->ucore->get_plagiarism_entity($file);
        $plagiarismentity->upload_file_on_server();
    }
}