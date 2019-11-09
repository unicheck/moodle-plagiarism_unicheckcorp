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
 * abstract_file_event.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   2018 UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\event;

use core\event\base;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/plagiarism/unicheck/lib.php');

/**
 * Class abstact_file_event
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 *
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.3
 */
abstract class abstract_file_event extends base {
    /**
     * Create event log from plagiarism file.
     *
     * @param object $plagiarismfile
     *
     * @return base
     * @throws \coding_exception
     */
    public static function create_from_plagiarismfile($plagiarismfile) {
        $data = [
            'userid'   => $plagiarismfile->userid,
            'objectid' => $plagiarismfile->id,
            'context'  => \context_module::instance($plagiarismfile->cm),
            'other'    => [
                'fileid' => $plagiarismfile->identifier
            ]
        ];

        return self::create($data);
    }

    /**
     * Create from plagiarism file and failed result message
     *
     * @param object $plagiarismfile
     * @param string $errormessage
     *
     * @return base
     * @throws \coding_exception
     */
    public static function create_from_failed_plagiarismfile($plagiarismfile, $errormessage) {
        $data = [
            'userid'   => $plagiarismfile->userid,
            'objectid' => $plagiarismfile->id,
            'context'  => \context_module::instance($plagiarismfile->cm),
            'other'    => [
                'fileid'       => $plagiarismfile->identifier,
                'errormessage' => $errormessage,
            ]
        ];

        return self::create($data);
    }

    /**
     * Validate plagiarismfile data
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->objectid)) {
            throw new \coding_exception("The 'objectid' must be set.");
        }

        if (!isset($this->other['fileid'])) {
            throw new \coding_exception("The 'fileid' value must be set in other.");
        }
    }
}