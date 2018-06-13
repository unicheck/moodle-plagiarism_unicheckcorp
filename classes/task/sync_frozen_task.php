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
 * sync_frozen_task.php
 *
 * @package     plagiarism_unicheck
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\task;

use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\services\api\unicheck_check_api;
use plagiarism_unicheck\classes\services\api\unicheck_file_api;
use plagiarism_unicheck\classes\unicheck_adhoc;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Get global class.
global $CFG;

require_once($CFG->dirroot . '/plagiarism/unicheck/autoloader.php');
require_once($CFG->dirroot . '/plagiarism/unicheck/constants.php');

/**
 * Class failed_task
 *
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_frozen_task extends \core\task\scheduled_task
{
    const CHECK = 'frozen_check';
    const FILE  = 'frozen_file';

    /**
     * @return string
     * @throws \coding_exception
     */
    public function get_name()
    {
        return get_string('sync_failed', 'plagiarism_unicheck');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute()
    {
        $files = [
            self::FILE        => [],
            self::CHECK       => []
        ];

        $frozenfiles = unicheck_file_provider::get_frozen_files();
        if ($frozenfiles) {
            foreach ($frozenfiles as $id => $file) {
                if (!is_null($file->check_id)) {
                    $files[self::CHECK][$file->check_id] = $file;
                } else if (!is_null($file->external_file_id)) {
                    $files[self::FILE][$id] = $file;
                }
            }
        }

        if ($files[self::CHECK]) {
            $checkservice = new unicheck_check_api();
            $cheklist = $checkservice->get_finished_check_by_ids(array_keys($files[self::CHECK]));

            if ($cheklist) {
                $this->fix_check($cheklist, $files[self::CHECK]);
            }
        }

        if ($files[self::FILE]) {
            $checkservice = new unicheck_file_api();
            $filelist = $checkservice->get_uploaded_file_by_dbfiles($files[self::FILE]);
            if ($filelist) {
                $this->fix_file($filelist, $files[self::FILE]);
            }
        }
    }

    /**
     * @param array $apicheklist
     * @param array $dbchecklist
     */
    protected function fix_check($externalcheklist, $dbchecklist) {
        foreach ($externalcheklist as $externalcheck) {
            if (isset($dbchecklist[$externalcheck->check->id])) {
                unicheck_file_provider::update_frozen_check(
                    $dbchecklist[$externalcheck->check->id],
                    $externalcheck->check
                );
            }
        }
    }

    /**
     * @param array $externalfiles
     * @param array $dbfiles
     */
    protected function fix_file($externalfiles, $dbfiles) {
        if ($externalfiles[unicheck_file_api::FOR_UPDATE]) {
            foreach ($externalfiles[unicheck_file_api::FOR_UPDATE] as $key => $check) {
                unicheck_file_provider::update_frozen_check(
                    $dbfiles[$key],
                    $check
                );
            }
        }

        if ($externalfiles[unicheck_file_api::FOR_CREATE]) {
            foreach ($externalfiles[unicheck_file_api::FOR_CREATE] as $file) {
                unicheck_adhoc::check($file);
            }
        }
    }
}