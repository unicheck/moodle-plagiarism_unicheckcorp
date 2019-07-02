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
 * unicheck_status_table.class.php
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_unicheck\classes\table\debugging;

use cache;
use html_writer;
use plagiarism_unicheck;
use plagiarism_unicheck\classes\services\api\api_regions;
use plagiarism_unicheck\classes\services\api\integration_api;
use plagiarism_unicheck\classes\services\availability_check\availability_check_results;
use plagiarism_unicheck\classes\unicheck_api_request;
use plagiarism_unicheck\classes\unicheck_settings;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Class unicheck_status_table
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      2019 Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unicheck_status_table extends \html_table {

    /** @var  availability_check_results[] */
    private $availabilitycheckresults;

    /** @var cache */
    private $cache;

    /**
     * availability_check_table constructor.
     *
     * @param availability_check_results[] $availabilitycheckresults
     */
    public function __construct(array $availabilitycheckresults = []) {

        parent::__construct();

        $this->head = [
            get_string('name'),
            get_string('info'),
            get_string('report'),
            get_string('status'),
        ];
        $this->colclasses = ['centeralign name', 'centeralign info', 'leftalign report', 'centeralign status'];
        $this->attributes['class'] = 'admintable environmenttable generaltable';
        $this->id = 'serverstatus';
        $this->availabilitycheckresults = $availabilitycheckresults;
        $this->cache = cache::make(UNICHECK_PLAGIN_NAME, 'debugging', ['status_table']);
    }

    /**
     * Display html table
     */
    public function display() {
        if (!$this->cache->has('status_table')) {
            $this->cache_status_table();
        }

        $this->data = $this->cache->get('status_table');

        // Print table.
        echo html_writer::table($this);
    }

    /**
     * reset table cache
     *
     * @param \moodle_url|null $redirecturl
     */
    public function reset_cache(\moodle_url $redirecturl = null) {
        $this->cache->purge();

        if ($redirecturl) {
            redirect($redirecturl);
        }
    }

    /**
     * invalidate_caches
     */
    private function cache_status_table() {
        $this->run_tests();
        $serverdata = ['ok' => [], 'warn' => [], 'error' => []];
        foreach ($this->availabilitycheckresults as $availabilitycheckresult) {
            $errorline = false;
            $warningline = false;
            $type = $availabilitycheckresult->get_part();
            $info = $availabilitycheckresult->get_info();
            $errorcode = $availabilitycheckresult->get_errorcode();
            $status = get_string('ok');
            if ($errorcode) {
                $status = get_string('error');
                $errorline = true;
            } else {
                if ($availabilitycheckresult->get_bypassstr() != '') {
                    $status = get_string('bypassed');
                    $warningline = true;
                } else if ($availabilitycheckresult->get_restrictstr() != '') {
                    $status = get_string('restricted');
                    $errorline = true;
                }
            }

            // Format error or warning line.
            if ($errorline) {
                $messagetype = 'error';
                $statusclass = 'label-important';
            } else if ($warningline) {
                $messagetype = 'warn';
                $statusclass = 'label-warning';
            } else {
                $messagetype = 'ok';
                $statusclass = 'label-success';
            }

            $status = html_writer::span($status, 'label ' . $statusclass);
            // Append the feedback if there is some.
            $feedbacktext = $availabilitycheckresult->str_to_report($availabilitycheckresult->get_feedbackstr(), 'ok');
            // Append the bypass if there is some.
            $feedbacktext .= $availabilitycheckresult->str_to_report($availabilitycheckresult->get_bypassstr(), 'warn');
            // Append the restrict if there is some.
            $feedbacktext .= $availabilitycheckresult->str_to_report($availabilitycheckresult->get_restrictstr(), 'error');

            $serverdata[$messagetype][] = [
                $type,
                $info,
                $feedbacktext,
                $status
            ];
        }

        $this->cache->set('status_table', array_merge($serverdata['error'], $serverdata['warn'], $serverdata['ok']));
    }

    /**
     * run_tests
     */
    private function run_tests() {
        global $CFG, $DB;

        $apiregion = unicheck_settings::get_current_region();
        $apikey = unicheck_settings::get_settings('client_id');
        $apiurl = api_regions::get_api_base_url_by_region($apiregion);
        $callbackurl = sprintf(
            '%1$s%2$s?token=plugin_test_%3$s',
            $CFG->wwwroot,
            UNICHECK_CALLBACK_URL,
            time()
        );

        $response = (new integration_api())->test($callbackurl);
        $lastcurl = unicheck_api_request::instance()->get_last_curl();

        $availabilitycheckresults = [];

        $stoptestingby = null;
        $currenttest = 'unicheck_host';
        $unicheckhosttest = new availability_check_results($currenttest);
        $unicheckhosttest->set_info(
            plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest) .
            "<br>Region: $apiregion<br>API Base URL: $apiurl<br>"
        );

        $httpcode = $lastcurl->get_info()['http_code'];
        if ($httpcode < 200 || $httpcode >= 500) {
            $unicheckhosttest->set_status(false);
            if ($lastcurl->get_errno()) {
                $unicheckhosttest->set_restrictstr($lastcurl->error);
            }

            if ($lastcurl->getResponse()) {
                $unicheckhosttest->set_bypassstr(json_encode($lastcurl->getResponse(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $unicheckhosttest->set_errorcode(availability_check_results::FAILED);
            $stoptestingby = $currenttest;
        } else {
            $unicheckhosttest->set_status(true);
        }

        $availabilitycheckresults[] = $unicheckhosttest;

        $currenttest = 'unicheck_api_key';
        $unicheckapikeytest = new availability_check_results($currenttest);
        $unicheckapikeytest->set_info(
            plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest) .
            "<br>API Key: $apikey"
        );

        if (!$stoptestingby) {
            if (in_array($httpcode, [401, 403, 404])) {
                $unicheckapikeytest->set_status(false);
                $unicheckapikeytest->set_errorcode(availability_check_results::FAILED);
                $unicheckapikeytest->set_restrictstr(json_encode($lastcurl->getResponse(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $stoptestingby = $currenttest;
            } else {
                $unicheckapikeytest->set_status(true);
            }
        } else {
            $unicheckapikeytest->set_bypassstr(
                plagiarism_unicheck::trans('debugging:statustable:fixtest', $stoptestingby)
            );
        }

        $availabilitycheckresults[] = $unicheckapikeytest;

        $currenttest = 'callback_url';
        $moodlecallbackurltest = new availability_check_results($currenttest);
        $moodlecallbackurltest->set_info(
            plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest) .
            "<br>Callback URL: $callbackurl"
        );

        if (!$stoptestingby) {
            if (!$response->integration_tests->callback_sent->passed) {
                $moodlecallbackurltest->set_status(false);
                $moodlecallbackurltest->set_errorcode(availability_check_results::FAILED);
                $moodlecallbackurltest->set_restrictstr(json_encode($response->integration_tests->callback_sent->info,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $moodlecallbackurltest->set_status(true);
            }
        } else {
            $moodlecallbackurltest->set_bypassstr(
                plagiarism_unicheck::trans('debugging:statustable:fixtest', $stoptestingby)
            );
        }

        $availabilitycheckresults[] = $moodlecallbackurltest;

        $currenttest = 'license';
        $licensetest = new availability_check_results($currenttest);
        $licensetest->set_info(plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest));

        if (!$stoptestingby) {
            if (!$response->integration_tests->has_license->passed) {
                $licensetest->set_status(false);
                $licensetest->set_errorcode(availability_check_results::FAILED);
            } else {
                $licensetest->set_status(true);
            }
        } else {
            $licensetest->set_bypassstr(
                plagiarism_unicheck::trans('debugging:statustable:fixtest', $stoptestingby)
            );
        }

        $availabilitycheckresults[] = $licensetest;

        $currenttest = 'moodle_adhoc';
        $crontest = new availability_check_results($currenttest);

        $adhoctaskscount = $DB->count_records('task_adhoc', ['component' => UNICHECK_PLAGIN_NAME]);
        $lastexecution = (int) $DB->get_field_sql("SELECT MIN(nextruntime) FROM {task_adhoc}");

        $infotext = plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest)
            . "<br>Tasks count: $adhoctaskscount";
        if ($lastexecution) {
            $infotext .= "<br>Last execution(timestamp): $lastexecution";
        }
        $crontest->set_info($infotext);

        if ($adhoctaskscount > 100) {
            $crontest->set_restrictstr(plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest . 'bigqueue'));
            $crontest->set_status(false);
        } else if ($adhoctaskscount > 50) {
            $crontest->set_bypassstr(plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest . 'slowly'));
        }

        if ($lastexecution > 0 && $lastexecution < time() - 3600) {
            $crontest->set_restrictstr(
                plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest . 'lastexecution', 60)
            );
            $crontest->set_status(false);
        } else if ($lastexecution > 0 && $lastexecution < time() - 600) {
            $crontest->set_bypassstr(
                plagiarism_unicheck::trans('debugging:statustable:check' . $currenttest . 'lastexecution', 10)
            );
        }

        $availabilitycheckresults[] = $crontest;

        $this->availabilitycheckresults = array_merge($this->availabilitycheckresults, $availabilitycheckresults);
    }
}