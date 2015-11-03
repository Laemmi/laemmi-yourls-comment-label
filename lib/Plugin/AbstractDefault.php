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
 * @category    Laemmi\Yourls\Plugin
 * @package     Laemmi\Yourls\Plugin
 * @author      Michael Lämmlein <ml@spacerabbit.de>
 * @copyright   ©2015 laemmi
 * @license     http://www.opensource.org/licenses/mit-license.php MIT-License
 * @version     1.0.0
 * @since       23.10.15
 */

/**
 * Namespace
 */
namespace Laemmi\Yourls\Plugin;

/**
 * Class AbstractDefault
 *
 * @package Laemmi\Yourls\Plugin
 */
class AbstractDefault
{
    /**
     * Session namespace
     */
    const SESSION_NAMESPACE = 'default';

    /**
     * Options
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);

        foreach (get_class_methods($this) as $key => $method) {
            switch(substr($method, 0, 7)) {
                case 'action_':
                    $this->addAction($method);
                    break;
                case 'filter_':
                    $this->addFilter($method);
                    break;
            }
        }
    }

    /**
     * Set options
     *
     * @param array $options
     * @return $this
     */
    protected function setOptions(array $options)
    {
        $options = array_filter($options);
        $this->_options = array_merge($this->_options, $options);

        return $this;
    }

    /**
     * Add action function
     *
     * @param $name
     */
    protected function addAction($name)
    {
//         $hook, $function_name, $priority = 10, $accepted_args = 1, $type = 'action'
//        yourls_add_filter(substr($name, 7), [$this, $name], 10, 1, 'action');
        yourls_add_action(substr($name, 7), [$this, $name]);
    }

    /**
     * Add filter function
     *
     * @param $name
     */
    protected function addFilter($name)
    {
//         $hook, $function_name, $priority = 10, $accepted_args = NULL, $type = 'filter'
//        yourls_add_filter(substr($name, 7), [$this, $name], 10, NULL, 'filter');
        yourls_add_filter(substr($name, 7), [$this, $name]);
    }

    /**
     * Get db connection
     *
     * @return mixed
     * @throws \Exception
     */
    protected function db()
    {
        if(! isset($this->_options['db']) && ! $this->_options['db'] instanceof ezSQLcore) {
            throw new \Exception('No database instance available');
        }

        return $this->_options['db'];
    }

    /**
     * Return DateTime instance
     *
     * @param string $time
     * @return \DateTime
     */
    protected function getDateTime($time='now')
    {
        $obj = new \DateTime($time);
        return $obj;
    }

    /**
     * Add setting column in url table
     *
     * @param $key
     * @param $val
     * @throws \Exception
     */
    protected function addUrlSetting($key, $val)
    {
        $query = "SHOW COLUMNS FROM " . YOURLS_DB_TABLE_URL . " LIKE '%s'";
        $results = $this->db()->get_results(sprintf($query, $key));
        if(! $results) {
            $query = "ALTER TABLE " . YOURLS_DB_TABLE_URL . " ADD (%s)";
            $this->db()->query(sprintf($query, $key . " " . $val['field']));
        }
    }

    /**
     * Drop setting column in url table
     *
     * @param $key
     * @throws \Exception
     */
    protected function dropUrlSetting($key)
    {
        $query = "SHOW COLUMNS FROM " . YOURLS_DB_TABLE_URL . " LIKE '%s'";
        $results = $this->db()->get_results(sprintf($query, $key));
        if($results) {
            $query = "ALTER TABLE " . YOURLS_DB_TABLE_URL . " DROP %s";
            $this->db()->query(sprintf($query, $key));
        }
    }

    /**
     * Update setting in url table
     *
     * @param array $values
     * @param $keyword
     * @return mixed
     * @throws \Exception
     */
    protected function updateUrlSetting(array $values, $keyword)
    {
        $query = "UPDATE
            ".YOURLS_DB_TABLE_URL."
         SET
            %s
         WHERE
            keyword = '%s'
         LIMIT 1";

        $data = [];
        foreach($values as $key => $val) {
            $data[] = $key . "='" . $val ."'";
        }

        return $this->db()->query(sprintf($query, implode(',', $data), $keyword));
    }

    ####################################################################################################################

    /**
     * Start session
     */
    protected function startSession()
    {
        if(session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /**
     * Set session value
     *
     * @param $key
     * @param $value
     * @param null $namespace
     */
    protected function setSession($key, $value, $namespace=null)
    {
        $namespace = $this->getSessionNamespace($namespace);

        $_SESSION['laemmi'][$namespace][$key] = $value;
    }

    /**
     * Get session value
     *
     * @param $key
     * @param null $namespace
     * @return bool
     */
    protected function getSession($key, $namespace=null)
    {
        $namespace = $this->getSessionNamespace($namespace);

        return isset($_SESSION['laemmi'][$namespace][$key]) ? $_SESSION['laemmi'][$namespace][$key] : false;
    }

    /**
     * Reset session
     *
     * @param null $namespace
     */
    protected function resetSession($namespace=null)
    {
        $namespace = $this->getSessionNamespace($namespace);

        unset($_SESSION['laemmi'][$namespace]);
    }

    /**
     * Get session namespace
     *
     * @param null $namespace
     * @return null|string
     */
    protected function getSessionNamespace($namespace=null)
    {
        $namespace = !is_null($namespace) ? $namespace : self::SESSION_NAMESPACE;

        return $namespace;
    }

    ####################################################################################################################

    /**
     * Get request value
     *
     * @param $key
     * @return null
     */
    protected function getRequest($key)
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
    }

    /**
     * Set request value
     *
     * @param $key
     * @param $val
     */
    protected function setRequest($key, $val)
    {
        $_REQUEST[$key] = $val;
    }
}