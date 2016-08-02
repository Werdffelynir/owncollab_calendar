<?php

namespace OCA\Owncollab_Calendar\Db;


use League\Flysystem\Exception;

class CalendarGroup
{
    /** @var Connect $connect object instance working with database */
    private $connect;

    /** @var string $tableName table name in database */
    private $tableName;


    /** @var string $fields table fields name in database */
    private $fields = [
        'gid',
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

    public function getGroupsId()
    {
        $sql = "SELECT gid
                FROM `{$this->tableName}` ";
        $result = $this->connect->queryAll($sql);
        $arrId = [];
        for ($i = 0; $i < count($result); $i++) {
            $arrId[] = $result[$i]['gid'];
        }
        return $arrId;
    }

    public function getById($gid)
    {
        $sql = "SELECT `id_tasks` FROM `{$this->tableName}`
                WHERE gid = :gid";
        $result = $this->connect->query($sql, [':gid' => $gid]);
        return (array)(json_decode($result['id_tasks']));
    }

    public function insertAllById(array $data)
    {
        if (!count($data))
            return false;
        $data = array_values($data);
        $sql = "INSERT INTO `{$this->tableName}` (`gid`) VALUES (:gid)";

        for ($i = 0; $i < count($data); $i++) {
            $this->connect->db->prepare($sql)->execute(array(':gid' => $data[$i]));
        }

        return true;

    }


    public function deleteAllById(array $ids)
    {
        if (!count($ids))
            return false;
        $ids = array_values($ids);
        $result = false;
        $prep = '';
        $bind = [];
        try {
            for ($i = 0; $i < count($ids); $i++) {
                if (!empty($prep)) $prep .= " OR ";
                $prep .= "gid = :gid$i";
                $bind[":gid$i"] = $ids[$i];
            }
            $result = $this->connect->delete($this->tableName, $prep, $bind);
            if ($result) {
                $result = $result->rowCount();
            }
        } catch (\AbstractDriverException $error) {
        }
        return $result;
    }

    public function updateUsersTasks($gid, array $usersTasks)
    {
        $result = false;
        $usersTasks = json_encode($usersTasks);
        if(!$usersTasks)
            $usersTasks = '';
        $sql = "UPDATE `{$this->tableName}`
                    SET `id_tasks` = :id_tasks
                    WHERE `gid` = :gid";
        $result = $this->connect->db->executeUpdate($sql, [
            ':gid' => $gid,
            ':id_tasks' => $usersTasks
        ]);
        return $result;
    }


}