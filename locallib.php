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

use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\helpers\unicheck_translate;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_settings;

global $CFG;

require_once($CFG->libdir . '/filelib.php');

require_once(__DIR__ . '/constants.php');
require_once(__DIR__ . '/autoloader.php');

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
    private static $supportedplagiarismmods = [
        UNICHECK_MODNAME_ASSIGN,
        UNICHECK_MODNAME_WORKSHOP,
        UNICHECK_MODNAME_FORUM,
    ];
    /**
     * @var array
     */
    private static $supportedarchivemimetypes = [
        unicheck_archive::RAR_MIMETYPE,
        unicheck_archive::ZIP_MIMETYPE,
    ];
    /** @var array */
    private static $supportedfilearea = [
        UNICHECK_WORKSHOP_FILES_AREA,
        UNICHECK_DEFAULT_FILES_AREA,
        UNICHECK_FORUM_FILES_AREA,
        'submission_files',
        'submission_attachment',
        'attachment',
    ];
    /**
     * @var array
     */
    private static $supportedextension = [
        'pdf',
        'odt',
        'odp',
        'doc',
        'docx',
        'html',
        'txt',
        'rtf',
        'ppt',
        'pptx',
        'pages',
        'htm',
        'xls',
        'xlsx',
        'ods'
    ];

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
     * Verify supporting for file extension
     *
     * @param string $ext
     *
     * @return bool
     */
    public static function is_supported_extension($ext) {
        return in_array(strtolower($ext), self::$supportedextension);
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
     * Get list of files for current context
     *
     * @param int    $contextid
     * @param string $filearea
     * @param null   $itemid
     *
     * @return stored_file[]
     * @throws coding_exception
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
     * @throws coding_exception
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
}