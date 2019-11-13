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
 * activity_form.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\table\debugging;

use moodleform;
use plagiarism_unicheck;
use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\unicheck_notification;
use plagiarism_unicheck\classes\user\preferences;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/assign/mod_form.php');

/**
 * Class filter_options_form.class
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_options_form extends moodleform {

    /** @var string|null */
    protected $mintimesubmitted;

    /**
     * Define this form - called from the parent constructor.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'debugging_filter', plagiarism_unicheck::trans('debugging:filter:title'));

        $options = [
            10  => '10',
            20  => '20',
            50  => '50',
            100 => '100'
        ];
        $mform->addElement('select', 'perpage', plagiarism_unicheck::trans('debugging:filter:perpage'), $options);
        $mform->addElement('text', 'errormessage', plagiarism_unicheck::trans('debugging:filter:errormessage'));
        $mform->setType('errormessage', PARAM_TEXT);

        $dateoptions = [
            'startyear' => date('Y', $this->get_min_timesubmitted()),
            'stopyear'  => date('Y'),
            'optional'  => false
        ];

        $mform->addElement(
            'date_time_selector',
            'timesubmittedfrom',
            plagiarism_unicheck::trans('debugging:filter:timesubmittedfrom'),
            $dateoptions
        );

        $mform->addElement(
            'date_time_selector',
            'timesubmittedto',
            plagiarism_unicheck::trans('debugging:filter:timesubmittedto'),
            $dateoptions
        );

        // When two elements we need a group.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement(
            'submit',
            'submitbutton',
            plagiarism_unicheck::trans('debugging:filter:button:submit')
        );
        $buttonarray[] = $mform->createElement(
            'cancel',
            'cancel',
            plagiarism_unicheck::trans('debugging:filter:button:reset')
        );
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);

        $mform->setExpanded('debugging_filter', false);
    }

    /**
     * Apply filters
     *
     * @param \moodle_url|null $redirecturl
     */
    public function apply_filters(\moodle_url $redirecturl = null) {
        $perpage = 20;

        $minsubmitteddate = $this->get_min_timesubmitted();
        $maxsubmitteddate = strtotime("tomorrow 00:00:00");
        $currentfilters = [
            'perpage'           => get_user_preferences(preferences::DEBUGGING_PER_PAGE, $perpage),
            'errormessage'      => get_user_preferences(preferences::DEBUGGING_ERROR_MESSAGE, null),
            'timesubmittedfrom' => get_user_preferences(preferences::DEBUGGING_TIME_SUBMITTED_FROM, $minsubmitteddate),
            'timesubmittedto'   => get_user_preferences(preferences::DEBUGGING_TIME_SUBMITTED_TO, $maxsubmitteddate)
        ];

        $this->set_data($currentfilters);

        if (!$this->is_submitted()) {
            return;
        }

        if (!confirm_sesskey()) {
            return;
        }

        $data = $this->is_cancelled() ? [
            'perpage'           => (int) $perpage,
            'errormessage'      => null,
            'timesubmittedfrom' => (int) $minsubmitteddate,
            'timesubmittedto'   => (int) $maxsubmitteddate
        ] : $this->get_data();

        foreach ($data as $filter => $value) {
            set_user_preference("plagiarism/unicheck:debugging{$filter}", $value);
        }

        unicheck_notification::success('defaultupdated', true);

        if ($redirecturl) {
            redirect($redirecturl);
        }
    }

    /**
     * get_min_timesubmitted
     *
     * @return string|null
     */
    protected function get_min_timesubmitted() {

        if (!$this->mintimesubmitted) {
            $this->mintimesubmitted = unicheck_file_provider::get_min_timesubmitted();
        }

        return $this->mintimesubmitted;
    }
}