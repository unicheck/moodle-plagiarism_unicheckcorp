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
        var existing = Y.one('.unicheck-detect_result.fid-' + record.file_id);
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

        var url = M.cfg.wwwroot + '/plagiarism/unicheck/track_progress.php';

        var callback = {
            method: 'get',
            context: this,
            sync: false,
            data: {
                'action': 'track_progress',
                'sesskey': M.cfg.sesskey,
                'cmid': contextid,
                'fileids': items.join(',')
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
        Y.all('.unicheck-detect_result .unicheck-data').each(function(row) {
            var jsondata = Y.JSON.parse(row.getHTML());
            M.plagiarismUnicheck.items.push(jsondata.fid);
        });
    };

    var runPlugin = function() {

        collectItems();

        if (M.plagiarismUnicheck.items.length) {
            trackProgress(Y, M.plagiarismUnicheck.items, contextid);
            M.plagiarismUnicheck.interval = setInterval(function() {
                trackProgress(Y, M.plagiarismUnicheck.items, contextid);
            }, 10000);
        }
    };

    runPlugin();
};

/**
 * Javascript helper function for plugin
 *
 * @package   plagiarism_unicheck
 * @author    2019 Aleksandr kostylev <a.kostylev@p1k.co.uk>
 * @copyright UKU Group, LTD, https://www.unicheck.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/** global: M */
M.plagiarismUnicheck.init_debugging_table = function(Y) {
    Y.use('node', function(Y) {
        var checkboxes = Y.all('td.c0 input');
        checkboxes.each(function(node) {
            node.on('change', function(e) {
                var rowelement = e.currentTarget.get('parentNode').get('parentNode');
                if (e.currentTarget.get('checked')) {
                    rowelement.removeClass('unselectedrow');
                    rowelement.addClass('selectedrow');
                } else {
                    rowelement.removeClass('selectedrow');
                    rowelement.addClass('unselectedrow');
                }
            });

            var rowelement = node.get('parentNode').get('parentNode');
            if (node.get('checked')) {
                rowelement.removeClass('unselectedrow');
                rowelement.addClass('selectedrow');
            } else {
                rowelement.removeClass('selectedrow');
                rowelement.addClass('unselectedrow');
            }
        });

        var selectall = Y.one('th.c0 input');
        if (selectall) {
            selectall.on('change', function(e) {
                if (e.currentTarget.get('checked')) {
                    checkboxes = Y.all('td.c0 input[type="checkbox"]');
                    checkboxes.each(function(node) {
                        var rowelement = node.get('parentNode').get('parentNode');
                        node.set('checked', true);
                        rowelement.removeClass('unselectedrow');
                        rowelement.addClass('selectedrow');
                    });
                } else {
                    checkboxes = Y.all('td.c0 input[type="checkbox"]');
                    checkboxes.each(function(node) {
                        var rowelement = node.get('parentNode').get('parentNode');
                        node.set('checked', false);
                        rowelement.removeClass('selectedrow');
                        rowelement.addClass('unselectedrow');
                    });
                }
            });
        }

        var batchform = Y.one('form.debuggingbatchoperationsform');
        if (batchform) {
            batchform.on('submit', function(e) {
                checkboxes = Y.all('td.c0 input');
                var selectedfiles = [];
                checkboxes.each(function(node) {
                    if (node.get('checked')) {
                        selectedfiles[selectedfiles.length] = node.get('value');
                    }
                });

                var operation = Y.one('#id_operation');
                var usersinput = Y.one('input.selectedfiles');
                usersinput.set('value', selectedfiles.join(','));
                if (selectedfiles.length === 0) {
                    alert(M.util.get_string('debugging:batchoperations:nofilesselected', 'plagiarism_unicheck'));
                    e.preventDefault();
                } else {
                    var confirmmessage = M.util.get_string(
                        'debugging:batchoperations:confirm' + operation.get('value'), 'plagiarism_unicheck'
                    );
                    if (!confirm(confirmmessage)) {
                        e.preventDefault();
                    }
                }
            });
        }
    });
};
