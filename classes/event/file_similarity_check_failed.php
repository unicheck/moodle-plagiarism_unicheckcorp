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
 * similarity_check_failed.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   2018 UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\event;

use core\event\base;
use plagiarism_unicheck;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once(dirname(__FILE__) . '/../../locallib.php');

/**
 * Class similarity_check_failed
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 *
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class file_similarity_check_failed extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = UNICHECK_FILES_TABLE;
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public static function get_name() {
        return plagiarism_unicheck::trans('event:file_similarity_check_failed');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "File {$this->objectid} similarity check failed. Reason {$this->other['errormessage']}: ";
    }

    /**
     * Create from plagiarism file
     *
     * @param object $plagiarismfile
     * @param string $errormessage
     * @return base
     */
    public static function create_from_plagiarismfile($plagiarismfile, $errormessage) {
        $data = [
            'objectid' => $plagiarismfile->id,
            'context'  => \context_module::instance($plagiarismfile->cm),
            'other'    =>
                [
                    'fileid'       => $plagiarismfile->identifier,
                    'errormessage' => $errormessage,
                ]
        ];

        return self::create($data);
    }
}