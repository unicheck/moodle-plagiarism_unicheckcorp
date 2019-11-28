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
 * assessable_observer.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\observers;

use core\event\base;
use plagiarism_unicheck;
use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\unicheck_adhoc;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class assessable_observer
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessable_observer extends abstract_observer {
    /**
     * handle event
     *
     * @param unicheck_core $core
     * @param base          $event
     */
    public function submitted(unicheck_core $core, base $event) {
        $submission = unicheck_assign::get_user_submission_by_cmid($event->contextinstanceid);
        if (!$submission) {
            return;
        }

        $assign = unicheck_assign::get($submission->assignment);
        if ((bool) $assign->teamsubmission) {
            /* All users of group must confirm submission */
            if ((bool) $assign->requireallteammemberssubmit && !$this->all_users_confirm_submition($assign)) {
                return;
            }

            $core->enable_teamsubmission();
        }

        $submissionid = (!empty($submission->id) ? $submission->id : false);

        $ufiles = plagiarism_unicheck::get_area_files($event->contextid, UNICHECK_DEFAULT_FILES_AREA, $submissionid);
        $assignfiles = unicheck_assign::get_area_files($event->contextid, $submissionid);

        /** @var \stored_file[] $files */
        $files = array_merge($ufiles, $assignfiles);
        if (!empty($files)) {
            foreach ($files as $file) {
                try {
                    if (\plagiarism_unicheck::is_archive($file)) {
                        (new unicheck_archive($file, $core))->upload();
                        continue;
                    }

                    unicheck_adhoc::upload($file, $core);
                } catch (\Exception $exception) {
                    unicheck_file_provider::to_error_state_by_pathnamehash($file->get_pathnamehash(), $exception->getMessage());
                }
            }
        }
    }
}