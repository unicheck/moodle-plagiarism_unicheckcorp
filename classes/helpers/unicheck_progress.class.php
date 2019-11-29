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
use plagiarism_unicheck\classes\permissions\capability;
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
     * @param int    $cmid
     * @param array  $trackedfiles
     *
     * @return array|bool
     */
    public static function get_check_progress_info($plagiarismfile, $cmid, &$trackedfiles) {
        $childs = [];
        if ($plagiarismfile->type == unicheck_plagiarism_entity::TYPE_ARCHIVE) {
            $childs = unicheck_file_provider::get_files_by_parent_id_in_states(
                $plagiarismfile->id,
                [unicheck_file_state::HAS_ERROR],
                false
            );
        }

        if ($plagiarismfile->progress != 100) {
            $trackedfiles[$plagiarismfile->id]['checks'] = [];
            if (count($childs)) {
                foreach ($childs as $child) {
                    if ($child->check_id) {
                        $trackedfiles[$plagiarismfile->id]['checks'][] = $child->check_id;
                    }
                }
            } else {
                if ($plagiarismfile->check_id) {
                    $trackedfiles[$plagiarismfile->id]['checks'][] = $plagiarismfile->check_id;
                }
            }
        }

        $info = [
            'file_id'  => $plagiarismfile->id,
            'state'    => $plagiarismfile->state,
            'progress' => (int) $plagiarismfile->progress,
            'content'  => self::gen_row_content_score($cmid, $plagiarismfile),
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
            $trackedfiles = unicheck_file_provider::get_files_by_parent_id_in_states(
                $plagiarismfile->id,
                [unicheck_file_state::HAS_ERROR],
                false
            );
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
     * @param int   $cmid
     * @param array $trackedfiles
     * @param array $resp
     *
     * @throws unicheck_exception
     */
    public static function get_real_check_progress($cmid, $trackedfiles, &$resp) {
        $progressids = [];
        foreach ($trackedfiles as $recordid => $recordparams) {
            $progressids = array_merge($progressids, $recordparams['checks']);
        }

        $progressids = array_unique($progressids);
        $progresses = unicheck_api::instance()->get_check_progress($progressids);

        if ($progresses->result) {
            foreach ($trackedfiles as $recordid => $recordparams) {
                // Progresses of single file or sum of archive contents.
                $fileprogress = 0;
                $checks = $recordparams['checks'];
                $childscount = count($checks);
                $plagiarismfile = null;

                foreach ($progresses->progress as $checkid => $checkprogress) {
                    if (in_array($checkid, $checks)) {
                        $checkprogress = $checkprogress * 100;
                        $fileprogress += $checkprogress;
                        $checkedfile = self::update_file_progress_by_check($checkid, $checkprogress);
                        if ($checkedfile->id == $recordid) {
                            $plagiarismfile = $checkedfile;
                        }
                    }
                }

                if ($childscount > 1) {
                    $fileprogress = floor($fileprogress / $childscount);
                    $plagiarismfile = self::update_parent_progress($recordid, $fileprogress);
                }

                $resp[$recordid]['progress'] = $fileprogress;
                $resp[$recordid]['content'] = self::gen_row_content_score($cmid, $plagiarismfile);
            }
        }

        if ($progresses->errors) {
            foreach ($progresses->errors as $checkid => $error) {
                $plagiarismfile = unicheck_file_provider::find_by_check_id($checkid);
                unicheck_file_provider::to_error_state($plagiarismfile, $error->message);
                $resp[$plagiarismfile->id]['content'] = self::gen_row_content_score($cmid, $plagiarismfile);
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
        global $USER, $CFG;

        // Not allowed to view similarity check result.
        if (!capability::can_view_similarity_check_result($cid, $USER->id)) {
            return null;
        }

        if ($fileobj->progress == 100 && $cid) {
            return require($CFG->dirroot . '/plagiarism/unicheck/views/view_tmpl_processed.php');
        }

        switch ($fileobj->state) {
            case unicheck_file_state::UPLOADING:
            case unicheck_file_state::UPLOADED:
            case unicheck_file_state::CHECKING:
                return require($CFG->dirroot . '/plagiarism/unicheck/views/view_tmpl_progress.php');
            case unicheck_file_state::HAS_ERROR:
                return require($CFG->dirroot . '/plagiarism/unicheck/views/view_tmpl_invalid_response.php');
        }

        return '';
    }

    /**
     * update_file_progress
     *
     * @param int $checkid
     * @param int $progress
     *
     * @return object
     * @throws unicheck_exception
     */
    private static function update_file_progress_by_check($checkid, $progress) {
        $record = unicheck_file_provider::find_by_check_id($checkid);
        if ($record->progress < $progress) {
            $record->progress = $progress;

            if ($record->progress === 100) {
                $resp = unicheck_api::instance()->get_check_data($checkid);
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