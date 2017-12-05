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
 * Translations
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Unicheck plagiarism plugin';
$string['unicheck_enable'] = 'Enable Unicheck plugin';
$string['studentdisclosuredefault'] = 'All  uploaded files will be submitted to the plagiarism detection system Unicheck.';
$string['studentdisclosure'] = 'Familiarize students with the Unicheck Privacy Policy';
$string['studentdisclosure_help'] = 'This text will be displayed to all students on the file upload page.';
$string['unicheck'] = 'Unicheck plagiarism plugin';
$string['unicheck_settings_url_text'] = 'Open unicheck.com admin account to view/copy Client ID/API Secret';
$string['client_id'] = 'Client ID';
$string['client_id_help'] = 'ID of Client provided by Unicheck to access the API you can find it on <a href="https://unicheck.com/profile/apisettings">https://unicheck.com/profile/apisettings</a>';
$string['unicheck_lang'] = 'Language';
$string['unicheck_lang_help'] = 'Language code provided by Unicheck';
$string['api_secret'] = 'API Secret';
$string['api_secret_help'] = 'API Secret provided by Unicheck to access the API you can find it on <a href="https://unicheck.com/profile/apisettings">https://unicheck.com/profile/apisettings</a>';
$string['use_unicheck'] = 'Auto check new submissions';
$string['use_unicheck_help'] = 'When enabled, student ​assignments will be checked in real-time​, right after ​submission. ​Otherwise, ​you will need to either manually kick start check for selected submissions or use "​Check already submitted assignments" option.';
$string['use_assign_desc_param'] = 'To unlock Unicheck settings';
$string['use_assign_desc_value'] = 'Set Submissions settings → Require students click submit button = Yes';
$string['unicheck_enableplugin'] = 'Enable in {$a} activity';
$string['savedconfigsuccess'] = 'Plagiarism detection settings saved';
$string['savedconfigfailed'] = 'An incorrect Client ID/API Secret combination has been entered. Unicheck has been disabled, please try again.';
$string['show_student_score'] = 'Show similarity scores to students';
$string['show_student_score_help'] = 'Students only see similarity scores for their own submissions and do not see scores of other students.';
$string['show_student_report'] = 'Show similarity reports to students';
$string['show_student_report_help'] = 'Students only see similarity ​reports for their own submissions and do not see ​reports of other students.';
$string['draft_submit'] = 'When should the file be submitted to Unicheck';
$string['showwhenclosed'] = 'When Activity closed';
$string['submitondraft'] = 'Submit file when first uploaded';
$string['submitonfinal'] = 'Submit file when student sends it for grading';
$string['defaultupdated'] = 'Default values updated';
$string['defaultsdesc'] = 'Configure defaults for Assignment activity. Teachers can adjust settings in the individual assignments';
$string['unicheckdefaults'] = 'Unicheck defaults';
$string['similarity'] = 'Similarity';
$string['processing'] = 'This file has been submitted to Unicheck, now waiting for the analysis to be available';
$string['pending'] = 'This file is pending submission to Unicheck';
$string['previouslysubmitted'] = 'Previously submitted as';
$string['report'] = 'Report';
$string['unknownwarning'] = 'An error occurred when trying to send this file to Unicheck';
$string['unsupportedfiletype'] = 'This filetype is not supported by Unicheck';
$string['toolarge'] = 'This file is too large for Unicheck to process';
$string['plagiarism'] = 'Potential plagiarism ';
$string['report'] = 'View full report';
$string['progress'] = 'Unicheck scan';
$string['studentemailsubject'] = 'File processed by Unicheck';
$string['studentemailcontent'] = 'The file you submitted to {$a->modulename} in {$a->coursename} has already been processed by the plagiarism detection system Unicheck
{$a->modulelink}';

