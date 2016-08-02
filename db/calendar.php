<?php

namespace OCA\Owncollab_Calendar\Db;


use League\Flysystem\Exception;

class Calendar
{
    /** @var Connect $connect object instance working with database */
    private $connect;

    /** @var string $tableName table name in database */
    private $tableName;


    /** @var string $fields table fields name in database */
    private $fields = [
        'uid',
        'id_tasks',
    ];

    /**
     * Project constructor.
     * @param $connect
     * @param $tableName
     */

    public function __construct($connect, $tableName)
    {
        $this->connect = $connect;
        $this->tableName = '*PREFIX*' . $tableName;
    }


    public function get()
    {
        $sql = "SELECT *
                FROM `{$this->tableName}` ";
        $result = $this->connect->queryAll($sql);
        return $result;
    }

    public function getUsersId()
    {
        $sql = "SELECT uid
                FROM `{$this->tableName}` ";
        $result = $this->connect->queryAll($sql);
        $arrId = [];
        for ($i = 0; $i < count($result); $i++) {
            $arrId[] = $result[$i]['uid'];
        }
        return $arrId;
    }
    public function getById($uid) {
        $sql = "SELECT `id_tasks` FROM `{$this->tableName}`
                WHERE uid = :uid";
        $result = $this->connect->query($sql, [':uid'=>$uid]);
        return (array)(json_decode($result['id_tasks']));
    }

    public function insertAllById(array $data)
    {
        if(!count($data))
            return false;
        $data = array_values($data);
        $sql = "INSERT INTO `{$this->tableName}` (`uid`) VALUES (:uid)";

        for ($i = 0; $i<count($data); $i++) {
            $this->connect->db->prepare($sql)->execute(array(':uid' => $data[$i]));
        }

        return true;

    }
    

    public function deleteAllById(array $ids)
    {
        if(!count($ids))
            return false;
        $ids = array_values($ids);
        $result = false;
        $prep = '';
        $bind = [];
        try{
            for($i = 0; $i < count($ids); $i ++){
                if(!empty($prep)) $prep .= " OR ";
                $prep .= "uid = :uid$i";
                $bind[":uid$i"] = $ids[$i];
            }
            $result = $this->connect->delete($this->tableName, $prep, $bind);
            if($result){
                $result = $result->rowCount();
            }
        }catch(\AbstractDriverException $error ){}
        return $result;
    }
    public function update($uid, $value) {
        $result = false;

            $sql = "UPDATE `{$this->tableName}`
                    SET `id_tasks` = :id_tasks
                    WHERE `uid` = :uid";
            $result = $this->connect->db->executeUpdate($sql, [
                ':uid' => $uid,
                ':id_tasks' => $value
            ]);
        return $result;
    }




}