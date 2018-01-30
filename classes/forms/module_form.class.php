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

namespace plagiarism_unicheck\classes\forms;

use coding_exception;
use context;
use HTML_QuickForm_element;
use moodleform;
use MoodleQuickForm;
use plagiarism_plugin_unicheck;
use plagiarism_unicheck;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\forms\rules\range_rule;
use plagiarism_unicheck\classes\permissions\capability;
use plagiarism_unicheck\classes\unicheck_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class activity_form
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_form extends moodleform {
    /** @var bool */
    private $internalusage = false;
    /** @var string */
    private $modname = '';

    /** @var context */
    private $context;

    /**
     * unicheck_defaults_form constructor.
     *
     * @param object|null  $mform - Moodle form
     * @param string|null  $modname
     * @param context|null $context
     */
    public function __construct($mform = null, $modname = null, context $context = null) {
        $this->context = $context;

        parent::__construct();

        if (!is_null($mform)) {
            $this->_form = $mform;
            $this->internalusage = true;
        }

        if (!is_null($modname) && is_string($modname) && plagiarism_plugin_unicheck::is_enabled_module($modname)) {
            $modname = str_replace('mod_', '', $modname);
            if (plagiarism_unicheck::is_support_mod($modname)) {
                $this->modname = $modname;
            }
        }

    }

    /**
     * Define the form
     *
     * @throws coding_exception
     */
    public function definition() {
        /** @var MoodleQuickForm $mform */
        $mform = &$this->_form;

        $defaultsforfield = function(MoodleQuickForm &$mform, $setting, $defaultvalue) {
            if (!isset($mform->exportValues()[$setting]) || is_null($mform->exportValues()[$setting])) {
                $mform->setDefault($setting, $defaultvalue);
            }
        };

        /**
         * @param      $setting
         * @param bool $showhelpballoon
         * @param null $defaultvalue
         * @return HTML_QuickForm_element|object
         */
        $addyesnoelem =
            function($setting, $showhelpballoon = false, $defaultvalue = null, $capability = null) use (&$mform, $defaultsforfield
            ) {
                $ynoptions = [get_string('no'), get_string('yes')];
                $elem = $mform->addElement('select', $setting, plagiarism_unicheck::trans($setting), $ynoptions);
                if ($showhelpballoon) {
                    $mform->addHelpButton($setting, $setting, UNICHECK_PLAGIN_NAME);
                }

                if ($defaultvalue !== null) {
                    $defaultsforfield($mform, $setting, $defaultvalue);
                }

                if (null !== $capability && !$this->has_capability($capability)) {
                    $elem->freeze();
                }

                return $elem;
            };

        $addtextelem = function($setting, $defaultvalue = null) use ($defaultsforfield, &$mform) {
            $mform->addElement('text', $setting, plagiarism_unicheck::trans($setting));
            $mform->addHelpButton($setting, $setting, UNICHECK_PLAGIN_NAME);
            $mform->setType($setting, PARAM_TEXT);
            if ($defaultvalue !== null) {
                $defaultsforfield($mform, $setting, $defaultvalue);
            }
        };

        $mform->addElement('header', 'plagiarismdesc', plagiarism_unicheck::trans('unicheck'));

        if ($this->modname === UNICHECK_MODNAME_ASSIGN) {
            $mform->addElement('static', 'use_static_description', plagiarism_unicheck::trans('use_assign_desc_param'),
                plagiarism_unicheck::trans('use_assign_desc_value'));
        }

        $addyesnoelem(unicheck_settings::ENABLE_UNICHECK, true, null, capability::CHANGE_ENABLE_UNICHECK_SETTING);

        if (!in_array($this->modname, [UNICHECK_MODNAME_FORUM, UNICHECK_MODNAME_WORKSHOP])) {
            $addyesnoelem(unicheck_settings::CHECK_ALREADY_DELIVERED_ASSIGNMENT_SUBMISSIONS, true, null,
                capability::CHANGE_CHECK_ALREADY_SUBMITTED_ASSIGNMENT_SETTING);
            $addyesnoelem(unicheck_settings::NO_INDEX_FILES, true, null, capability::CHANGE_ADD_SUBMISSION_TO_LIBRARY_SETTING);
        }

        $checktypedata = [];
        foreach (unicheck_settings::$supportedchecktypes as $checktype) {
            $checktypedata[$checktype] = plagiarism_unicheck::trans($checktype);
        }

        $setting = unicheck_settings::SOURCES_FOR_COMPARISON;
        $mform->addElement('select', $setting, plagiarism_unicheck::trans($setting), $checktypedata);
        $mform->addHelpButton($setting, $setting, UNICHECK_PLAGIN_NAME);

        $availablefromgroup = [];
        $availablefromgroup[] =& $mform->createElement('date_selector', 'availablefrom', '');
        $availablefromgroup[] =& $mform->createElement('checkbox', 'availablefromenabled', '', get_string('enable'));
        $mform->addGroup($availablefromgroup, 'availablefromgroup', get_string('availablefromdate', 'data'), ' ', false);
        $mform->disabledIf('availablefromgroup', 'availablefromenabled');

        $addtextelem(unicheck_settings::SENSITIVITY_SETTING_NAME, 0);
        $addtextelem(unicheck_settings::WORDS_SENSITIVITY, 8);
        $addyesnoelem(unicheck_settings::EXCLUDE_CITATIONS, true, 1);
        $addyesnoelem(unicheck_settings::SHOW_STUDENT_SCORE, true);
        $addyesnoelem(unicheck_settings::SHOW_STUDENT_REPORT, true);
        $addtextelem(unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT, 10);

        $mform::registerRule('range', null, new range_rule());

        $mform->addRule(unicheck_settings::WORDS_SENSITIVITY, 'Invalid value range. Allowed 8-999',
            'range', ['min' => 8, 'max' => 999], 'server'
        );

        $min = unicheck_archive::MIN_SUPPORTED_FILES_COUNT;
        $max = unicheck_archive::MAX_SUPPORTED_FILES_COUNT;
        $mform->addRule(
            unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT,
            "Invalid value range. Allowed $min-$max",
            'range',
            ['min' => $min, 'max' => $max],
            'server',
            true
        );

        if (!$this->internalusage) {
            $this->add_action_buttons(true);
        }
    }

    /**
     * @param $capability
     * @return bool
     */
    private function has_capability($capability) {
        if (!$this->internalusage) {
            return true;
        }

        if (!$this->context) {
            return false;
        }

        return has_capability($capability, $this->context);
    }
}