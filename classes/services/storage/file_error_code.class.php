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
 * file_error_code.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\services\storage;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class file_error_code
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_error_code {
    /**
     * SIZE_ERROR
     */
    const SIZE_ERROR = 'size_error';
    /**
     * INTERNAL_SERVER_ERROR
     */
    const INTERNAL_SERVER_ERROR = 'internal_server_error';
    /**
     * CORRUPTED_FILE
     */
    const CORRUPTED_FILE = 'corrupted_file';
    /**
     * PASSWORD_PROTECTED
     */
    const PASSWORD_PROTECTED = 'password_protected';
    /**
     * DOCX_SIZE_LIMIT
     */
    const DOCX_SIZE_LIMIT = 'docx_size_limit';
    /**
     * UNSUPPORTED_STYLE
     */
    const UNSUPPORTED_STYLE = 'unsupported_style';
    /**
     * NEED_MORE_WORDS
     */
    const NEED_MORE_WORDS = 'need_more_words';
    /**
     * EMPTY_FILE
     */
    const EMPTY_FILE = 'empty_file';
    /**
     * FORMAT_ERROR
     */
    const FORMAT_ERROR = 'format_error';
    /**
     * CONVERSION_ERROR
     */
    const CONVERSION_ERROR = 'conversion_error';

    /**
     * Shows, whether upload error should be considered failed due to file issues, not system issues.
     *
     * @param string $error
     *
     * @return bool
     */
    public static function is_consider_file_issue($error) {
        return in_array($error, [
            self::SIZE_ERROR,
            self::UNSUPPORTED_STYLE,
            self::PASSWORD_PROTECTED,
            self::DOCX_SIZE_LIMIT,
            self::EMPTY_FILE,
            self::NEED_MORE_WORDS,
            self::CORRUPTED_FILE,
        ]);
    }
}