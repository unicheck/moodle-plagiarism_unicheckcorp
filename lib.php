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
 * lib.php - Contains Plagiarism plugin specific functions called by Modules.
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\forms\module_form;
use plagiarism_unicheck\classes\helpers\unicheck_linkarray;
use plagiarism_unicheck\classes\permissions\capability;
use plagiarism_unicheck\classes\services\storage\filesize_checker;
use plagiarism_unicheck\classes\task\unicheck_bulk_check_assign_files;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Get global class.
global $CFG;

require_once($CFG->dirroot . '/plagiarism/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/accesslib.php');
require_once(dirname(__FILE__) . '/autoloader.php');
require_once(dirname(__FILE__) . '/locallib.php');

// There is a new Unicheck API - The Integration Service - we only currently use this to verify the receiver address.
// If we convert the existing calls to send file/get score we should move this to a config setting.

/**
 * Class plagiarism_plugin_unicheck
 *
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plagiarism_plugin_unicheck extends plagiarism_plugin {
    /**
     * Define default options
     *
     * @return string[]
     */
    public static function default_plugin_options() {
        return [
            'unicheck_use',
            'unicheck_enable_mod_assign',
            'unicheck_enable_mod_forum',
            'unicheck_enable_mod_workshop',
        ];
    }

    /**
     * Hook to allow plagiarism specific information to be displayed beside a submission.
     *
     * @param array $linkarray all relevant information for the plugin to generate a link.
     *
     * @return string
     */
    public function get_links($linkarray) {
        global $USER;
        if (!plagiarism_unicheck::is_plugin_enabled() || !unicheck_settings::get_activity_settings(
                $linkarray['cmid'], unicheck_settings::ENABLE_UNICHECK
            )
        ) {
            // Not allowed access to this content.
            return null;
        }

        $cm = get_coursemodule_from_id('', $linkarray['cmid'], 0, false, MUST_EXIST);

        $output = '';
        if (self::is_enabled_module('mod_' . $cm->modname)) {
            // Not allowed to view similarity check result.
            if (!capability::can_view_similarity_check_result($linkarray['cmid'], $USER->id)) {
                return null;
            }

            $file = unicheck_linkarray::get_file_from_linkarray($cm, $linkarray);
            if ($file && plagiarism_unicheck::is_support_filearea($file->get_filearea())) {
                $ucore = new unicheck_core($linkarray['cmid'], $file->get_userid(), $cm->modname);

                if ($cm->modname == UNICHECK_MODNAME_ASSIGN && (bool) unicheck_assign::get($cm->instance)->teamsubmission) {
                    $ucore->enable_teamsubmission();
                }

                $fileobj = $ucore->get_plagiarism_entity($file)->get_internal_file();
                if (!empty($fileobj) && is_object($fileobj)) {
                    $output = unicheck_linkarray::get_output_for_linkarray($fileobj, $cm, $linkarray);
                }
            } else {
                if (isset($linkarray['content']) && filesize_checker::is_valid_content($linkarray['content'])) {
                    $output = require(dirname(__FILE__) . '/views/' . 'view_tmpl_can_check.php');
                }
            }
        }

        return $output;
    }

    /**
     *  Hook to save plagiarism specific settings on a module settings page
     *
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB;

        if (!plagiarism_unicheck::is_support_mod($data->modulename) || !isset($data->use_unicheck)) {
            return;
        }

        if (isset($data->submissiondrafts) && !$data->submissiondrafts) {
            $data->use_unicheck = 0;
        }

        // First get existing values.
        $existingelements = $DB->get_records_menu(UNICHECK_CONFIG_TABLE, ['cm' => $data->coursemodule], '', 'name, id');
        // Array of possible plagiarism config options.
        foreach (self::config_options() as $element) {
            if ($element == unicheck_settings::SENSITIVITY_SETTING_NAME
                && (!is_numeric($data->$element)
                    || $data->$element < 0
                    || $data->$element > 100)
            ) {
                if (isset($existingelements[$element])) {
                    continue;
                }

                $data->$element = 0;
            }

            if ($element == unicheck_settings::MAX_SUPPORTED_ARCHIVE_FILES_COUNT
                && (!is_numeric($data->$element) || $data->$element < 0 || $data->$element > 100)
            ) {
                if (isset($existingelements[$element])) {
                    continue;
                }

                $data->$element = unicheck_archive::DEFAULT_SUPPORTED_FILES_COUNT;
            }

            $newelement = new stdClass();
            $newelement->cm = $data->coursemodule;
            $newelement->name = $element;
            $newelement->value = (isset($data->$element) ? $data->$element : 0);

            if (isset($existingelements[$element])) {
                $newelement->id = $existingelements[$element];
                $DB->update_record(UNICHECK_CONFIG_TABLE, $newelement);
            } else {
                $DB->insert_record(UNICHECK_CONFIG_TABLE, $newelement);
            }
        }

        // Plugin is enabled.
        if ($data->use_unicheck == 1) {
            if ($data->modulename == UNICHECK_MODNAME_ASSIGN && $data->check_all_submitted_assignments == 1) {
                $cm = get_coursemodule_from_id('', $data->coursemodule);
                unicheck_bulk_check_assign_files::add_task([
                    'contextid' => $data->gradingman->get_context()->id,
                    'cmid'      => $data->coursemodule,
                    'modname'   => $cm->modname
                ]);
            }
        }
    }

    /**
     * Function which returns an array of all the module instance settings.
     *
     * @return array
     */
    public static function config_options() {
        $constants = unicheck_settings::get_constants();

        return array_values($constants);
    }

    /**
     * Checking whether module supported
     *
     * @param string $modulename
     *
     * @return bool
     */
    public static function is_enabled_module($modulename) {
        $plagiarismsettings = unicheck_settings::get_settings();
        $modname = 'unicheck_enable_' . $modulename;

        if (!$plagiarismsettings || empty($plagiarismsettings[$modname])) {
            return false; // Return if plugin is not enabled for the module.
        }

        return true;
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     *
     * @param object  $mform   - Moodle form
     * @param context $context - current context
     * @param string  $modulename
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        if ($modulename && !self::is_enabled_module($modulename)) {
            return;
        }
        $defaultcmid = 0;
        $cmid = optional_param('update', $defaultcmid, PARAM_INT); // Get cm as $this->_cm is not available here.

        $plagiarismelements = self::config_options();
        if (has_capability(capability::ENABLE, $context)) {
            $settingsform = new module_form($mform, $modulename, $context);
            $defaultsettings = $activitysettings = unicheck_settings::get_activity_settings($defaultcmid, null, true);
            if ($defaultcmid !== $cmid) {
                $activitysettings = unicheck_settings::get_activity_settings($cmid, null, true);
                foreach ($defaultsettings as $setting => $value) {
                    if (isset($activitysettings[$setting])) {
                        continue;
                    }

                    $activitysettings[$setting] = $defaultsettings[$setting];
                }
            }

            $settingsform->set_data($activitysettings);
            $settingsform->definition();

            if ($mform->elementExists('submissiondrafts')) {
                // Disable all plagiarism elements if submissiondrafts eg 0.
                foreach ($plagiarismelements as $element) {
                    $mform->disabledIf($element, 'submissiondrafts', 'eq', 0);
                }
            } else {
                if ($mform->elementExists(unicheck_settings::DRAFT_SUBMIT) && $mform->elementExists('var4')) {
                    $mform->disabledIf(unicheck_settings::DRAFT_SUBMIT, 'var4', 'eq', 0);
                }
            }
            $this->disable_elements_if_not_use($plagiarismelements, $mform);
        } else { // Add plagiarism settings as hidden vars.
            $this->add_plagiarism_hidden_vars($plagiarismelements, $mform);
        }

        return;
    }

    /**
     * Disable elements if not use
     *
     * @param array  $plagiarismelements
     * @param object $mform - Moodle form
     */
    private function disable_elements_if_not_use($plagiarismelements, $mform) {
        // Disable all plagiarism elements if use_plagiarism eg 0.
        foreach ($plagiarismelements as $element) {
            if ($element <> unicheck_settings::ENABLE_UNICHECK) { // Ignore this var.
                $mform->disabledIf($element, unicheck_settings::ENABLE_UNICHECK, 'eq', 0);
            }
        }
    }

    /**
     * Add plagiarism hidden vars
     *
     * @param array                  $plagiarismelements
     * @param MoodleQuickForm|object $mform - Moodle form
     */
    private function add_plagiarism_hidden_vars($plagiarismelements, $mform) {
        foreach ($plagiarismelements as $element) {
            $mform->addElement('hidden', $element);
            $mform->setType($element, unicheck_settings::get_setting_type($element));
        }
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission.
     *
     * @param int $cmid - course module id
     *
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT;

        $outputhtml = '';

        $useplugin = unicheck_settings::get_activity_settings($cmid, unicheck_settings::ENABLE_UNICHECK);
        $disclosure = unicheck_settings::get_settings('student_disclosure');

        if (!empty($disclosure) && $useplugin) {
            $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $outputhtml .= format_text($disclosure, FORMAT_MOODLE, $formatoptions);
            $outputhtml .= $OUTPUT->box_end();
        }

        return $outputhtml;
    }

    /**
     * Workaround MDL-52702 before version 3.1.
     */
    public function cron() {
        // Do nothing.
        // Workaround MDL-52702 before version 3.1.
        // Affected branches moodle 2.7 - 3.0.
    }
}