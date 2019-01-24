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
 * Javascript helper function for plugin
 *
 * @package   plagiarism_unicheck
 * @author    Vadim Titov <v.titov@p1k.co.uk>
 * @copyright UKU Group, LTD, https://www.unicheck.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/** global: M */
M.plagiarismUnicheck = {
    interval: null,
    items: []
};

M.plagiarismUnicheck.init = function(Y, contextid) {
    var handleRecord = function(record) {
        var existing = Y.one('.un_detect_result.fid-' + record.file_id);
        if (!existing) {
            return;
        }

        existing.insert(record.content, 'after').remove();
        if (record.progress === 100 || record.state === 'HAS_ERROR') {
            var items = M.plagiarismUnicheck.items;
            items.splice(items.indexOf(record.file_id), 1);
        }
    };

    var trackProgress = function(Y, items, contextid) {

        if (!items[0]) {
            clearInterval(M.plagiarismUnicheck.interval);
            return;
        }

        var url = M.cfg.wwwroot + '/plagiarism/unicheck/ajax.php';

        var callback = {
            method: 'get',
            context: this,
            sync: false,
            data: {
                'action': 'track_progress',
                'sesskey': M.cfg.sesskey,
                'data': Y.JSON.stringify({
                    ids: items,
                    cid: contextid
                })
            },
            on: {
                success: function(tid, response) {
                    var jsondata = Y.JSON.parse(response.responseText);
                    if (!jsondata) {
                        return;
                    }

                    Y.each(jsondata, handleRecord);
                },
                failure: function() {
                    M.plagiarismUnicheck.items = [];
                }
            }
        };

        Y.io(url, callback);
    };

    var collectItems = function() {
        Y.all('.un_detect_result .un_data').each(function(row) {
            var jsondata = Y.JSON.parse(row.getHTML());
            M.plagiarismUnicheck.items.push(jsondata.fid);
        });
    };

    var runPlugin = function() {

        collectItems();

        if (M.plagiarismUnicheck.items.length) {
            M.plagiarismUnicheck.interval = setInterval(function() {
                trackProgress(Y, M.plagiarismUnicheck.items, contextid);
            }, 3000);
        }
    };

    runPlugin();
};