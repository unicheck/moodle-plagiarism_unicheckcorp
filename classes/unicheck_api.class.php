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
 * unicheck_api.class.php - SDK for working with unicheck api.
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_api
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_api {
    /**
     * ACCESS_SCOPE_WRITE
     */
    const ACCESS_SCOPE_WRITE = 'w';
    /**
     * ACCESS_SCOPE_READ
     */
    const ACCESS_SCOPE_READ = 'r';
    /**
     * CHECK_PROGRESS
     */
    const CHECK_PROGRESS = 'check/progress';
    /**
     * CHECK_GET
     */
    const CHECK_GET = 'check/get';
    /**
     * FILE_UPLOAD
     */
    const FILE_UPLOAD = 'file/async_upload';
    /**
     * TRACK_UPLOAD
     */
    const TRACK_UPLOAD = 'file/trackfileupload';
    /**
     * CHECK_CREATE
     */
    const CHECK_CREATE = 'check/create';
    /**
     * CHECK_DELETE
     */
    const CHECK_DELETE = 'check/delete';
    /**
     * USER_CREATE
     */
    const USER_CREATE = 'user/create';
    /**
     * Get supported similarity check source types API url
     */
    const GET_SUPPORTED_SEARCH_TYPES = 'check/get_supported_search_types';

    /**
     * @var null|unicheck_api
     */
    private static $instance = null;

    /**
     * Get instance
     *
     * @return null|static
     */
    final public static function instance() {
        return isset(self::$instance) ? self::$instance : self::$instance = new unicheck_api();
    }

    /**
     * Upload file
     *
     * @param string|resource $content
     * @param string          $filename
     * @param string          $format
     * @param integer         $cmid
     * @param object|null     $owner
     * @param \stdClass       $internalfile
     *
     * @return \stdClass
     */
    public function upload_file(&$content, $filename, $format = 'html', $cmid, $owner = null, $internalfile) {
        global $CFG;

        if (is_resource($content)) {
            $content = stream_get_contents($content);
        }

        $postdata = [
            'format'       => strtolower($format),
            'file_data'    => base64_encode($content),
            'name'         => $filename,
            'callback_url' => sprintf(
                '%1$s%2$s?token=%3$s', $CFG->wwwroot, UNICHECK_CALLBACK_URL, $internalfile->identifier
            ),
            'options'      => [
                'utoken'        => unicheck_core::get_external_token($cmid, $owner),
                'submission_id' => $cmid,
            ],
        ];

        $content = null;

        if ($mustindex = unicheck_settings::get_activity_settings($cmid, unicheck_settings::ADD_TO_INSTITUTIONAL_LIBRARY)) {
            $postdata['options']['no_index'] = !$mustindex;
        }

        $response = unicheck_api_request::instance()->http_post()->request(self::FILE_UPLOAD, $postdata);
        if (!is_object($response)) {
            $response = (object)[
                "result" => false,
                "errors" => [
                    [
                        "message"      => \plagiarism_unicheck::trans('unknownwarning'),
                        "error_code"   => "invalid_response",
                        "extra_params" => null
                    ]
                ]
            ];
        }

        return $response;
    }

    /**
     * Run check
     *
     * @param \stdClass $file
     *
     * @return \stdClass
     */
    public function run_check(\stdClass $file) {
        global $CFG;

        if (empty($file)) {
            throw new \InvalidArgumentException('Invalid argument $file');
        }

        $checktype = unicheck_settings::get_activity_settings($file->cm, unicheck_settings::SOURCES_FOR_COMPARISON);

        $options = [];
        $this->advanced_check_options($file->cm, $options);

        $postdata = [
            'type'         => is_null($checktype) ? UNICHECK_CHECK_TYPE_WEB : $checktype,
            'file_id'      => $file->external_file_id,
            'callback_url' => sprintf('%1$s%2$s?token=%3$s', $CFG->wwwroot, UNICHECK_CALLBACK_URL, $file->identifier),
            'options'      => $options,
        ];

        if (unicheck_settings::get_activity_settings($file->cm, unicheck_settings::EXCLUDE_CITATIONS)) {
            $postdata = array_merge($postdata, ['exclude_citations' => 1, 'exclude_references' => 1]);
        }

        return unicheck_api_request::instance()->http_post()->request(self::CHECK_CREATE, $postdata);
    }

    /**
     * Get check progress
     *
     * @param array $checkids
     *
     * @return \stdClass
     */
    public function get_check_progress(array $checkids) {
        if (empty($checkids)) {
            throw new \InvalidArgumentException('Invalid argument $checkids');
        }

        return unicheck_api_request::instance()->http_get()->request(self::CHECK_PROGRESS, [
            'id' => implode(',', $checkids),
        ]);
    }

    /**
     * Track file upload progress
     *
     * @param string $token
     *
     * @return \stdClass
     */
    public function get_file_upload_progress($token) {
        return unicheck_api_request::instance()->http_get()->request(self::TRACK_UPLOAD, [
            'uuid' => $token
        ]);
    }

    /**
     * Get check data
     *
     * @param int $id
     *
     * @return \stdClass
     */
    public function get_check_data($id) {
        if (empty($id)) {
            throw new \InvalidArgumentException('Invalid argument id');
        }

        return unicheck_api_request::instance()->http_get()->request(self::CHECK_GET, [
            'id' => $id,
        ]);
    }

    /**
     * Delete check
     *
     * @param \stdClass $file
     *
     * @return mixed
     */
    public function delete_check(\stdClass $file) {
        if (empty($file->check_id)) {
            throw new \InvalidArgumentException('Invalid argument check_id');
        }

        return unicheck_api_request::instance()->http_post()->request(self::CHECK_DELETE, [
            'id' => $file->check_id,
        ]);
    }

    /**
     * Create user
     *
     * @param object $user
     * @param bool   $cancomment
     *
     * @return mixed
     */
    public function user_create($user, $cancomment = false) {
        $postdata = [
            'sys_id'    => $user->id,
            'email'     => $user->email,
            'firstname' => $user->firstname,
            'lastname'  => $user->lastname,
            'scope'     => $cancomment ? self::ACCESS_SCOPE_WRITE : self::ACCESS_SCOPE_READ,
        ];

        return unicheck_api_request::instance()->http_post()->request(self::USER_CREATE, $postdata);
    }

    /**
     * Set advanced check options
     *
     * @param int   $cmid
     * @param array $options
     */
    private function advanced_check_options($cmid, &$options) {
        $options['exclude_self_plagiarism'] = 1;

        $similaritysensitivity = unicheck_settings::get_activity_settings($cmid, unicheck_settings::SENSITIVITY_SETTING_NAME);
        if (!empty($similaritysensitivity)) {
            $options['sensitivity'] = $similaritysensitivity / 100;
        }

        $wordssensitivity = unicheck_settings::get_activity_settings($cmid, unicheck_settings::WORDS_SENSITIVITY);
        if (!empty($wordssensitivity)) {
            $options['words_sensitivity'] = $wordssensitivity;
        }
    }

    /**
     * Get supported similarity check source types
     *
     * @return \stdClass
     */
    public function get_supported_search_types() {
        return unicheck_api_request::instance()->http_get()->request(self::GET_SUPPORTED_SEARCH_TYPES, []);
    }
}