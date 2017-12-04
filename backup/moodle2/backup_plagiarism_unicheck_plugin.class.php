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
 * backup_plagiarism_unicheck_plugin.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class backup_plagiarism_unicheck_plugin
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_plagiarism_unicheck_plugin extends backup_plagiarism_plugin {
    /**
     * define_module_plugin_structure
     *
     * @return mixed
     */
    public function define_module_plugin_structure() {
        // Define the virtual plugin element without conditions as the global class checks already.
        $plugin = $this->get_plugin_element();

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);

        $configs = new backup_nested_element('unicheck_configs');
        $config = new backup_nested_element('unicheck_config', ['id'], ['name', 'value']);
        $pluginwrapper->add_child($configs);
        $configs->add_child($config);
        $config->set_source_table(UNICHECK_CONFIG_TABLE, ['cm' => backup::VAR_PARENTID]);

        // Now information about files to module.
        $ufiles = new backup_nested_element('unicheck_files');
        $ufile = new backup_nested_element('unicheck_file', ['id'], [
            'parent_id', 'cm', 'userid', 'identifier', 'check_id', 'filename', 'type', 'progress', 'reporturl',
            'optout', 'statuscode', 'similarityscore', 'errorresponse', 'timesubmitted', 'state', 'external_file_id',
            'reportediturl', 'external_file_uuid'
        ]);

        $pluginwrapper->add_child($ufiles);
        $ufiles->add_child($ufile);

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');
        if ($userinfo) {
            $ufile->set_source_table(UNICHECK_FILES_TABLE, ['cm' => backup::VAR_PARENTID]);
        }

        return $plugin;
    }

    /**
     * define_course_plugin_structure
     *
     * @return mixed
     */
    public function define_course_plugin_structure() {
        // Define the virtual plugin element without conditions as the global class checks already.
        $plugin = $this->get_plugin_element();

        // Create one standard named plugin element (the visible container).
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // Connect the visible container ASAP.
        $plugin->add_child($pluginwrapper);
        // Save id from unicheck course.
        $unconfigs = new backup_nested_element('unicheck_configs');
        $unconfig = new backup_nested_element('unicheck_config', ['id'], ['plugin', 'name', 'value']);
        $pluginwrapper->add_child($unconfigs);
        $unconfigs->add_child($unconfig);
        $unconfig->set_source_table('config_plugins', [
            'name' => backup::VAR_PARENTID, 'plugin' => backup_helper::is_sqlparam('plagiarism_unicheck_course'),
        ]);

        return $plugin;
    }
}