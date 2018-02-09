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
 * comment.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\entities;

use plagiarism_unicheck\classes\services\comments\commentable_interface;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class file_comment
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment {

    /**
     * @var int Identifier
     */
    private $id;

    /**
     * @var string
     */
    private $body;

    /**
     * @var int
     */
    private $commentableid;

    /**
     * @var string
     */
    private $commentabletype;

    /**
     * @var int
     */
    private $timecreated;

    /**
     * comment constructor.
     *
     * @param commentable_interface $commentable
     * @param  string               $body
     */
    public function __construct(commentable_interface $commentable, $body) {
        $this->set_commentable($commentable);
        $this->set_body($body);
        $this->timecreated = time();
    }

    /**
     * Get comment id
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get comment body
     *
     * @return string
     */
    public function get_body() {
        return $this->body;
    }

    /**
     * Set commentable object
     *
     * @param commentable_interface $commentable
     * @return $this
     */
    public function set_commentable(commentable_interface $commentable) {
        $this->commentableid = $commentable->get_commentable_id();
        $this->commentabletype = $commentable->get_commentable_type();

        return $this;
    }

    /**
     * Set comment body
     *
     * @param string $body
     * @return $this
     */
    public function set_body($body) {
        $this->body = $body;

        return $this;
    }

    /**
     * Get comment time created
     *
     * @return int
     */
    public function get_timecreated() {
        return $this->timecreated;
    }

    /**
     * Convert entity to data array
     *
     * @return array
     */
    public function to_array() {
        return get_object_vars($this);
    }
}