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
 * Class unicheck_translate
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\helpers;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_translate
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait unicheck_translate {
    /**
     * Translate
     *
     * @param string $message
     * @param null   $param
     *
     * @return string
     */
    public static function trans($message, $param = null) {
        return get_string($message, UNICHECK_PLAGIN_NAME, $param);
    }

    /**
     * Translate api error response
     *
     * @param array $error
     *
     * @return string
     */
    private static function api_trans($error) {
        global $CFG;
        static $translates;

        if (empty($translates)) {
            $lang = current_language();
            $path = "{$CFG->dirroot}/plagiarism/unicheck/lang/{$lang}/api_translates.json";
            if (file_exists($path)) {
                $translates = json_decode(file_get_contents($path));
            }
        }

        $message = $error['message'];
        if (isset($error['extra_params']) && !is_array($error['extra_params'])) {
            $message = self::trans($error['extra_params']);
        }

        return isset($translates->{$message}) ? $translates->{$message} : $error['message'];
    }
}