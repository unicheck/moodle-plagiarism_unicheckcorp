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
 * quiz_observer.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Yurii Filchenko <y.filchenko@unicheck.com>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\observers;

use core\event\base;
use plagiarism_unicheck\classes\unicheck_core;
use quiz_attempt;
use stored_file;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class quiz_observer
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Yurii Filchenko <y.filchenko@unicheck.com>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_observer extends abstract_observer {
    /**
     * @var unicheck_core
     */
    private $core;

    /**
     * @var base
     */
    private $event;

    /**
     * @var quiz_attempt
     */
    private $attempt;

    /**
     * handle event
     *
     * @param unicheck_core $core
     * @param base          $event
     *
     * @throws \plagiarism_unicheck\classes\exception\unicheck_exception
     */
    public function attempt_submitted(unicheck_core $core, base $event) {
        $this->core = $core;
        $this->event = $event;

        if (isset($this->event->objectid)) {
            $this->attempt = quiz_attempt::create($this->event->objectid);

            $this->handle_content();
            $this->handle_files();
        }
    }

    /**
     * handle content
     *
     * @throws \plagiarism_unicheck\classes\exception\unicheck_exception
     */
    private function handle_content() {
        foreach ($this->attempt->get_slots() as $slot) {
            $qa = $this->attempt->get_question_attempt($slot);
            $content = $qa->get_response_summary();
            if (empty($content) || count(preg_split('/\s+/', trim(strip_tags($content)))) < 30) {
                continue;
            }

            $identifier = unicheck_core::content_hash($content);
            $filename = sprintf("%s-content-%d-%d-%s.html",
                str_replace('_', '-', $this->event->objecttable), $this->event->contextid, $this->core->cmid, $identifier
            );

            $file = $this->core->create_file_from_content(
                $content,
                $this->event->objecttable,
                $this->event->contextid,
                $this->event->objectid,
                null,
                $filename
            );

            if ($file instanceof stored_file) {
                $this->add_after_handle_task($file);
            }

            $this->after_handle_event($this->core);
        }
    }

    /**
     * handle files
     *
     * @throws \plagiarism_unicheck\classes\exception\unicheck_exception
     */
    private function handle_files() {
        foreach ($this->attempt->get_slots() as $slot) {
            $qa = $this->attempt->get_question_attempt($slot);
            $files = $qa->get_last_qt_files('attachments', $this->event->contextid);

            foreach ($files as $pathnamehash => $value) {
                $file = get_file_storage()->get_file_by_hash($pathnamehash);

                if (!$file || $file->is_directory()) {
                    continue;
                }

                $this->add_after_handle_task($file);
            }
        }

        $this->after_handle_event($this->core);
    }
}
