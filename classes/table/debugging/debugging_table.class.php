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
 * debugging_table.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\table\debugging;

use html_writer;
use moodle_url;
use plagiarism_unicheck;
use plagiarism_unicheck\classes\services\storage\file_error_code;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_plagiarism_entity;
use stdClass;
use table_sql;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class debugging_table
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class debugging_table extends table_sql {

    /**
     * ERRORMESSAGE_CONDITION
     */
    const ERRORMESSAGE_CONDITION = 'errormessage';
    /**
     * TIMESUBMITTED_FROM_CONDITION
     */
    const TIMESUBMITTED_FROM_CONDITION = 'timesubmittedfrom';
    /**
     * TIMESUBMITTED_TO_CONDITION
     */
    const TIMESUBMITTED_TO_CONDITION = 'timesubmittedto';

    /** @var stdClass filters parameters */
    private $filterparams;

    /**
     * debugging_table constructor.
     *
     * @param string      $uniqueid
     * @param array|null  $filterparams
     * @param string|null $exportfilename
     * @param string      $exportformat
     */
    public function __construct($uniqueid, $filterparams = null, $exportfilename = null, $exportformat = 'csv') {
        parent::__construct($uniqueid);

        $this->filterparams = $filterparams;

        if ($exportfilename) {
            $this->is_downloading($exportformat, $exportfilename);
        }

        $this->init();
    }

    /**
     * Initialize table
     */
    public function init() {
        // Define the list of columns to show.

        $tablecolumns = [];
        $tableheaders = [];
        if (!$this->is_downloading()) {
            $tablecolumns[] = 'select';
            $tableheaders[] = get_string('select') .
                '<div class="selectall"><label class="accesshide" for="selectall">' . get_string('selectall') . '</label>
                    <input type="checkbox" id="selectall" name="selectall" title="' . get_string('selectall') . '"/></div>';
        }

        $tablecolumns = array_merge($tablecolumns, [
            'id',
            'username',
            'module',
            'identifier',
            'timesubmitted',
            'type',
            'error_message',
            'error_code',
            'action'
        ]);

        // Define the titles of columns to show in header.
        $tableheaders = array_merge($tableheaders, [
            plagiarism_unicheck::trans('debugging:table:id'),
            get_string('user'),
            plagiarism_unicheck::trans('debugging:table:module'),
            plagiarism_unicheck::trans('debugging:table:identifier'),
            plagiarism_unicheck::trans('debugging:table:timesubmitted'),
            plagiarism_unicheck::trans('debugging:table:isarchive'),
            plagiarism_unicheck::trans('debugging:table:errormessage'),
            plagiarism_unicheck::trans('debugging:table:errorcode'),
            plagiarism_unicheck::trans('debugging:table:possibleoperations'),
        ]);

        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);
        $this->sortable(true, 'id', SORT_DESC);

        $fields = 'puf.*,m.name AS module, cm.course AS courseid, cm.instance AS cminstance, u.username';
        $from = '{plagiarism_unicheck_files} puf '
            . 'JOIN {user} u ON u.id = puf.userid AND u.deleted = 0 '
            . 'JOIN {course_modules} cm ON cm.id = puf.cm '
            . 'JOIN {modules} m ON  m.id=cm.module';

        $where = 'puf.parent_id IS NULL AND (puf.errorresponse IS NOT NULL OR puf.state = :state)';
        $params = [
            'state' => unicheck_file_state::HAS_ERROR
        ];

        if (isset($this->filterparams[self::ERRORMESSAGE_CONDITION]) && $this->filterparams[self::ERRORMESSAGE_CONDITION]) {
            $where .= " AND puf.errorresponse LIKE :errormessage";
            $params['errormessage'] = '%' . $this->filterparams[self::ERRORMESSAGE_CONDITION] . '%';
        }

        if (isset($this->filterparams[self::TIMESUBMITTED_FROM_CONDITION]) &&
            $this->filterparams[self::TIMESUBMITTED_FROM_CONDITION]) {
            $where .= " AND puf.timesubmitted >= :timesubmittedfrom";
            $params['timesubmittedfrom'] = $this->filterparams[self::TIMESUBMITTED_FROM_CONDITION];
        }

        if (isset($this->filterparams[self::TIMESUBMITTED_TO_CONDITION]) && $this->filterparams[self::TIMESUBMITTED_TO_CONDITION]) {
            $where .= " AND puf.timesubmitted <= :timesubmittedto";
            $params['timesubmittedto'] = $this->filterparams[self::TIMESUBMITTED_TO_CONDITION];
        }

        $this->no_sorting('select');
        $this->no_sorting('identifier');
        $this->no_sorting('error_code');
        $this->no_sorting('error_message');
        $this->no_sorting('select');
        $this->no_sorting('action');

        $this->set_sql($fields, $from, $where, $params);

        $this->set_attribute('class', 'generaltable generalbox');

        $this->show_download_buttons_at([TABLE_P_BOTTOM]);
        $this->define_baseurl(new moodle_url('/plagiarism/unicheck/debugging.php'));
    }

    /**
     * This function is called for each data row to allow processing of the id value.
     *
     * @param stdClass $row
     *
     * @return int
     */
    public function col_id(stdClass $row) {
        return $row->id;
    }

    /**
     * This function is called for each data row to allow processing of the username value.
     *
     * @param stdClass $row
     *
     * @return string
     */
    public function col_username(stdClass $row) {
        // If the data is being downloaded than we don't want to show HTML.
        if ($this->is_downloading()) {
            return $row->username;
        }

        return '<a href="/user/profile.php?id=' . $row->userid . '">' . $row->username . '</a>';
    }

    /**
     * This function is called for each data row to allow processing of the module value.
     *
     * @param stdClass $row
     *
     * @return string
     */
    public function col_module(stdClass $row) {

        $cmlink = $row->module . ' ' . $row->cm;

        if ($this->is_downloading()) {
            return $cmlink;
        }

        $coursemodule = get_coursemodule_from_id($row->module, $row->cm);
        if ($coursemodule) {
            $cmurl = new moodle_url("/mod/{$row->module}/view.php", ['id' => $coursemodule->id]);
            $cmlink = html_writer::link($cmurl, shorten_text($coursemodule->name, 40, true), ['title' => $coursemodule->name]);
        }

        return $cmlink;
    }

    /**
     * This function is called for each data row to allow processing of the identifier value.
     *
     * @param stdClass $row
     *
     * @return string
     */
    public function col_identifier(stdClass $row) {
        return $row->identifier;
    }

    /**
     * This function is called for each data row to allow processing of the type value.
     *
     * @param stdClass $row
     *
     * @return int
     */
    public function col_type(stdClass $row) {
        return $row->type == unicheck_plagiarism_entity::TYPE_ARCHIVE
            ? get_string('yes')
            : get_string('no');
    }

    /**
     * This function is called for each data row to allow processing of the error_message value.
     *
     * @param stdClass $row
     *
     * @return string
     */
    public function col_error_message(stdClass $row) {
        $error = json_decode($row->errorresponse, true);

        return $error[0]['message'];
    }

    /**
     * This function is called for each data row to allow processing of the error_code value.
     *
     * @param stdClass $row
     *
     * @return string
     */
    public function col_error_code(stdClass $row) {
        $error = json_decode($row->errorresponse, true);
        $errorcode = 'internal_error';
        if (isset($error[0]['error_code'])) {
            $errorcode = $error[0]['error_code'];
        }

        return $errorcode;
    }

    /**
     * This function is called for each data row to allow processing of the timesubmitted value.
     *
     * @param stdClass $row
     *
     * @return string
     */
    public function col_timesubmitted(stdClass $row) {
        return userdate($row->timesubmitted);
    }

    /**
     * This function is called for each data row to allow processing of the action value.
     *
     * @param stdClass $row
     *
     * @return string
     */
    public function col_action(stdClass $row) {

        $error = json_decode($row->errorresponse, true);
        $errorcode = 'internal_error';
        if (isset($error[0]['error_code'])) {
            $errorcode = $error[0]['error_code'];
        }

        if ($this->is_downloading()) {
            $operations = ['delete'];
            if ($row->type == unicheck_plagiarism_entity::TYPE_DOCUMENT && !file_error_code::is_consider_file_issue($errorcode)) {
                $operations[] = 'resubmit';
            }

            return implode(',', $operations);
        }

        $builddebuglink = function($row, $action) {
            $actionurl = new moodle_url('/plagiarism/unicheck/debugging.php', [
                'action'  => $action,
                'id'      => $row->id,
                'sesskey' => sesskey(),
            ]);

            if ($this->currpage) {
                $actionurl->param('page', $this->currpage);
            }

            $actiontext = plagiarism_unicheck::trans($action);

            return "<a href=\"{$actionurl}\">{$actiontext}</a>";

        };

        $operations = [$builddebuglink($row, 'delete')];
        if ($row->type == unicheck_plagiarism_entity::TYPE_DOCUMENT && !file_error_code::is_consider_file_issue($errorcode)) {
            $operations[] = $builddebuglink($row, 'resubmit');
        }

        return implode('|', $operations);
    }

    /**
     * This function is called for each data row to allow processing of the other_cols.
     *
     * @param stdClass $row
     *
     * @return string
     */

    /**
     * This function is called for each data row to allow processing of the other_cols.
     *
     * @param string   $colname
     * @param stdClass $row
     *
     * @return string|null
     */
    public function other_cols($colname, $row) {
        // For security reasons we don't want to show the password hash.
        if ($colname == 'password') {
            return "****";
        }
    }

    /**
     * Insert a checkbox for selecting the current row for batch operations.
     *
     * @param stdClass $row
     *
     * @return string
     */
    public function col_select(stdClass $row) {
        $selectcol = '<label class="accesshide" for="selectfile_' . $row->id . '">';
        $selectcol .= plagiarism_unicheck::trans('debugging:batchoperations:selectfile', $row->id);
        $selectcol .= '</label>';
        $selectcol .= '<input type="checkbox"
                              id="selectfile_' . $row->id . '"
                              name="selectedfiles"
                              value="' . $row->id . '"/>';

        return $selectcol;
    }
}