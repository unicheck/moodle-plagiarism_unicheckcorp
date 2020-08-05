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
 * unicheck_check_api.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\services\api;

use plagiarism_unicheck\classes\unicheck_api;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_check_api
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_check_api {

    /**
     * Get checks throw API
     *
     * @param array $ids
     *
     * @return array
     */
    public function get_checks_by_ids($ids) {
        $completedchecks = [];
        $errorchecks = [];
        $apirequest = new unicheck_api();
        $checkprogresslist = $apirequest->get_check_progress($ids);
        if (!$checkprogresslist || !$checkprogresslist->result) {
            return [];
        }

        if (!isset($checkprogresslist->progress)) {
            return [];
        }

        foreach ($ids as $checkid) {
            if (isset($checkprogresslist->progress->$checkid)) {
                if ($checkprogresslist->progress->$checkid == 1) {
                    $checkresponse = $apirequest->get_check_data($checkid);
                    array_push($completedchecks, $checkresponse);
                }

                continue;
            }

            // If check not found set check to error state.
            $checkresponse = $apirequest->get_check_data($checkid);
            if (!$checkresponse->result && isset($checkresponse->errors[0]->error_code)) {
                $check = (object) ['id' => $checkid];
                $checkresponse->check = $check;
                array_push($errorchecks, $checkresponse);
            }
        }

        return [$completedchecks, $errorchecks];
    }
}