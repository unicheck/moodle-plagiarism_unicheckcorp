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
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unicheck\classes\services\report\unicheck_url;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $OUTPUT, $USER, $PAGE;

if (AJAX_SCRIPT) {
    $PAGE->set_context(null);
}

// Normal situation - Unicheck has successfully analyzed the file.
$htmlparts = ['<span class="un_report">'];

if (empty($cid) && !empty($linkarray['cmid'])) {
    $cid = $linkarray['cmid'];
}

if (!empty($cid) && !empty($fileobj->reporturl) || !empty($fileobj->similarityscore)) {
    // User is allowed to view the report.
    // Score is contained in report, so they can see the score too.
    $htmlparts[] = sprintf('<a href="%s" target="_blank"><img src="%s" title="%s"></a>',
        new moodle_url(UNICHECK_DOMAIN),
        $OUTPUT->pix_url('logo', UNICHECK_PLAGIN_NAME),
        plagiarism_unicheck::trans('pluginname')
    );

    // This is a teacher viewing the responses.
    $canviewsimilarity = unicheck_core::can('plagiarism/unicheck:viewsimilarity', $cid, $USER->id);
    $assigncfg = unicheck_settings::get_assign_settings($cid, null, true);

    if (isset($fileobj->similarityscore)) {
        if ($canviewsimilarity || $assigncfg[unicheck_settings::SHOW_STUDENT_SCORE]) {
            $score = (float)$fileobj->similarityscore;
            $rankclass = 'rankBlue';
            switch (true) {
                case ($score >= 1 && $score < 25):
                    $rankclass = 'rankGreen';
                    break;
                case ($score >= 25 && $score < 50):
                    $rankclass = 'rankYellow';
                    break;
                case ($score >= 50 && $score < 75):
                    $rankclass = 'rankOrange';
                    break;
                case ($score >= 75 && $score <= 100):
                    $rankclass = 'rankRed';
                    break;
            }

            // User is allowed to view only the score.
            $htmlparts[] = sprintf('%s<br><span class="rank1 %s">%s%%</span>',
                plagiarism_unicheck::trans('plagiarismcheckerid', ['id' => $fileobj->check_id]),
                $rankclass,
                $fileobj->similarityscore
            );
        }
    }

    if (!empty($fileobj->reporturl)) {
        $canviewreport = unicheck_core::can('plagiarism/unicheck:viewreport', $cid, $USER->id);
        if ($canviewreport || $assigncfg[unicheck_settings::SHOW_STUDENT_REPORT]) {
            $reporturl = new unicheck_url($fileobj);
            $canvieweditreport = unicheck_core::can('plagiarism/unicheck:vieweditreport', $cid, $USER->id);
            // Display opt-out link.
            $htmlparts[] = '&nbsp;<span class"plagiarismoptout">';
            $htmlparts[] = sprintf('<a title="%s" href="%s" target="_blank">',
                plagiarism_unicheck::trans('report'),
                $canvieweditreport ? $reporturl->get_edit_url($cid) : $reporturl->get_view_url($cid)
            );
            $htmlparts[] = '<img class="un_tooltip" src="' . $OUTPUT->pix_url('link', UNICHECK_PLAGIN_NAME) . '">';
            $htmlparts[] = '</a></span>';
        }
    }
}

$htmlparts[] = '</span>';

return implode('', $htmlparts);