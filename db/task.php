<?php
/**
 * Created by PhpStorm.
 * User: werd
 * Date: 28.01.16
 * Time: 22:29
 */

namespace OCA\Owncollab_Calendar\Db;


use OCA\Owncollab\Helper;

class Task
{
    /** @var Connect $connect object instance working with database */
    private $connect;

    /** @var string $tableName table name in database */
    private $tableName;

    /** @var string $fields table fields name in database */
    private $fields = [
        'id',
        'is_project',
        'type',
        'text',
        'users',
        'start_date',
        'end_date',
        'duration',
        'order',
        'progress',
        'sortorder',
        'parent',
        'open',
        'buffer',
    ];

    /**
     * Task constructor.
     * @param $connect
     * @param $tableName
     */
    public function __construct($connect, $tableName) {
        $this->connect = $connect;
        $this->tableName = '*PREFIX*' . $tableName;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id) {
        $project = $this->connect->select("*", $this->tableName, "id = :id", [':id' => $id]);
        if(count($project)===1) return $project[0];
        else return false;
    }

    /**
     * Get last id
     * @return mixed
     */
    public function getLastId() {
        $result = $this->connect->query("SELECT id FROM `{$this->tableName}` ORDER BY id DESC LIMIT 1");
        return (!$result) ? 1 : $result['id'];
    }

    /**
     * @param $id
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function deleteById($id) {
        $result = $this->connect->delete($this->tableName, 'id = :id', [':id' => $id]);
        return ($result) ? $result->rowCount() : $result;
    }


    /**
     * @param $task
     * @return int
     */
    public function update($task) {
       $sql = "UPDATE {$this->tableName} SET
                  type = :type, text = :text, users = :users, start_date = :start_date, end_date = :end_date, duration = :duration, progress = :progress, parent = :parent, open = :open, buffer = :buffer
                  WHERE id = :id";

        return  $this->connect->db->executeUpdate($sql, [
            ':type'         => $task['type'] ? $task['type'] : 'task',
            ':text'         => $task['text'] ? $task['text'] : 'text',
            ':users'        => $task['users'] ? $task['users'] : '',
            ':start_date'   => Helper::toTimeFormat($task['start_date']),
            ':end_date'     => Helper::toTimeFormat($task['end_date']),
            ':duration'     => $task['duration'] ? $task['duration'] : 0,
            ':progress'     => $task['progress'] ? $task['progress'] : 0,
            ':parent'       => $task['parent'] ? $task['parent'] : 0,
            ':open'         => $task['open'] ? 1 : 0,
            ':buffer'       => $task['buffer'] ? $task['buffer'] : 0,
            ':id'           => (int)$task['id']
        ]);
    }

    /**
     * @param $data
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function insertWithId($data) {

        $task['id'] = $data['id'];
        $task['is_project'] = $data['is_project'];
        $task['type'] = $data['type'] ? $data['type'] : 'task';
        $task['text'] = $data['text'];
        $task['users'] = $data['users'];
        $task['start_date'] = Helper::toTimeFormat($data['start_date']);
        $task['end_date'] = Helper::toTimeFormat($data['end_date']);
        $task['duration'] = $data['duration'] ? $data['duration'] : 0;
        $task['order'] = $data['order'] ? $data['order'] : 0;
        $task['progress'] = $data['progress'] ? $data['progress'] : 0;
        $task['sortorder'] = $data['sortorder'] ? $data['sortorder'] : 0;
        $task['parent'] = $data['parent'] ? $data['parent'] : 1;

        $result = $this->connect->db->insertIfNotExist($this->tableName, $task);


        if($result)
            return $this->connect->db->lastInsertId();
        return $result;
    }

    /**
     * Retrieve tasks-data of project
     * Database query selects all opened records, and all columns of type timestamp output
     * formatting for JavaScript identification
     * @return array|null
     */
    public function get(){
        $sql = "SELECT *,
                DATE_FORMAT( `start_date`, '%d-%m-%Y %H:%i:%s') as start_date,
                DATE_FORMAT( `end_date`, '%d-%m-%Y %H:%i:%s') as end_date
                FROM `{$this->tableName}` WHERE open = 1";
        return $this->connect->queryAll($sql);
    }
    public function getCountFinishedTasks(){
        $sql = "SELECT COUNT(*) as count
                FROM `{$this->tableName}` WHERE progress = 1";
       $result = $this->connect->query($sql);
        return $result['count'];
    }
    public function getCountAllTasks(){
        $sql = "SELECT COUNT(*) as count
                FROM `{$this->tableName}`";
        $result = $this->connect->query($sql);
        return $result['count'];
    }



}