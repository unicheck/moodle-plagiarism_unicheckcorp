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
 * debugging.php - Displays default values to use inside assignments for plugin
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, 2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\table\debugging\batch_operations_form;
use plagiarism_unicheck\classes\table\debugging\debugging_table;
use plagiarism_unicheck\classes\table\debugging\filter_options_form;
use plagiarism_unicheck\classes\table\debugging\unicheck_status_recheck_form;
use plagiarism_unicheck\classes\table\debugging\unicheck_status_table;
use plagiarism_unicheck\classes\unicheck_notification;
use plagiarism_unicheck\classes\unicheck_settings;
use plagiarism_unicheck\classes\user\preferences;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/constants.php');

global $CFG, $DB, $OUTPUT;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once($CFG->libdir . '/datalib.php');

require_login();
admin_externalpage_setup('plagiarismunicheck');

$currenturl = new moodle_url('/plagiarism/unicheck/debugging.php');
$urlwithquery = clone $currenturl;

$context = context_system::instance();

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', 'tableview', PARAM_TEXT);
$downloadformat = optional_param('download', '', PARAM_ALPHA);

$exportfilename = 'DebugOutput';

unicheck_settings::get_settings();
$filestable = new debugging_table('files', [
    debugging_table::ERRORMESSAGE_CONDITION       => get_user_preferences(preferences::DEBUGGING_ERROR_MESSAGE),
    debugging_table::TIMESUBMITTED_FROM_CONDITION => get_user_preferences(preferences::DEBUGGING_TIME_SUBMITTED_FROM),
    debugging_table::TIMESUBMITTED_TO_CONDITION   => get_user_preferences(preferences::DEBUGGING_TIME_SUBMITTED_TO)
], $exportfilename, $downloadformat);

$curpage = optional_param('page', 0, PARAM_INT);
if ($curpage) {
    $urlwithquery->param('page', $curpage);
}

// Apply forms actions.
if (!$filestable->is_downloading()) {
    $unicheckstatustable = new unicheck_status_table();
    $filterform = new filter_options_form();
    $resetcacheform = new unicheck_status_recheck_form($urlwithquery);
    $batchoperationsform = new batch_operations_form(
        $urlwithquery,
        [],
        'post',
        '',
        ['class' => 'debuggingbatchoperationsform']
    );

    if ($resetcacheform->is_submitted()) {
        $unicheckstatustable->reset_cache($urlwithquery);
    }

    if ($batchoperationsform->is_submitted()) {
        $batchoperationsform->apply_operation($urlwithquery);
    }

    $filterform->apply_filters($currenturl);
}

if (!$filestable->is_downloading()) {
    $PAGE->set_title(plagiarism_unicheck::trans('unicheckdebug'));
    $PAGE->set_heading(plagiarism_unicheck::trans('unicheckdebug'));
    $PAGE->navbar->add(plagiarism_unicheck::trans('unicheckdebug'), $currenturl);
    echo $OUTPUT->header();

    $jsmodule = [
        'name'     => UNICHECK_PLAGIN_NAME,
        'fullpath' => '/plagiarism/unicheck/ajax.js',
        'requires' => ['json'],
    ];

    $PAGE->requires->strings_for_js(
        [
            'debugging:batchoperations:nofilesselected',
            'debugging:batchoperations:confirmresubmit',
            'debugging:batchoperations:confirmdelete'
        ],
        'plagiarism_unicheck'
    );

    $PAGE->requires->js_init_call('M.plagiarismUnicheck.init_debugging_table', [], true, $jsmodule);

    $currenttab = 'unicheckdebug';
    require_once(dirname(__FILE__) . '/views/view_tabs.php');

    // Unicheck status table.
    echo $OUTPUT->heading(plagiarism_unicheck::trans('debugging:statustable:header'));

    $unicheckstatustable->display();
    $resetcacheform->display();

    // Files table.
    if ($id && confirm_sesskey()) {
        switch ($action) {
            case 'resubmit':
                if (unicheck_file_provider::resubmit_by_ids([$id])) {
                    unicheck_notification::success('fileresubmitted', true);
                }

                break;
            case 'delete':
                unicheck_file_provider::delete_by_ids([$id]);
                unicheck_notification::success('filedeleted', true);

                break;
        }
    }

    echo $OUTPUT->heading(plagiarism_unicheck::trans('ufiles'));
    echo $OUTPUT->box(plagiarism_unicheck::trans('explainerrors'));

    $filterform->display();
}

$filestable->out(get_user_preferences(preferences::DEBUGGING_PER_PAGE, 20), true);

if (!$filestable->is_downloading() && $filestable->totalrows > 0) {
    $batchoperationsform->display();
}

if (!$filestable->is_downloading()) {
    echo $OUTPUT->footer();
}