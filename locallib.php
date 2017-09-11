<?php
// This file is part of the Checklist plugin for Moodle - http://moodle.org/
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
 * locallib.php - Stores all the functions for manipulating a plagiarism_unicheck
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\event\base;
use plagiarism_unicheck\classes\entities\unicheck_event;
use plagiarism_unicheck\classes\event\unicheck_event_validator;
use plagiarism_unicheck\classes\helpers\unicheck_check_helper;
use plagiarism_unicheck\classes\helpers\unicheck_progress;
use plagiarism_unicheck\classes\helpers\unicheck_translate;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_settings;

global $CFG;

require_once($CFG->dirroot . '/config.php');
require_once($CFG->libdir . '/filelib.php');

require_once(dirname(__FILE__) . '/constants.php');
require_once(dirname(__FILE__) . '/autoloader.php');

/**
 * Class plagiarism_unicheck
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_unicheck {
    use unicheck_translate;
    /**
     * @var array
     */
    private static $supportedplagiarismmods = array(
        UNICHECK_MODNAME_ASSIGN, UNICHECK_MODNAME_WORKSHOP, UNICHECK_MODNAME_FORUM,
    );
    /**
     * @var array
     */
    private static $supportedarchivemimetypes = array(
        'application/zip',
    );
    /** @var array */
    private static $supportedfilearea = array(
        UNICHECK_WORKSHOP_FILES_AREA,
        UNICHECK_DEFAULT_FILES_AREA,
        UNICHECK_FORUM_FILES_AREA,
        'submission_files',
        'submission_attachment',
        'attachment',
    );

    /**
     * Handle all system events
     *
     * @param base $event
     */
    public static function event_handler(base $event) {
        if (unicheck_event_validator::validate_event($event)) {
            $eventinstance = new unicheck_event();
            $eventinstance->process($event);
        }
    }

    /**
     * Verify supporting for modules like: assign, workshop, forum
     *
     * @param string $modname
     *
     * @return bool
     */
    public static function is_support_mod($modname) {
        return in_array($modname, self::$supportedplagiarismmods);
    }

    /**
     * Verify supporting for file areas
     *
     * @param string $filearea
     *
     * @return bool
     */
    public static function is_support_filearea($filearea) {
        return in_array($filearea, self::$supportedfilearea);
    }

    /**
     * Verify supporting for file mimetype
     *
     * @param stored_file $file
     *
     * @return bool
     */
    public static function is_archive(stored_file $file) {
        if ($mimetype = $file->get_mimetype()) {
            if (in_array($mimetype, self::$supportedarchivemimetypes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert object to array
     *
     * @param object $obj
     *
     * @return array
     */
    public static function object_to_array($obj) {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }
        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = self::object_to_array($val);
            }
        } else {
            $new = $obj;
        }

        return $new;
    }

    /**
     * Get list of files for current context
     *
     * @param int    $contextid
     * @param string $filearea
     * @param null   $itemid
     *
     * @return stored_file[]
     */
    public static function get_area_files($contextid, $filearea = UNICHECK_DEFAULT_FILES_AREA, $itemid = null) {

        $itemid = ($itemid !== null) ? $itemid : false;

        return get_file_storage()->get_area_files(
            $contextid, UNICHECK_PLAGIN_NAME, $filearea, $itemid, null, false
        );
    }

    /**
     * Check whether the plugin is enabled
     *
     * @return null|false
     */
    public static function is_plugin_enabled() {
        return unicheck_settings::get_settings('use');
    }

    /**
     * Get forum topic results
     *
     * @param context_module $context
     * @param array          $linkarray
     *
     * @return null|stored_file
     */
    public static function get_forum_topic_results(context_module $context, array $linkarray) {
        $contenthash = unicheck_core::content_hash($linkarray['content']);
        $file = unicheck_core::get_file_by_hash($context->id, $contenthash);

        return $file;
    }

    /**
     * Error handler
     *
     * @param string $errorresponse
     *
     * @return string
     */
    public static function error_resp_handler($errorresponse) {
        $errors = json_decode($errorresponse, true);
        if (is_array($errors)) {
            $error = self::api_trans(current($errors));
        } else {
            $error = self::trans('unknownwarning');
        }

        return $error;
    }

    /**
     * Track current file status
     *
     * @param string $data
     *
     * @return string
     */
    public function track_progress($data) {
        global $DB;

        $data = unicheck_core::parse_json($data);
        $resp = null;
        $records = $DB->get_records_list(UNICHECK_FILES_TABLE, 'id', $data->ids);

        if ($records) {
            $checkstatusforids = array();

            foreach ($records as $record) {
                $progressinfo = unicheck_progress::get_file_progress_info($record, $data->cid, $checkstatusforids);

                if ($progressinfo) {
                    $resp[$record->id] = $progressinfo;
                }
            }

            try {
                if (!empty($checkstatusforids)) {
                    unicheck_progress::check_real_file_progress($data->cid, $checkstatusforids, $resp);
                }
            } catch (\Exception $ex) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                $resp['error'] = $ex->getMessage();
            }
        }

        return unicheck_core::json_response($resp);
    }

    /**
     * Callback handler
     *
     * @param string $token
     *
     * @throws moodle_exception
     */
    public function callback_handler($token) {
        global $DB;

        if (self::access_granted($token)) {
            $record = $DB->get_record(UNICHECK_FILES_TABLE, array('identifier' => $token));
            $rawjson = file_get_contents('php://input');
            $respcheck = unicheck_core::parse_json($rawjson);
            if ($record && isset($respcheck->check)) {
                $progress = 100 * $respcheck->check->progress;
                unicheck_check_helper::check_complete($record, $respcheck->check, $progress);
            }
        } else {
            print_error('error');
        }
    }

    /**
     * Check access grunt
     *
     * @param string $token
     *
     * @return bool
     */
    private static function access_granted($token) {
        return ($token && strlen($token) === 40 && $_SERVER['REQUEST_METHOD'] == 'POST');
    }
}