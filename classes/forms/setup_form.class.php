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
 * setup_form.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\forms;

use coding_exception;
use core_component;
use moodleform;
use plagiarism_unicheck;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;

require_once($CFG->libdir . '/formslib.php');

/**
 * Class setup_form
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class setup_form extends moodleform {
    /**
     * Define the form
     *
     * @throws coding_exception
     */
    public function definition() {
        $mform = &$this->_form;

        $addyesnoelem = function($setting, $transkey, $defaultvalue = null) use (&$mform) {
            $ynoptions = [get_string('no'), get_string('yes')];
            $mform->addElement('select', $setting, plagiarism_unicheck::trans($transkey), $ynoptions);
            $mform->addHelpButton($setting, $transkey, UNICHECK_PLAGIN_NAME);

            if ($defaultvalue !== null) {
                if (!isset($mform->exportValues()[$setting]) || is_null($mform->exportValues()[$setting])) {
                    $mform->setDefault($setting, $defaultvalue);
                }
            }
        };

        $addyesnoelem('unicheck_use', 'enable_plugin', false);

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
                $addyesnoelem('unicheck_enable_mod_' . $mod, 'enable_mod_' . $mod);
            }
        }

        $addyesnoelem('unicheck_enable_api_logging', 'enable_api_logging', false);
        $addyesnoelem('unicheck_exclude_self_plagiarism', 'exclude_self_plagiarism', false);

        $this->add_action_buttons(true);
    }
}