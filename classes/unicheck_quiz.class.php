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
 * Class unicheck_quiz
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Yurii Filchenko <y.filchenko@unicheck.com>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

use context_module;
use quiz_attempt;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_quiz
 *
 * @package     plagiarism_unicheck
 * @author      Yurii Filchenko <y.filchenko@unicheck.com>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_quiz {

    public static function get_user_attempt($context, $linkarray) {
        $contenthash = unicheck_core::content_hash($linkarray['content']);
        $file = unicheck_core::get_file_by_hash($context->id, $contenthash);

        return $file;
    }
}
