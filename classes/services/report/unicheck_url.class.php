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
 * unicheck_url.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\services\report;

use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_language;
use plagiarism_unicheck\classes\unicheck_plagiarism_entity;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_url
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_url {

    /** @var  \stdClass */
    private $file;

    /**
     * unicheck_url constructor.
     *
     * @param \stdClass $file
     */
    public function __construct(\stdClass $file) {
        $this->file = $file;
    }

    /**
     * Get report non-edit URL
     *
     * @param int $cid
     *
     * @return \moodle_url
     */
    public function get_view_url($cid) {
        return $this->get_moodle_url($this->file->reporturl, $cid);
    }

    /**
     * Get report edit URL
     *
     * @param int $cid
     *
     * @return \moodle_url
     */
    public function get_edit_url($cid) {
        return $this->get_moodle_url($this->file->reportediturl, $cid);
    }

    /**
     * Create moodle URL based on string URL
     *
     * @param string $url
     * @param int    $cid
     * @return \moodle_url
     */
    private function get_moodle_url($url, $cid) {
        if ($this->file->type == unicheck_plagiarism_entity::TYPE_ARCHIVE) {
            $url = str_replace('unplag', 'unicheck', $url);
        } else {
            unicheck_language::inject_language_to_url($url);
            unicheck_core::inject_comment_token($url, $cid);
        }

        return new \moodle_url($url);
    }
}