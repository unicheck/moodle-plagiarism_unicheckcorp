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
 * OAuthDataStoreInterface.class.php
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
 * Class OAuthDataStoreInterface
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface OAuthDataStoreInterface {
    /**
     * Get OAuth consumer given its key
     *
     * @param string $consumerkey Consumer key
     *
     * @return OAuthConsumer|null  OAuthConsumer object
     */
    public function lookup_consumer($consumerkey);

    /**
     * lookup token
     *
     * @param OAuthConsumer $consumer
     * @param string        $tokentype
     * @param string        $token
     *
     * @return OAuthToken OAuthToken object
     */
    public function lookup_token($consumer, $tokentype, $token);

    /**
     * lookupnonce
     *
     * @param OAuthConsumer $consumer
     * @param string        $token
     * @param string        $nonce
     * @param int           $timestamp
     */
    public function lookup_nonce($consumer, $token, $nonce, $timestamp);

    /**
     * return a new token attached to this consumer
     *
     * @param OAuthConsumer $consumer
     * @param null          $callback
     */
    public function new_request_token($consumer, $callback = null);

    /**
     * return a new access token attached to this consumer
     * for the user associated with this token if the request token
     * is authorized
     * should also invalidate the request token
     *
     * @param OAuthToken    $token
     * @param OAuthConsumer $consumer
     * @param null          $verifier
     */
    public function new_access_token($token, $consumer, $verifier = null);
}
