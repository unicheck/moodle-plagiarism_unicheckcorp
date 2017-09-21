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
 * unicheck_abstract_task.class.php
 *
 * @package     plagiarism_unicheck
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\task;

use core\task\adhoc_task;
use core\task\manager;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\helpers\unicheck_check_helper;
use plagiarism_unicheck\classes\plagiarism\unicheck_content;
use plagiarism_unicheck\classes\unicheck_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Interface unicheck_abstract_task
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class unicheck_abstract_task extends adhoc_task {
    /**
     * @var unicheck_core
     */
    protected $ucore;
    /**
     * @var object
     */
    protected $archiveinternalfile;

    /**
     * Add new task for execution
     *
     * @param array $data
     *
     * @return bool
     */
    public static function add_task(array $data) {
        $task = new static();
        $task->set_component(UNICHECK_PLAGIN_NAME);
        $task->set_custom_data($data);

        return manager::queue_adhoc_task($task);
    }

    /**
     * process_archive_item
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
            $this->archiveinternalfile->id
        );
        $plagiarismentity->get_internal_file();

        unicheck_check_helper::upload_and_run_detection($plagiarismentity);

        unset($plagiarismentity, $content);

        unicheck_archive::unlink($item['path']);
    }
}