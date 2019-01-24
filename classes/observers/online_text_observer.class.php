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
 * online_text_observer.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\observers;

use core\event\base;
use plagiarism_unicheck\classes\services\storage\interfaces\pluginfile_url_interface;
use plagiarism_unicheck\classes\unicheck_core;
use stored_file;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class online_text_observer
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class online_text_observer extends abstract_observer {
    /**
     * handle_event
     *
     * @param unicheck_core                 $core
     * @param base                          $event
     * @param pluginfile_url_interface|null $pluginfileurl
     */
    public function assessable_uploaded(unicheck_core $core, base $event, pluginfile_url_interface $pluginfileurl = null) {
        if (empty($event->other['content'])) {
            return;
        }

        $file = $core->create_file_from_onlinetext_event($event, $pluginfileurl);

        if (self::is_submition_draft($event)) {
            return;
        }

        if ($file instanceof stored_file) {
            $this->add_after_handle_task($file);
        }

        $this->after_handle_event($core);
    }
}