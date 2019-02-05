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

// Normal situation - Unicheck has successfully analyzed the file.
$htmlparts = [];

if (empty($cid) && !empty($linkarray['cmid'])) {
    $cid = $linkarray['cmid'];
}

$getrankclass = function ($fileobj) {
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

    $getcheatingblock = function ($reporturl = null) use ($fileobj, $metadata) {
        $htmlparts = [];
        if ($fileobj->type === unicheck_plagiarism_entity::TYPE_ARCHIVE) {
            return '';
        }

        if (isset($metadata[unicheck_file_metadata::CHEATING_CHAR_REPLACEMENTS_COUNT])) {
            $cheatingchars = (int)$metadata[unicheck_file_metadata::CHEATING_CHAR_REPLACEMENTS_COUNT];
            if ($cheatingchars > 0) {
                $htmlparts[] = ($reporturl) ? sprintf(
                    '<a title="%s" href="%s" target="_blank" class="un-cheating">',
                    plagiarism_unicheck::trans('report'),
                    $reporturl
                ) : '<div class="un-cheating">';

                $htmlparts[] = sprintf(
                    '<span>%s</span>',
                    plagiarism_unicheck::trans('ui:possiblecheating')
                );

                $htmlparts[] = ($reporturl) ? '</a>' : '</div>';
            }
        }

        return implode('', $htmlparts);
    };

    $canviewreport = capability::user_can(capability::VIEW_REPORT, $cid, $USER->id);
    if (!$canviewreport) {
        $canviewreport = $activitycfg[unicheck_settings::SHOW_STUDENT_REPORT];
    }

    $canvieweditreport = capability::user_can(capability::VIEW_EDIT_REPORT, $cid, $USER->id);

    $htmlparts[] = '<div class="un_detect_result">';
    $htmlparts[] = sprintf(
        '<a href="%s" class="un_link" target="_blank">' .
        '<img width="69" src="%s" title="%s">',
        new moodle_url(UNICHECK_DOMAIN),
        $OUTPUT->image_url('logo', UNICHECK_PLAGIN_NAME),
        plagiarism_unicheck::trans('pluginname')
    );
    if (in_array(true, [$canviewsimilarity, $canviewreport, $canvieweditreport]) && !empty($fileobj->check_id)) {
        $htmlparts[] = sprintf('<span class="un_report_id">ID:%s</span>', $fileobj->check_id);
    }
    $htmlparts[] = '</a>';

    if (isset($fileobj->similarityscore)) {
        if (in_array(true, [$canviewsimilarity, $canviewreport, $canvieweditreport])) {
            $rankclass = $getrankclass($fileobj);
            $reporturl = null;
            if (!empty($fileobj->reporturl)) {
                $unicheckurl = new unicheck_url($fileobj);
                if ($canvieweditreport) {
                    $reporturl = $unicheckurl->get_edit_url($cid);
                } else {
                    if ($canviewreport) {
                        $reporturl = $unicheckurl->get_view_url($cid);
                    }
                }
            }

            if ($canviewsimilarity && $reporturl) {
                $htmlparts[] = '<div class="un-report">';
                $htmlparts[] = sprintf(
                    '<span class="un_report_percentage rank1 %s">%s%%</span>',
                    $rankclass,
                    $fileobj->similarityscore
                );
                $htmlparts[] = sprintf(
                    '<a title="%s" href="%s" class="un-report-link" target="_blank">',
                    plagiarism_unicheck::trans('report'),
                    $reporturl
                );
                $htmlparts[] = sprintf(
                    '<span class="un_report_text">%s</span>',
                    plagiarism_unicheck::trans('ui:reportlink')
                );
                $htmlparts[] = '</a>';
                $htmlparts[] = '</div>';
            } else if ($reporturl) {
                $htmlparts[] = '<div class="un-report">';
                $htmlparts[] = sprintf(
                    '<a title="%s" href="%s" class="un-report-without-score-link" target="_blank">',
                    plagiarism_unicheck::trans('report'),
                    $reporturl
                );
                $htmlparts[] = sprintf(
                    '<span class="un-report-without-score-text">%s</span>',
                    plagiarism_unicheck::trans('ui:reportlink')
                );
                $htmlparts[] = '</a>';
                $htmlparts[] = '</div>';
            } else if ($canviewsimilarity) {
                $htmlparts[] = '<div class="un-report un_report_without_link">';
                // User is allowed to view only the score.
                $htmlparts[] = sprintf(
                    '<span class="un_report_percentage rank1 %s">%s%%</span>',
                    $rankclass,
                    $fileobj->similarityscore
                );
                $htmlparts[] = '</div>';
            }
            $htmlparts[] = $getcheatingblock($reporturl);
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
            $htmlparts[] = '<br><span class="text-danger">';
            $htmlparts[] = plagiarism_unicheck::trans('archive:limitreachedshortdescripton');
            $htmlparts[] = '</span>';
        }
    }

    $htmlparts[] = '</div>';
}

return implode('', $htmlparts);