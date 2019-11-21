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
 * file_observer.class.php
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
use plagiarism_unicheck\classes\unicheck_adhoc;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_workshop;
use workshop;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class file_observer
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workshop_observer extends abstract_observer {

    /**
     * handle event
     *
     * @param unicheck_core $core
     * @param base          $event
     */
    public function phase_switched(unicheck_core $core, base $event) {
        if (empty($event->other['workshopphase'])) {
            return;
        }

        switch ($event->other['workshopphase']) {
            case workshop::PHASE_SUBMISSION:
                $this->submission_phase($core, $event);
                break;
            case workshop::PHASE_ASSESSMENT:
                $this->assessment_phase($core, $event);
                break;
        }
    }

    /**
     * handle Submission phase
     *
     * @param unicheck_core $core
     * @param base          $event
     */
    public function submission_phase(unicheck_core $core, base $event) {
        if (!$event->relateduserid) {
            $core->enable_teamsubmission();
        } else {
            $core->userid = $event->relateduserid;
        }

        $unplagfiles = plagiarism_unicheck::get_area_files($event->contextid, UNICHECK_WORKSHOP_FILES_AREA);
        $workshopfiles = unicheck_workshop::get_area_files($event->contextid);
        $files = array_merge($unplagfiles, $workshopfiles);

        $ids = [];
        foreach ($files as $file) {
            $plagiarismentity = $core->get_plagiarism_entity($file);
            $internalfile = $plagiarismentity->get_internal_file();
            $ids[] = $internalfile->id;
        }

        unicheck_file_provider::delete_by_ids($ids);
    }

    /**
     * handle Assessment phase
     *
     * @param unicheck_core $core
     * @param base          $event
     */
    public function assessment_phase(unicheck_core $core, base $event) {
        $unplagfiles = plagiarism_unicheck::get_area_files($event->contextid, UNICHECK_WORKSHOP_FILES_AREA);
        $workshopfiles = unicheck_workshop::get_area_files($event->contextid);
        $files = array_merge($unplagfiles, $workshopfiles);

        if (!empty($files)) {
            foreach ($files as $file) {
                $core->userid = $file->get_userid();
                unicheck_adhoc::upload($file, $core);
            }
        }
    }
}