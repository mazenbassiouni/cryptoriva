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

namespace Think\Db\Driver;

use Think\Db\Driver;

/**
 * mysqlDatabase-driven
 */
class Mysql extends Driver
{

    /**
     * Resolvepdoconnecteddsninformation
     * @access public
     * @param array $config Connection Information
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = 'mysql:dbname=' . $config['database'] . ';host=' . $config['hostname'];
        if (!empty($config['hostport'])) {
            $dsn .= ';port=' . $config['hostport'];
        } elseif (!empty($config['socket'])) {
            $dsn .= ';unix_socket=' . $config['socket'];
        }

        if (!empty($config['charset'])) {
            //It is compatible with all versionsPHP,Setting the encoding in two ways
            #$this->options[\PDO::MYSQL_ATTR_INIT_COMMAND]    =   'SET NAMES '.$config['charset'];
            $this->options[1002] = 'SET NAMES ' . $config['charset'];
            $dsn .= ';charset=' . $config['charset'];
        }
        return $dsn;
    }

    /**
     * Obtaindata sheetofFieldinformation
     * @access public
     */
    public function getFields($tableName)
    {
        $this->initConnect(true);
        list($tableName) = explode(' ', $tableName);
        if (strpos($tableName, '.')) {
            list($dbName, $tableName) = explode('.', $tableName);
            $sql = 'SHOW COLUMNS FROM `' . $dbName . '`.`' . $tableName . '`';
        } else {
            $sql = 'SHOW COLUMNS FROM `' . $tableName . '`';
        }

        $result = $this->query($sql);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                if (\PDO::CASE_LOWER != $this->_linkID->getAttribute(\PDO::ATTR_CASE)) {
                    $val = array_change_key_case($val, CASE_LOWER);
                }
                $info[$val['field']] = array(
                    'name' => $val['field'],
                    'type' => $val['type'],
                    'notnull' => (bool)($val['null'] === ''), // not null is empty, null is yes
                    'default' => $val['default'],
                    'primary' => (strtolower($val['key']) == 'pri'),
                    'autoinc' => (strtolower($val['extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }

    /**
     * ObtainDatabasetableinformation
     * @access public
     */
    public function getTables($dbName = '')
    {
        $sql = !empty($dbName) ? 'SHOW TABLES FROM ' . $dbName : 'SHOW TABLES ';
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     * Processing field and table names
     * @access protected
     * @param string $key
     * @return string
     */
    protected function parseKey(&$key)
    {
        $key = trim($key);
        if (!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            $key = '`' . $key . '`';
        }
        return $key;
    }

    /**
     * Bulk Insert record
     * @access public
     * @param mixed $dataSet data set
     * @param array $options Parameter expression
     * @param boolean $replace whetherreplace
     * @return false | integer
     */
    public function insertAll($dataSet, $options = array(), $replace = false)
    {
        $values = array();
        $this->model = $options['model'];
        if (!is_array($dataSet[0])) return false;
        $this->parseBind(!empty($options['bind']) ? $options['bind'] : array());
        $fields = array_map(array($this, 'parseKey'), array_keys($dataSet[0]));
        foreach ($dataSet as $data) {
            $value = array();
            foreach ($data as $key => $val) {
                if (is_array($val) && 'exp' == $val[0]) {
                    $value[] = $val[1];
                } elseif (is_null($val)) {
                    $value[] = 'NULL';
                } elseif (is_scalar($val)) {
                    if (0 === strpos($val, ':') && in_array($val, array_keys($this->bind))) {
                        $value[] = $this->parseValue($val);
                    } else {
                        $name = count($this->bind);
                        $value[] = ':' . $name;
                        $this->bindParam($name, $val);
                    }
                }
            }
            $values[] = '(' . implode(',', $value) . ')';
        }
        // compatibledigitalAfferentthe way
        $replace = (is_numeric($replace) && $replace > 0) ? true : $replace;
        $sql = (true === $replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTable($options['table']) . ' (' . implode(',', $fields) . ') VALUES ' . implode(',', $values) . $this->parseDuplicate($replace);
        $sql .= $this->parseComment(!empty($options['comment']) ? $options['comment'] : '');
        return $this->execute($sql, !empty($options['fetch_sql']) ? true : false);
    }

    /**
     * ON DUPLICATE KEY UPDATE analysis
     * @access protected
     * @param mixed $duplicate
     * @return string
     */
    protected function parseDuplicate($duplicate)
    {
        // Boolean, or is emptyreturnairString
        if (is_bool($duplicate) || empty($duplicate)) return '';

        if (is_string($duplicate)) {
            // field1,field2 Transfer array
            $duplicate = explode(',', $duplicate);
        } elseif (is_object($duplicate)) {
            // Turn an array of objects
            $duplicate = get_class_vars($duplicate);
        }
        $updates = array();
        foreach ((array)$duplicate as $key => $val) {
            if (is_numeric($key)) { // array('field1', 'field2', 'field3') Resolves to ON DUPLICATE KEY UPDATE field1=VALUES(field1), field2=VALUES(field2), field3=VALUES(field3)
                $updates[] = $this->parseKey($val) . "=VALUES(" . $this->parseKey($val) . ")";
            } else {
                if (is_scalar($val)) // Compatible scalar by value
                    $val = array('value', $val);
                if (!isset($val[1])) continue;
                switch ($val[0]) {
                    case 'exp': // expression
                        $updates[] = $this->parseKey($key) . "=($val[1])";
                        break;
                    case 'value': // value
                    default:
                        $name = count($this->bind);
                        $updates[] = $this->parseKey($key) . "=:" . $name;
                        $this->bindParam($name, $val[1]);
                        break;
                }
            }
        }
        if (empty($updates)) return '';
        return " ON DUPLICATE KEY UPDATE " . join(', ', $updates);
    }


    /**
     * Execute a stored procedure queries Return the plurality of data sets
     * @access public
     * @param string $str sqlinstruction
     * @param boolean $fetchSql Just do not get executedSQL
     * @return mixed
     */
    public function procedure($str, $fetchSql = false)
    {
        $this->initConnect(false);
        $this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING);
        if (!$this->_linkID) return false;
        $this->queryStr = $str;
        if ($fetchSql) {
            return $this->queryStr;
        }
        //Previous releaseofsearch result
        if (!empty($this->PDOStatement)) $this->free();
        $this->queryTimes++;
        N('db_query', 1); // compatibleCode
        // debuggingStart
        $this->debug(true);
        $this->PDOStatement = $this->_linkID->prepare($str);
        if (false === $this->PDOStatement) {
            $this->error();
            return false;
        }
        try {
            $result = $this->PDOStatement->execute();
            // debuggingEnd
            $this->debug(false);
            do {
                $result = $this->PDOStatement->fetchAll(\PDO::FETCH_ASSOC);
                if ($result) {
                    $resultArr[] = $result;
                }
            } while ($this->PDOStatement->nextRowset());
            $this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, $this->options[\PDO::ATTR_ERRMODE]);
            return $resultArr;
        } catch (\PDOException $e) {
            $this->error();
            $this->_linkID->setAttribute(\PDO::ATTR_ERRMODE, $this->options[\PDO::ATTR_ERRMODE]);
            return false;
        }
    }
}
