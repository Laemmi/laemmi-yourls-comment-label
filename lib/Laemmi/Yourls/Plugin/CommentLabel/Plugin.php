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
namespace Laemmi\Yourls\Plugin\CommentLabel;

use Laemmi\Yourls\Plugin\AbstractDefault;

/**
 * Class Plugin
 *
 * @package Laemmi\Yourls\Plugin\CommentLabel
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
     * Keyword
     *
     * @var null
     */
    private $_keyword = null;

    /**
     * Init
     */
    public function init()
    {
        $this->startSession();
        parent::__construct();
        $this->initTemplate();
    }

    ####################################################################################################################

    /**
     * Yourls action plugins_loaded
     */
    public function action_plugins_loaded()
    {
        $this->loadTextdomain();
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

        $html = $this->getTemplate()->render('edit_row_comment_label', [
            'keyword' => $keyword,
            'nonce' => $nonce,
            'id' => $id,
            'comment' => $comment,
            'label' => $label,
            'PERMISSION_ACTION_EDIT_COMMENT' => $this->_hasPermission(self::PERMISSION_ACTION_EDIT_COMMENT),
            'PERMISSION_ACTION_EDIT_LABEL' => $this->_hasPermission(self::PERMISSION_ACTION_EDIT_LABEL),
        ]);

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
     * Filter yourls_link
     *
     * @return mixed
     */
    public function filter_yourls_link()
    {
        list($link, $keyword) = func_get_args();

        $this->_keyword = $keyword;

        return $link;
    }

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

        $id = yourls_string2htmlid($this->_keyword);

        $href = yourls_nonce_url(
            'laemmi_edit_comment_label_' . $id,
            yourls_add_query_arg(['action' => 'laemmi_edit_comment_label', 'keyword' => $this->_keyword], yourls_admin_url('admin-ajax.php'))
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
        list($cells, $keyword, $url, $title, $ip, $clicks, $timestamp) = func_get_args();

        $permissions = $this->helperGetAllowedPermissions();

        if(isset($permissions[self::PERMISSION_LIST_SHOW_LABEL])) {
            $infos = yourls_get_keyword_infos($keyword);
            $label = json_decode($infos[self::SETTING_URL_LABEL], true);
            $label = @implode('</span><span>', $label);
            if ($label) {
                $cells['url']['template'] .= '<div class="laemmi_label"><span>%laemmi_label%</span></div>';
                $cells['url']['laemmi_label'] = $label;
            }
        }

        if(isset($permissions[self::PERMISSION_LIST_SHOW_COMMENT])) {
            $infos = yourls_get_keyword_infos($keyword);
            $comment = trim($infos[self::SETTING_URL_COMMENT]);
            if($comment) {
                $cells['url']['template'] .= '<div class="laemmi_comment"><dl><dt><a href="#">%laemmi_comment_title%</a></dt><dd>%laemmi_comment%</dd></dl></div>';
                $cells['url']['laemmi_comment_title'] = yourls__('Comment', self::APP_NAMESPACE);
                $cells['url']['laemmi_comment'] = $comment;
            }
        }

        return $cells;
    }
}