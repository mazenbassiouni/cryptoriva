<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------
namespace Think;
/**
 * Cache Management
 */
class Cache
{

    /**
     * Operation Handle
     * @var string
     * @access protected
     */
    protected $handler;

    /**
     * Cache connection parameters
     * @var integer
     * @access protected
     */
    protected $options = array();

    /**
     * Connection Cache
     * @access public
     * @param string $type Cache type
     * @param array $options ConfigurationArray
     * @return object
     */
    public function connect($type = '', $options = array())
    {

        if (empty($type)) $type = C('DATA_CACHE_TYPE');
		
        $class = strpos($type, '\\') ? $type : 'Think\\Cache\\Driver\\' . ucwords(strtolower($type));
        if (class_exists($class))
            $cache = new $class($options);
        else
            E(L('_CACHE_TYPE_INVALID_') . ':' . $type);
        return $cache;
    }

    /**
     * Cache obtain class instance
     * @static
     * @access public
     * @return mixed
     */
    static function getInstance($type = '', $options = array())
    {
        static $_instance = array();
        $guid = $type . to_guid_string($options);
        if (!isset($_instance[$guid])) {
            $obj = new Cache();
            $_instance[$guid] = $obj->connect($type, $options);
        }
        return $_instance[$guid];
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    public function __unset($name)
    {
        $this->rm($name);
    }

    public function setOptions($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function getOptions($name)
    {
        return $this->options[$name];
    }

    /**
     * Sequence Caching
     * @access protected
     * @param string $key queue Name
     * @return mixed
     */
    // 
    protected function queue($key)
    {
        static $_handler = array(
            'file' => array('F', 'F'),
            'xcache' => array('xcache_get', 'xcache_set'),
            'apc' => array('apc_fetch', 'apc_store'),
        );
        $queue = isset($this->options['queue']) ? $this->options['queue'] : 'file';
        $fun = isset($_handler[$queue]) ? $_handler[$queue] : $_handler['file'];
        $queue_name = isset($this->options['queue_name']) ? $this->options['queue_name'] : 'think_queue';
        $value = $fun[0]($queue_name);
        if (!$value) {
            $value = array();
        }
        // Enter row
        if (false === array_search($key, $value)) array_push($value, $key);
        if (count($value) > $this->options['length']) {
            // The column
            $key = array_shift($value);
            // Delete Cache
            $this->rm($key);
            if (APP_DEBUG) {
                //debugging mode ,recordingThe number of columns
                N($queue_name . '_out_times', 1);
            }
        }
        return $fun[1]($queue_name, $value);
    }

    public function __call($method, $args)
    {
        //transferCache typeOwnofmethod
        if (method_exists($this->handler, $method)) {
            return call_user_func_array(array($this->handler, $method), $args);
        } else {
            E(__CLASS__ . ':' . $method . L('_METHOD_NOT_EXIST_'));
            return;
        }
    }
}