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
 * view_tmpl_processed.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

use plagiarism_unicheck\classes\permissions\capability;

global $PAGE, $OUTPUT;

if (AJAX_SCRIPT) {
    $PAGE->set_context(null);
}

$check = '';
$modulecontext = context_module::instance($linkarray['cmid']);
// This is a teacher viewing the responses.

if (has_capability(capability::CHECK_FILE, $modulecontext) && empty($fileobj->check_id) && !empty($fileobj->id)) {

    $url = new moodle_url('/plagiarism/unicheck/check.php', [
        'cmid'    => $linkarray['cmid'],
        'pf'      => $fileobj->id,
        'sesskey' => sesskey(),
    ]);

    $check = sprintf('<a href="%1$s" class="un-check">%4$s</a>',
        $url,
        $OUTPUT->image_url('logo', UNICHECK_PLAGIN_NAME),
        plagiarism_unicheck::trans('check_file'),
        plagiarism_unicheck::trans('check_file')
    );
}

$htmlparts = ['<span class="un_report">'];
$htmlparts[] = sprintf('<a href="%s" target="_blank"><img src="%s" title="%s"></a> ',
    new moodle_url(UNICHECK_DOMAIN),
    $OUTPUT->image_url('logo', UNICHECK_PLAGIN_NAME),
    plagiarism_unicheck::trans('pluginname')
);
$htmlparts[] = sprintf('%1$s', $check);
$htmlparts[] = '</span>';

return implode('', $htmlparts);