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
 * callback.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
define('NO_MOODLE_COOKIES', true);

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

use plagiarism_unicheck\classes\unicheck_callback;
use plagiarism_unicheck\classes\unicheck_core;
use plagiarism_unicheck\classes\unicheck_settings;
use plagiarism_unicheck\event\callback_accepted;
use plagiarism_unicheck\library\OAuth\OAuthBody;

try {
    $token = required_param('token', PARAM_ALPHANUMEXT);
    if (!$token) {
        header('HTTP/1.1 403 Token is absent');
        die;
    }

    $rawbody = file_get_contents('php://input');
    $oauthconsumerkey = unicheck_settings::get_settings('client_id');
    $oauthconsumersecret = unicheck_settings::get_settings('api_secret');

    $verifiedrawbody = OAuthBody::handle_oauth_body_post($oauthconsumerkey, $oauthconsumersecret, $rawbody);

    $body = unicheck_core::parse_json($verifiedrawbody);
    if (!is_object($body)) {
        http_response_code(400);
        echo 'Invalid callback body';

        die;
    }

    $callback = new unicheck_callback();

    $isapiloggingenable = unicheck_settings::get_settings('enable_api_logging');
    if ($isapiloggingenable) {
        callback_accepted::create_log_message($body, $token)->trigger();
    }

    $callback->handle($body, $token);

    echo 'OK';
} catch (Exception $exception) {
    http_response_code(400);

    throw $exception;
}
