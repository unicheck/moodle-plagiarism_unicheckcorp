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
 * unicheck_upload_and_check_task.class.php
 *
 * @package     plagiarism_unicheck
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\task;

use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_upload_and_check_task
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_upload_and_check_task extends unicheck_abstract_task {
    /**
     * Execute task
     */
    public function execute() {
        $data = $this->get_custom_data();

        $this->ucore = new unicheck_core($data->ucore->cmid, $data->ucore->userid);

        if ((bool) unicheck_assign::get_by_cmid($this->ucore->cmid)->teamsubmission) {
            $this->ucore->enable_teamsubmission();
        }

        $file = get_file_storage()->get_file_by_hash($data->pathnamehash);
        $this->archiveinternalfile = $this->ucore->get_plagiarism_entity($file)->get_internal_file();

        try {
            foreach ((new unicheck_archive($file, $this->ucore))->extract() as $item) {
                $this->process_archive_item($item);
            }
        } catch (\Exception $e) {
            $this->invalid_response($e->getMessage());
            mtrace('Archive error ' . $e->getMessage());
        }
        unset($this->ucore, $file);
    }

    /**
     * Check response validation
     *
     * @param string $reason
     */
    private function invalid_response($reason) {
        global $DB;

        $this->archiveinternalfile->statuscode = UNICHECK_STATUSCODE_INVALID_RESPONSE;
        $this->archiveinternalfile->errorresponse = json_encode([
            ["message" => $reason],
        ]);

        $DB->update_record(UNICHECK_FILES_TABLE, $this->archiveinternalfile);
    }
}