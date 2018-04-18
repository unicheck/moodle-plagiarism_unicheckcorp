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
 * File events.php
 *
 * @package     plagiarism_unicheck
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/*
 * Event observers
*/
$observers = [
    [
        'eventname' => '\assignsubmission_file\event\submission_updated',
        'callback'  => 'plagiarism_unicheck_observer::assignsubmission_file_submission_updated',
    ],
    [
        'eventname' => '\assignsubmission_file\event\assessable_uploaded',
        'callback'  => 'plagiarism_unicheck_observer::assignsubmission_file_assessable_uploaded',
    ],
    [
        'eventname' => '\assignsubmission_onlinetext\event\assessable_uploaded',
        'callback'  => 'plagiarism_unicheck_observer::assignsubmission_onlinetext_assessable_uploaded',
    ],
    [
        'eventname' => '\mod_forum\event\assessable_uploaded',
        'callback'  => 'plagiarism_unicheck_observer::mod_forum_assessable_uploaded',
    ],
    [
        'eventname' => '\mod_workshop\event\assessable_uploaded',
        'callback'  => 'plagiarism_unicheck_observer::mod_workshop_assessable_uploaded',
    ],
    [
        'eventname' => '\mod_assign\event\assessable_submitted',
        'callback'  => 'plagiarism_unicheck_observer::mod_assign_assessable_submitted',
    ],
    [
        'eventname' => '\mod_workshop\event\phase_switched',
        'callback'  => 'plagiarism_unicheck_observer::mod_workshop_phase_switched',
    ],
    [
        'eventname' => '\mod_assign\event\submission_status_updated',
        'callback'  => 'plagiarism_unicheck_observer::mod_assign_submission_status_updated',
    ],
    [
        'eventname' => '\mod_assign\event\submission_status_viewed',
        'callback'  => 'plagiarism_unicheck_observer::mod_assign_submission_status_viewed',
    ]
];