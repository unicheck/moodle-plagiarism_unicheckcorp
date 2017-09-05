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

namespace plagiarism_unicheck\library\OAuth;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class OAuthConsumer
 *
 * @package plagiarism_unicheck\library\OAuth
 */
class OAuthConsumer {
    public $key;
    public $secret;

    /**
     * OAuthConsumer constructor.
     *
     * @param      $key
     * @param      $secret
     * @param null $callbackurl
     */
    public function __construct($key, $secret, $callbackurl = null) {
        $this->key = $key;
        $this->secret = $secret;
        $this->callback_url = $callbackurl;
    }

    /**
     * @return string
     */
    public function __toString() {
        return "OAuthConsumer[key=$this->key,secret=$this->secret]";
    }
}