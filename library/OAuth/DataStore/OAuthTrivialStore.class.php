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
 * OAuthTrivialStore.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\library\OAuth\DataStore;

use plagiarism_unicheck\library\OAuth\OAuthConsumer;
use plagiarism_unicheck\library\OAuth\OAuthDataStoreInterface;
use plagiarism_unicheck\library\OAuth\OAuthToken;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class OAuthTrivialStore
 *
 * A Trivial memory-based store - no support for tokens.
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OAuthTrivialStore implements OAuthDataStoreInterface {
    /**
     * @var array $consumers Array of tool consumer keys and secrets
     */
    private $consumers = [];

    /**
     * Add a consumer to the array
     *
     * @param string $consumerkey    Consumer key
     * @param string $consumersecret Consumer secret
     */
    public function add_consumer($consumerkey, $consumersecret) {
        $this->consumers[$consumerkey] = $consumersecret;
    }

    /**
     * Get OAuth consumer given its key
     *
     * @param string $consumerkey Consumer key
     *
     * @return OAuthConsumer|null  OAuthConsumer object
     */
    public function lookup_consumer($consumerkey) {
        if (strpos($consumerkey, "http://") === 0) {
            $consumer = new OAuthConsumer($consumerkey, "secret", null);

            return $consumer;
        }
        if ($this->consumers[$consumerkey]) {
            $consumer = new OAuthConsumer($consumerkey, $this->consumers[$consumerkey], null);

            return $consumer;
        }

        return null;
    }

    /**
     * Create a dummy OAuthToken object for a consumer
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param string        $tokentype Type of token
     * @param string        $token     Token ID
     *
     * @return OAuthToken OAuthToken object
     */
    public function lookup_token($consumer, $tokentype, $token) {
        return new OAuthToken($consumer, '');
    }

    /**
     * Nonce values are not checked so just return a null
     *
     * @param OAuthConsumer $consumer  Consumer
     * @param string        $token     Token ID
     * @param string        $nonce     Nonce value
     * @param string        $timestamp Timestamp
     *
     * @return null
     */
    public function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        // Should add some clever logic to keep nonces from
        // being reused - for now we are really trusting
        // that the timestamp will save us.
        return null;
    }

    /**
     * Tokens are not used so just return a null.
     *
     * @param OAuthConsumer $consumer
     * @param null          $callback
     *
     * @return null
     */
    public function new_request_token($consumer, $callback = null) {
        return null;
    }

    /**
     * Tokens are not used so just return a null.
     *
     * @param string        $token
     * @param OAuthConsumer $consumer
     * @param null          $verifier
     *
     * @return null;
     */
    public function new_access_token($token, $consumer, $verifier = null) {
        return null;
    }
}