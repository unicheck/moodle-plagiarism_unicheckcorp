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
 * capability.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\permissions;

use context_course;
use context_module;
use plagiarism_unicheck\classes\unicheck_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

/**
 * Class capability
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capability {

    /**
     * ENABLE
     */
    const ENABLE = 'plagiarism/unicheck:enable';
    /**
     * VIEW_SIMILARITY
     */
    const VIEW_SIMILARITY = 'plagiarism/unicheck:viewsimilarity';
    /**
     * VIEW_REPORT
     */
    const VIEW_REPORT = 'plagiarism/unicheck:viewreport';
    /**
     * VIEW_EDIT_REPORT
     */
    const VIEW_EDIT_REPORT = 'plagiarism/unicheck:vieweditreport';
    /**
     * RESET_FILE
     */
    const RESET_FILE = 'plagiarism/unicheck:resetfile';
    /**
     * CHECK_FILE
     */
    const CHECK_FILE = 'plagiarism/unicheck:checkfile';

    // Settings change capabilities.

    /**
     * CHANGE_ENABLE_UNICHECK_SETTING
     */
    const CHANGE_ENABLE_UNICHECK_SETTING = 'plagiarism/unicheck:changeenableunichecksetting';
    /**
     * CHANGE_CHECK_ALREADY_SUBMITTED_ASSIGNMENT_SETTING
     */
    const CHANGE_CHECK_ALREADY_SUBMITTED_ASSIGNMENT_SETTING = 'plagiarism/unicheck:changecheckalreadysubmittedassignmentsetting';
    /**
     * CHANGE_ADD_SUBMISSION_TO_LIBRARY_SETTING
     */
    const CHANGE_ADD_SUBMISSION_TO_LIBRARY_SETTING = 'plagiarism/unicheck:changeaddsubmissiontolibrarysetting';
    /**
     * CHANGE_SOURCES_FOR_COMPARISON_SETTING
     */
    const CHANGE_SOURCES_FOR_COMPARISON_SETTING = 'plagiarism/unicheck:changesourcesforcomparisonsetting';
    /**
     * CHANGE_SENSITIVITY_PERCENTAGE_SETTING
     */
    const CHANGE_SENSITIVITY_PERCENTAGE_SETTING = 'plagiarism/unicheck:changesensitivitypercentagesetting';
    /**
     * CHANGE_WORD_SENSITIVITY_SETTING
     */
    const CHANGE_WORD_SENSITIVITY_SETTING = 'plagiarism/unicheck:changewordsensitivitysetting';
    /**
     * CHANGE_EXCLUDE_CITATIONS_SETTING
     */
    const CHANGE_EXCLUDE_CITATIONS_SETTING = 'plagiarism/unicheck:changeexcludecitationssetting';
    /**
     * CHANGE_SHOW_STUDENT_SCORE_SETTING
     */
    const CHANGE_SHOW_STUDENT_SCORE_SETTING = 'plagiarism/unicheck:changeshowstudentscoresetting';
    /**
     * CHANGE_SHOW_STUDENT_REPORT_SETTING
     */
    const CHANGE_SHOW_STUDENT_REPORT_SETTING = 'plagiarism/unicheck:changeshowstudentreportsetting';
    /**
     * CHANGE_MAX_SUPPORTED_ARCHIVE_FILES_COUNT_SETTING
     */
    const CHANGE_MAX_SUPPORTED_ARCHIVE_FILES_COUNT_SETTING = 'plagiarism/unicheck:changemaxsupportedarchivefilescountsetting';
    /**
     * CHANGE_SENT_STUDENT_REPORT_SETTING
     */
    const CHANGE_SENT_STUDENT_REPORT_SETTING = 'plagiarism/unicheck:changesentstudentreportsetting';

    /**
     * Check user capability
     *
     * @param string $capability
     * @param int    $instanceid
     * @param int    $userid
     * @param int    $contextlevel
     *
     * @return bool
     * @throws \coding_exception
     */
    public static function user_can($capability, $instanceid, $userid, $contextlevel = CONTEXT_MODULE) {

        switch ($contextlevel) {
            case CONTEXT_MODULE:
                $context = context_module::instance($instanceid);
                break;
            case CONTEXT_COURSE:
                $context = context_course::instance($instanceid);
                break;
            default:
                return false;
        }

        return has_capability($capability, $context, $userid);
    }

    /**
     *  Check if user can view similarity check result
     *
     * @param int $cmid
     * @param int $userid
     *
     * @return bool
     */
    public static function can_view_similarity_check_result($cmid, $userid) {
        $activitycfg = unicheck_settings::get_activity_settings($cmid, null, true);
        $canviewsimilarity = self::user_can(self::VIEW_SIMILARITY, $cmid, $userid);
        if (!$canviewsimilarity) {
            $canviewsimilarity = $activitycfg[unicheck_settings::SHOW_STUDENT_SCORE];
        }

        $canviewreport = self::user_can(self::VIEW_REPORT, $cmid, $userid);
        if (!$canviewreport) {
            $canviewreport = $activitycfg[unicheck_settings::SHOW_STUDENT_REPORT];
        }

        return in_array(true, [$canviewsimilarity, $canviewreport]);
    }
}