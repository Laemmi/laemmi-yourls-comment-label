<?php
/**
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
 * @category    Laemmi\Yourls\Comment\Label
 * @package     Laemmi\Yourls\Comment\Label
 * @author      Michael Lämmlein <ml@spacerabbit.de>
 * @copyright   ©2015 laemmi
 * @license     http://www.opensource.org/licenses/mit-license.php MIT-License
 * @version     1.0.0
 * @since       23.10.15
 */

/**
 * Namespace
 */
namespace Laemmi\Yourls\Comment\Label;

/**
 * Class AbstractDefault
 */
require_once 'Plugin/AbstractDefault.php';

use Laemmi\Yourls\Plugin\AbstractDefault;

/**
 * Class Plugin
 *
 * @package Laemmi\Yourls\Bind\User\To\Entry
 */
class Plugin extends AbstractDefault
{
    /**
     * Localization domain
     */
    const LOCALIZED_DOMAIN = 'laemmi-yourls-comment-label';

    /**
     * Settings constants
     */
    const SETTING_URL_COMMENT = 'laemmi_comment';
    const SETTING_URL_LABEL = 'laemmi_label';

    /**
     * Settings for url table
     *
     * @var array
     */
    protected $_setting_url = [
        self::SETTING_URL_COMMENT => ["field" => "TEXT"],
        self::SETTING_URL_LABEL => ["field" => "TEXT"]
    ];

    ####################################################################################################################

    /**
     * Yourls action plugins_loaded
     */
    public function action_plugins_loaded()
    {
        yourls_load_custom_textdomain(self::LOCALIZED_DOMAIN, realpath(dirname( __FILE__ ) . '/../translations'));
    }

    /**
     * Action activated_plugin
     *
     * @param array $args
     * @throws \Exception
     */
    public function action_activated_plugin(array $args)
    {
        list($plugin) = $args;

        if(false === stripos($plugin, self::LOCALIZED_DOMAIN)) {
            return;
        }

        foreach($this->_setting_url as $key => $val) {
            $this->addUrlSetting($key, $val);
        }
    }

    /**
     * Action deactivated_plugin
     *
     * @param array $args
     * @throws \Exception
     */
    public function action_deactivated_plugin(array $args)
    {
        list($plugin) = $args;

        if(false === stripos($plugin, self::LOCALIZED_DOMAIN)) {
            return;
        }

//        foreach($this->_setting_url as $key => $val) {
//            $this->dropUrlSetting($key, $val);
//        }
    }

    /**
     * Action: html_head
     */
    public function action_html_head()
    {
        echo '<script>' . file_get_contents(YOURLS_PLUGINDIR . '/' . self::LOCALIZED_DOMAIN . '/admin.js') . '</script>';

        $css = file_get_contents(YOURLS_PLUGINDIR . '/' . self::LOCALIZED_DOMAIN . '/style.css');
        $css = preg_replace_callback("/url\((.*?)\)/", function($matches) {
            $file = YOURLS_PLUGINDIR . '/' . self::LOCALIZED_DOMAIN . '/' . $matches[1];
            if(! is_file($file)) {
                return;
            }
            return 'url(data:'.mime_content_type($file).';base64,'.base64_encode(file_get_contents($file)).')';
        }, $css);

        echo '<style>' . $css . '</style>';
    }

    /**
     * Action insert_link
     *
     * @param array $args
     * @throws \Exception
     */
    public function action_insert_link(array $args)
    {
        list($insert, $url, $keyword, $title, $timestamp, $ip) = $args;

        $comment = $this->getRequest('comment');
        $label = $this->getRequest('label');

        $label = explode(',', $label);
        $label = array_map('trim', $label);
        $label = array_map('strtolower', $label);
        $label = array_filter($label);
        $label = array_unique($label);
        natsort($label);
        $label = json_encode($label);

        $this->updateUrlSetting([
            self::SETTING_URL_COMMENT => $comment,
            self::SETTING_URL_LABEL => $label,
        ], $keyword);
    }

    /**
     * Action yourls_ajax_laemmi_edit_comment_label_getfields
     */
    public function action_yourls_ajax_laemmi_edit_comment_label_getfields()
    {
        $html = $this->getHtmlFields(['comment' => '', 'label' => '']);
        echo json_encode($html);
    }

    /**
     * Action yourls_ajax_laemmi_edit_comment_label
     */
    public function action_yourls_ajax_laemmi_edit_comment_label()
    {
        $keyword = yourls_sanitize_string($this->getRequest('keyword'));
        $nonce = $this->getRequest('nonce');
        $id = yourls_string2htmlid($keyword);

        yourls_verify_nonce('laemmi_edit_comment_label_' . $id, $nonce, false, 'omg error');

        $nonce = yourls_create_nonce('laemmi_edit_comment_label_save_' . $id);

        $infos = yourls_get_keyword_infos($keyword);
        $comment = $infos[self::SETTING_URL_COMMENT];
        $label = json_decode($infos[self::SETTING_URL_LABEL], true);
        $label = implode(',', $label);

        $html = '
        <tr id="edit-' . $id . '" class="edit-row laemmi_edit_comment_label_row" data-id="' . $id . '"><td colspan="5">
        <form action="admin-ajax.php" method="post">
        <input type="hidden" name="action" value="laemmi_edit_comment_label_save" />
        <input type="hidden" name="keyword" value="' . $keyword . '" />
        <input type="hidden" name="nonce" value="' . $nonce . '" />';
        $html .= $this->getHtmlFields(['comment' => $comment, 'label' => $label]);
        $html .= '</form>
        </td><td colspan="1">
        <input class="button" type="button" name="save" value="' . yourls__('Save') . '">
        <input class="button" type="button" name="cancel" value="' . yourls__('Cancel') . '">
        </td></tr>';

        echo json_encode(['html' => $html]);
    }

