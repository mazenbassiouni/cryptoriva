<?php

use Workerman\Connection;

/**
 * Created by Codono.
 * User: Ronald C.
 * Date: 2019/1/10
 * Time: 11:51
 */
class SocketRedis
{

    private Redis $redis;
    private $host;  //redis ip
    private $port;  //redis port
    public static $db = null;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        //Connect redis
        if (class_exists('Redis')) {
            $this->redis = new Redis();
            if ($this->redis->connect($this->host, $this->port)) {
                $this->connect = true;
            }
        } else {
            exit('redis extension does not exist');
        }
    }


    /**
     * Notes: Write data to the has table
     * User: ${USER}
     * Date: ${DATE}
     * Time: ${TIME}
     * @param $table
     */
    public function write($table, $data)
    {
        //Batch settings
        return $this->redis->hMset($table, $data);
    }

    /**
     * Notes:Find if there are duplicate ids. If there are duplicates, update the coverage. If there are no duplicates, take them out and save them in mysql.
     * User: ${USER}
     * Date: ${DATE}
     * Time: ${TIME}
     * @param $table
     * @param $id
     * @return int
     */
    public function SeachId($table, $id)
    {

        $idtime = $this->redis->hGet($table, 'time'); // Get time field value in table h

        if ($idtime) {
            if ($idtime == $id) {
                return 1;       //Existence equality
            } else {
                return 2;       //not equal
            }
        }
        return 0;  //does not exist
    }


    /**
     * Notes: Read data from cached has table
     * User: ${USER}
     * Date: ${DATE}
     * Time: ${TIME}
     * @param $table
     * @return array
     */
    public function read($table)
    {

        return $this->redis->hGetAll($table);
    }


    public function insertmysql($table, $data)
    {
        self::$db = new Connection('127.0.0.1', '3306', 'root', 'root', 'huobiapi');

        $insert_id = static::$db->insert($table)->cols($data)->query();

        return $insert_id;
    }


}

// $huobiredis= new HuobiRedis("127.0.0.1",6379);

// $huobiredis->huobi1min();

//   $ids= $huobiredis->SeachId("klin1mina",1556160420);

// $tables=$huobiredis->Read("klin1min");
/*  $data=array (
      'id' => 1556150400,
      'open' => 5413.0,
      'close' => 5409.02,
      'low' => 5404.12,
      'high' => 5436.0,
      'amount' => 1776.0156640772,
      'vol' => 9631184.0369913,
      'count' => 15163,
  );
  $huobiredis->write("klin1mins",$data)*/

?>