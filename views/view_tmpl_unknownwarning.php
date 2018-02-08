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
 * view_tmpl_unknownwarning.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

use plagiarism_unicheck\classes\permissions\capability;

global $OUTPUT, $PAGE;

if (AJAX_SCRIPT) {
    $PAGE->set_context(null);
}

$title = plagiarism_unicheck::trans('unknownwarning');
$reset = '';
$modulecontext = context_module::instance($linkarray['cmid']);
// This is a teacher viewing the responses.
if (has_capability(capability::RESET_FILE, $modulecontext) && !empty($fileobj->errorresponse)) {
    // Strip out some possible known text to tidy it up.
    $erroresponse = plagiarism_unicheck::error_resp_handler($fileobj->errorresponse);

    $erroresponse = str_replace('{&quot;LocalisedMessage&quot;:&quot;', '', $erroresponse);
    $erroresponse = str_replace('&quot;,&quot;Message&quot;:null}', '', $erroresponse);
    $title .= ': ' . $erroresponse;
    $url = new moodle_url('/plagiarism/unicheck/reset.php', [
        'cmid'    => $linkarray['cmid'],
        'pf'      => $fileobj->id,
        'sesskey' => sesskey(),
    ]);
    $reset = sprintf('<a href="%1$s"><img src="%2$s" title="%3$s"></a>',
        $url, $OUTPUT->image_url('reset', UNICHECK_PLAGIN_NAME), get_string('reset')
    );
}

$htmlparts = ['<span class="un_report">'];
$htmlparts[] = sprintf('<img class="un_tooltip" src="%1$s" alt="%2$s" title="%3$s" />%4$s',
    $OUTPUT->image_url('error', UNICHECK_PLAGIN_NAME),
    plagiarism_unicheck::trans('unknownwarning'), $title, $reset
);

$htmlparts[] = '</span>';

return implode('', $htmlparts);