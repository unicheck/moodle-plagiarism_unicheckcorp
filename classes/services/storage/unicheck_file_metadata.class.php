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
 * unicheck_file_state.class.php
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
 * Class unicheck_file_metadata
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_file_metadata {
    /**
     * ARCHIVE_SUPPORTED_FILES_COUNT
     */
    const ARCHIVE_SUPPORTED_FILES_COUNT = 'archive_supported_files_count';
    /**
     * EXTRACTED_SUPPORTED_FILES_FROM_ARCHIVE_COUNT
     */
    const EXTRACTED_SUPPORTED_FILES_FROM_ARCHIVE_COUNT = 'extracted_supported_files_from_archive_count';
    /**
     * CHAR_COUNT
     */
    const CHAR_COUNT = 'char_count';
    /**
     * CHEATING_EXIST
     */
    const CHEATING_EXIST = 'cheating_exist';
    /**
     * CHEATING_CHAR_REPLACEMENTS_COUNT
     */
    const CHEATING_CHAR_REPLACEMENTS_COUNT = 'cheating_char_replacements_count';
    /**
     * CHEATING_CHAR_REPLACEMENTS_WORDS_COUNT
     */
    const CHEATING_CHAR_REPLACEMENTS_WORDS_COUNT = 'cheating_char_replacements_words_count';
    /**
     * CHEATING_IS_SIMILARITY_AFFECTED
     */
    const CHEATING_IS_SIMILARITY_AFFECTED = 'cheating_is_similarity_affected';
    /**
     * CHEATING_SUSPICIOUS_PAGES_COUNT
     */
    const CHEATING_SUSPICIOUS_PAGES_COUNT = 'cheating_suspicious_pages_count';
    /**
     * CHEATING_TOTAL_PAGES_COUNT
     */
    const CHEATING_TOTAL_PAGES_COUNT = 'cheating_total_pages_count';
}