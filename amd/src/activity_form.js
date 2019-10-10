/**
 * Activity form
 *
 * @package     plagiarism_unicheck
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* jshint ignore:start */
define(['jquery'], function($) {
    return {
        init: function() {
            $('#id_plagiarism_unicheck').find('#id_show_student_score').change(function() {
                if ($(this).val() == 0) {
                    $('#id_plagiarism_unicheck').find('#id_sent_student_report').val($(this).val()).change();
                }
            });
        }
    };
});
/* jshint ignore:end */