$string['filereset'] = 'A file has been reset for re-submission to Unicheck';
$string['noreceiver'] = 'No receiver address was specified';
$string['unicheck:enable'] = 'Allow the teacher to enable/disable Unicheck inside an activity';
$string['unicheck:resetfile'] = 'Allow the teacher to resubmit the file to Unicheck after an error occurred';
$string['unicheck:viewreport'] = 'Allow the teacher to view the full report from Unicheck';
$string['unicheck:vieweditreport'] = 'Allow the teacher to view and edit the full report from Unicheck';
$string['unicheckdebug'] = 'Debugging';
$string['explainerrors'] = 'This page lists any files that are currently in an error state. <br/>When files are deleted on this page they will not be able to be resubmitted and errors will no longer display to teachers or students';
$string['id'] = 'ID';
$string['name'] = 'Name';
$string['file'] = 'File';
$string['status'] = 'Status';
$string['module'] = 'Module';
$string['resubmit'] = 'Resubmit';
$string['identifier'] = 'Identifier';
$string['fileresubmitted'] = 'File Queued for resubmission';
$string['filedeleted'] = 'File deleted from queue';
$string['cronwarning'] = 'The <a href="../../admin/cron.php">cron.php</a> maintenance script has not been run for at least 30 min - Cron must be configured to allow Unicheck to function correctly.';
$string['waitingevents'] = 'There are {$a->countallevents} events waiting for cron and {$a->countheld} events are being held for resubmission';
$string['deletedwarning'] = 'This file could not be found - it may have been deleted by the user';
$string['heldevents'] = 'Held events';
$string['heldeventsdescription'] = 'These are events that did not complete on the first attempt and were queued for resubmission - this prevents subsequent events from completing and may need further investigation. Some of these events may not be relevant to Unicheck.';
$string['ufiles'] = 'Unicheck Files';
$string['getscore'] = 'Get score';
$string['scorenotavailableyet'] = 'This file has not been processed by Unicheck yet.';
$string['scoreavailable'] = 'This file has been processed by Unicheck and a report is now available.';
$string['receivernotvalid'] = 'This is not a valid receiver address.';
$string['attempts'] = 'Attempts made';
$string['refresh'] = 'Refresh page to see results';
$string['delete'] = 'Delete';
$string['plagiarism_run_success'] = 'File sent for plagiarism scan';

$string['check_type'] = 'Sources to check';
$string['check_type_help'] = 'Internet source - billions of articles, pages, files available on Internet. This includes academic and scientific articles (open access), blog posts and news.
Library source - institution\'s Unicheck database which includes recorded past student submissions except for \'drafts\'.
​​';
$string['check_confirm'] = 'Are you sure you want start checking by Unicheck plagiarism plugin?';
$string['check_start'] = 'Unicheck originality grading in progress';
$string['check_file'] = 'Start a scan';

$string['web'] = 'Doc vs Internet';
$string['my_library'] = 'Doc vs Library';
$string['web_and_my_library'] = 'Doc vs Internet + Library';
$string['external_database'] = 'Doc vs External Database';
$string['web_and_my_lib_and_external_db'] = 'Doc vs Internet + Library + DB';

$string['reportready'] = 'Report ready';
$string['generalinfo'] = 'General information';
$string['similarity_sensitivity'] = 'Hide sources with a match less than (%)';
$string['similarity_sensitivity_help'] = 'Specify minimum total % match for a source. Such sources will not be shown in the list of sources and will be excluded from total similarity score.';
$string['similarity_words_sensitivity'] = 'Hide sources with a match less than (words)';
$string['similarity_words_sensitivity_help'] = 'Specify minimum total word count match for a source. Such sources will not be shown in the list of sources and will be excluded from total similarity score.';
$string['exclude_citations'] = 'Identify citations and references';
$string['exclude_citations_help'] = 'Enable this option to filter ​properly ​cite​d material and a references block. Unicheck identifies citation​s ​​​​according to ​rules described in APA, MLA, Chicago, ​​Turabian, Harvard​ guides. ​Citations will be marked with blue color and references will be marked with violet color. Such items will be excluded from total similarity score.';
$string['exclude_self_plagiarism'] = 'Exclude self-plagiarism';
$string['check_all_submitted_assignments'] = 'Check already submitted assignments';
$string['check_all_submitted_assignments_help'] = 'Use this option to ​manually run ​a bulk check of all submitted assignments​ in current activity​. ​C​heck ​will start in about 10 minutes after ​updating assignment settings.';
$string['no_index_files'] = 'Draft assignment';
$string['no_index_files_help'] = 'Use this option to mark an assignment as \'draft\' if you create separate assignments for draft and final submissions.
Drafts will not be saved in the institution\'s Unicheck database.';
$string['min_30_words'] = 'At least 30 words are required';
$string['max_100000_words'] = 'File(s) should have no more than 100 000 words and be not larger than 70MB';
$string['max_supported_archive_files_count'] = 'Max supported archive files';
$string['max_supported_archive_files_count_help'] = 'The maximum number of supported files that will be extracted from the archives';
$string['uploading'] = 'Uploading';