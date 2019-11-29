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
 * OAuthSignatureMethod_HMAC_SHA1.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\library\OAuth\Signature;

use plagiarism_unicheck\library\OAuth\OAuthConsumer;
use plagiarism_unicheck\library\OAuth\OAuthRequest;
use plagiarism_unicheck\library\OAuth\OAuthToken;
use plagiarism_unicheck\library\OAuth\OAuthUtil;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class OAuthSignatureMethod_HMAC_SHA1
 *
 * @package     plagiarism_unicheck
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {
    /**
     * Get method name
     *
     * @return string
     */
    public function get_name() {
        return "HMAC-SHA1";
    }

    /**
     * Build signature
     *
     * @param OAuthRequest  $request
     * @param OAuthConsumer $consumer
     * @param OAuthToken    $token
     *
     * @return string
     */
    public function build_signature(OAuthRequest $request, $consumer, $token) {
        $basestring = $request->get_signature_base_string();
        $request->basestring = $basestring;

        $keyparts = [
            $consumer->secret,
            ($token) ? $token->secret : ""
        ];

        $keyparts = OAuthUtil::urlencode_rfc3986($keyparts);
        $key = implode('&', $keyparts);

        $computedsignature = base64_encode(hash_hmac('sha1', $basestring, $key, true));

        return $computedsignature;
    }
}