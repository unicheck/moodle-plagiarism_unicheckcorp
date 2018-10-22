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

use context;
use moodleform;
use MoodleQuickForm;
use plagiarism_plugin_unicheck;
use plagiarism_unicheck;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\forms\rules\range_rule;
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
        global $PAGE;

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

        $PAGE->requires->js_call_amd(UNICHECK_PLAGIN_NAME . '/activity_form', 'init');
    }

    /**
     * Define the form
     */
    public function definition() {
        /** @var MoodleQuickForm $mform */
        $mform = &$this->_form;

        $defaultsforfield = function(MoodleQuickForm &$mform, $setting, $defaultvalue) {
            if (!isset($mform->exportValues()[$setting]) || is_null($mform->exportValues()[$setting])) {
                $mform->setDefault($setting, $defaultvalue);
            }
        };

        $addyesnoelem = function($setting, $showhelpballoon = false, $defaultvalue = null) use (&$mform, $defaultsforfield) {
            $ynoptions = [get_string('no'), get_string('yes')];
            $elem = $mform->addElement('select', $setting, plagiarism_unicheck::trans($setting), $ynoptions);
            if ($showhelpballoon) {
                $mform->addHelpButton($setting, $setting, UNICHECK_PLAGIN_NAME);
            }

            if ($defaultvalue !== null) {
                $defaultsforfield($mform, $setting, $defaultvalue);
            }

            if (!$this->has_change_capability($setting)) {
                $elem->freeze();
            }

            return $elem;
        };

        $addtextelem = function($setting, $defaultvalue = null) use ($defaultsforfield, &$mform) {
            $elem = $mform->addElement('text', $setting, plagiarism_unicheck::trans($setting));
            $mform->addHelpButton($setting, $setting, UNICHECK_PLAGIN_NAME);
            $mform->setType($setting, unicheck_settings::get_setting_type($setting));
            if ($defaultvalue !== null) {
                $defaultsforfield($mform, $setting, $defaultvalue);
            }

            if (!$this->has_change_capability($setting)) {
                $elem->freeze();
            }
        };

        $mform->addElement('header', UNICHECK_PLAGIN_NAME, plagiarism_unicheck::trans('unicheck'));

        if ($this->modname === UNICHECK_MODNAME_ASSIGN) {
            $mform->addElement('static', 'use_static_description', plagiarism_unicheck::trans('use_assign_desc_param'),
                plagiarism_unicheck::trans('use_assign_desc_value'));
        }

        $addyesnoelem(unicheck_settings::ENABLE_UNICHECK, true, 0);

        if (!in_array($this->modname, [UNICHECK_MODNAME_FORUM, UNICHECK_MODNAME_WORKSHOP])) {
            $addyesnoelem(unicheck_settings::CHECK_ALREADY_DELIVERED_ASSIGNMENT_SUBMISSIONS, true, 0);
            $addyesnoelem(unicheck_settings::ADD_TO_INSTITUTIONAL_LIBRARY, true, 0);
        }

        $checktypedata = [];
        foreach (unicheck_settings::get_supported_check_source_types() as $checktype) {
            $checktypedata[$checktype] = plagiarism_unicheck::trans($checktype);
        }

        $setting = unicheck_settings::SOURCES_FOR_COMPARISON;
        $elem = $mform->addElement('select', $setting, plagiarism_unicheck::trans($setting), $checktypedata);
        $defaultsforfield($mform, $setting, UNICHECK_CHECK_TYPE_WEB);
        $mform->addHelpButton($setting, $setting, UNICHECK_PLAGIN_NAME);
        if (!$this->has_change_capability($setting)) {
            $elem->freeze();
        }

        $addtextelem(unicheck_settings::SENSITIVITY_SETTING_NAME, unicheck_settings::$defaultsensitivity);
        $addtextelem(unicheck_settings::WORDS_SENSITIVITY, unicheck_settings::$defaultwordssensitivity);
        $addyesnoelem(unicheck_settings::EXCLUDE_CITATIONS, true, 1);
        $addyesnoelem(unicheck_settings::SHOW_STUDENT_SCORE, true, 0);
        $addyesnoelem(unicheck_settings::SHOW_STUDENT_REPORT, true, 0);
        $addyesnoelem(unicheck_settings::SENT_STUDENT_REPORT, true, 0);

        $addtextelem(
            unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT,
            unicheck_archive::DEFAULT_SUPPORTED_FILES_COUNT
        );

        $mform::registerRule('range', null, new range_rule());

        $mform->addRule(
            unicheck_settings::WORDS_SENSITIVITY,
            "Invalid value range. Allowed " . unicheck_settings::$defaultwordssensitivity . "-999",
            'range',
            ['min' => unicheck_settings::$defaultwordssensitivity, 'max' => 999],
            'server'
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

        $mform->addFormRule(function($values) {
            // Number could not be less than 0.
            $errors = [];
            if (isset($values[unicheck_settings::SENSITIVITY_SETTING_NAME]) &&
                $values[unicheck_settings::SENSITIVITY_SETTING_NAME] < 0
            ) {
                $errors[unicheck_settings::SENSITIVITY_SETTING_NAME] =
                    plagiarism_unicheck::trans('validation:min_numeric_value', 0);
            }

            // Number could not be less than 8.
            if (isset($values[unicheck_settings::WORDS_SENSITIVITY])
                && $values[unicheck_settings::WORDS_SENSITIVITY] < unicheck_settings::$defaultwordssensitivity
            ) {
                $errors[unicheck_settings::WORDS_SENSITIVITY] = plagiarism_unicheck::trans('validation:min_numeric_value',
                    unicheck_settings::$defaultwordssensitivity);
            }

            // Number could not be less than 1.
            if (isset($values[unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT])
                && $values[unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT] < unicheck_archive::MIN_SUPPORTED_FILES_COUNT
            ) {
                $errors[unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT] =
                    plagiarism_unicheck::trans('validation:min_numeric_value', unicheck_archive::MIN_SUPPORTED_FILES_COUNT);
            }

            return !empty($errors) ? $errors : true;
        });

        if (!$this->internalusage) {
            $this->add_action_buttons(true);
        }
    }

    /**
     * Check is current context can change setting
     *
     * @param string $setting
     * @return bool
     */
    private function has_change_capability($setting) {
        if (!$this->internalusage) {
            return true;
        }

        if (!$this->context) {
            return false;
        }

        $capability = unicheck_settings::get_capability($setting);
        if (null === $capability) {
            return true;
        }

        return has_capability($capability, $this->context);
    }
}