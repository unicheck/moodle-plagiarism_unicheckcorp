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
 * unicheck_comment_provider.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\entities\providers;

use plagiarism_unicheck\classes\entities\comment;
use plagiarism_unicheck\classes\services\comments\commentable_interface;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_comment_provider
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_comment_provider {

    /**
     * Update plagiarism comment
     *
     * @param comment $filecomment
     *
     * @return bool
     */
    public static function save(comment $filecomment) {
        global $DB;

        $row = (object) $filecomment->to_array();
        if ($filecomment->get_id()) {
            return $DB->update_record(UNICHECK_COMMENTS_TABLE, $row);
        }

        return $DB->insert_record(UNICHECK_COMMENTS_TABLE, $row);
    }

    /**
     * Get plagiarism file comment by id
     *
     * @param int $id
     *
     * @return comment
     */
    public static function get_by_id($id) {
        global $DB;

        return $DB->get_record(UNICHECK_COMMENTS_TABLE, ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Find plagiarism file comment by id
     *
     * @param int $id
     *
     * @return comment|null
     */
    public static function find_by_id($id) {
        global $DB;

        return $DB->get_record(UNICHECK_COMMENTS_TABLE, ['id' => $id]);
    }

    /**
     * Find plagiarism comments by commentable object
     *
     * @param commentable_interface $commentable
     *
     * @return comment[]
     */
    public static function find_by_commentable(commentable_interface $commentable) {
        global $DB;

        return $DB->get_records(
            UNICHECK_COMMENTS_TABLE,
            ['commentable_id' => $commentable->get_commentable_id(), 'commentable_type' => $commentable->get_commentable_type()]
        );
    }
}