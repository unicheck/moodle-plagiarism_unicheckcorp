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
 * settings.php - allows the admin to configure plagiarism stuff
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unicheck\classes\unicheck_notification;
use plagiarism_unicheck\classes\forms\setup_form;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $CFG, $OUTPUT, $USER;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');

require_login();
admin_externalpage_setup('plagiarismunicheck');

$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

$mform = new setup_form();

if ($mform->is_cancelled()) {
    redirect('');
}

echo $OUTPUT->header();
$currenttab = 'unichecksettings';
require_once(dirname(__FILE__) . '/views/view_tabs.php');

if (($data = $mform->get_data()) && confirm_sesskey()) {
    foreach (plagiarism_plugin_unicheck::default_plugin_options() as $option) {
        if (!isset($data->{$option})) {
            $data->{$option} = 0;
        }
    }

    foreach ($data as $field => $value) {
        if (strpos($field, 'unicheck') === 0) {
            set_config($field, $value, 'plagiarism');
        }
    }

    cache_helper::purge_by_definition('plagiarism_unicheck', 'debugging');
    unicheck_notification::success('savedconfigsuccess', true);
}

$mform->set_data(get_config('plagiarism'));

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
