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

use Laemmi\Yourls\Plugin\AbstractDefault;

/**
 * Class Plugin
 *
 * @package Laemmi\Yourls\Comment\Label
 */
class Plugin extends AbstractDefault
{
    /**
     * Namespace
     */
    const APP_NAMESPACE = 'laemmi-yourls-comment-label';

    /**
     * Settings constants
     */
    const SETTING_URL_COMMENT = 'laemmi_comment';
    const SETTING_URL_LABEL = 'laemmi_label';

    /**
     * Permission constants
     */
    const PERMISSION_ACTION_EDIT_COMMENT = 'action-edit-comment';
    const PERMISSION_ACTION_EDIT_LABEL = 'action-edit-label';
    const PERMISSION_LIST_SHOW_COMMENT = 'list-show-comment';
    const PERMISSION_LIST_SHOW_LABEL = 'list-show-label';

    /**
     * Settings for url table
     *
     * @var array
     */
    protected $_setting_url = [
        self::SETTING_URL_COMMENT => ["field" => "TEXT"],
        self::SETTING_URL_LABEL => ["field" => "TEXT"]
    ];

    /**
     * Options
     *
     * @var array
     */
    protected $_options = [
        'allowed_groups' => []
    ];

    /**
     * Admin permissions
     *
     * @var array
     */
    protected $_adminpermission = [
        self::PERMISSION_ACTION_EDIT_COMMENT,
        self::PERMISSION_ACTION_EDIT_LABEL,
        self::PERMISSION_LIST_SHOW_COMMENT,
        self::PERMISSION_LIST_SHOW_LABEL
    ];

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->startSession();
        parent::__construct($options);
    }

    ####################################################################################################################

    /**
     * Yourls action plugins_loaded
     */
    public function action_plugins_loaded()
    {
        yourls_load_custom_textdomain(self::APP_NAMESPACE, realpath(dirname( __FILE__ ) . '/../translations'));
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

        if(false === stripos($plugin, self::APP_NAMESPACE)) {
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

        if(false === stripos($plugin, self::APP_NAMESPACE)) {
            return;
        }

//        foreach($this->_setting_url as $key => $val) {
//            $this->dropUrlSetting($key, $val);
//        }
    }

    /**
     * Action: html_head
     *
     * @param array $args
     */
    public function action_html_head(array $args)
    {
        list($context) = $args;

        if('index' === $context) {
            echo $this->getJsScript('assets/admin.js');
            echo $this->getCssStyle();
        }
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

        $permissions = $this->helperGetAllowedPermissions();

        $data = [];

        if(isset($permissions[self::PERMISSION_ACTION_EDIT_COMMENT])) {
            $comment = $this->getRequest('comment');
            $data[self::SETTING_URL_COMMENT] = $comment;
        }

        if(isset($permissions[self::PERMISSION_ACTION_EDIT_LABEL])) {
            $label = $this->getRequest('label');
            $label = explode(',', $label);
            $label = array_map('trim', $label);
            $label = array_map('strtolower', $label);
            $label = array_filter($label);
            $label = array_unique($label);
            natsort($label);
            $label = json_encode($label);
            $data[self::SETTING_URL_LABEL] = $label;
        }

        if(!$data) {
            return;
        }

        $this->updateUrlSetting($data, $keyword);
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
        $return['message'] = yourls__('Link updated in database', self::APP_NAMESPACE);

        echo json_encode($return);
    }

    ####################################################################################################################

    /**
     * Filter table_add_row_action_array
     *
     * @return mixed
     */
    public function filter_table_add_row_action_array()
    {
        list($actions) = func_get_args();

        $permissions = $this->helperGetAllowedPermissions();

        if(! isset($permissions[self::PERMISSION_ACTION_EDIT_COMMENT]) && ! isset($permissions[self::PERMISSION_ACTION_EDIT_LABEL])) {
            return $actions;
        }

        global $keyword;

        $id = yourls_string2htmlid($keyword);

        $href = yourls_nonce_url(
            'laemmi_edit_comment_label_' . $id,
            yourls_add_query_arg(['action' => 'laemmi_edit_comment_label', 'keyword' => $keyword], yourls_admin_url('admin-ajax.php'))
        );

        $actions['laemmi_edit_comment_label'] = [
                'href' => $href,
                'id' => '',
                'title' => yourls__('Edit comment & label', self::APP_NAMESPACE),
                'anchor' => 'edit_comment_label',
                'onclick' => ''
        ];

        return $actions;
    }

    /**
     * Filter: table_add_row_cell_array
     *
     * @return mixed
     */
    public function filter_table_add_row_cell_array()
    {
        global $url_result;

        list($cells, $keyword, $url, $title, $ip, $clicks, $timestamp) = func_get_args();

        if(!isset($url_result)) {
            return $cells;
        }

        $permissions = $this->helperGetAllowedPermissions();

        if(isset($permissions[self::PERMISSION_LIST_SHOW_LABEL])) {
            $label = json_decode($url_result->{self::SETTING_URL_LABEL}, true);
            $label = @implode('</span><span>', $label);
            if ($label) {
                $cells['url']['template'] .= '<div class="laemmi_label"><span>%laemmi_label%</span></div>';
                $cells['url']['laemmi_label'] = $label;
            }
        }

        if(isset($permissions[self::PERMISSION_LIST_SHOW_COMMENT])) {
            $comment = trim($url_result->{self::SETTING_URL_COMMENT});
            if($comment) {
                $cells['url']['template'] .= '<div class="laemmi_comment"><dl><dt><a href="#">%laemmi_comment_title%</a></dt><dd>%laemmi_comment%</dd></dl></div>';
                $cells['url']['laemmi_comment_title'] = yourls__('Comment', self::APP_NAMESPACE);
                $cells['url']['laemmi_comment'] = $comment;
            }
        }

        return $cells;
    }

    ####################################################################################################################

    /**
     * Returns the html fields for form
     *
     * @param array $options
     * @return string
     */
    private function getHtmlFields(array $options = [])
    {
        $permissions = $this->helperGetAllowedPermissions();

        $html = '';
        if(isset($permissions[self::PERMISSION_ACTION_EDIT_COMMENT])) {
            $html .= '<div class="laemmi_form_field"><label>' . yourls__('Comment', self::APP_NAMESPACE) . ':</label> <textarea name="comment" rows="5">' . $options['comment'] . '</textarea></div>';
        }
        if(isset($permissions[self::PERMISSION_ACTION_EDIT_LABEL])) {
            $html .= '<div class="laemmi_form_field"><label>' . yourls__('Label', self::APP_NAMESPACE) . ':</label> <input class="text" type="text" name="label" placeholder="' . yourls__('Add labels comma separated', self::APP_NAMESPACE) . '" value="' . $options['label'] . '" /></div>';
        }

        return $html;
    }

    /**
     * Get allowed permissions
     *
     * @return array
     */
    private function helperGetAllowedPermissions()
    {
        if($this->getSession('login', 'laemmi-yourls-easy-ldap')) {
            $inter = array_intersect_key($this->_options['allowed_groups'], $this->getSession('groups', 'laemmi-yourls-easy-ldap'));
            $permissions = [];
            foreach ($inter as $val) {
                foreach ($val as $_val) {
                    $permissions[$_val] = $_val;
                }
            }
        } else {
            $permissions = array_combine($this->_adminpermission, $this->_adminpermission);
        }

        return $permissions;
    }
}