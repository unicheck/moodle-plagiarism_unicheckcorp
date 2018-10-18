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
 * pluginfile_url_contract.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\services\storage\interfaces;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Interface pluginfile_url_interface
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface pluginfile_url_interface {

    /**
     * Rewrite @@PLUGINFILE@@ URLs in content
     *
     * @param string $content
     * @param int    $contextid
     * @param int    $itemid
     * @return string
     */
    public function rewrite($content, $contextid, $itemid);

    /**
     * Set processor filename
     *
     * @param string $processorfilename
     */
    public function set_processorfilename($processorfilename);

    /**
     * Set component type
     *
     * @param string $component
     */
    public function set_component($component);

    /**
     * Set filearea
     *
     * @param string $filearea
     */
    public function set_filearea($filearea);

    /**
     * Set result URLs options
     *
     * @param array $options
     */
    public function set_options(array $options);
}