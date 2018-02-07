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
 * unicheck_plagiarism_entity.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\helpers\unicheck_stored_file;
use plagiarism_unicheck\classes\permissions\capability;
use plagiarism_unicheck\classes\services\report\unicheck_url;
use plagiarism_unicheck\classes\services\storage\unicheck_file_metadata;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_plagiarism_entity;

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once(dirname(__FILE__) . '/lib.php');

global $PAGE, $CFG, $OUTPUT, $USER;

$cmid = required_param('cmid', PARAM_INT); // Course Module ID.
$cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
require_login($cm->course, true, $cm);

$pf = required_param('pf', PARAM_INT); // Plagiarism file id.
$childs = unicheck_stored_file::get_plagiarism_file_childs_by_id($pf);

$modulecontext = context_module::instance($cmid);

$pageparams = ['cmid' => $cmid, 'pf' => $pf];
$cpf = optional_param('cpf', null, PARAM_INT); // Plagiarism child file id.
if ($cpf !== null) {
    $current = unicheck_file_provider::get_by_id($cpf);
    $currenttab = 'unicheck_file_id_' . $current->id;
    $pageparams['cpf'] = $cpf;
} else {
    $currenttab = 'un_files_info';
}

$PAGE->set_pagelayout('report');
$pageurl = new \moodle_url('/plagiarism/unicheck/reports.php', $pageparams);
$PAGE->set_url($pageurl);

echo $OUTPUT->header();

$tabs = [];
$fileinfos = [];
$canvieweditreport = capability::user_can(capability::VIEW_EDIT_REPORT, $cmid, $USER->id);
foreach ($childs as $child) {

    switch ($child->state) {
        case unicheck_file_state::CHECKED:

            $url = new \moodle_url('/plagiarism/unicheck/reports.php', [
                'cmid' => $cmid,
                'pf'   => $pf,
                'cpf'  => $child->id,
            ]);

            if ($child->check_id !== null && $child->progress == 100) {

                $tabs[] = new tabobject('unicheck_file_id_' . $child->id, $url->out(), $child->filename, '', false);

                $link = html_writer::link($url, $child->filename);
                $fileinfos[] = [
                    'filename' => html_writer::tag('div', $link, ['class' => 'edit-link']),
                    'status'   => $OUTPUT->pix_icon('i/valid', plagiarism_unicheck::trans('reportready')) .
                        plagiarism_unicheck::trans('reportready'),
                ];
            }
            break;
        case unicheck_file_state::HAS_ERROR :

            $erroresponse = plagiarism_unicheck::error_resp_handler($child->errorresponse);
            $fileinfos[] = [
                'filename' => $child->filename,
                'status'   => $OUTPUT->pix_icon('i/invalid', $erroresponse) . $erroresponse,
            ];
            break;
    }
};

$generalinfourl = new \moodle_url('/plagiarism/unicheck/reports.php', [
    'cmid' => $cmid,
    'pf'   => $pf,
]);

array_unshift($tabs,
    new tabobject('un_files_info', $generalinfourl->out(), plagiarism_unicheck::trans('generalinfo'), '', false));

print_tabs([$tabs], $currenttab);

echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');

if ($cpf !== null) {
    $reporturl = (new unicheck_url($current));
    $link = $reporturl->get_view_url($cmid);
    if ($canvieweditreport) {
        $link = $reporturl->get_edit_url($cmid);
    }

    echo '<iframe src="' . $link . '" frameborder="0" id="_un_report_frame" style="width: 100%; height: 750px;"></iframe>';
} else {
    $table = new html_table();
    $table->head = ['Filename', 'Status'];
    $table->align = ['left', 'left'];

    foreach ($fileinfos as $fileinfo) {
        $linedata = [$fileinfo['filename'], $fileinfo['status']];
        $table->data[] = $linedata;
    }

    echo html_writer::table($table);

    $fileobj = unicheck_file_provider::find_by_id($pf);
    if ($fileobj && $fileobj->type === unicheck_plagiarism_entity::TYPE_ARCHIVE) {
        $metadata = $fileobj->metadata;
        if ($metadata) {
            $metadata = json_decode($metadata, true);
            $archivefilescount = isset($metadata[unicheck_file_metadata::ARCHIVE_FILES_COUNT])
                ? $metadata[unicheck_file_metadata::ARCHIVE_FILES_COUNT]
                : 0;
            $extractedfilescount = isset($metadata[unicheck_file_metadata::EXTRACTED_FILES_FROM_ARCHIVE_COUNT])
                ? $metadata[unicheck_file_metadata::EXTRACTED_FILES_FROM_ARCHIVE_COUNT]
                : 0;

            if ($archivefilescount > $extractedfilescount) {
                $params = new stdClass();
                $params->filename = $fileobj->filename;
                $params->max_supported_count = $extractedfilescount;

                echo html_writer::span(plagiarism_unicheck::trans('archive:limitreachedfulldescription', $params), 'text-danger');
            }
        }
    }
}
echo $OUTPUT->box_end();

echo $OUTPUT->footer();