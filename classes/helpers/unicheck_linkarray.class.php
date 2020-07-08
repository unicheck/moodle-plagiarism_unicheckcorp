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
 * unicheck_linkarray.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\helpers;

use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_workshop;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_linkarray
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_linkarray {
    /**
     * Get file from linkarray
     *
     * @param object $cm
     * @param array  $linkarray
     *
     * @return mixed|null|\stored_file
     */
    public static function get_file_from_linkarray($cm, $linkarray) {
        $file = null;
        if (isset($linkarray['content'])) {
            $context = \context_module::instance($linkarray['cmid']);
            switch ($cm->modname) {
                case UNICHECK_MODNAME_WORKSHOP:
                    $workshopsubmission = unicheck_workshop::get_user_workshop_submission_by_cm($cm, $linkarray['userid']);
                    $files = \plagiarism_unicheck::get_area_files(
                        $context->id,
                        UNICHECK_WORKSHOP_FILES_AREA,
                        $workshopsubmission->id
                    );
                    $file = array_shift($files);
                    break;
                case UNICHECK_MODNAME_FORUM:
                    $file = \plagiarism_unicheck::get_forum_topic_results($context, $linkarray);
                    break;
                case UNICHECK_MODNAME_ASSIGN:
                    $submission = unicheck_assign::get_user_submission_by_cmid($linkarray['cmid'], $linkarray['userid']);
                    $itemid = $submission ? $submission->id : null;
                    $files = \plagiarism_unicheck::get_area_files($context->id, UNICHECK_DEFAULT_FILES_AREA, $itemid);
                    $file = array_shift($files);
                    break;
                default:
                    $files = \plagiarism_unicheck::get_area_files($context->id, UNICHECK_DEFAULT_FILES_AREA);
                    $file = array_shift($files);
                    break;
            }
        } else {
            if (isset($linkarray['file'])) {
                $file = $linkarray['file'];
            }
        }

        return $file;
    }

    /**
     * Get output for linkarray
     *
     * @param \stdClass $fileobj
     * @param object    $cm
     * @param array     $linkarray
     *
     * @return mixed
     */
    public static function get_output_for_linkarray(\stdClass $fileobj, $cm, $linkarray) {
        global $CFG;
        static $iterator; // This iterator for one-time start-up.

        $tmpl = null;
        $inciterator = false;

        switch ($fileobj->state) {
            case unicheck_file_state::CHECKED:
                $tmpl = 'view_tmpl_processed.php';
                break;
            case unicheck_file_state::UPLOADING:
            case unicheck_file_state::UPLOADED:
            case unicheck_file_state::CHECKING:
                $tmpl = 'view_tmpl_progress.php';
                $inciterator = true;
                break;
            case unicheck_file_state::HAS_ERROR:
                $tmpl = 'view_tmpl_invalid_response.php';
                break;
            case unicheck_file_state::CREATED:
                if ($cm->modname != UNICHECK_MODNAME_ASSIGN) {
                    break;
                }

                $submission = unicheck_assign::get_user_submission_by_cmid($linkarray['cmid'], $linkarray['userid']);
                if ($submission && self::is_pending($cm, $fileobj)) {
                    $tmpl = 'view_tmpl_can_check.php';
                    $inciterator = true;
                }
                break;
            default:
                $tmpl = 'view_tmpl_unknownwarning.php';
                break;
        }

        if (is_null($tmpl)) {
            $output = '';
        } else {
            $output = require($CFG->dirroot . '/plagiarism/unicheck/views/' . $tmpl);
        }

        if ($inciterator) {
            $iterator++;
        }

        return $output;
    }

    /**
     * Check is file already pending
     *
     * @param object $cm
     * @param object $fileobj
     *
     * @return bool
     */
    private static function is_pending($cm, $fileobj) {
        return $cm->modname == UNICHECK_MODNAME_ASSIGN && empty($fileobj->external_file_uuid);
    }
}