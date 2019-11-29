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

use plagiarism_unicheck\classes\permissions\capability;
use plagiarism_unicheck\classes\services\report\unicheck_url;
use plagiarism_unicheck\classes\services\storage\unicheck_file_metadata;
use plagiarism_unicheck\classes\unicheck_plagiarism_entity;
use plagiarism_unicheck\classes\unicheck_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $OUTPUT, $USER, $PAGE;

if (AJAX_SCRIPT) {
    $PAGE->set_context(null);
}

if (!isset($fileobj)) {
    return null;
}

if (empty($cid) && !empty($linkarray['cmid'])) {
    $cid = $linkarray['cmid'];
}

$getrankclass = function($fileobj) {
    $score = (float) $fileobj->similarityscore;
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

    return $rankclass;
};

$metadata = [];
if ($fileobj->metadata) {
    $metadata = json_decode($fileobj->metadata, true);
}

if (!empty($cid) && !empty($fileobj->reporturl) || !empty($fileobj->similarityscore)) {

    $activitycfg = unicheck_settings::get_activity_settings($cid, null, true);
    $canviewsimilarity = capability::user_can(capability::VIEW_SIMILARITY, $cid, $USER->id);
    if (!$canviewsimilarity) {
        $canviewsimilarity = $activitycfg[unicheck_settings::SHOW_STUDENT_SCORE];
    }

    $canviewreport = capability::user_can(capability::VIEW_REPORT, $cid, $USER->id);
    if (!$canviewreport) {
        $canviewreport = $activitycfg[unicheck_settings::SHOW_STUDENT_REPORT];
    }
    $canvieweditreport = capability::user_can(capability::VIEW_EDIT_REPORT, $cid, $USER->id);

    if (!in_array(true, [$canviewsimilarity, $canviewreport, $canvieweditreport])) {
        return null;
    }

    $unicheckurl = new moodle_url(UNICHECK_DOMAIN);
    $unichecklogourl = $OUTPUT->image_url('logo', UNICHECK_PLAGIN_NAME);
    $pluginname = plagiarism_unicheck::trans('pluginname');
    $checkid = !empty($fileobj->check_id) ? $fileobj->check_id : null;
    $similarityscore = !empty($fileobj->similarityscore) ? $fileobj->similarityscore : null;;
    $reporturl = null;
    $rankclass = null;
    $reporttitle = plagiarism_unicheck::trans('report');
    $uireportlinktitle = plagiarism_unicheck::trans('ui:reportlink');
    $bigarchive = false;
    $bigarchivetitle = plagiarism_unicheck::trans('archive:limitreachedshortdescripton');
    $hascheating = false;
    $cheatingtitle = plagiarism_unicheck::trans('ui:cheatingtitle');
    $cheatingtooltip = plagiarism_unicheck::trans('ui:cheatingtooltip');

    if ($similarityscore) {
        $rankclass = $getrankclass($fileobj);
        if (!empty($fileobj->reporturl)) {
            if ($canvieweditreport) {
                $reporturl = (new unicheck_url($fileobj))->get_edit_url($cid);
            } else {
                if ($canviewreport) {
                    $reporturl = (new unicheck_url($fileobj))->get_view_url($cid);
                }
            }
        }

        if ($canvieweditreport) {
            if (isset($metadata[unicheck_file_metadata::CHEATING_EXIST])) {
                $hascheating = (bool) $metadata[unicheck_file_metadata::CHEATING_EXIST];
            }

            if (!$hascheating && isset($metadata[unicheck_file_metadata::CHEATING_CHAR_REPLACEMENTS_COUNT])) {
                $cheatingchars = (int) $metadata[unicheck_file_metadata::CHEATING_CHAR_REPLACEMENTS_COUNT];
                if ($cheatingchars > 0) {
                    $hascheating = true;
                }
            }
        }
    }

    if ($metadata && $fileobj->type === unicheck_plagiarism_entity::TYPE_ARCHIVE) {
        $archivefilescount = 0;
        if (isset($metadata[unicheck_file_metadata::ARCHIVE_SUPPORTED_FILES_COUNT])) {
            $archivefilescount = $metadata[unicheck_file_metadata::ARCHIVE_SUPPORTED_FILES_COUNT];
        }
        $extractedfilescount = 0;
        if (isset($metadata[unicheck_file_metadata::EXTRACTED_SUPPORTED_FILES_FROM_ARCHIVE_COUNT])) {
            $extractedfilescount = $metadata[unicheck_file_metadata::EXTRACTED_SUPPORTED_FILES_FROM_ARCHIVE_COUNT];
        };
        if ($archivefilescount > $extractedfilescount) {
            $bigarchive = true;
        }
    }

    $context = [
        'canviewsimilarity' => $canviewsimilarity,
        'pluginname'        => s($pluginname),
        'similarityscore'   => $similarityscore,
        'unicheckurl'       => (string) $unicheckurl,
        'unichecklogourl'   => (string) $unichecklogourl,
        'checkid'           => $checkid,
        'reporturl'         => (string) $reporturl,
        'rankclass'         => $rankclass,
        'reporttitle'       => s($reporttitle),
        'uireportlinktitle' => format_text($uireportlinktitle, FORMAT_HTML),
        'hascheating'       => $hascheating,
        'cheatingtitle'     => s($cheatingtitle),
        'cheatingtooltip'   => s($cheatingtooltip),
        'bigarchive'        => $bigarchive,
        'bigarchivetitle'   => s($bigarchivetitle)
    ];

    return $OUTPUT->render_from_template('plagiarism_unicheck/processed', $context);
}