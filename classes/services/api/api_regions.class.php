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
 * api_regions.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\services\api;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class api_regions
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_regions {
    /**
     * US East (N. Virginia)
     */
    const US_EAST_1 = 'us-east-1';
    /**
     * Europe (Frankfurt)
     */
    const EU_CENTRAL_1 = 'eu-central-1';
    /**
     * Asia Pacific (Sydney)
     */
    const AP_SOUTHEAST_2 = 'ap-southeast-2';

    /**
     * API base url mapped by regions
     *
     * @var array
     */
    private static $regionsapibaseurls = [
        self::US_EAST_1      => UNICHECK_API_URL,
        self::EU_CENTRAL_1   => UNICHECK_EU_API_URL,
        self::AP_SOUTHEAST_2 => UNICHECK_AU_API_URL
    ];

    /**
     * Host base url mapped by regions
     *
     * @var array
     */
    private static $regionsbaseurls = [
        self::US_EAST_1      => UNICHECK_CORP_DOMAIN,
        self::EU_CENTRAL_1   => UNICHECK_CORP_EU_DOMAIN,
        self::AP_SOUTHEAST_2 => UNICHECK_CORP_AU_DOMAIN
    ];

    /**
     * get_api_base_url_by_region
     *
     * @param string $region
     *
     * @return string API base URL
     */
    public static function get_api_base_url_by_region($region) {

        if (isset(self::$regionsapibaseurls[$region])) {
            return self::$regionsapibaseurls[$region];
        }

        // By default return US_EAST_1 API url.
        return self::$regionsapibaseurls[self::US_EAST_1];
    }

    /**
     * get_base_url_by_region
     *
     * @param string $region
     *
     * @return string API base URL
     */
    public static function get_base_url_by_region($region) {

        if (isset(self::$regionsbaseurls[$region])) {
            return self::$regionsbaseurls[$region];
        }

        // By default return US_EAST_1 API url.
        return self::$regionsbaseurls[self::US_EAST_1];
    }

    /**
     * Get api regions list
     *
     * @return array
     */
    public static function get_list() {
        $class = new \ReflectionClass(__CLASS__);

        return $class->getConstants();
    }
}