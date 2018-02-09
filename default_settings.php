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

use plagiarism_unicheck\classes\forms\module_form;
use plagiarism_unicheck\classes\unicheck_notification;
use plagiarism_unicheck\classes\unicheck_settings;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $CFG, $DB, $OUTPUT;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');

require_login();
admin_externalpage_setup('plagiarismunicheck');

$context = context_system::instance();

$mform = new module_form(null);
// The cmid(0) is the default list.
$defaults = $DB->get_records_menu(UNICHECK_CONFIG_TABLE, ['cm' => 0], '', 'name, value');
if (!empty($defaults)) {
    $mform->set_data($defaults);
}
echo $OUTPUT->header();
$currenttab = 'unicheckdefaults';
require_once(dirname(__FILE__) . '/views/view_tabs.php');

if (($data = $mform->get_data()) && confirm_sesskey()) {
    $plagiarismelements = plagiarism_plugin_unicheck::config_options();
    foreach ($plagiarismelements as $element) {
        if (isset($data->{$element})) {
            if ($element == unicheck_settings::SENSITIVITY_SETTING_NAME
                && (!is_numeric($data->{$element})
                    || $data->{$element} < 0
                    || $data->{$element} > 100)) {
                if (isset($defaults[$element])) {
                    continue;
                }

                $data->{$element} = 0;
            }

            $newelement = new Stdclass();
            $newelement->cm = 0;
            $newelement->name = $element;
            $newelement->value = $data->{$element};

            if (isset($defaults[$element])) {
                $newelement->id = $DB->get_field(UNICHECK_CONFIG_TABLE, 'id', (['cm' => 0, 'name' => $element]));
                $DB->update_record(UNICHECK_CONFIG_TABLE, $newelement);
            } else {
                $DB->insert_record(UNICHECK_CONFIG_TABLE, $newelement);
            }
        }
    }

    unicheck_notification::success('defaultupdated', true);
}
echo $OUTPUT->box(plagiarism_unicheck::trans('defaultsdesc'));

$mform->display();
echo $OUTPUT->footer();