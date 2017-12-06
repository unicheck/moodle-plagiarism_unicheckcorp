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
 * view_tmpl_progress.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unicheck\classes\services\storage\unicheck_file_state;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $PAGE, $OUTPUT;

if (AJAX_SCRIPT) {
    $PAGE->set_context(null);
}

if (!$iterator) {
    // Now add JS to validate receiver indicator using Ajax.
    $jsmodule = [
        'name'     => UNICHECK_PLAGIN_NAME,
        'fullpath' => '/plagiarism/unicheck/ajax.js',
        'requires' => ['json'],
    ];

    $PAGE->requires->js_init_call('M.plagiarismUnicheck.init', [$linkarray['cmid']], true, $jsmodule);
}

$htmlparts = [sprintf('<div class="un_report fid-%1$s"><div class="un_data">{"fid":"%1$s"}</div>', $fileobj->id)];
$htmlparts[] = sprintf('<img  class="un_progress un_tooltip" src="%1$s" alt="%2$s" title="%2$s" />',
    $OUTPUT->image_url('loader', UNICHECK_PLAGIN_NAME),
    plagiarism_unicheck::trans('processing')
);

if ($fileobj->state === unicheck_file_state::UPLOADING) {
    $htmlparts[] = sprintf('%s', plagiarism_unicheck::trans('uploading'));
} else {
    $htmlparts[] = sprintf('%s: <span class="un_progress-val" >%d%%</span>',
        plagiarism_unicheck::trans('progress'), intval($fileobj->progress)
    );
}

$htmlparts[] = '</div>';

return implode('', $htmlparts);