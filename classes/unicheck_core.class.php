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
 * Class unicheck_core
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes;

use core\event\base;
use plagiarism_unicheck;
use plagiarism_unicheck\classes\entities\providers\unicheck_file_provider;
use plagiarism_unicheck\classes\entities\providers\user_provider;
use plagiarism_unicheck\classes\entities\unicheck_archive;
use plagiarism_unicheck\classes\exception\unicheck_exception;
use plagiarism_unicheck\classes\permissions\capability;
use plagiarism_unicheck\classes\plagiarism\unicheck_file;
use plagiarism_unicheck\classes\services\storage\interfaces\pluginfile_url_interface;
use plagiarism_unicheck\event\api_user_created;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_core
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Vadim Titov <v.titov@p1k.co.uk>, Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_core {
    /**
     * @var unicheck_plagiarism_entity
     */
    private $plagiarismentity;
    /** @var  bool */
    private $teamsubmission = false;
    /**
     * @var int
     */
    public $userid = null;

    /**
     * Context module instance ID (assign/forum/workshop ID)
     *
     * @var int
     */
    public $cmid = null;

    /**
     * Assign/forum/workshop
     *
     * @var string
     */
    public $modname = null;

    /**
     * unicheck_core constructor.
     *
     * @param int    $cmid
     * @param int    $userid
     * @param string $modname
     */
    public function __construct($cmid, $userid, $modname) {
        $this->cmid = $cmid;
        $this->userid = $userid;
        $this->modname = $modname;
    }

    /**
     * Convert array to json
     *
     * @param array $data
     *
     * @return string
     */
    public static function json_response($data) {
        return json_encode($data);
    }

    /**
     * resubmit_file
     *
     * @param int $id
     *
     * @return bool
     */
    public static function resubmit_file($id) {
        $plagiarismfile = unicheck_file_provider::get_by_id($id);
        if (!unicheck_file_provider::can_start_check($plagiarismfile)) {
            return false;
        }

        try {
            $cm = get_coursemodule_from_id('', $plagiarismfile->cm);

            if (!plagiarism_unicheck::is_support_mod($cm->modname)) {
                return false;
            }

            $file = get_file_storage()->get_file_by_hash($plagiarismfile->identifier);
            $ucore = new unicheck_core($plagiarismfile->cm, $plagiarismfile->userid, $cm->modname);

            if (plagiarism_unicheck::is_archive($file)) {
                $archive = new unicheck_archive($file, $ucore);
                $archive->restart_check();

                return true;
            }

            if ($plagiarismfile->check_id) {
                unicheck_api::instance()->delete_check($plagiarismfile);
            }
            unicheck_adhoc::upload($file, $ucore);

            unicheck_notification::success('plagiarism_run_success', true);
        } catch (\Exception $exception) {
            unicheck_file_provider::to_error_state($plagiarismfile, $exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * get_plagiarism_entity
     *
     * @param \stored_file $file
     *
     * @return unicheck_file|unicheck_plagiarism_entity
     * @throws unicheck_exception
     */
    public function get_plagiarism_entity($file) {
        if (empty($file)) {
            throw new unicheck_exception(unicheck_exception::FILE_NOT_FOUND);
        }

        $this->plagiarismentity = new unicheck_file($this, $file);

        return $this->plagiarismentity;
    }

    /**
     * parse_json
     *
     * @param string $data
     *
     * @return mixed
     */
    public static function parse_json($data) {
        return json_decode($data);
    }

    /**
     * get_file_by_hash
     *
     * @param int    $contextid
     * @param string $contenthash
     *
     * @return null|\stored_file
     */
    public static function get_file_by_hash($contextid, $contenthash) {
        global $DB;

        $filerecord = $DB->get_records('files', [
            'contextid'   => $contextid,
            'component'   => UNICHECK_PLAGIN_NAME,
            'contenthash' => $contenthash,
        ], 'id desc', '*', 0, 1);

        if (!$filerecord) {
            return null;
        }

        return get_file_storage()->get_file_instance(array_shift($filerecord));
    }

    /**
     * create_file_from_content
     *
     * @param string                        $content
     * @param string                        $filearea
     * @param int                           $contexid
     * @param int                           $itemid
     * @param pluginfile_url_interface|null $pluginfileurl
     *
     * @return \stored_file
     */
    public function create_file_from_content(
        $content,
        $filearea,
        $contexid,
        $itemid,
        pluginfile_url_interface $pluginfileurl = null
    ) {
        $author = self::get_user($this->userid);
        $filerecord = [
            'component' => UNICHECK_PLAGIN_NAME,
            'filearea'  => $filearea,
            'contextid' => $contexid,
            'itemid'    => $itemid,
            'filename'  => sprintf("%s-content-%d-%d-%d.html",
                str_replace('_', '-', $filearea), $contexid, $this->cmid, $itemid
            ),
            'filepath'  => '/',
            'userid'    => $author->id,
            'license'   => 'allrightsreserved',
            'author'    => $author->firstname . ' ' . $author->lastname,
        ];

        /** @var \stored_file $storedfile */
        $storedfile = get_file_storage()->get_file(
            $filerecord['contextid'], $filerecord['component'], $filerecord['filearea'],
            $filerecord['itemid'], $filerecord['filepath'], $filerecord['filename']
        );

        if ($storedfile) {
            if ($storedfile->get_contenthash() == self::content_hash($content)) {
                return $storedfile;
            }
            $this->delete_old_file_from_content($storedfile);
        }

        if ($pluginfileurl instanceof pluginfile_url_interface) {
            $content = $pluginfileurl->rewrite($content, $contexid, $itemid);
        }

        return get_file_storage()->create_file_from_string($filerecord, $content);
    }

    /**
     * create_file_from_onlinetext_event
     *
     * @param base                          $event
     * @param pluginfile_url_interface|null $pluginfileurl
     *
     * @return \stored_file|null
     */
    public function create_file_from_onlinetext_event(base $event, pluginfile_url_interface $pluginfileurl = null) {
        if (empty($event->other['content'])) {
            return null;
        }

        return $this->create_file_from_content(
            $event->other['content'],
            $event->objecttable,
            $event->contextid,
            $event->objectid,
            $pluginfileurl
        );
    }

    /**
     * Get content hash
     *
     * @param mixed $content
     *
     * @return string
     */
    public static function content_hash($content) {
        return sha1($content);
    }

    /**
     * inject_comment_token
     *
     * @param string $url
     * @param int    $cmid
     */
    public static function inject_comment_token(&$url, $cmid) {
        $url .= '&ctoken=' . self::get_external_token($cmid);
    }

    /**
     * get_external_token
     *
     * @param int         $cmid
     * @param null|object $user
     *
     * @return mixed
     */
    public static function get_external_token($cmid, $user = null) {
        $user = $user ? $user : self::get_user();
        $apikey = unicheck_settings::get_settings('client_id');
        $storeduser = user_provider::find_by_user_id_and_api_key($user->id, $apikey);

        if ($storeduser) {
            return $storeduser->external_token;
        } else {
            $resp = unicheck_api::instance()->user_create($user, self::is_teacher($cmid, $user->id));

            if ($resp && $resp->result) {
                $externaluserdata = new \stdClass;
                $externaluserdata->user_id = $user->id;
                $externaluserdata->external_user_id = $resp->user->id;
                $externaluserdata->external_token = $resp->user->token;
                $externaluserdata->api_key = $apikey;

                $apiuserid = user_provider::create($externaluserdata);
                $externaluserdata->id = $apiuserid;

                api_user_created::create_from_apiuser($externaluserdata)->trigger();

                return $externaluserdata->external_token;
            }
        }

        return null;
    }

    /**
     * is_teacher
     *
     * @param int $cmid
     * @param int $userid
     *
     * @return bool
     */
    public static function is_teacher($cmid, $userid) {
        return capability::user_can('moodle/grade:edit', $cmid, $userid);
    }

    /**
     * delete_old_file_from_content
     *
     * @param \stored_file $storedfile
     */
    private function delete_old_file_from_content(\stored_file $storedfile) {
        global $DB;

        $DB->delete_records(UNICHECK_FILES_TABLE, [
            'cm'         => $this->cmid,
            'userid'     => $storedfile->get_userid(),
            'identifier' => $storedfile->get_pathnamehash(),
        ]);

        $storedfile->delete();
    }

    /**
     * enable_teamsubmission
     *
     * @return $this
     */
    public function enable_teamsubmission() {
        $this->teamsubmission = true;

        return $this;
    }

    /**
     * is_teamsubmission_mode
     *
     * @return bool
     */
    public function is_teamsubmission_mode() {
        return $this->teamsubmission;
    }

    /**
     * Get user
     *
     * @param null|int $uid
     *
     * @return object
     */
    public static function get_user($uid = null) {
        global $USER, $DB;

        if ($uid !== null) {
            return $DB->get_record('user', ['id' => $uid]);
        }

        return $USER;
    }
}