<?php

namespace OCA\Owncollab_Calendar\Db;


use League\Flysystem\Exception;

class Users
{
    /** @var Connect $connect object instance working with database */
    private $connect;

    /** @var string $tableName table name in database */
    private $tableName;
    

    /**
     * Project constructor.
     * @param $connect
     * @param $tableName
     */
    public function __construct($connect, $tableName) {
        $this->connect = $connect;
        $this->tableName = '*PREFIX*' . $tableName;
    }

    public function get(){
        $sql = "SELECT uid
                FROM `{$this->tableName}` ";
        $result = $this->connect->queryAll($sql);
        
        return $result;
    }


}