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
 * File access.php
 *
 * @package     plagiarism_unicheck
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

$capabilities = [
    'plagiarism/unicheck:enable'                                       => [
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW
        ],
    ],
    'plagiarism/unicheck:viewsimilarity'                               => [
        'riskbitmask'  => RISK_PERSONAL,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PREVENT
        ],
    ],
    'plagiarism/unicheck:viewreport'                                   => [
        'riskbitmask'  => RISK_PERSONAL,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PREVENT
        ],
    ],
    'plagiarism/unicheck:vieweditreport'                               => [
        'riskbitmask'  => RISK_PERSONAL,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PREVENT
        ],
    ],
    'plagiarism/unicheck:resetfile'                                    => [
        'riskbitmask'  => RISK_PERSONAL,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
    'plagiarism/unicheck:checkfile'                                    => [
        'riskbitmask'  => RISK_PERSONAL,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'manager'        => CAP_ALLOW,
        ],
    ],
    'plagiarism/unicheck:changeenableunichecksetting'                  => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changecheckalreadysubmittedassignmentsetting' => [
        'riskbitmask'  => RISK_CONFIG | RISK_SPAM,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changeaddsubmissiontolibrarysetting'          => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changesourcesforcomparisonsetting'            => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changesensitivitypercentagesetting'           => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changewordsensitivitysetting'                 => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changeexcludecitationssetting'                => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changeshowstudentscoresetting'                => [
        'riskbitmask'  => RISK_CONFIG | RISK_SPAM,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changeshowstudentreportsetting'               => [
        'riskbitmask'  => RISK_CONFIG | RISK_SPAM,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changemaxsupportedarchivefilescountsetting'   => [
        'riskbitmask'  => RISK_CONFIG,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ],
    'plagiarism/unicheck:changesentstudentreportsetting'               => [
        'riskbitmask'  => RISK_CONFIG | RISK_SPAM,
        'captype'      => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => [
            'editingteacher' => CAP_ALLOW,
            'teacher'        => CAP_PREVENT,
            'manager'        => CAP_ALLOW,
            'coursecreator'  => CAP_ALLOW,
            'student'        => CAP_PROHIBIT
        ],
    ]
];
