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
 * unicheck_bulk_check_assign_files.class.php
 *
 * @package     plagiarism_unicheck
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\task;

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\services\storage\unicheck_file_state;
use plagiarism_unicheck\classes\unicheck_adhoc;
use plagiarism_unicheck\classes\unicheck_assign;
use plagiarism_unicheck\classes\unicheck_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_bulk_check_assign_files
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_bulk_check_assign_files extends unicheck_abstract_task {
    /**
     * Key of contextid data parameter
     */
    const CONTEXT_ID_KEY = 'contextid';

    /**
     * Key of cmid data parameter
     */
    const INSTANCE_ID_KEY = 'cmid';

    /** @var  unicheck_core */
    private $ucore;

    /**
     * Execute of adhoc task
     */
    public function execute() {
        $data = $this->get_custom_data();

        if (!is_object($data)) {
            return;
        }

        if (!property_exists($data, self::CONTEXT_ID_KEY) || !property_exists($data, self::INSTANCE_ID_KEY)) {
            return;
        }

        try {
            $contextid = $data->{self::CONTEXT_ID_KEY};
            $instanceid = $data->{self::INSTANCE_ID_KEY};

            $storedfiles = unicheck_assign::get_area_files($contextid);
            if (empty($storedfiles)) {
                mtrace("File not found in context with ID {$contextid}. Skipped");

                return;
            }

            foreach ($storedfiles as $storedfile) {
                if (unicheck_assign::is_draft($storedfile->get_itemid())) {
                    mtrace("File with ID {$storedfile->get_itemid()} is draft. Skipped");

                    continue;
                }

                $this->ucore = new unicheck_core($instanceid, $storedfile->get_userid(), $this->get_modname($data));
                $plagiarismfile = $this->get_plagiarism_file($storedfile);
                if (!$plagiarismfile || $plagiarismfile->state !== unicheck_file_state::CREATED) {
                    mtrace("File with ID {$storedfile->get_itemid()} can't processed. Skipped");

                    continue;
                }

                try {
                    if (\plagiarism_unicheck::is_archive($storedfile)) {
                        (new unicheck_archive($storedfile, $this->ucore))->upload();
                    } else {
                        unicheck_adhoc::upload($storedfile, $this->ucore);
                    }
                } catch (\Exception $exception) {
                    unicheck_file_provider::to_error_state($plagiarismfile, $exception->getMessage());
                }
            }
        } catch (\Exception $exception) {
            mtrace('Exception catched');
            mtrace('Message: ' . $exception->getMessage() . '. Task Data: ' . $this->get_custom_data_as_string());
        }
    }

    /**
     * Get plagiarism file
     *
     * @param \stored_file $storedfile
     * @return null|object
     */
    private function get_plagiarism_file(\stored_file $storedfile) {
        $plagiarismentity = $this->ucore->get_plagiarism_entity($storedfile);
        $plagiarismfile = $plagiarismentity->get_internal_file();
        if (!$plagiarismfile) {
            return null;
        }

        return $plagiarismfile;
    }
}