    /**
     * Action yourls_ajax_laemmi_edit_comment_label_save
     */
    public function action_yourls_ajax_laemmi_edit_comment_label_save()
    {
        $keyword = yourls_sanitize_string($this->getRequest('keyword'));
        $nonce = $this->getRequest('nonce');
        $id = yourls_string2htmlid($keyword);

        yourls_verify_nonce('laemmi_edit_comment_label_save_' . $id, $nonce, false, 'omg error');

        $this->action_insert_link(['', '', $keyword, '', '', '']);

        $return = [];
        $return['status']  = 'success';
        $return['message'] = yourls__('Link updated in database', self::LOCALIZED_DOMAIN);

        echo json_encode($return);
    }

    ####################################################################################################################


//    public function filter_table_edit_row___()
//    {
//        list($return, $keyword, $url, $title) = func_get_args();
//
//        $doc = new \DOMDocument();
//        $doc->loadHTML($return, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
//
//        $infos = yourls_get_keyword_infos($keyword);
//
//        $td = $doc->getElementsByTagName('td');
//
//        $div = $doc->createElement('div');
//        $element = $doc->createElement('textarea', nl2br($infos[self::SETTING_URL_COMMENT]));
//        $element->setAttribute('name', 'comment');
//        $div->appendChild($doc->createElement('strong', 'Comment:'));
//        $div->appendChild($element);
//        $td[0]->appendChild($div);
//
//        $div = $doc->createElement('div');
//        $element = $doc->createElement('input');
//        $element->setAttribute('type', 'text');
//        $element->setAttribute('class', 'text');
//        $element->setAttribute('name', 'label');
//        $element->setAttribute('value', @implode(',',@json_decode($infos[self::SETTING_URL_LABEL])));
//        $div->appendChild($doc->createElement('strong', 'Label:'));
//        $div->appendChild($element);
//        $td[0]->appendChild($div);
//
//        $return = $doc->saveHTML();
//
//        return $return;
//    }

    /**
     * Filter table_add_row_action_array
     *
     * @return mixed
     */
    public function filter_table_add_row_action_array()
    {
        list($actions) = func_get_args();

        global $keyword;

        $id = yourls_string2htmlid($keyword);

        $href = yourls_nonce_url(
            'laemmi_edit_comment_label_' . $id,
            yourls_add_query_arg(['action' => 'laemmi_edit_comment_label', 'keyword' => $keyword], yourls_admin_url('admin-ajax.php'))
        );

        $actions['laemmi_edit_comment_label'] = [
                'href' => $href,
                'id' => '',
                'title' => yourls__('Edit comment & label', self::LOCALIZED_DOMAIN),
                'anchor' => 'edit_comment_label',
                'onclick' => ''
        ];

        return $actions;
    }

    public function filter_table_add_row_cell_array()
    {
        global $url_result;

        list($cells, $keyword, $url, $title, $ip, $clicks, $timestamp) = func_get_args();

        if(!isset($url_result)) {
            return $cells;
        }

        $label = json_decode($url_result->{self::SETTING_URL_LABEL}, true);
        $label = @implode('</span><span>', $label);

        if($label) {
            $cells['url']['template'] .= '<div class="laemmi_label"><span>%laemmi_label%</span></div>';
            $cells['url']['laemmi_label'] = $label;
        }

        $comment = trim($url_result->{self::SETTING_URL_COMMENT});

        if($comment) {
            $cells['url']['template'] .= '<dl class="laemmi_comment"><dt><a href="#">%laemmi_comment_title%</a></dt><dd>%laemmi_comment%</dd></dl>';
            $cells['url']['laemmi_comment_title'] = yourls__('Comment', self::LOCALIZED_DOMAIN);
            $cells['url']['laemmi_comment'] = $comment;
        }

        return $cells;
    }

    /**
     * Yourls filter admin_list_where
     *
     * @return string
     */
//    public function filter_admin_list_where()
//    {
//        list($where) = func_get_args();
//
//        $permissions = $this->helperGetAllowedPermissions();
//
//        if(! isset($permissions[self::PERMISSION_LIST_SHOW])) {
//            $or = [
//                self::SETTING_URL_USER_CREATE . " IS NULL",
//                self::SETTING_URL_USER_CREATE . " = '" . YOURLS_USER . "'"
//            ];
//
//            $where .= " AND (" . implode(' OR ', $or) . ")";
//        }
//
//        return $where;
//    }

    ####################################################################################################################

    /**
     * Returns the html fields for form
     *
     * @param array $options
     * @return string
     */
    private function getHtmlFields(array $options = [])
    {
        $html = '<div class="laemmi_form_field"><label>' . yourls__('Comment', self::LOCALIZED_DOMAIN) . ':</label> <textarea name="comment" rows="5">' . $options['comment'] . '</textarea></div>';
        $html .= '<div class="laemmi_form_field"><label>' . yourls__('Label', self::LOCALIZED_DOMAIN) . ':</label> <input class="text" type="text" name="label" placeholder="' . yourls__('Add labels comma separated', self::LOCALIZED_DOMAIN) . '" value="' . $options['label'] . '" /></div>';

        return $html;
    }
}