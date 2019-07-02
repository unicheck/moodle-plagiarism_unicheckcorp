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
 * unicheck_status_recheck_form.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\table\debugging;

use moodleform;
use plagiarism_unicheck;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/assign/mod_form.php');

/**
 * Class unicheck_status_recheck_form.class
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_status_recheck_form extends moodleform {

    /**
     * Define this form - called by the parent constructor.
     */
    public function definition() {
        $mform = $this->_form;

        $objs = [];
        $objs[] =& $mform->createElement(
            'submit',
            'submit',
            plagiarism_unicheck::trans('debugging:statustable:recheck')
        );

        $batchdescription = plagiarism_unicheck::trans('debugging:statustable:recheckdescription');
        $mform->addElement('group', 'actionsgrp', $batchdescription, $objs, ' ', false);
    }
}