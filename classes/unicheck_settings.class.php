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
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

use plagiarism_unicheck\classes\permissions\capability;
use plagiarism_unicheck\classes\services\api\api_regions;

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
     * Enable Unicheck Plagiarism Service
     */
    const ENABLE_UNICHECK = 'use_unicheck';
    /**
     * Check already delivered assignment submissions
     */
    const CHECK_ALREADY_DELIVERED_ASSIGNMENT_SUBMISSIONS = 'check_all_submitted_assignments';
    /**
     * Add submissions to Institutional Library
     */
    const ADD_TO_INSTITUTIONAL_LIBRARY = 'no_index_files';
    /**
     * Sources for comparison
     */
    const SOURCES_FOR_COMPARISON = 'check_type';
    /**
     * Exclude sources with a match less than (%)
     */
    const SENSITIVITY_SETTING_NAME = 'similarity_sensitivity';
    /**
     * Exclude sources with a match less than (words)
     */
    const WORDS_SENSITIVITY = 'similarity_words_sensitivity';
    /**
     * Exclude references and citations
     */
    const EXCLUDE_CITATIONS = 'exclude_citations';
    /**
     * Show similarity scores to student
     */
    const SHOW_STUDENT_SCORE = 'show_student_score';
    /**
     * Show similarity reports to student
     */
    const SHOW_STUDENT_REPORT = 'show_student_report';
    /**
     * Maximum number of files to be checked in archive
     */
    const MAX_SUPPORTED_ARCHIVE_FILES_COUNT = 'max_supported_archive_files_count';
    /**
     * Sent students report
     */
    const SENT_STUDENT_REPORT = 'sent_student_report';

    /**
     * DRAFT_SUBMIT
     */
    const DRAFT_SUBMIT = 'draft_submit';

    /**
     * @var int
     */
    public static $defaultsensitivity = 0;

    /**
     * @var int
     */
    public static $defaultwordssensitivity = 8;

    /**
     * @var array
     */
    private static $supportedchecktypes = [
        UNICHECK_CHECK_TYPE_WEB__LIBRARY,
        UNICHECK_CHECK_TYPE_WEB,
        UNICHECK_CHECK_TYPE_MY_LIBRARY,
        UNICHECK_CHECK_TYPE_EXTERNAL_DB,
        UNICHECK_CHECK_TYPE_WEB__MY_LIB__EXTERNAL_DB,
    ];

    /**
     * @var array
     */
    private static $settingcapabilities = [
        self::ENABLE_UNICHECK                                => capability::CHANGE_ENABLE_UNICHECK_SETTING,
        self::CHECK_ALREADY_DELIVERED_ASSIGNMENT_SUBMISSIONS => capability::CHANGE_CHECK_ALREADY_SUBMITTED_ASSIGNMENT_SETTING,
        self::ADD_TO_INSTITUTIONAL_LIBRARY                   => capability::CHANGE_ADD_SUBMISSION_TO_LIBRARY_SETTING,
        self::SOURCES_FOR_COMPARISON                         => capability::CHANGE_SOURCES_FOR_COMPARISON_SETTING,
        self::SENSITIVITY_SETTING_NAME                       => capability::CHANGE_SENSITIVITY_PERCENTAGE_SETTING,
        self::WORDS_SENSITIVITY                              => capability::CHANGE_WORD_SENSITIVITY_SETTING,
        self::EXCLUDE_CITATIONS                              => capability::CHANGE_EXCLUDE_CITATIONS_SETTING,
        self::SHOW_STUDENT_SCORE                             => capability::CHANGE_SHOW_STUDENT_SCORE_SETTING,
        self::SHOW_STUDENT_REPORT                            => capability::CHANGE_SHOW_STUDENT_REPORT_SETTING,
        self::MAX_SUPPORTED_ARCHIVE_FILES_COUNT              => capability::CHANGE_MAX_SUPPORTED_ARCHIVE_FILES_COUNT_SETTING,
        self::SENT_STUDENT_REPORT                            => capability::CHANGE_SENT_STUDENT_REPORT_SETTING
    ];

    /**
     * @var array
     */
    private static $settingstypemap = [
        self::ENABLE_UNICHECK                                => PARAM_BOOL,
        self::CHECK_ALREADY_DELIVERED_ASSIGNMENT_SUBMISSIONS => PARAM_BOOL,
        self::ADD_TO_INSTITUTIONAL_LIBRARY                   => PARAM_BOOL,
        self::SOURCES_FOR_COMPARISON                         => PARAM_TEXT,
        self::SENSITIVITY_SETTING_NAME                       => PARAM_INT,
        self::WORDS_SENSITIVITY                              => PARAM_INT,
        self::EXCLUDE_CITATIONS                              => PARAM_BOOL,
        self::SHOW_STUDENT_SCORE                             => PARAM_BOOL,
        self::SHOW_STUDENT_REPORT                            => PARAM_BOOL,
        self::MAX_SUPPORTED_ARCHIVE_FILES_COUNT              => PARAM_INT,
        self::SENT_STUDENT_REPORT                            => PARAM_BOOL
    ];

    /**
     * Get activity settings
     * Activity - assign,forum,workshop
     *
     * @param int  $cmid
     * @param null $name
     *
     * @param bool $assoc
     *
     * @return \stdClass|array
     */
    public static function get_activity_settings($cmid, $name = null, $assoc = null) {
        global $DB;

        $condition = [
            'cm' => $cmid,
        ];

        if (isset($name)) {
            $condition['name'] = $name;
        }

        $data = $DB->get_records(UNICHECK_CONFIG_TABLE, $condition, '', 'name,value');
        $data = array_map(function($item) {
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
     * @param null $key
     *
     * @return bool|null
     * @throws \coding_exception
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

    /**
     * Get setting capability
     *
     * @param string $setting
     *
     * @return mixed|null
     */
    public static function get_capability($setting) {
        if (!array_key_exists($setting, self::$settingcapabilities)) {
            return null;
        }

        return self::$settingcapabilities[$setting];
    }

    /**
     * Get class constants
     *
     * @return array
     */
    public static function get_constants() {
        $class = new \ReflectionClass(__CLASS__);

        return $class->getConstants();
    }

    /**
     * Get supported check source types
     *
     * @return array
     */
    public static function get_supported_check_source_types() {
        $supportedchecktypes = [];
        $checktypes = unicheck_api::instance()->get_supported_search_types();
        if (!$checktypes || !$checktypes->result) {
            return [UNICHECK_CHECK_TYPE_WEB];
        }

        foreach ($checktypes->search_types as $searchtype) {
            if (in_array($searchtype->key, self::$supportedchecktypes)) {
                $supportedchecktypes[] = $searchtype->key;
            }
        }

        return $supportedchecktypes;
    }

    /**
     * Get current region
     *
     * @return string
     */
    public static function get_current_region() {
        $apiregion = self::get_settings('api_region');
        if (!$apiregion) {
            $apiregion = api_regions::US_EAST_1;
        }

        return $apiregion;
    }

    /**
     * Get setting type
     *
     * @param string $setting
     *
     * @return string
     */
    public static function get_setting_type($setting) {
        return isset(self::$settingstypemap[$setting]) ? self::$settingstypemap[$setting] : PARAM_RAW;
    }
}