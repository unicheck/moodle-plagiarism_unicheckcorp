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
 * api_called.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   2018 UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\event;

use core\event\base;
use plagiarism_unicheck;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/plagiarism/unicheck/lib.php');

/**
 * Class api_called
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 *
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Moodle 3.3
 */
class api_called extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->context = \context_system::instance();
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public static function get_name() {
        return plagiarism_unicheck::trans('event:api_called');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $apiurl = isset($this->other['api_url']) ? s($this->other['api_url']) : '?';
        $apikey = isset($this->other['api_key']) ? s($this->other['api_key']) : '-';
        $requestdata = [];
        if (isset($this->other['request_data'])) {
            $requestdata = s(json_encode($this->other['request_data']));
        }
        $responsecode = isset($this->other['response_code']) ? (int) $this->other['response_code'] : '?';
        $response = isset($this->other['response_data']) ? s($this->other['response_data']) : '';

        $message = <<<HTML
                URL: $apiurl<br>
                API key: $apikey<br>
                Request data: $requestdata<br>
                Response code: $responsecode<br>
                Response: $response
HTML;

        return $message;
    }

    /**
     * Creates the event object.
     *
     * @param string $apikey
     * @param string $apiurl
     * @param array  $requestdata
     * @param string $responsedata
     * @param int    $responsecode
     *
     * @return base
     */
    public static function create_log_message($apikey, $apiurl, $requestdata, $responsedata, $responsecode = 200) {

        if (is_array($requestdata) && isset($requestdata['file_data'])) {
            $requestdata['file_data'] = 'base64 encoding of file';
        }

        return self::create([
                'other' => [
                    'api_key'       => $apikey,
                    'api_url'       => $apiurl,
                    'request_data'  => $requestdata,
                    'response_data' => $responsedata,
                    'response_code' => $responsecode,

                ]
            ]
        );
    }
}