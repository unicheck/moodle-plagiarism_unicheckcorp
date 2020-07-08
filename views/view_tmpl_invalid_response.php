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
 * view_tmpl_invalid_response.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $OUTPUT, $PAGE;

if (AJAX_SCRIPT) {
    $PAGE->set_context(null);
}

$unicheckurl = new moodle_url(UNICHECK_DOMAIN);
$unichecklogourl = $OUTPUT->image_url('logo', UNICHECK_PLAGIN_NAME);
$pluginname = plagiarism_unicheck::trans('pluginname');
$errormessage = plagiarism_unicheck::error_resp_handler($fileobj->errorresponse);

$context = [
    'unicheckurl'     => (string) $unicheckurl,
    'unichecklogourl' => (string) $unichecklogourl,
    'pluginname'      => s($pluginname),
    'errormessage'    => format_text($errormessage, FORMAT_HTML)
];

return $OUTPUT->render_from_template('plagiarism_unicheck/invalid_response', $context);