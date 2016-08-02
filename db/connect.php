<?php

namespace OCA\Owncollab_Calendar\Db;


use \OCP\IDBConnection;

class Connect
{
    /** @var IDBConnection  */
    public $db;

    /** @var Project model of database table */
    private $project;

    /** @var Task model of database table */
    private $task;

    /** @var CalendarGroup model of database table */
    private $link;



    /**
     * Connect constructor.
     * @param IDBConnection $db
     */
    public function __construct(IDBConnection $db) {
        $this->db = $db;

        // Register tables models
        //$this->resource = new Resource($this, 'collab_resources');
        $this->task = new Task($this, 'collab_tasks');
        $this->users = new Users($this, 'users');
        $this->groups = new Groups($this, 'groups');
        $this->calendar = new Calendar($this, 'collab_calendar');
        $this->groupUser = new GroupUser($this, 'group_user');
        $this->calendarGroup = new CalendarGroup($this, 'collab_calendar_group');
        $this->calendarChanges = new CalendarChanges($this, 'calendarchanges');
        $this->calendarObjects = new CalendarObjects($this, 'calendarobjects');
    }

    /**
     * Execute prepare SQL string $query with binding $params, and return one record
     * @param $query
     * @param array $params
     * @return mixed
     */
    public function query($query, array $params = []) {
        return $this->db->executeQuery($query, $params)->fetch();
    }

    /**
     * Execute prepare SQL string $query with binding $params, and return all match records
     * @param $query
     * @param array $params
     * @return mixed
     */
    public function queryAll($query, array $params = []) {
        return $this->db->executeQuery($query, $params)->fetchAll();
    }

    /**
     * Quick selected records
     * @param $fields
     * @param $table
     * @param null $where
     * @param null $params
     * @return mixed
     */
    public function select($fields, $table, $where = null, $params = null) {
        $sql = "SELECT " . $fields . " FROM " . $table . ($where ? " WHERE " . $where : "") . ";";
        return  $this->queryAll($sql, $params);
    }

    /**
     * Quick insert record
     * @param $table
     * @param array $columnData
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function insert($table, array $columnData) {
        $columns = array_keys($columnData);
        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s);",
            $table,
            implode(', ', $columns),
            implode(', ', array_fill(0, count($columnData), '?'))
        );
        return $this->db->executeQuery($sql, array_values($columnData));
    }

    /**
     * Quick delete records
     * @param $table
     * @param $where
     * @param null $bind
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function delete($table, $where, $bind=null) {
        $sql = "DELETE FROM " . $table . " WHERE " . $where . ";";
        return $this->db->executeQuery($sql, $bind);
    }

    /**
     * Quick update record
     * @param $table
     * @param array $columnData
     * @param $where
     * @param null $bind
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function update($table, array $columnData, $where, $bind=null) {
        $columns = array_keys($columnData);
        $where = preg_replace('|:\w+|','?', $where);
        if(empty($bind)) $bind = array_values($columnData);
        else $bind = array_values(array_merge($columnData, (array) $bind));
        $sql = sprintf("UPDATE %s SET %s WHERE %s;", $table, implode('=?, ', $columns) . '=?', $where);
        return $this->db->executeQuery($sql, $bind);
    }

    /**
     * Access to tables
     * @return Project
     */

    

    /**
     * Retry instance of class working with database
     * Table of collab_tasks
     * @return Task
     */
    public function task() {
        return $this->task;
    }
    
    

    /**
     * Retry instance of class working with database
     * Table of collab_calendarchanges
     * @return \OCA\Owncollab_Calendar\Db\CalendarChanges
     */
    public function calendarChanges() {
        return $this->calendarChanges;
    }
    /**
     * Retry instance of class working with database
     * Table of collab_calendarobjects
     * @return \OCA\Owncollab_Calendar\Db\CalendarObjects
     */
    public function calendarObjects() {
        return $this->calendarObjects;
    }

    /**
     * Retry instance of class working with database
     * Table of collab_calendar
     * @return \OCA\Owncollab_Calendar\Db\Calendar
     */
    public function calendar() {
        return $this->calendar;
    }
    /**
     * Retry instance of class working with database
     * Table of collab_calendar_group
     * @return \OCA\Owncollab_Calendar\Db\CalendarGroup
     */
    public function calendarGroup() {
        return $this->calendarGroup;
    }
    /**
     * Retry instance of class working with database
     * Table of group_user
     * @return \OCA\Owncollab_Calendar\Db\GroupUser
     */
    public function groupUser() {
        return $this->groupUser;
    }
    /**
     * Retry instance of class working with database
     * Table of collab_calendar
     * @return \OCA\Owncollab_Calendar\Db\Calendar
     */
    public function randhesh() {
        return $this->randhesh;
    }


}