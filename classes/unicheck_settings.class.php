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
    const NO_INDEX_FILES = 'no_index_files';
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
     * DRAFT_SUBMIT
     */
    const DRAFT_SUBMIT = 'draft_submit';
    /**
     * EXCLUDE_SELF_PLAGIARISM
     */
    const EXCLUDE_SELF_PLAGIARISM = 'exclude_self_plagiarism';

    /** @var array */
    public static $supportedchecktypes = [
        UNICHECK_CHECK_TYPE_WEB__LIBRARY,
        UNICHECK_CHECK_TYPE_WEB,
        UNICHECK_CHECK_TYPE_MY_LIBRARY,
        UNICHECK_CHECK_TYPE_EXTERNAL_DB,
        UNICHECK_CHECK_TYPE_WEB__MY_LIB__EXTERNAL_DB,
    ];

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

        $settings = (array)get_config('plagiarism');

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