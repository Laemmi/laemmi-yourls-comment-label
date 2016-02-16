/*
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @category  laemmi-yourls-comment-label
 * @package   admin.js
 * @author    Michael Lämmlein <ml@spacerabbit.de>
 * @copyright ©2015 laemmi
 * @license   http://www.opensource.org/licenses/mit-license.php MIT-License
 * @version   1.0
 * @since     23.10.15
 */

$(function() {

    // Edit button, show form
    $('#main_table').on('click', '.button_laemmi_edit_comment_label', function(e) {
        e.preventDefault();
        var self = $(this);
        if(self.hasClass('disabled')) {
            return false;
        }
        var buttons = self.closest('.actions').find('.button');
        add_loading(buttons);
        $.post(self.attr('href'), function(data) {
            self.closest('tr').after(data.html);
            end_loading(buttons);
        }, 'json');
    });

    // Submit edit form
    $('#main_table').on('click', '.laemmi_edit_comment_label_row input[name="save"]', function(e) {
        e.preventDefault();
        var row = $(this).closest('.laemmi_edit_comment_label_row');
        var form = row.find('form');

        $.post(ajaxurl, form.serialize(), function(data) {
            switch (data.status) {
                case 'success':
                    $('#edit-' + row.data('id')).fadeOut(200, function(){
                        $('#main_table tbody').trigger("update");
                    });
                    form.trigger('reset');
                    end_disable('#actions-' + row.data('id') + ' .button');
                    break;
            }

            feedback(data.message, data.status);
        }, 'json')
    });

    // Cancel edit
    $('#main_table').on('click', '.laemmi_edit_comment_label_row input[name="cancel"]', function(e) {
        e.preventDefault();
        var row = $(this).closest('.laemmi_edit_comment_label_row');
        $("#edit-" + row.data('id')).fadeOut(200, function() {
            end_disable('#actions-' + row.data('id') + ' .button');
        });
    });

    // #################################################################################################################

    // Add fields to add form
    $.post(ajaxurl, {'action': 'laemmi_edit_comment_label_getfields'}, function(data) {
        $("#new_url #new_url_form #add-button")
            .before(data);
    });

    // Prepare some hidden fields
    $("#new_url #new_url_form").append('<input type="hidden" name="action" value="add">');
    $("#new_url #nonce-add").clone().appendTo('#new_url_form').attr('name', 'nonce').removeAttr('id');

    // Submit add form
    $("#add-button").click(function(e) {
        e.preventDefault();
        if( $('#add-button').hasClass('disabled') ) {
            return false;
        }

        var newurl = $("#add-url").val();
        if ( !newurl || newurl == 'http://' || newurl == 'https://' ) {
            return;
        }

        var form = $('#new_url_form');

        add_loading("#add-button");
        $.post(ajaxurl, form.serialize(), function(data) {
            switch (data.status) {
                case 'success':
                    //$('#main_table tbody').prepend(data.html).trigger("update");
                    $('#main_table #TableTitle').after(data.html).trigger("update");
                    $('#nourl_found').css('display', 'none');
                    zebra_table();
                    increment_counter();
                    toggle_share_fill_boxes(data.url.url, data.shorturl, data.url.title);
                    break;
            }

            //form.trigger("reset");
            add_link_reset();
            end_loading("#add-button");
            end_disable("#add-button");
            feedback(data.message, data.status);
        }, 'json')
        .fail(function(e) {
            //console.log( e );
        });
    });

    // #################################################################################################################

    // Toggle show comment in list
    $('.laemmi_comment dt').click(function(e) {
        e.preventDefault();
        $(this).closest('.laemmi_comment').find('dd').toggle('slow');
    });
});

function add_link() {
    // disable function
}