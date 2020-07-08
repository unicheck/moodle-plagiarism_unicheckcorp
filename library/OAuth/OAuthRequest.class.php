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
 * OAuthRequest.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\library\OAuth;

use plagiarism_unicheck\library\OAuth\Signature\OAuthSignatureMethod;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class OAuthRequest
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OAuthRequest {
    /** @var array|null $parameters */
    protected $parameters;
    /** @var string|null $httpmethod */
    protected $httpmethod;
    /** @var string|null $httpurl */
    protected $httpurl;
    /** @var string|null $basestring for debug purposes */
    public $basestring;
    /** @var string $version */
    public static $version = '1.0';
    /** @var string $postinput */
    public static $postinput = 'php://input';

    /**
     * OAuthRequest constructor.
     *
     * @param string $httpmethod
     * @param string $httpurl
     * @param null   $parameters
     */
    public function __construct($httpmethod, $httpurl, $parameters = null) {
        $parameters = ($parameters) ? $parameters : [];
        $parameters = array_merge(OAuthUtil::parse_parameters(parse_url($httpurl, PHP_URL_QUERY)), $parameters);
        $this->parameters = $parameters;
        $this->httpmethod = $httpmethod;
        $this->httpurl = $httpurl;
    }

    /**
     * attempt to build up a request from what was passed to the server
     *
     * @param string|null $httpmethod
     * @param string|null $httpurl
     * @param array|null  $parameters
     *
     * @return OAuthRequest
     */
    public static function from_request($httpmethod = null, $httpurl = null, $parameters = null) {
        $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
            ? 'http'
            : 'https';

        $httpurl = ($httpurl)
            ? $httpurl
            : $scheme .
            '://' . $_SERVER['SERVER_NAME'] .
            ':' .
            $_SERVER['SERVER_PORT'] .
            $_SERVER['REQUEST_URI'];

        $httpmethod = ($httpmethod) ? $httpmethod : $_SERVER['REQUEST_METHOD'];

        // We weren't handed any parameters, so let's find the ones relevant to
        // this request.
        // If you run XML-RPC or similar you should use this to provide your own
        // parsed parameter-list.
        if (!$parameters) {
            // Find request headers.
            $requestheaders = OAuthUtil::get_headers();

            // Parse the query-string to find GET parameters.
            if (isset($_SERVER['QUERY_STRING'])) {
                $parameters = OAuthUtil::parse_parameters($_SERVER['QUERY_STRING']);
            } else {
                $parameters = [];
            }

            // It's a POST request of the proper content-type, so parse POST
            // parameters and add those overriding any duplicates from GET.
            if ($httpmethod == "POST"
                && isset($requestheaders['Content-Type'])
                && strstr($requestheaders['Content-Type'], 'application/x-www-form-urlencoded')) {
                $postdata = OAuthUtil::parse_parameters(file_get_contents(self::$postinput));
                $parameters = array_merge($parameters, $postdata);
            }

            // We have a Authorization-header with OAuth data. Parse the header
            // and add those overriding any duplicates from GET or POST.
            if (isset($requestheaders['Authorization']) && substr($requestheaders['Authorization'], 0, 6) == 'OAuth ') {
                $headerparameters = OAuthUtil::split_header($requestheaders['Authorization']);
                $parameters = array_merge($parameters, $headerparameters);
            }
        }

        return new OAuthRequest($httpmethod, $httpurl, $parameters);
    }

    /**
     * pretty much a helper function to set up the request
     *
     * @param OAuthConsumer   $consumer
     * @param OAuthToken|null $token
     * @param string          $httpmethod
     * @param string          $httpurl
     * @param array|null      $parameters
     *
     * @return OAuthRequest
     */
    public static function from_consumer_and_token(
        OAuthConsumer $consumer,
        $token,
        $httpmethod,
        $httpurl,
        $parameters = null
    ) {

        $parameters = ($parameters) ? $parameters : [];
        $defaults = [
            'oauth_version'      => self::$version,
            'oauth_nonce'        => self::generate_nonce(),
            'oauth_timestamp'    => self::generate_timestamp(),
            'oauth_consumer_key' => $consumer->key
        ];
        if ($token) {
            $defaults['oauth_token'] = $token->key;
        }

        $parameters = array_merge($defaults, $parameters);

        return new OAuthRequest($httpmethod, $httpurl, $parameters);

    }

    /**
     * set_parameter
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $allowduplicates
     */
    public function set_parameter($name, $value, $allowduplicates = true) {

        if ($allowduplicates && isset($this->parameters[$name])) {
            // We have already added parameter(s) with this name, so add to the list.
            if (is_scalar($this->parameters[$name])) {
                // This is the first duplicate, so transform scalar (string)
                // into an array so we can add the duplicates.
                $this->parameters[$name] = [$this->parameters[$name]];
            }

            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * get_parameter
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public function get_parameter($name) {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * get_parameters
     *
     * @return array|null
     */
    public function get_parameters() {
        return $this->parameters;
    }

    /**
     * unset_parameter
     *
     * @param string $name
     */
    public function unset_parameter($name) {
        unset($this->parameters[$name]);
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     *
     * @return string
     */
    public function get_signable_parameters() {

        // Grab all parameters.
        $params = $this->parameters;

        // Remove oauth_signature if present.
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return OAuthUtil::build_http_query($params);

    }

    /**
     * Returns the base string of this request
     *
     * The base string defined as the method, the url
     * and the parameters (normalized), each urlencoded
     * and the concated with &.
     *
     * @return string
     */
    public function get_signature_base_string() {
        $parts = [
            $this->get_normalized_http_method(),
            $this->get_normalized_http_url(),
            $this->get_signable_parameters()
        ];

        $parts = OAuthUtil::urlencode_rfc3986($parts);

        return implode('&', $parts);

    }

    /**
     * just uppercases the http method
     *
     * @return string
     */
    public function get_normalized_http_method() {
        return strtoupper($this->httpmethod);
    }

    /**
     * parses the url and rebuilds it to be
     * scheme://host/path
     *
     * @return string
     */
    public function get_normalized_http_url() {

        $parts = parse_url($this->httpurl);

        $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
        $port = (isset($parts['port'])) ? $parts['port'] : (($scheme == 'https') ? '443' : '80');
        $host = (isset($parts['host'])) ? strtolower($parts['host']) : '';
        $path = (isset($parts['path'])) ? $parts['path'] : '';

        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }

        return "$scheme://$host$path";

    }

    /**
     * builds a url usable for a GET request
     *
     * @return string
     */
    public function to_url() {

        $postdata = $this->to_postdata();
        $out = $this->get_normalized_http_url();
        if ($postdata) {
            $out .= '?' . $postdata;
        }

        return $out;
    }

    /**
     * builds the data one would send in a POST request
     *
     * @return string
     */
    public function to_postdata() {
        return OAuthUtil::build_http_query($this->parameters);
    }

    /**
     * builds the Authorization: header
     *
     * @param string|null $realm
     *
     * @return string
     * @throws OAuthException
     */
    public function to_header($realm = null) {

        $first = true;
        if ($realm) {
            $out = 'Authorization: OAuth realm="' . OAuthUtil::urlencode_rfc3986($realm) . '"';
            $first = false;
        } else {
            $out = 'Authorization: OAuth';
        }

        foreach ($this->parameters as $k => $v) {
            if (substr($k, 0, 5) != "oauth") {
                continue;
            }
            if (is_array($v)) {
                throw new OAuthException('Arrays not supported in headers');
            }
            $out .= ($first) ? ' ' : ',';
            $out .= OAuthUtil::urlencode_rfc3986($k) .
                '="' .
                OAuthUtil::urlencode_rfc3986($v) .
                '"';
            $first = false;
        }

        return $out;

    }

    /**
     * String representation of current class
     *
     * @return string
     */
    public function __toString() {
        return $this->to_url();
    }

    /**
     * sign_request
     *
     * @param OAuthSignatureMethod $signaturemethod
     * @param OAuthConsumer        $consumer
     * @param OAuthToken           $token
     */
    public function sign_request(OAuthSignatureMethod $signaturemethod, $consumer, $token) {

        $this->set_parameter(
            "oauth_signature_method",
            $signaturemethod->get_name(),
            false
        );
        $signature = $this->build_signature($signaturemethod, $consumer, $token);
        $this->set_parameter("oauth_signature", $signature, false);
    }

    /**
     * build_signature
     *
     * @param OAuthSignatureMethod $signaturemethod
     * @param OAuthConsumer        $consumer
     * @param OAuthToken           $token
     *
     * @return string
     */
    public function build_signature(OAuthSignatureMethod $signaturemethod, $consumer, $token) {
        $signature = $signaturemethod->build_signature($this, $consumer, $token);

        return $signature;
    }

    /**
     * util function: current timestamp
     *
     * @return int
     */
    private static function generate_timestamp() {
        return time();
    }

    /**
     * util function: current nonce
     *
     * @return string
     */
    private static function generate_nonce() {
        $mt = microtime();
        $rand = mt_rand();

        return md5($mt . $rand);
    }
}
