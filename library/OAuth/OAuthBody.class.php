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
 * OAuthBody.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\library\OAuth;

use plagiarism_unicheck\library\OAuth\DataStore\OAuthTrivialStore;
use plagiarism_unicheck\library\OAuth\Signature\OAuthSignatureMethod_HMAC_SHA1;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class OAuthBody
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OAuthBody {
    /**
     * get_oauth_key_from_headers
     *
     * @return null|string
     */
    public static function get_oauth_key_from_headers() {
        $requestheaders = OAuthUtil::get_headers();

        if (!isset($requestheaders['Authorization'])) {
            return null;
        }

        if (substr($requestheaders['Authorization'], 0, 6) == "OAuth ") {
            $headerparameters = OAuthUtil::split_header($requestheaders['Authorization']);

            return format_string($headerparameters['oauth_consumer_key']);
        }

        return null;
    }

    /**
     * handle_oauth_body_post
     *
     * @param string     $oauthconsumerkey
     * @param string     $oauthconsumersecret
     * @param string     $body
     * @param array|null $requestheaders
     *
     * @return mixed
     * @throws OAuthException
     */
    public static function handle_oauth_body_post($oauthconsumerkey, $oauthconsumersecret, $body, $requestheaders = null) {

        if ($requestheaders == null) {
            $requestheaders = OAuthUtil::get_headers();
        }

        // Must reject application/x-www-form-urlencoded.
        if (isset($requestheaders['Content-type'])) {
            if ($requestheaders['Content-type'] == 'application/x-www-form-urlencoded') {
                throw new OAuthException("OAuth request body signing must not use application/x-www-form-urlencoded");
            }
        }

        if (isset($requestheaders['Authorization'])) {
            if (substr($requestheaders['Authorization'], 0, 6) == "OAuth ") {
                $headerparameters = OAuthUtil::split_header($requestheaders['Authorization']);
                $oauthbodyhash = $headerparameters['oauth_body_hash'];
            }
        }

        if (!isset($oauthbodyhash)) {
            throw new OAuthException("OAuth request body signing requires oauth_body_hash body");
        }

        // Verify the message signature.
        $store = new OAuthTrivialStore();
        $store->add_consumer($oauthconsumerkey, $oauthconsumersecret);

        $server = new OAuthServer($store);

        $method = new OAuthSignatureMethod_HMAC_SHA1();
        $server->add_signature_method($method);
        $request = OAuthRequest::from_request();

        try {
            $server->verify_request($request);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new OAuthException("OAuth signature failed: " . $message);
        }

        $postdata = $body;

        $hash = base64_encode(sha1($postdata, true));

        if ($hash != $oauthbodyhash) {
            throw new OAuthException("OAuth oauth_body_hash mismatch");
        }

        return $postdata;
    }
}