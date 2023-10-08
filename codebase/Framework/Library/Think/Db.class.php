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
 * ThinkPHP databasemiddle layerImplementation class
 */
class Db
{

    static private $instance = array();     //  Examples of database connection
    static private $_instance = null;   //  The current database connection instance

    /**
     * Made database class instance
     * @static
     * @access public
     * @param mixed $config Connection Configuration
     * @return Object Returns the database driver class
     */
    static public function getInstance($config = array())
    {
        $md5 = md5(serialize($config));
        if (!isset(self::$instance[$md5])) {
            // Parse the connection parameters Support arrays and strings
            $options = self::parseConfig($config);
            // compatiblemysqli
            if ('mysqli' == $options['type']) $options['type'] = 'mysql';
            // Iflitethe way Only supports nativeSQL includequerywithexecutemethod
            $class = !empty($options['lite']) ? 'Think\Db\Lite' : 'Think\\Db\\Driver\\' . ucwords(strtolower($options['type']));
            if (class_exists($class)) {
                self::$instance[$md5] = new $class($options);
            } else {
                clog(__CLASS__.'/'.__METHOD__ ,  $class . L('_NO_DB_DRIVER_'));
                 echo "We are upgrading our system :FLTCC02";
                // classNodefinition
                //E(L('_NO_DB_DRIVER_') . ': ' . $class);
            }
        }
        self::$_instance = self::$instance[$md5];
        return self::$_instance;
    }

    /**
     * Database connection parameter parsing
     * @static
     * @access private
     * @param mixed $config
     * @return array
     */
    static private function parseConfig($config)
    {
        if (!empty($config)) {
            if (is_string($config)) {
                return self::parseDsn($config);
            }
            $config = array_change_key_case($config);
            $config = array(
                'type' => $config['db_type'],
                'username' => $config['db_user'],
                'password' => $config['db_pwd'],
                'hostname' => $config['db_host'],
                'hostport' => $config['db_port'],
                'database' => $config['db_name'],
                'dsn' => $config['db_dsn'] ?? null,
                'params' => $config['db_params'] ?? null,
                'charset' => $config['db_charset'] ?? 'utf8',
                'deploy' => $config['db_deploy_type'] ?? 0,
                'rw_separate' => $config['db_rw_separate'] ?? false,
                'master_num' => $config['db_master_num'] ?? 1,
                'slave_no' => $config['db_slave_no'] ?? '',
                'debug' => $config['db_debug'] ?? APP_DEBUG,
                'lite' => $config['db_lite'] ?? false,
            );
        } else {
            $config = array(
                'type' => C('DB_TYPE'),
                'username' => C('DB_USER'),
                'password' => C('DB_PWD'),
                'hostname' => C('DB_HOST'),
                'hostport' => C('DB_PORT'),
                'database' => C('DB_NAME'),
                'dsn' => C('DB_DSN'),
                'params' => C('DB_PARAMS'),
                'charset' => C('DB_CHARSET'),
                'deploy' => C('DB_DEPLOY_TYPE'),
                'rw_separate' => C('DB_RW_SEPARATE'),
                'master_num' => C('DB_MASTER_NUM'),
                'slave_no' => C('DB_SLAVE_NO'),
                'debug' => C('DB_DEBUG', null, APP_DEBUG),
                'lite' => C('DB_LITE'),
            );
        }
        return $config;
    }

    /**
     * DSNResolve
     * format: mysql://username:passwd@localhost:3306/DbName?param1=val1&param2=val2#utf8
     * @static
     * @access private
     * @param string $dsnStr
     * @return array
     */
    static private function parseDsn($dsnStr)
    {
        if (empty($dsnStr)) {
            return false;
        }
        $info = parse_url($dsnStr);
        if (!$info) {
            return false;
        }
        $dsn = array(
            'type' => $info['scheme'],
            'username' => $info['user'] ?? '',
            'password' => $info['pass'] ?? '',
            'hostname' => $info['host'] ?? '',
            'hostport' => $info['port'] ?? '',
            'database' => isset($info['path']) ? substr($info['path'], 1) : '',
            'charset' => $info['fragment'] ?? 'utf8',
        );

        if (isset($info['query'])) {
            parse_str($info['query'], $dsn['params']);
        } else {
            $dsn['params'] = array();
        }
        return $dsn;
    }

    // Method Invocation driving class
    static public function __callStatic($method, $params)
    {
        return call_user_func_array(array(self::$_instance, $method), $params);
    }
}
