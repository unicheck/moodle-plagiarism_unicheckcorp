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

$check = '';
$modulecontext = context_module::instance($linkarray['cmid']);
// This is a teacher viewing the responses.

if (!has_capability(capability::CHECK_FILE, $modulecontext)) {
    return '';
}

if (!empty($fileobj->check_id)) {
    return '';
}

$modname = $PAGE->cm->modname;

if (!in_array($modname, [UNICHECK_MODNAME_ASSIGN])) {
    return '';
}

$params = [
    'cmid'    => $linkarray['cmid'],
    'modname' => $modname,
    'sesskey' => sesskey(),
    'uid'     => $linkarray['userid']
];

if (!empty($fileobj->id)) {
    $params['pf'] = $fileobj->id;
}

$submissiontype = 'file';
if (isset($linkarray['content'])) {
    $submissiontype = 'onlinetext';
}

$params['submissiontype'] = $submissiontype;
$startcheckurl = new moodle_url('/plagiarism/unicheck/check.php', $params);
$startchecktitle = plagiarism_unicheck::trans('check_file');

$unicheckurl = new moodle_url(UNICHECK_DOMAIN);
$unichecklogourl = $OUTPUT->image_url('logo', UNICHECK_PLAGIN_NAME);
$pluginname = plagiarism_unicheck::trans('pluginname');

$context = [
    'unicheckurl'     => (string) $unicheckurl,
    'unichecklogourl' => (string) $unichecklogourl,
    'pluginname'      => s($pluginname),
    'startcheckurl'   => (string) $startcheckurl,
    'startchecktitle' => s($startchecktitle)
];

return $OUTPUT->render_from_template('plagiarism_unicheck/can_check', $context);