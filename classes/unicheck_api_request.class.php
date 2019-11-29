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
 * unicheck_api_request.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

use plagiarism_unicheck\classes\services\api\api_regions;
use plagiarism_unicheck\event\api_called;
use plagiarism_unicheck\library\OAuth\OAuthConsumer;
use plagiarism_unicheck\library\OAuth\OAuthException;
use plagiarism_unicheck\library\OAuth\OAuthRequest;
use plagiarism_unicheck\library\OAuth\OAuthToken;
use plagiarism_unicheck\library\OAuth\Signature\OAuthSignatureMethod_HMAC_SHA1;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_api_request
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_api_request {
    /**
     * @var null|unicheck_api_request
     */
    private static $instance = null;
    /**
     * @var  string|array
     */
    private $requestdata;
    /**
     * @var  string
     */
    private $url;
    /**
     * @var  string
     */
    private $httpmethod = 'get';

    /**
     * @var \curl|null
     */
    private $lastcurl;

    /**
     * Get instance
     *
     * @return null|static
     */
    final public static function instance() {
        return isset(self::$instance) ? self::$instance : self::$instance = new unicheck_api_request();
    }

    /**
     * Set request method post
     *
     * @return $this
     */
    public function http_post() {
        $this->httpmethod = 'post';

        return $this;
    }

    /**
     * Set request method get
     *
     * @return $this
     */
    public function http_get() {
        $this->httpmethod = 'get';

        return $this;
    }

    /**
     * Make request
     *
     * @param string $method
     * @param array  $data
     *
     * @return \stdClass
     * @throws OAuthException
     */
    public function request($method, $data) {
        $this->set_request_data($data);
        $this->set_action($method);

        try {
            $domain = (new \moodle_url('/'))->get_host();
        } catch (\Exception $exception) {
            $domain = 'undefined';
        }

        $ch = new \curl();
        $ch->setHeader($this->gen_oauth_headers());
        $ch->setHeader('Content-Type: application/json');
        $ch->setHeader('Plugin-Identifier: ' . $domain);
        $ch->setHeader('Plugin-Version: ' . get_config(UNICHECK_PLAGIN_NAME, 'version'));
        $ch->setHeader('Plugin-Type: ' . UNICHECK_PLAGIN_NAME);
        $ch->setopt([
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ]);

        if (UNICHECK_DEBUG_MODE) {
            $ch->setopt([
                'CURLOPT_SSL_VERIFYHOST' => false,
                'CURLOPT_SSL_VERIFYPEER' => false,
            ]);
        }

        $response = $ch->{$this->httpmethod}($this->url, $this->get_request_data());

        $isapiloggingenable = unicheck_settings::get_settings('enable_api_logging');
        if ($isapiloggingenable) {
            api_called::create_log_message(
                unicheck_settings::get_settings('client_id'),
                $this->url,
                $data,
                $response,
                $ch->get_info()['http_code']
            )->trigger();
        }

        $this->lastcurl = $ch;

        return $this->handle_response($response);
    }

    /**
     * Set request data
     *
     * @param array $requestdata
     */
    private function set_request_data(&$requestdata) {
        if ($this->httpmethod === 'get') {
            $this->requestdata = $requestdata;
        } else {
            $this->requestdata = json_encode($requestdata);
        }
    }

    /**
     * Set action
     *
     * @param string $action
     */
    private function set_action($action) {

        $apiregion = unicheck_settings::get_current_region();

        $this->url = api_regions::get_api_base_url_by_region($apiregion) . $action;
    }

    /**
     * Generate oauth headers
     *
     * @return string
     * @throws OAuthException
     */
    private function gen_oauth_headers() {
        $oauthdata = [];
        if ($this->httpmethod == 'post') {
            $oauthdata['oauth_body_hash'] = $this->gen_oauth_body_hash();
        } else {
            $oauthdata = $this->get_request_data();
        }

        $oauthconsumer = new OAuthConsumer(
            unicheck_settings::get_settings('client_id'),
            unicheck_settings::get_settings('api_secret')
        );

        $oauthtoken = new OAuthToken($oauthconsumer, '');
        $oauthreq = OAuthRequest::from_consumer_and_token(
            $oauthconsumer, $oauthtoken, $this->httpmethod, $this->get_url(), $oauthdata
        );
        $oauthreq->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $oauthconsumer, $oauthtoken);

        return $oauthreq->to_header();
    }

    /**
     * gen_oauth_body_hash
     *
     * @return string
     */
    private function gen_oauth_body_hash() {
        return base64_encode(sha1($this->get_request_data(), true));
    }

    /**
     * get_request_data
     *
     * @return string
     */
    public function get_request_data() {
        return $this->requestdata;
    }

    /**
     * Get request url
     *
     * @return string
     */
    public function get_url() {
        return $this->url;
    }

    /**
     * Handle response
     *
     * @param string $resp
     *
     * @return \stdClass
     */
    private function handle_response($resp) {
        return json_decode($resp);
    }

    /**
     * Get last curl
     *
     * @return \curl|null
     */
    public function get_last_curl() {
        return $this->lastcurl;
    }
}