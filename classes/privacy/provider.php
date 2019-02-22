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
 * Privacy class for requesting user data.
 *
 * @package    plagiarism_unicheck
 * @copyright  2018 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\privacy;

defined('MOODLE_INTERNAL') || die();

use core_plagiarism\privacy\plagiarism_user_provider;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

if (interface_exists('\core_plagiarism\privacy\plagiarism_user_provider')) {
    interface user_provider extends plagiarism_user_provider {
    }
} else {
    //@codingStandardsIgnoreStart

    /**
     * This interface exists to provide backwards compatibility with moodle 3.3
     */
    interface user_provider {
    };
    // @codingStandardsIgnoreEnd
}

/**
 * Privacy class for requesting user data.
 *
 * @package    plagiarism_unicheck
 * @copyright  2018 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin has data and must therefore define the metadata provider in order to describe it.
    \core_privacy\local\metadata\provider,
    // This is a plagiarism plugin. It interacts with the plagiarism subsystem rather than with core.
    \core_plagiarism\privacy\plagiarism_provider,
    // The Plagiarism subsystem will be called by other components and will forward
    // requests to each plagiarism plugin implementing its APIs.
    user_provider {

    // This trait must be included to provide the relevant polyfill for the metadata provider.
    use \core_privacy\local\legacy_polyfill;
    // This trait must be included to provide the relevant polyfill for the plagirism provider.
    use \core_plagiarism\privacy\legacy_polyfill;

    /**
     * Returns meta data about this system.
     *
     * @param   collection $collection The initialised collection to add items to.
     *
     * @return  collection     A listing of user data stored through this system.
     */
    public static function _get_metadata(collection $collection) {
        // Moodle core components.
        $collection->link_subsystem('core_plagiarism', 'privacy:metadata:core_plagiarism');
        $collection->link_subsystem('core_files', 'privacy:metadata:core_files');

        $collection->add_database_table(
            'plagiarism_unicheck_files',
            [
                'userid'             => 'privacy:metadata:plagiarism_unicheck_files:userid',
                'identifier'         => 'privacy:metadata:plagiarism_unicheck_files:identifier',
                'check_id'           => 'privacy:metadata:plagiarism_unicheck_files:check_id',
                'filename'           => 'privacy:metadata:plagiarism_unicheck_files:filename',
                'type'               => 'privacy:metadata:plagiarism_unicheck_files:type',
                'similarityscore'    => 'privacy:metadata:plagiarism_unicheck_files:similarityscore',
                'attempt'            => 'privacy:metadata:plagiarism_unicheck_files:attempt',
                'errorresponse'      => 'privacy:metadata:plagiarism_unicheck_files:errorresponse',
                'timesubmitted'      => 'privacy:metadata:plagiarism_unicheck_files:timesubmitted',
                'external_file_id'   => 'privacy:metadata:plagiarism_unicheck_files:external_file_id',
                'state'              => 'privacy:metadata:plagiarism_unicheck_files:state',
                'external_file_uuid' => 'privacy:metadata:plagiarism_unicheck_files:external_file_uuid',
                'metadata'           => 'privacy:metadata:plagiarism_unicheck_files:metadata',
            ],
            'privacy:metadata:plagiarism_unicheck_files'
        );

        $collection->add_database_table(
            'plagiarism_unicheck_users',
            [
                'user_id'          => 'privacy:metadata:plagiarism_unicheck_users:user_id',
                'external_user_id' => 'privacy:metadata:plagiarism_unicheck_users:external_user_id',
                'external_token'   => 'privacy:metadata:plagiarism_unicheck_users:external_token'
            ],
            'privacy:metadata:plagiarism_unicheck_users'
        );

        $collection->add_database_table(
            'plagiarism_unicheck_callback',
            [
                'event_type'    => 'privacy:metadata:plagiarism_unicheck_callback:event_type',
                'event_id'      => 'privacy:metadata:plagiarism_unicheck_callback:event_id',
                'resource_type' => 'privacy:metadata:plagiarism_unicheck_callback:resource_type',
                'resource_id'   => 'privacy:metadata:plagiarism_unicheck_callback:resource_id',
                'request_body'  => 'privacy:metadata:plagiarism_unicheck_callback:request_body'
            ],
            'privacy:metadata:plagiarism_unicheck_callback'
        );

        // External Services.
        $collection->link_external_location('External Unicheck API', [
            'domain'        => 'privacy:metadata:plagiarism_external_unicheck_api:domain',
            'userid'        => 'privacy:metadata:plagiarism_external_unicheck_api:userid',
            'useremail'     => 'privacy:metadata:plagiarism_external_unicheck_api:useremail',
            'userfirstname' => 'privacy:metadata:plagiarism_external_unicheck_api:userfirstname',
            'userlastname'  => 'privacy:metadata:plagiarism_external_unicheck_api:userlastname',
            'userscope'     => 'privacy:metadata:plagiarism_external_unicheck_api:userscope',
            'fileformat'    => 'privacy:metadata:plagiarism_external_unicheck_api:fileformat',
            'filedata'      => 'privacy:metadata:plagiarism_external_unicheck_api:filedata',
            'filename'      => 'privacy:metadata:plagiarism_external_unicheck_api:filename',
            'submissionid'  => 'privacy:metadata:plagiarism_external_unicheck_api:submissionid',
        ], 'privacy:metadata:plagiarism_external_unicheck_api');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     *
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function _get_contexts_for_userid($userid) {
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'userid'       => $userid
        ];

        $sql = "SELECT DISTINCT c.id
                FROM {context} c
                JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {plagiarism_unicheck_files} puf ON cm.id = puf.cm
                WHERE puf.userid = :userid";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all plagiarism data from each plagiarism plugin for the specified userid and context.
     *
     * @param   int      $userid     The user to export.
     * @param   \context $context    The context to export.
     * @param   array    $subcontext The subcontext within the context to export this information to.
     * @param   array    $linkarray  The weird and wonderful link array used to display information for a specific item
     */
    public static function _export_plagiarism_user_data($userid, \context $context, array $subcontext, array $linkarray) {
        if (!$userid) {
            return;
        }

        if (isset($linkarray['forum'])) {
            $subcontext = [];
        }

        array_push($subcontext, get_string('privacy:export:plagiarism_unicheck:plagiarismpath', 'plagiarism_unicheck'));

        if (isset($linkarray['file'])) {
            self::export_plagiarism_file_report($userid, $context, $subcontext, $linkarray);
        }

        if (isset($linkarray['content']) || isset($linkarray['forum'])) {
            self::export_plagiarism_content_report($userid, $context, $subcontext, $linkarray);
        }

        return;
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function _delete_plagiarism_for_context(\context $context) {
        global $DB;

        $DB->delete_records('plagiarism_unicheck_files', ['cm' => $context->instanceid]);
    }

    /**
     * Delete all user information for the provided user and context.
     *
     * @param  int      $userid  The user to delete
     * @param  \context $context The context to refine the deletion.
     */
    public static function _delete_plagiarism_for_user($userid, \context $context) {
        global $DB;

        $DB->delete_records('plagiarism_unicheck_files', ['userid' => $userid, 'cm' => $context->instanceid]);
    }

    /**
     * Delete all user information for the provided users and context.
     *
     * @param  array    $userids The users to delete
     * @param  \context $context The context to refine the deletion.
     */
    public static function delete_plagiarism_for_users(array $userids, \context $context) {
        global $DB;

        list($userinsql, $userinparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params = array_merge(['cmid' => $context->instanceid], $userinparams);
        $sql = "cm = :cmid AND userid {$userinsql}";

        $DB->delete_records_select('plagiarism_unicheck_files', $sql, $params);
    }

    /**
     * Export all plagiarism data from each plagiarism plugin for the specified userid and context.
     *
     * @param   int      $userid     The user to export.
     * @param   \context $context    The context to export.
     * @param   array    $subcontext The subcontext within the context to export this information to.
     * @param   array    $linkarray  The weird and wonderful link array used to display information for a specific item
     */
    protected static function export_plagiarism_file_report($userid, \context $context, array $subcontext, array $linkarray) {
        global $DB;

        /** @var \stored_file $storedfile */
        $storedfile = $linkarray['file'];
        $params = ['userid' => $userid, 'cmid' => $context->instanceid, 'identifier' => $storedfile->get_pathnamehash()];
        $sql = "SELECT id,
                cm,
                identifier,
                check_id,
                filename,
                type,
                similarityscore,
                attempt,
                errorresponse,
                timesubmitted,
                external_file_id,
                state,
                external_file_uuid,
                metadata
                FROM {plagiarism_unicheck_files}
                WHERE userid = :userid and cm = :cmid and identifier = :identifier";

        $reports = $DB->get_records_sql($sql, $params);
        if (!$reports) {
            return;
        }

        foreach ($reports as $report) {
            self::export_plagiarism_metadata($report, $context, $subcontext);
        }
    }

    /**
     * Export all plagiarism data from each plagiarism plugin for the specified userid and context.
     *
     * @param   int      $userid     The user to export.
     * @param   \context $context    The context to export.
     * @param   array    $subcontext The subcontext within the context to export this information to.
     * @param   array    $linkarray  The weird and wonderful link array used to display information for a specific item
     */
    protected static function export_plagiarism_content_report($userid, \context $context, array $subcontext, array $linkarray) {
        global $DB;

        $params = [
            'userid'    => $userid,
            'cmid'      => $context->instanceid,
            'component' => 'plagiarism_unicheck',
            'contextid' => $context->id
        ];

        $sql = "SELECT puf.id,
                   puf.cm,
                   puf.identifier,
                   puf.check_id,
                   puf.filename,
                   puf.type,
                   puf.reporturl,
                   puf.progress,
                   puf.similarityscore,
                   puf.attempt,
                   puf.errorresponse,
                   puf.timesubmitted,
                   puf.external_file_id,
                   puf.state,
                   puf.external_file_uuid,
                   puf.metadata
                  FROM {plagiarism_unicheck_files} puf
                  INNER JOIN {files} f on f.pathnamehash = puf.identifier
                  WHERE puf.userid = :userid AND puf.cm = :cmid AND f.component = :component  AND f.contextid = :contextid";

        $reports = $DB->get_records_sql($sql, $params);
        if (!$reports) {
            return;
        }

        foreach ($reports as $report) {
            self::export_plagiarism_metadata($report, $context, $subcontext);

            if (!$report->identifier) {
                return;
            }

            // Get storage file by report file identifier.
            $report = get_file_storage()->get_file_by_hash($report->identifier);
            if (!$report) {
                return;
            }

            writer::with_context($context)->export_file($subcontext, $report);
        }
    }

    /**
     * export_plagiarism_metadata
     *
     * @param \stdClass $report
     * @param \context  $context
     * @param array     $subcontext
     */
    protected static function export_plagiarism_metadata($report, \context $context, array $subcontext) {

        $value = (object)[
            'cm'                 => $report->cm,
            'identifier'         => $report->identifier,
            'check_id'           => $report->check_id,
            'filename'           => $report->filename,
            'type'               => $report->type,
            'similarityscore'    => $report->similarityscore . '%',
            'attempt'            => $report->attempt,
            'errorresponse'      => json_decode($report->errorresponse, true),
            'timesubmitted'      => transform::datetime($report->timesubmitted),
            'external_file_id'   => $report->external_file_id,
            'state'              => $report->state,
            'external_file_uuid' => $report->external_file_uuid,
            'metadata'           => json_decode($report->metadata, true),
        ];

        writer::with_context($context)->export_metadata(
            $subcontext,
            'plagiarism_unicheck_report_' . $report->id,
            $value,
            get_string('privacy:export:plagiarism_unicheck:reportcontentdescription', 'plagiarism_unicheck')
        );
    }
}
