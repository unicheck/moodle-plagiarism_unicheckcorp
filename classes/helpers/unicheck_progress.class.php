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
 * Class unicheck_progress
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\helpers;

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\exception\unicheck_exception;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_adhoc;
use plagiarism_unicheck\classes\unicheck_api;
use plagiarism_unicheck\classes\unicheck_plagiarism_entity;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_progress
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_progress {
    /**
     * get_file_progress_info
     *
     * @param object $plagiarismfile
     * @param int    $cid
     * @param array  $checkstatusforids
     *
     * @return array|bool
     */
    public static function get_check_progress_info($plagiarismfile, $cid, &$checkstatusforids) {
        $childs = [];
        if ($plagiarismfile->type == unicheck_plagiarism_entity::TYPE_ARCHIVE) {
            $childs = unicheck_file_provider::get_file_list_by_parent_id($plagiarismfile->id);
        }

        if ($plagiarismfile->progress != 100) {
            if (count($childs)) {
                foreach ($childs as $child) {
                    if ($child->check_id) {
                        $checkstatusforids[$plagiarismfile->id][] = $child->check_id;
                    }
                }
            } else {
                if ($plagiarismfile->check_id) {
                    $checkstatusforids[$plagiarismfile->id][] = $plagiarismfile->check_id;
                }
            }
        }

        $info = [
            'file_id'  => $plagiarismfile->id,
            'state'    => $plagiarismfile->state,
            'progress' => (int)$plagiarismfile->progress,
            'content'  => self::gen_row_content_score($cid, $plagiarismfile),
        ];

        return $info;
    }

    /**
     * Track file upload
     *
     * @param \stdClass $plagiarismfile
     */
    public static function track_upload(\stdClass $plagiarismfile) {
        $trackedfiles = [$plagiarismfile];
        if ($plagiarismfile->type == unicheck_plagiarism_entity::TYPE_ARCHIVE) {
            $trackedfiles = unicheck_file_provider::get_file_list_by_parent_id($plagiarismfile->id);
        }

        foreach ($trackedfiles as $trackedfile) {
            if (!$trackedfile->external_file_uuid) {
                continue;
            }

            $response = unicheck_api::instance()->get_file_upload_progress($trackedfile->external_file_uuid);
            if (!$response->result) {
                unicheck_response::store_errors($response->errors, $plagiarismfile, unicheck_response::FILE_RESOURCE);
                continue;
            }

            $progress = $response->progress;
            if ($progress->file && $progress->file->id && !$trackedfile->check_id) {
                unicheck_upload_helper::upload_complete($trackedfile, $progress->file);
                unicheck_adhoc::check($trackedfile);
            }
        }
    }

    /**
     * get_real_check_progress
     *
     * @param int   $cid
     * @param array $checkstatusforids
     * @param array $resp
     *
     * @throws unicheck_exception
     */
    public static function get_real_check_progress($cid, $checkstatusforids, &$resp) {
        global $DB;

        $progressids = [];

        foreach ($checkstatusforids as $recordid => $checkids) {
            $progressids = array_merge($progressids, $checkids);
        }

        $progressids = array_unique($progressids);
        $progresses = unicheck_api::instance()->get_check_progress($progressids);

        if ($progresses->result) {
            foreach ($progresses->progress as $id => $val) {
                $val *= 100;
                $fileobj = self::update_file_progress($id, $val);
                $resp[$fileobj->id]['progress'] = $val;
                $resp[$fileobj->id]['content'] = self::gen_row_content_score($cid, $fileobj);
            }

            foreach ($checkstatusforids as $recordid => $checkids) {
                if (count($checkids) > 0) {
                    $childscount = $DB->count_records_select(UNICHECK_FILES_TABLE, "parent_id = ? AND state not in (?)",
                        [$recordid, unicheck_file_state::HAS_ERROR]) ?: 1;

                    $progress = 0;

                    foreach ($checkids as $id) {
                        $progress += ($progresses->progress->{$id} * 100);
                    }

                    $progress = floor($progress / $childscount);
                    $fileobj = self::update_parent_progress($recordid, $progress);
                    $resp[$recordid]['progress'] = $progress;
                    $resp[$recordid]['content'] = self::gen_row_content_score($cid, $fileobj);
                }
            }
        }

        if ($progresses->errors) {
            foreach ($progresses->errors as $checkid => $error) {
                $plagiarismfile = unicheck_file_provider::find_by_check_id($checkid);
                unicheck_file_provider::to_error_state($plagiarismfile, $error->message);
                $resp[$plagiarismfile->id]['content'] = self::gen_row_content_score($cid, $plagiarismfile);
            }
        }
    }

    /**
     * gen_row_content_score
     *
     * @param int    $cid
     * @param object $fileobj
     *
     * @return string
     */
    public static function gen_row_content_score($cid, $fileobj) {
        if ($fileobj->progress == 100 && $cid) {
            return require(dirname(__FILE__) . '/../../views/view_tmpl_processed.php');
        }

        switch ($fileobj->state) {
            case unicheck_file_state::UPLOADING:
            case unicheck_file_state::UPLOADED:
            case unicheck_file_state::CHECKING:
                return require(dirname(__FILE__) . '/../../views/view_tmpl_progress.php');
            case unicheck_file_state::HAS_ERROR:
                return require(dirname(__FILE__) . '/../../views/view_tmpl_invalid_response.php');
        }

        return '';
    }

    /**
     * update_file_progress
     *
     * @param int $id
     * @param int $progress
     *
     * @return mixed
     * @throws unicheck_exception
     */
    private static function update_file_progress($id, $progress) {
        $record = unicheck_file_provider::find_by_check_id($id);
        if ($record->progress <= $progress) {
            $record->progress = $progress;

            if ($record->progress === 100) {
                $resp = unicheck_api::instance()->get_check_data($id);
                if (!$resp->result) {
                    $errors = array_shift($resp->errors);
                    throw new unicheck_exception($errors->message);
                }

                unicheck_check_helper::check_complete($record, $resp->check);
            } else {
                unicheck_file_provider::save($record);
            }
        }

        return $record;
    }

    /**
     * update_parent_progress
     *
     * @param int $fileid
     * @param int $progress
     *
     * @return mixed
     */
    private static function update_parent_progress($fileid, $progress) {
        $record = unicheck_file_provider::find_by_id($fileid);
        if ($record->progress <= $progress) {
            $record->progress = $progress;
            if ($record->progress != 100) {
                unicheck_file_provider::save($record);
            }
        }

        return $record;
    }
}