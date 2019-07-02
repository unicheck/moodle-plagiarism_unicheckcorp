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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class debugging_table_batch_operations_form.class
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class batch_operations_form extends moodleform {
    /**
     * Define this form - called by the parent constructor.
     */
    public function definition() {
        $mform = $this->_form;

        // Visible elements.
        $options = [];
        $options['resubmit'] = plagiarism_unicheck::trans('debugging:batchoperations:resubmit');
        $options['delete'] = plagiarism_unicheck::trans('debugging:batchoperations:delete');

        $mform->addElement('hidden', 'action', 'debuggingbatchoperation');
        $mform->setType('action', PARAM_ALPHA);
        $mform->addElement('hidden', 'selectedfiles', '', ['class' => 'selectedfiles']);
        $mform->setType('selectedfiles', PARAM_SEQUENCE);
        $mform->addElement('hidden', 'returnaction', 'view');
        $mform->setType('returnaction', PARAM_ALPHA);

        $objs = [];
        $objs[] =& $mform->createElement(
            'select',
            'operation',
            plagiarism_unicheck::trans('debugging:batchoperations:chooseoperation'),
            $options
        );
        $objs[] =& $mform->createElement('submit', 'submit', get_string('go'));

        $batchdescription = plagiarism_unicheck::trans('debugging:batchoperations:batchoperationsdescription');
        $mform->addElement('group', 'actionsgrp', $batchdescription, $objs, ' ', false);
    }

    /**
     * apply_operation
     *
     * @param \moodle_url $redirect
     */
    public function apply_operation(\moodle_url $redirect = null) {
        if (!$this->is_submitted()) {
            return;
        }

        confirm_sesskey();

        $operation = $this->get_data()->operation;
        $selectedfiles = explode(',', $this->get_data()->selectedfiles);

        plagiarism_unicheck\classes\entities\providers\unicheck_file_provider::find_by_ids($selectedfiles);

        switch ($operation) {
            case 'resubmit':
                if (unicheck_file_provider::resubmit_by_ids($selectedfiles)) {
                    unicheck_notification::success('fileresubmitted', true);
                } else {
                    unicheck_notification::message('debugging:batchoperations:filesnotresubmitted', true);
                }

                break;
            case 'delete':
                unicheck_file_provider::delete_by_ids($selectedfiles);
                unicheck_notification::success('filedeleted', true);

                break;
        }

        if ($redirect) {
            redirect($redirect);
        }
    }
}