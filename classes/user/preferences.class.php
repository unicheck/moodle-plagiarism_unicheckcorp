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
 * preferences.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\user;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class preferences
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences {
    /**
     * DEBUGGING_TIME_SUBMITTED_FROM
     */
    const DEBUGGING_TIME_SUBMITTED_FROM = 'plagiarism/unicheck:debuggingtimesubmittedfrom';
    /**
     * DEBUGGING_TIME_SUBMITTED_TO
     */
    const DEBUGGING_TIME_SUBMITTED_TO = 'plagiarism/unicheck:debuggingtimesubmittedto';
    /**
     * DEBUGGING_PER_PAGE
     */
    const DEBUGGING_PER_PAGE = 'plagiarism/unicheck:debuggingperpage';
    /**
     * DEBUGGING_ERROR_TYPE
     */
    const DEBUGGING_ERROR_TYPE = 'plagiarism/unicheck:debuggingerrortype';
    /**
     * DEBUGGING_ERROR_FILTER
     */
    const DEBUGGING_ERROR_MESSAGE = 'plagiarism/unicheck:debuggingerrormessage';
}