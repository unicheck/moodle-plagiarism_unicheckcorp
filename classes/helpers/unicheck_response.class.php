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
 * unicheck_response.class.php
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
use plagiarism_unicheck\event\file_similarity_check_failed;
use plagiarism_unicheck\event\file_upload_failed;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_response
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class unicheck_response {
    /**
     * FILE_RESOURCE
     */
    const FILE_RESOURCE = 'file';
    /**
     * SIMILARITY_CHECK_RESOURCE
     */
    const SIMILARITY_CHECK_RESOURCE = 'similarity_check';

    /**
     * handle_check_response
     *
     * @param \stdClass $response
     * @param \stdClass $plagiarismfile
     * @return bool
     */
    public static function handle_check_response(\stdClass $response, \stdClass $plagiarismfile) {
        if (!$response->result) {
            return self::store_errors($response->errors, $plagiarismfile, self::SIMILARITY_CHECK_RESOURCE);
        }

        if ($response->check->id) {
            $progress = 100 * $response->check->progress;
            if ($progress === 100) {
                return unicheck_check_helper::check_complete($plagiarismfile, $response->check, $progress);
            }

            $plagiarismfile->attempt = 0; // Reset attempts for status checks.
            $plagiarismfile->check_id = $response->check->id;
            $plagiarismfile->errorresponse = null;
            $plagiarismfile->state = unicheck_file_state::CHECKING;
        }

        return unicheck_file_provider::save($plagiarismfile);
    }

    /**
     * process_after_upload
     *
     * @param \stdClass $response
     * @param \stdClass $plagiarismfile
     * @return bool
     */
    public static function process_after_upload(\stdClass $response, \stdClass $plagiarismfile) {
        if (!$response->result) {
            return self::store_errors($response->errors, $plagiarismfile, self::FILE_RESOURCE);
        }

        $plagiarismfile->external_file_uuid = $response->file->uuid;
        if ($response->file->id) {
            return unicheck_upload_helper::upload_complete($plagiarismfile, $response->file);
        }

        return unicheck_file_provider::save($plagiarismfile);
    }

    /**
     * store_errors
     *
     * @param array     $errors
     * @param \stdClass $plagiarismfile
     * @param string    $resourcetype
     * @return bool
     */
    public static function store_errors(array $errors, \stdClass $plagiarismfile, $resourcetype) {
        global $DB;

        $plagiarismfile->state = unicheck_file_state::HAS_ERROR;
        $plagiarismfile->errorresponse = json_encode($errors);
        $result = unicheck_file_provider::save($plagiarismfile);
        if (!$result) {
            return false;
        }

        self::trigger_failed_event($plagiarismfile, $resourcetype);

        if ($plagiarismfile->parent_id) {
            $hasgoodchild = $DB->count_records_select(UNICHECK_FILES_TABLE, "parent_id = ? AND state not in (?)",
                [$plagiarismfile->parent_id, unicheck_file_state::HAS_ERROR]
            );

            if (!$hasgoodchild) {
                $parentplagiarismfile = unicheck_file_provider::get_by_id($plagiarismfile->parent_id);
                $parentplagiarismfile->state = unicheck_file_state::HAS_ERROR;
                $parentplagiarismfile->errorresponse = json_encode($errors);

                unicheck_file_provider::save($parentplagiarismfile);

                self::trigger_failed_event($parentplagiarismfile, $resourcetype);
            }
        }

        return $result;
    }

    /**
     * Trigger failed event
     *
     * @param object $plagiarismfile
     * @param string $resourcetype
     */
    private static function trigger_failed_event($plagiarismfile, $resourcetype) {
        switch ($resourcetype) {
            case self::SIMILARITY_CHECK_RESOURCE:
                $event = file_similarity_check_failed::create_from_failed_plagiarismfile(
                    $plagiarismfile,
                    $plagiarismfile->errorresponse
                );
                break;
            default:
                $event = file_upload_failed::create_from_failed_plagiarismfile($plagiarismfile, $plagiarismfile->errorresponse);
                break;
        }

        $event->trigger();
    }
}