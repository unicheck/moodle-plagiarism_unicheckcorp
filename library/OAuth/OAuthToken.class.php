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
 * OAuthToken.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\library\OAuth;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class OAuthToken
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OAuthToken {
    /**
     * @var string
     */
    public $key;
    /**
     * @var string
     */
    public $secret;

    /**
     * OAuthToken constructor.
     *
     * @param string $key    the token
     * @param string $secret the token secret
     */
    public function __construct($key, $secret) {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Generates the basic string serialization of a token that a server
     * would respond to request_token and access_token calls with
     *
     * @return string
     */
    public function to_string() {
        return 'oauth_token=' .
            OAuthUtil::urlencode_rfc3986($this->key) .
            '&oauth_token_secret=' .
            OAuthUtil::urlencode_rfc3986($this->secret);
    }

    /**
     * String representation of current class
     *
     * @return string
     */
    public function __toString() {
        return $this->to_string();
    }
}