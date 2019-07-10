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
 * constants.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

define('UNICHECK_PLAGIN_NAME', 'plagiarism_unicheck');

define('UNICHECK_DOMAIN', 'https://unicheck.com/');
define('UNICHECK_CORP_DOMAIN', 'https://corp.unicheck.com/');
define('UNICHECK_CORP_EU_DOMAIN', 'https://corp.eu.unicheck.com/');
define('UNICHECK_API_URL', 'https://corpapi.unicheck.com/api/v2/');
define('UNICHECK_EU_API_URL', 'https://corpapi.eu.unicheck.com/api/v2/');
define('UNICHECK_CALLBACK_URL', '/plagiarism/unicheck/callback.php');

define('UNICHECK_PROJECT_PATH', dirname(__FILE__) . '/');

define('UNICHECK_DEFAULT_FILES_AREA', 'assign_submission');
define('UNICHECK_WORKSHOP_FILES_AREA', 'workshop_submissions');
define('UNICHECK_FORUM_FILES_AREA', 'forum_posts');

/** TABLES **/
define('UNICHECK_FILES_TABLE', 'plagiarism_unicheck_files');
define('UNICHECK_COMMENTS_TABLE', 'plagiarism_unicheck_comments');
define('UNICHECK_USER_DATA_TABLE', 'plagiarism_unicheck_users');
define('UNICHECK_CONFIG_TABLE', 'plagiarism_unicheck_config');
define('UNICHECK_CALLBACK_TABLE', 'plagiarism_unicheck_callback');

define('UNICHECK_CHECK_TYPE_WEB', 'web');
define('UNICHECK_CHECK_TYPE_MY_LIBRARY', 'my_library');
define('UNICHECK_CHECK_TYPE_WEB__LIBRARY', 'web_and_my_library');
define('UNICHECK_CHECK_TYPE_EXTERNAL_DB', 'external_database');
define('UNICHECK_CHECK_TYPE_WEB__MY_LIB__EXTERNAL_DB', 'web_and_my_lib_and_external_db');

define('UNICHECK_WORKSHOP_SETUP_PHASE', 10);
define('UNICHECK_WORKSHOP_SUBMISSION_PHASE', 20);
define('UNICHECK_WORKSHOP_ASSESSMENT_PHASE', 30);
define('UNICHECK_WORKSHOP_GRADING_PHASE', 40);

define('UNICHECK_MODNAME_WORKSHOP', 'workshop');
define('UNICHECK_MODNAME_FORUM', 'forum');
define('UNICHECK_MODNAME_ASSIGN', 'assign');

define('UNICHECK_DEBUG_MODE', false);