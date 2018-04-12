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
 * uform.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\unicheck_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class unicheck_setup_form
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_setup_form extends moodleform {
    /**
     * Define the form
     *
     * @throws coding_exception
     */
    public function definition() {
        $mform = &$this->_form;
        $mform->addElement('checkbox', 'unicheck_use', plagiarism_unicheck::trans(unicheck_settings::USE_UNICHECK));

        $settingstext = '<div id="fitem_id_settings_link" class="fitem fitem_ftext ">
                            <div class="felement ftext">
                                <a href="' . UNICHECK_CORP_DOMAIN . 'profile/apisettings" target="_blank"> '
            . plagiarism_unicheck::trans('unicheck_settings_url_text') . '
                                </a>
                            </div>
                        </div>';
        $mform->addElement('html', $settingstext);

        $mform->addElement('text', 'unicheck_client_id', plagiarism_unicheck::trans('client_id'));
        $mform->addHelpButton('unicheck_client_id', 'client_id', UNICHECK_PLAGIN_NAME);
        $mform->addRule('unicheck_client_id', null, 'required', null, 'client');
        $mform->setType('unicheck_client_id', PARAM_TEXT);

        $mform->addElement('text', 'unicheck_api_secret', plagiarism_unicheck::trans('api_secret'));
        $mform->addHelpButton('unicheck_api_secret', 'api_secret', UNICHECK_PLAGIN_NAME);
        $mform->addRule('unicheck_api_secret', null, 'required', null, 'client');
        $mform->setType('unicheck_api_secret', PARAM_TEXT);

        $mform->addElement('textarea', 'unicheck_student_disclosure', plagiarism_unicheck::trans('studentdisclosure'),
            'wrap="virtual" rows="6" cols="100"');
        $mform->addHelpButton('unicheck_student_disclosure', 'studentdisclosure', UNICHECK_PLAGIN_NAME);
        $mform->setDefault('unicheck_student_disclosure', plagiarism_unicheck::trans('studentdisclosuredefault'));
        $mform->setType('unicheck_student_disclosure', PARAM_TEXT);

        $mods = core_component::get_plugin_list('mod');
        foreach (array_keys($mods) as $mod) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM) && plagiarism_unicheck::is_support_mod($mod)) {
                $modstring = 'unicheck_enable_mod_' . $mod;
                $mform->addElement('checkbox', $modstring, plagiarism_unicheck::trans('unicheck_enableplugin', ucfirst($mod)));
            }
        }

        $this->add_action_buttons(true);
    }
}

/**
 * Class unicheck_defaults_form
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_defaults_form extends moodleform {
    /** @var bool */
    private $internalusage = false;
    /** @var string */
    private $modname = '';

    /**
     * unicheck_defaults_form constructor.
     *
     * @param object|null $mform - Moodle form
     * @param string|null $modname
     */
    public function __construct($mform = null, $modname = null) {
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

        $addyesnoelem = function($setting, $showhelpballoon = false, $defaultvalue = null) use (&$mform, $defaultsforfield) {
            $ynoptions = [get_string('no'), get_string('yes')];
            $mform->addElement('select', $setting, plagiarism_unicheck::trans($setting), $ynoptions);
            if ($showhelpballoon) {
                $mform->addHelpButton($setting, $setting, UNICHECK_PLAGIN_NAME);
            }

            if ($defaultvalue !== null) {
                $defaultsforfield($mform, $setting, $defaultvalue);
            }
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

        $addyesnoelem(unicheck_settings::USE_UNICHECK, true);

        if (!in_array($this->modname, [UNICHECK_MODNAME_FORUM, UNICHECK_MODNAME_WORKSHOP])) {
            $addyesnoelem(unicheck_settings::CHECK_ALL_SUBMITTED_ASSIGNMENTS, true);
            $addyesnoelem(unicheck_settings::NO_INDEX_FILES, true);
        }

        $checktypedata = [];
        foreach (unicheck_settings::$supportedchecktypes as $checktype) {
            $checktypedata[$checktype] = plagiarism_unicheck::trans($checktype);
        }

        $setting = unicheck_settings::CHECK_TYPE;
        $mform->addElement('select', $setting, plagiarism_unicheck::trans($setting), $checktypedata);
        $mform->addHelpButton($setting, $setting, UNICHECK_PLAGIN_NAME);

        $addtextelem(unicheck_settings::SENSITIVITY_SETTING_NAME, 0);
        $addtextelem(unicheck_settings::WORDS_SENSITIVITY, 8);
        $addyesnoelem(unicheck_settings::EXCLUDE_CITATIONS, true, 1);
        $addyesnoelem(unicheck_settings::SHOW_STUDENT_SCORE, true);
        $addyesnoelem(unicheck_settings::SHOW_STUDENT_REPORT, true);
        $addtextelem(unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT, 10);

        $mform::registerRule('range', null, new unicheck_form_rule_range);

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
}

/**
 * Class unicheck_form_rule_range
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_form_rule_range extends HTML_QuickForm_Rule {
    /**
     * validate
     *
     * @param int        $value Value to check
     * @param array|null $options
     *
     * @return bool true if value in valid range
     */
    public function validate($value, $options = null) {
        if ($value < $options['min'] || $value > $options['max']) {
            return false;
        }

        return true;
    }
}