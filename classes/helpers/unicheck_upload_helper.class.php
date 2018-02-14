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
 * unicheck_upload_helper.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\helpers;

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\event\archive_files_uploaded;
use plagiarism_unicheck\event\file_upload_completed;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_upload_helper
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_upload_helper {
    /**
     * upload_complete
     *
     * @param \stdClass $plagiarismfile
     * @param \stdClass $responsefile
     * @return bool
     */
    public static function upload_complete(\stdClass & $plagiarismfile, \stdClass $responsefile) {
        global $DB;

        $plagiarismfile->external_file_id = $responsefile->id;
        $plagiarismfile->state = unicheck_file_state::UPLOADED;
        $plagiarismfile->errorresponse = null;

        $updated = unicheck_file_provider::save($plagiarismfile);
        if (!$updated) {
            return false;
        }

        file_upload_completed::create_from_plagiarismfile($plagiarismfile)->trigger();

        if ($plagiarismfile->parent_id !== null) {
            $parentrecord = unicheck_file_provider::get_by_id($plagiarismfile->parent_id);
            $childs = $DB->get_records_select(UNICHECK_FILES_TABLE, "parent_id = ? AND state in (?)",
                [$plagiarismfile->parent_id, unicheck_file_state::UPLOADING]);

            if (!count($childs)) {
                $parentrecord->state = unicheck_file_state::UPLOADED;
                $plagiarismfile->errorresponse = null;
                unicheck_file_provider::save($parentrecord);
                archive_files_uploaded::create_from_plagiarismfile($parentrecord)->trigger();
            }
        }

        return $updated;
    }
}