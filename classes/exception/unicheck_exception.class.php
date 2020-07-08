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
 * unicheck_exception.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\exception;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_exception
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 *
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_exception extends \Exception {
    /**
     * ARCHIVE_IS_EMPTY
     */
    const ARCHIVE_IS_EMPTY = 'Archive is empty or contains document(s) with no text';
    /**
     * ZIP_ARCHIVE_CANT_BE_OPEN
     */
    const ZIP_ARCHIVE_CANT_BE_OPEN = 'Can not open zip archive';
    /**
     * RAR_ARCHIVE_CANT_BE_OPEN
     */
    const RAR_ARCHIVE_CANT_BE_OPEN = 'Can not open rar archive';
    /**
     * UNSUPPORTED_MIMETYPE
     */
    const UNSUPPORTED_MIMETYPE = 'Unsupported mimetype';
    /**
     * FILE_NOT_FOUND
     */
    const FILE_NOT_FOUND = 'File not found';
    /**
     * FILE_IS_TOO_LARGE
     */
    const FILE_IS_TOO_LARGE = 'File is too large for similarity checking';
    /**
     * CAN_NOT_READ_FILE
     */
    const CAN_NOT_READ_FILE = 'File does not exist or there is a permission problem';
}