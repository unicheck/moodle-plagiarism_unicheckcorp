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
 * default_settings.php - Displays default values to use inside assignments for plugin
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unicheck\classes\entities\providers\config_provider;
use plagiarism_unicheck\classes\forms\module_form;
use plagiarism_unicheck\classes\unicheck_notification;
use plagiarism_unicheck\classes\unicheck_settings;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

global $CFG, $DB, $OUTPUT;

require_once($CFG->libdir . '/adminlib.php');

require_login();
admin_externalpage_setup('plagiarismunicheck');

$context = context_system::instance();

$mform = new module_form(null);

$defaultconfigs = config_provider::get_configs(0);
$formdata = [];
if (!empty($defaultconfigs)) {
    $formdata = [];
    foreach ($defaultconfigs as $configname => $defaultconfig) {
        $formdata[$configname] = s($defaultconfig['value']);
    }

    $mform->set_data($formdata);
}

echo $OUTPUT->header();
$currenttab = 'unicheckdefaults';
require_once(__DIR__ . '/views/view_tabs.php');

if (($data = $mform->get_data()) && confirm_sesskey()) {
    $plagiarismelements = plagiarism_plugin_unicheck::config_options();
    $updates = [];
    $inserts = [];
    foreach ($plagiarismelements as $element) {
        if (!isset($data->{$element})) {
            continue;
        }

        $defaultconfigvalue = isset($defaultconfigs[$element]) ? $defaultconfigs[$element]['value'] : null;
        $newconfigvalue = unicheck_settings::get_sanitized_value($element, $data->{$element}, $defaultconfigvalue);

        $configrow = new Stdclass();
        $configrow->cm = 0;
        $configrow->name = $element;
        $configrow->value = $newconfigvalue;

        if (isset($defaultconfigs[$element])) {
            $configrow->id = $defaultconfigs[$element]['id'];
            $updates[] = $configrow;
        } else {
            $inserts[] = $configrow;
        }
    }

    if (!empty($updates)) {
        config_provider::update_configs($updates);
    }

    if (!empty($inserts)) {
        config_provider::insert_configs($inserts);
    }

    unicheck_notification::success('defaultupdated', true);
}

echo $OUTPUT->box(plagiarism_unicheck::trans('defaultsdesc'));

$mform->display();
echo $OUTPUT->footer();