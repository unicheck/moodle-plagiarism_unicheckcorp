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
 * unicheck_settings.class.php
 *
 * @package     plagiarism_unicheck
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_settings
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_settings {
    /**
     * SENSITIVITY_SETTING_NAME
     */
    const SENSITIVITY_SETTING_NAME = 'similarity_sensitivity';
    /**
     * WORDS_SENSITIVITY
     */
    const WORDS_SENSITIVITY = 'similarity_words_sensitivity';
    /**
     * USE_UNICHECK
     */
    const USE_UNICHECK = 'use_unicheck';
    /**
     * SHOW_STUDENT_SCORE
     */
    const SHOW_STUDENT_SCORE = 'show_student_score';
    /**
     * SHOW_STUDENT_REPORT
     */
    const SHOW_STUDENT_REPORT = 'show_student_report';
    /**
     * DRAFT_SUBMIT
     */
    const DRAFT_SUBMIT = 'draft_submit';
    /**
     * CHECK_TYPE
     */
    const CHECK_TYPE = 'check_type';
    /**
     * EXCLUDE_CITATIONS
     */
    const EXCLUDE_CITATIONS = 'exclude_citations';
    /**
     * EXCLUDE_SELF_PLAGIARISM
     */
    const EXCLUDE_SELF_PLAGIARISM = 'exclude_self_plagiarism';
    /**
     * CHECK_ALL_SUBMITTED_ASSIGNMENTS
     */
    const CHECK_ALL_SUBMITTED_ASSIGNMENTS = 'check_all_submitted_assignments';
    /**
     * NO_INDEX_FILES
     */
    const NO_INDEX_FILES = 'no_index_files';
    /** @var array */
    public static $supportedchecktypes = array(
        UNICHECK_CHECK_TYPE_WEB__LIBRARY,
        UNICHECK_CHECK_TYPE_WEB,
        UNICHECK_CHECK_TYPE_MY_LIBRARY,
        UNICHECK_CHECK_TYPE_EXTERNAL_DB,
        UNICHECK_CHECK_TYPE_WEB__MY_LIB__EXTERNAL_DB,
    );

    /**
     * Get assign settings
     *
     * @param int  $cmid
     * @param null $name
     *
     * @param bool $assoc
     *
     * @return \stdClass|array
     */
    public static function get_assign_settings($cmid, $name = null, $assoc = null) {
        global $DB;

        $condition = array(
            'cm' => $cmid,
        );

        if (isset($name)) {
            $condition['name'] = $name;
        }

        $data = $DB->get_records(UNICHECK_CONFIG_TABLE, $condition, '', 'name,value');
        $data = array_map(function ($item) {
            return $item->value;
        }, $data);

        if (is_bool($assoc) && $assoc) {
            return $data;
        }

        if (isset($data[$name])) {
            return $data[$name];
        }

        return [];
    }

    /**
     * This function should be used to initialise settings and check if plagiarism is enabled.
     *
     * @param null|string $key
     *
     * @return array|bool
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_settings($key = null) {
        static $settings;

        if (!empty($settings)) {
            return self::get_settings_item($settings, $key);
        }

        $settings = (array) get_config('plagiarism');

        // Check if enabled.
        if (isset($settings['unicheck_use']) && $settings['unicheck_use']) {
            // Now check to make sure required settings are set!
            if (empty($settings['unicheck_api_secret'])) {
                throw new \coding_exception('API Secret not set!');
            }

            return self::get_settings_item($settings, $key);
        } else {
            return false;
        }
    }

    /**
     * Get item settings
     *
     * @param array $settings
     * @param null  $key
     *
     * @return null
     */
    private static function get_settings_item($settings, $key = null) {
        if (is_null($key)) {
            return $settings;
        }

        $key = 'unicheck_' . $key;

        return isset($settings[$key]) ? $settings[$key] : null;
    }
}