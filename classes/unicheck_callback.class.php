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
 * unicheck_callback.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

use plagiarism_unicheck\classes\entities\providers\callback_provider;
use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\helpers\unicheck_check_helper;
use plagiarism_unicheck\classes\helpers\unicheck_response;
use plagiarism_unicheck\classes\helpers\unicheck_stored_file;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_callback
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_callback {
    /**
     * Handle callback
     *
     * @param \stdClass $body
     * @param string    $token
     *
     * @throws \InvalidArgumentException
     *
     * @uses file_upload_success
     * @uses file_upload_error
     * @uses similarity_check_finish
     * @uses similarity_check_recalculated
     * @uses integration_callback_test
     */
    public function handle(\stdClass $body, $token) {
        if (!isset($body->event_type)) {
            throw new \InvalidArgumentException("Event type does't exist");
        }

        if (!isset($body->resource_type)) {
            throw new \InvalidArgumentException("Event resource type does't exist");
        }

        $methodname = str_replace('.', '_', strtolower($body->event_type));
        if (!method_exists($this, $methodname)) {
            throw new \LogicException('Invalid callback event type');
        }

        $apikey = unicheck_settings::get_settings('client_id');
        if (!$apikey) {
            throw new \InvalidArgumentException("Unicheck API key not set. Can't handle callback");
        }

        try {
            $resourceid = null;
            if (property_exists($body, $body->resource_type)) {
                $resourceid = $body->{$body->resource_type}->id;
            }
            $callback = new \stdClass();
            $callback->api_key = $apikey;
            $callback->event_type = $body->event_type;
            $callback->event_id = $body->event_id;
            $callback->resource_type = $body->resource_type;
            $callback->resource_id = $resourceid;
            $callback->request_body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $callbackid = callback_provider::create($callback);
            $callback->id = $callbackid;
        } catch (\Exception $exception) {
            throw new \InvalidArgumentException($exception->getMessage());
        }

        $this->{$methodname}($body, $token);

        $callback->processed = 1;
        callback_provider::save($callback);
    }

    /**
     * file_upload_success
     *
     * @param \stdClass $body
     * @param string    $identifier
     *
     * @throws \InvalidArgumentException
     */
    private function file_upload_success(\stdClass $body, $identifier) {
        if (!isset($body->file)) {
            throw new \InvalidArgumentException('File data does not exist');
        }

        $internalfile = unicheck_stored_file::get_plagiarism_file_by_identifier($identifier);

        unicheck_response::process_after_upload($body, $internalfile);
        unicheck_adhoc::check($internalfile);
    }

    /**
     * file_upload_error
     *
     * @param \stdClass $body
     * @param string    $identifier
     */
    private function file_upload_error(\stdClass $body, $identifier) {
        $internalfile = unicheck_stored_file::get_plagiarism_file_by_identifier($identifier);

        unicheck_response::process_after_upload($body, $internalfile);
    }

    /**
     * file_cheating_detected
     *
     * @param \stdClass $body
     * @param string    $identifier
     *
     * @throws \InvalidArgumentException
     */
    private function file_cheating_detected(\stdClass $body, $identifier) {
        if (!isset($body->file)) {
            throw new \InvalidArgumentException('File data does not exist');
        }

        $plagiarismfile = unicheck_stored_file::get_plagiarism_file_by_identifier($identifier);

        unicheck_file_provider::set_cheating_info($plagiarismfile, (array) $body->file->metadata->cheating);
    }

    /**
     * similarity_check_finish
     *
     * @param \stdClass $body
     * @param string    $identifier
     *
     * @throws \InvalidArgumentException
     */
    private function similarity_check_finish(\stdClass $body, $identifier) {
        if (!isset($body->check)) {
            throw new \InvalidArgumentException('Check data does not exist');
        }

        $internalfile = unicheck_stored_file::get_plagiarism_file_by_identifier($identifier);
        $progress = 100 * $body->check->progress;
        unicheck_check_helper::check_complete($internalfile, $body->check, $progress);
    }

    /**
     * similarity_check_recalculated
     *
     * @param \stdClass $body
     * @param string    $identifier
     *
     * @throws \InvalidArgumentException
     */
    private function similarity_check_recalculated(\stdClass $body, $identifier) {
        if (!isset($body->check)) {
            throw new \InvalidArgumentException('Check data does not exist');
        }

        $internalfile = unicheck_stored_file::get_plagiarism_file_by_identifier($identifier);
        unicheck_check_helper::check_recalculated($internalfile, $body->check);
    }

    /**
     * integration_callback_test
     *
     * @param \stdClass $body
     * @param string    $identifier
     *
     * @return string
     */
    private function integration_callback_test(\stdClass $body, $identifier) {
        return "OK";
    }
}