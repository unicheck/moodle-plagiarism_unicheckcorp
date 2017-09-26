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
 * unicheck_bulk_check_assign_files.class.php
 *
 * @package     plagiarism_unicheck
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\task;

use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\unicheck_api;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_bulk_check_assign_files
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_bulk_check_assign_files extends unicheck_abstract_task {
    /** @var  \stored_file */
    private $assignfile;

    /**
     * Execute task
     */
    public function execute() {
        $data = $this->get_custom_data();

        $assignfiles = unicheck_assign::get_area_files($data->contextid);

        if (empty($assignfiles)) {
            return;
        }

        foreach ($assignfiles as $this->assignfile) {
            if (unicheck_assign::is_draft($this->assignfile->get_itemid())) {
                continue;
            }

            $this->ucore = new unicheck_core($data->cmid, $this->assignfile->get_userid());

            $pattern = '%s with uuid ' . $this->assignfile->get_pathnamehash() . ' ready to send';
            if (\plagiarism_unicheck::is_archive($this->assignfile)) {
                mtrace(sprintf($pattern, 'Archive'));
                $this->handle_archive($data->cmid);
            } else {
                mtrace(sprintf($pattern, 'File'));
                $this->handle_non_archive();
            }
        }
    }

    /**
     * Process archives
     *
     * @param int $contextid
     */
    private function handle_archive($contextid) {
        if (!is_null(unicheck_core::get_file_by_hash($contextid, $this->assignfile->get_pathnamehash()))) {
            mtrace('... archive already sent to Unicheck');

            return;
        }

        (new unicheck_archive($this->assignfile, $this->ucore))->run_checks();
        mtrace('... archive send to Unicheck');
    }

    /**
     * Process files besides archives
     */
    private function handle_non_archive() {
        $plagiarismentity = $this->ucore->get_plagiarism_entity($this->assignfile);
        $internalfile = $plagiarismentity->upload_file_on_server();
        if (isset($internalfile->check_id)) {
            mtrace('... file already sent to Unicheck');
        } else if ($internalfile->external_file_id) {
            $checkresp = unicheck_api::instance()->run_check($internalfile);
            $plagiarismentity->handle_check_response($checkresp);
            mtrace('... file send to Unicheck');
        }
    }
}