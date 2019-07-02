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
 * integration_api.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Andrew Chirskiy <a.chirskiy@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\services\api;

use plagiarism_unicheck\classes\unicheck_api_request;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class integration_api
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class integration_api {

    /**
     * Integration test
     */
    const INTEGRATION_TEST = 'integration/test';

    /**
     * Integration test
     *
     * @param string $callbackurl
     *
     * @return \stdClass
     */
    public function test($callbackurl) {
        $postdata = [
            'integration_type' => 'PLUGIN',
            'callback_url'     => $callbackurl
        ];

        return unicheck_api_request::instance()->http_post()->request(self::INTEGRATION_TEST, $postdata);
    }
}