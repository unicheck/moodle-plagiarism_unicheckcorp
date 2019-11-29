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
 * unicheck_file_provider.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\entities\providers;

use plagiarism_unicheck\classes\helpers\unicheck_check_helper;
use plagiarism_unicheck\classes\services\storage\file_error_code;
use plagiarism_unicheck\classes\services\storage\unicheck_file_metadata;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_api;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_plagiarism_entity;
use stdClass;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_file_provider
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_file_provider {

    /**
     * Update plagiarism file
     *
     * @param \stdClass $file
     *
     * @return bool
     */
    public static function save(\stdClass $file) {
        global $DB;

        return $DB->update_record(UNICHECK_FILES_TABLE, $file);
    }

    /**
     * Get plagiarism file by id
     *
     * @param int $id
     *
     * @return mixed
     */
    public static function get_by_id($id) {
        global $DB;

        return $DB->get_record(UNICHECK_FILES_TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Find plagiarism file by id
     *
     * @param int $id
     *
     * @return mixed
     */
    public static function find_by_id($id) {
        global $DB;

        return $DB->get_record(UNICHECK_FILES_TABLE, ['id' => $id]);
    }

    /**
     * Find plagiarism file by check id
     *
     * @param int $checkid
     *
     * @return mixed
     */
    public static function find_by_check_id($checkid) {
        global $DB;

        return $DB->get_record(UNICHECK_FILES_TABLE, ['check_id' => $checkid]);
    }

    /**
     * Find plagiarism files by ids
     *
     * @param array $ids
     *
     * @return array
     */
    public static function find_by_ids($ids) {
        global $DB;

        return $DB->get_records_list(UNICHECK_FILES_TABLE, 'id', $ids);
    }

    /**
     * Find plagiarism files by ids for context
     *
     * @param array      $ids
     * @param int        $cmid
     * @param array|null $userids
     *
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function find_by_ids_for_context(array $ids, $cmid, array $userids = null) {
        global $DB;

        list($idssql, $idparams) = $DB->get_in_or_equal($ids);
        $params = $idparams;

        $sql = "id $idssql
                AND cm = ?";

        $params[] = $cmid;

        if ($userids) {
            list($useridssql, $useridparams) = $DB->get_in_or_equal($userids);

            $sql .= " AND userid $useridssql";
            $params = array_merge($params, $useridparams);
        }

        return $DB->get_records_select(UNICHECK_FILES_TABLE, $sql, $params);
    }

    /**
     * Can start check
     *
     * @param \stdClass $plagiarismfile
     *
     * @return bool
     */
    public static function can_start_check(\stdClass $plagiarismfile) {
        if (in_array($plagiarismfile->state,
            [
                unicheck_file_state::UPLOADING,
                unicheck_file_state::UPLOADED,
                unicheck_file_state::CHECKING,
                unicheck_file_state::CHECKED
            ])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Set file to error state
     *
     * @param \stdClass $plagiarismfile
     * @param string    $reason
     */
    public static function to_error_state(\stdClass $plagiarismfile, $reason) {
        $plagiarismfile->state = unicheck_file_state::HAS_ERROR;
        $plagiarismfile->errorresponse = json_encode([
            ["message" => $reason],
        ]);

        self::save($plagiarismfile);
    }

    /**
     * Set files to error state by pathnamehash
     *
     * @param string $pathnamehash
     * @param string $reason
     */
    public static function to_error_state_by_pathnamehash($pathnamehash, $reason) {
        global $DB;

        $files = $DB->get_recordset(UNICHECK_FILES_TABLE, ['identifier' => $pathnamehash], 'id asc', '*');
        foreach ($files as $plagiarismfile) {
            self::to_error_state($plagiarismfile, $reason);
        }
        $files->close(); // Don't forget to close the recordset!
    }

    /**
     * Get files list by parent id
     *
     * @param int $parentid
     *
     * @return array
     */
    public static function get_files_by_parent_id($parentid) {
        global $DB;

        return $DB->get_records_list(UNICHECK_FILES_TABLE, 'parent_id', [$parentid]);
    }

    /**
     * Get files list by parent id and states list
     *
     * @param int   $parentid
     *
     * @param array $states
     *
     * @param bool  $statesequel IN or NOT IN states
     *
     * @return array
     */
    public static function get_files_by_parent_id_in_states($parentid, array $states, $statesequel = true) {
        global $DB;

        $params = [$parentid];
        list($instatessql, $instatesparams) = $DB->get_in_or_equal($states, SQL_PARAMS_QM, 'param', $statesequel);
        $params = array_merge($params, $instatesparams);

        return $DB->get_records_select(UNICHECK_FILES_TABLE, "parent_id = ? AND state {$instatessql}", $params);
    }

    /**
     * Add file metadata
     *
     * @param int   $fileid
     * @param array $metadata
     *
     * @return bool
     */
    public static function add_metadata($fileid, array $metadata) {
        $fileobj = self::get_by_id($fileid);
        $metadata = array_merge($fileobj->metadata ? json_decode($fileobj->metadata, true) : [], $metadata);
        $fileobj->metadata = json_encode($metadata);

        return self::save($fileobj);
    }

    /**
     * set_cheating_info
     *
     * @param stdClass $plagiarismfile
     * @param array    $cheating
     *
     * @return bool
     */
    public static function set_cheating_info(stdClass $plagiarismfile, array $cheating) {
        $cheatinginfo = [];
        $hascheating = false;
        if (isset($cheating['char_replacement_count']) && $cheating['char_replacement_count']) {
            $hascheating = true;
            $cheatinginfo[unicheck_file_metadata::CHEATING_CHAR_REPLACEMENTS_COUNT] = (int) $cheating['char_replacement_count'];
        }

        if (isset($cheating['char_replacement_words_count']) && $cheating['char_replacement_words_count']) {
            $cheatinginfo[unicheck_file_metadata::CHEATING_CHAR_REPLACEMENTS_WORDS_COUNT]
                = (int) $cheating['char_replacement_words_count'];
        }

        if (isset($cheating['total_pages_count']) && $cheating['total_pages_count']) {
            $hascheating = true;
            $cheatingpages = (int) $cheating['total_pages_count'];
            $cheatinginfo[unicheck_file_metadata::CHEATING_TOTAL_PAGES_COUNT] = $cheatingpages;
        }

        if (isset($cheating['suspicious_pages_count']) && $cheating['suspicious_pages_count']) {
            $cheatinginfo[unicheck_file_metadata::CHEATING_SUSPICIOUS_PAGES_COUNT] = (int) $cheating['suspicious_pages_count'];
        }

        if (isset($cheating['is_similarity_affected']) && $cheating['is_similarity_affected']) {
            $cheatinginfo[unicheck_file_metadata::CHEATING_IS_SIMILARITY_AFFECTED] = (bool) $cheating['is_similarity_affected'];
        }

        if ($hascheating) {
            $cheatinginfo[unicheck_file_metadata::CHEATING_EXIST] = $hascheating;
        }

        if (empty($cheatinginfo)) {
            return true;
        }

        $result = self::add_metadata($plagiarismfile->id, $cheatinginfo);

        if ($plagiarismfile->parent_id && $hascheating) {
            self::add_metadata($plagiarismfile->parent_id, [
                unicheck_file_metadata::CHEATING_EXIST => $hascheating
            ]);
        }

        return $result;
    }

    /**
     * Get all frozen documents fron database
     *
     * @return array
     */
    public static function get_frozen_files() {
        global $DB;

        $querywhere = "(state <> :checked_state AND state <> :error_state)
                        AND UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY)) > timesubmitted
                        AND external_file_uuid IS NOT NULL";

        return $DB->get_records_select(
            UNICHECK_FILES_TABLE,
            $querywhere,
            [
                'checked_state' => unicheck_file_state::CHECKED,
                'error_state'   => unicheck_file_state::HAS_ERROR
            ]
        );
    }

    /**
     * Get all frozen archive
     *
     * @return array
     */
    public static function get_frozen_archive() {
        global $DB;

        $querywhere = "(state <> :checked_state AND state <> :error_state)
                        AND UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 DAY )) > timesubmitted
                        AND type = :archive_type";

        return $DB->get_records_select(
            UNICHECK_FILES_TABLE,
            $querywhere,
            [
                'checked_state' => unicheck_file_state::CHECKED,
                'error_state'   => unicheck_file_state::HAS_ERROR,
                'archive_type'  => unicheck_plagiarism_entity::TYPE_ARCHIVE
            ]
        );
    }

    /**
     * Update frozen documents in database
     *
     * @param \stdClass $dbobjectfile
     * @param \stdClass $apiobjectcheck
     */
    public static function update_frozen_check($dbobjectfile, $apiobjectcheck) {
        if (is_null($dbobjectfile->check_id)) {
            $dbobjectfile->check_id = $apiobjectcheck->id;
        }
        if (is_null($dbobjectfile->external_file_id)) {
            $dbobjectfile->external_file_id = $apiobjectcheck->file_id;
        }
        unicheck_check_helper::check_complete($dbobjectfile, $apiobjectcheck);
    }

    /**
     * Delete plagiarism files by id array
     *
     * @param array $ids
     */
    public static function delete_by_ids($ids) {
        global $DB;
        if (empty($ids)) {
            return;
        }

        list($select, $params) = $DB->get_in_or_equal($ids);
        // We are going to use select twice so double the params.
        $params = array_merge($params, $params);

        $DB->delete_records_select(UNICHECK_FILES_TABLE, "id {$select} OR parent_id {$select}", $params);
    }

    /**
     * Get min value from the timesubmitted field
     *
     * @return mixed|null
     */
    public static function get_min_timesubmitted() {
        global $DB;

        return $DB->get_field_sql("SELECT MIN(timesubmitted) FROM {plagiarism_unicheck_files} where timesubmitted > 0");
    }

    /**
     * resubmit_by_ids
     *
     * @param array $ids
     *
     * @return int
     */
    public static function resubmit_by_ids(array $ids) {
        /** @var \stdClass[] $plagiarismfiles */
        $plagiarismfiles = self::find_by_ids($ids);
        $resubmittedcount = 0;
        foreach ($plagiarismfiles as $plagiarismfile) {
            switch ($plagiarismfile->state) {
                case unicheck_file_state::CHECKING:
                    $response = unicheck_api::instance()->get_check_data($plagiarismfile->check_id);
                    if ($response->result) {
                        unicheck_check_helper::check_complete($plagiarismfile, $response->check);
                    } else {
                        $plagiarismfile->errorresponse = json_encode($response->errors);
                        self::save($plagiarismfile);
                    }

                    break;
                case unicheck_file_state::HAS_ERROR:
                    $error = json_decode($plagiarismfile->errorresponse, true);
                    $errorcode = 'internal_error';
                    if (isset($error[0]['error_code'])) {
                        $errorcode = $error[0]['error_code'];
                    }

                    if (file_error_code::is_consider_file_issue($errorcode)) {
                        break;
                    }

                    $resubmittedcount++;
                    unicheck_core::resubmit_file($plagiarismfile->id);

                    break;
            }
        }

        return $resubmittedcount;
    }
}
