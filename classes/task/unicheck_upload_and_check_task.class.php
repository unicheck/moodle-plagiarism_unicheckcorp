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

use plagiarism_unicheck\classes\helpers\unicheck_check_helper;
use plagiarism_unicheck\classes\plagiarism\unicheck_content;
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
        if (file_exists($data->tmpfile)) {
            $ucore = new unicheck_core($data->core->cmid, $data->core->userid);

            if ((bool) unicheck_assign::get_by_cmid($ucore->cmid)->teamsubmission) {
                $ucore->enable_teamsubmission();
            }

            $content = file_get_contents($data->tmpfile);
            $plagiarismentity = new unicheck_content($ucore, $content, $data->filename, $data->format, $data->parent_id);

            unset($content, $ucore);

            if (!unlink($data->tmpfile)) {
                mtrace('Error deleting ' . $data->tmpfile);
            }

            unicheck_check_helper::upload_and_run_detection($plagiarismentity);

            unset($internalfile, $plagiarismentity, $checkresp);
        } else {
            mtrace('file ' . $data->tmpfile . 'not exist');
        }
    }
}