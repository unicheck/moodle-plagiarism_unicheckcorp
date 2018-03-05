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
 * unicheck_adhoc.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\task\unicheck_check_starter;
use plagiarism_unicheck\classes\task\unicheck_upload_task;
use plagiarism_unicheck\event\plagiarism_entity_accepted;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_adhoc
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_adhoc {

    /**
     * Add task to upload queue
     *
     * @param \stored_file  $file
     * @param unicheck_core $ucore
     * @return bool
     */
    public static function upload(\stored_file $file, unicheck_core $ucore) {
        $plagiarismfile = $ucore->get_plagiarism_entity($file)->get_internal_file();
        $plagiarismfile->state = unicheck_file_state::UPLOADING;
        $plagiarismfile->errorresponse = null;

        unicheck_file_provider::save($plagiarismfile);

        return unicheck_upload_task::add_task([
            unicheck_upload_task::PATHNAME_HASH => $file->get_pathnamehash(),
            unicheck_upload_task::UCORE_KEY     => $ucore,
        ]);
    }

    /**
     * Add task to check queue
     *
     * @param \stdClass $plagiarismfile
     * @return bool
     */
    public static function check(\stdClass $plagiarismfile) {
        $plagiarismfile->state = unicheck_file_state::CHECKING;
        $plagiarismfile->errorresponse = null;
        unicheck_file_provider::save($plagiarismfile);

        return unicheck_check_starter::add_task([
            unicheck_check_starter::PLUGIN_FILE_ID_KEY => $plagiarismfile->id
        ]);
    }
}