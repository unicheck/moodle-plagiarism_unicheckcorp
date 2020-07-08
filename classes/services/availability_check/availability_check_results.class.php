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
 * availability_check_results.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\services\availability_check;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class availability_check_results
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_check_results {
    /**
     * FAILED
     */
    const FAILED = 'failed';

    /**
     * @var string Which are we checking (database, php, php_extension, php_extension)
     */
    public $part;
    /**
     * @var bool true means the test passed and all is OK. false means it failed.
     */
    public $status;
    /**
     * @var string|null See constants at the beginning of the file
     */
    public $errorcode;
    /**
     * @var string Aux. info (DB vendor, library...)
     */
    public $info;
    /**
     * @var string String to show on error|on check|on ok
     */
    public $feedbackstr;
    /**
     * @var string String to show if some bypass has happened
     */
    public $bypassstr;
    /**
     * @var string String to show if some restrict has happened
     */
    public $restrictstr;

    /**
     * Constructor of the environment_result class. Just set default values
     *
     * @param string $part
     */
    public function __construct($part) {
        $this->part = $part;
        $this->status = true;
        $this->errorcode = null;
        $this->info = '';
        $this->feedbackstr = '';
        $this->bypassstr = '';
        $this->restrictstr = '';
    }

    /**
     * Set the status
     *
     * @param bool $testpassed true means the test passed and all is OK. false means it failed.
     */
    public function set_status($testpassed) {
        $this->status = $testpassed;
        if ($testpassed) {
            $this->set_errorcode(null);
        }
    }

    /**
     * Set the error_code
     *
     * @param string|null $errorcode the error code (see constants above)
     */
    public function set_errorcode($errorcode) {
        $this->errorcode = $errorcode;
    }

    /**
     * Set the auxiliary info
     *
     * @param string $info the auxiliary info
     */
    public function set_info($info) {
        $this->info = $info;
    }

    /**
     * Set the feedback string
     *
     * @param mixed $str the feedback string that will be fetched from the admin lang file.
     *                   pass just the string or pass an array of params for get_string
     *                   You always should put your string in admin.php but a third param is useful
     *                   to pass an $a object / string to get_string
     */
    public function set_feedbackstr($str) {
        $this->feedbackstr = $str;
    }

    /**
     * Set the bypass string
     *
     * @param string $str the bypass string that will be fetched from the admin lang file.
     *                    pass just the string or pass an array of params for get_string
     *                    You always should put your string in admin.php but a third param is useful
     *                    to pass an $a object / string to get_string
     */
    public function set_bypassstr($str) {
        $this->bypassstr = $str;
    }

    /**
     * Set the restrict string
     *
     * @param string $str the restrict string that will be fetched from the admin lang file.
     *                    pass just the string or pass an array of params for get_string
     *                    You always should put your string in admin.php but a third param is useful
     *                    to pass an $a object / string to get_string
     */
    public function set_restrictstr($str) {
        $this->restrictstr = $str;
    }

    /**
     * Get the status
     *
     * @return bool true means the test passed and all is OK. false means it failed.
     */
    public function is_passed() {
        return $this->status;
    }

    /**
     * Get the error code
     *
     * @return integer error code
     */
    public function get_errorcode() {
        return $this->errorcode;
    }

    /**
     * Get the aux info
     *
     * @return string info
     */
    public function get_info() {
        return $this->info;
    }

    /**
     * Get the part this result belongs to
     *
     * @return string part
     */
    public function get_part() {
        return $this->part;
    }

    /**
     * Get the feedback string
     *
     * @return mixed feedback string (can be an array of params for get_string or a single string to fetch from
     *                  admin.php lang file).
     */
    public function get_feedbackstr() {
        return $this->feedbackstr;
    }

    /**
     * Get the bypass string
     *
     * @return mixed bypass string (can be an array of params for get_string or a single string to fetch from
     *                  admin.php lang file).
     */
    public function get_bypassstr() {
        return $this->bypassstr;
    }

    /**
     * Get the restrict string
     *
     * @return mixed restrict string (can be an array of params for get_string or a single string to fetch from
     *                  admin.php lang file).
     */
    public function get_restrictstr() {
        return $this->restrictstr;
    }

    /**
     * str_to_report
     *
     * @param mixed  $string params for get_string, either a string to fetch from admin.php or an array of
     *                       params for get_string.
     * @param string $class  css class(es) for message.
     *
     * @return string feedback string fetched from lang file wrapped in p tag with class $class or returns
     *                              empty string if $string is empty.
     *
     */
    public function str_to_report($string, $class) {
        if (!empty($string)) {
            return '<p class="' . s($class) . '">' . s($string) . '</p>';
        } else {
            return '';
        }
    }
}