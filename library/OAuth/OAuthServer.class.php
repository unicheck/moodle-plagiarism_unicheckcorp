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

use plagiarism_unicheck\library\OAuth\Signature\OAuthSignatureMethod;

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
class OAuthServer {
    /**
     * @var int $timestampthreshold
     */
    protected $timestampthreshold = 300; // In seconds, five minutes.
    /**
     * @var string $version
     */
    protected $version = '1.0';
    /**
     * @var array $signaturemethods
     */
    protected $signaturemethods = [];

    /**
     * @var OAuthDataStoreInterface $datastore
     */
    protected $datastore;

    /**
     * OAuthServer constructor.
     *
     * @param OAuthDataStoreInterface $datastore
     */
    public function __construct($datastore) {
        $this->datastore = $datastore;
    }

    /**
     * add_signature_method
     *
     * @param OAuthSignatureMethod $signaturemethod
     */
    public function add_signature_method(OAuthSignatureMethod $signaturemethod) {
        $this->signaturemethods[$signaturemethod->get_name()] = $signaturemethod;
    }

    /**
     * process a request_token request
     * returns the request token on success
     *
     * @param OAuthRequest $request
     *
     * @return string
     * @throws OAuthException
     */
    public function fetch_request_token(OAuthRequest &$request) {

        $this->get_version($request);

        $consumer = $this->get_consumer($request);

        // No token required for the initial token request.
        $token = null;

        $this->check_signature($request, $consumer, $token);

        // Rev A change.
        $callback = $request->get_parameter('oauth_callback');
        $newtoken = $this->datastore->new_request_token($consumer, $callback);

        return $newtoken;

    }

    /**
     * process an access_token request
     * returns the access token on success
     *
     * @param OAuthRequest $request
     *
     * @return string
     * @throws OAuthException
     */
    public function fetch_access_token(OAuthRequest &$request) {

        $this->get_version($request);

        $consumer = $this->get_consumer($request);

        // Requires authorized request token.
        $token = $this->get_token($request, $consumer, "request");

        $this->check_signature($request, $consumer, $token);

        // Rev A change.
        $verifier = $request->get_parameter('oauth_verifier');
        $newtoken = $this->datastore->new_access_token($token, $consumer, $verifier);

        return $newtoken;

    }

    /**
     * verify an api call, checks all the parameters
     *
     * @param OAuthRequest $request
     *
     * @return array
     * @throws OAuthException
     */
    public function verify_request(OAuthRequest &$request) {

        $this->get_version($request);
        $consumer = $this->get_consumer($request);
        $token = $this->get_token($request, $consumer, "access");
        $this->check_signature($request, $consumer, $token);

        return [$consumer, $token];

    }

    /**
     * get_version
     *
     * @param OAuthRequest $request
     *
     * @return string
     * @throws OAuthException
     */
    private function get_version(OAuthRequest &$request) {

        $version = $request->get_parameter("oauth_version");
        if (!$version) {
            // Service Providers MUST assume the protocol version to be 1.0 if this parameter is not present.
            $version = '1.0';
        }
        if ($version !== $this->version) {
            throw new OAuthException("OAuth version '$version' not supported");
        }

        return $version;

    }

    /**
     * figure out the signature with some defaults
     *
     * @param OAuthRequest|null $request
     *
     * @return OAuthSignatureMethod
     * @throws OAuthException
     */
    private function get_signature_method($request) {

        $signaturemethod = $request instanceof OAuthRequest
            ? $request->get_parameter('oauth_signature_method') : null;

        if (!$signaturemethod) {
            // According to chapter 7 ("Accessing Protected Ressources") the signature-method
            // parameter is required, and we can't just fallback to PLAINTEXT.
            throw new OAuthException('No signature method parameter. This parameter is required');
        }

        if (!in_array($signaturemethod,
            array_keys($this->signaturemethods))) {
            throw new OAuthException(
                "Signature method '$signaturemethod' not supported " .
                'try one of the following: ' .
                implode(', ', array_keys($this->signaturemethods))
            );
        }

        return $this->signaturemethods[$signaturemethod];

    }

    /**
     * try to find the consumer for the provided request's consumer key
     *
     * @param OAuthRequest|null $request
     *
     * @return OAuthConsumer|null
     * @throws OAuthException
     */
    private function get_consumer($request) {

        $consumerkey = $request instanceof OAuthRequest
            ? $request->get_parameter('oauth_consumer_key') : null;

        if (!$consumerkey) {
            throw new OAuthException('Invalid consumer key');
        }

        $consumer = $this->datastore->lookup_consumer($consumerkey);
        if (!$consumer) {
            throw new OAuthException('Invalid consumer');
        }

        return $consumer;

    }

    /**
     * try to find the token for the provided request's token key
     *
     * @param OAuthRequest|null  $request
     * @param OAuthConsumer|null $consumer
     * @param string             $tokentype
     *
     * @return OAuthToken
     * @throws OAuthException
     */
    private function get_token($request, $consumer, $tokentype = "access") {

        $tokenfield = $request instanceof OAuthRequest
            ? $request->get_parameter('oauth_token') : null;

        $token = $this->datastore->lookup_token($consumer, $tokentype, $tokenfield);
        if (!$token) {
            throw new OAuthException("Invalid $tokentype token: $tokenfield");
        }

        return $token;

    }

    /**
     * all-in-one function to check the signature on a request
     * should guess the signature method appropriately
     *
     * @param OAuthRequest|null  $request
     * @param OAuthConsumer|null $consumer
     * @param OAuthToken         $token
     *
     * @throws OAuthException
     */
    private function check_signature($request, $consumer, $token) {

        // This should probably be in a different method.
        $timestamp = $request instanceof OAuthRequest
            ? $request->get_parameter('oauth_timestamp')
            : null;
        $nonce = $request instanceof OAuthRequest
            ? $request->get_parameter('oauth_nonce')
            : null;

        $this->check_timestamp($timestamp);
        $this->check_nonce($consumer, $token, $nonce, $timestamp);

        $signaturemethod = $this->get_signature_method($request);

        $signature = $request->get_parameter('oauth_signature');
        $validsig = $signaturemethod->check_signature($request, $consumer, $token, $signature);

        if (!$validsig) {
            throw new OAuthException('Invalid signature');
        }
    }

    /**
     * check that the timestamp is new enough
     *
     * @param int $timestamp
     *
     * @throws OAuthException
     */
    private function check_timestamp($timestamp) {
        if (!$timestamp) {
            throw new OAuthException('Missing timestamp parameter. The parameter is required');
        }

        // Verify that timestamp is recentish.
        $now = time();
        if (abs($now - $timestamp) > $this->timestampthreshold) {
            throw new OAuthException("Expired timestamp, yours $timestamp, ours $now");
        }
    }

    /**
     * check that the nonce is not repeated
     *
     * @param OAuthConsumer $consumer
     * @param OAuthToken    $token
     * @param string        $nonce
     * @param int           $timestamp
     *
     * @throws OAuthException
     */
    private function check_nonce($consumer, $token, $nonce, $timestamp) {

        if (!$nonce) {
            throw new OAuthException('Missing nonce parameter. The parameter is required');
        }

        // Verify that the nonce is uniqueish.
        $found = $this->datastore->lookup_nonce($consumer, $token, $nonce, $timestamp);
        if ($found) {
            throw new OAuthException("Nonce already used: $nonce");
        }
    }
}
