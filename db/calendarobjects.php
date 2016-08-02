<?php

namespace OCA\Owncollab_Calendar\Db;


use League\Flysystem\Exception;

class CalendarObjects
{
    /** @var Connect $connect object instance working with database */
    private $connect;

    /** @var string $tableName table name in database */
    private $tableName;


    /** @var string $fields table fields name in database */
    private $fields = [
        'id',
        'calendardata',
        'uri',
        'calendarid',
        'lastmodified',
        'etag',
        'size',
        'componenttype',
        'firstoccurence',
        'lastoccurence',
        'uid',
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

//-----------перенести в хелпер
    public function randhesh($length)
    {
        $x = '';

        $str = "qwertyuiopasdfghjklzxcvbnm123456789";

        for ($i = 0; $i < $length; $i++) {
            $x .= substr($str, mt_rand(0, strlen($str) - 1), 1);
        }

        return $x;
    }


    public function insert($calendarId, $uri, $etag, $uid, $summary, $description, $date_start, $date_end)
    {

        $dt_elements = explode(' ', $date_start);
        // Разбиение даты
        $date_elements = explode('-', $dt_elements[0]);
        $time_elements = explode(':', $dt_elements[1]);
        $date_start_only = $date_elements[2] . $date_elements[1] . $date_elements[0] . "T" . $time_elements[0] . $time_elements[1] . $time_elements[2];

        $dt_elements = explode(' ', $date_end);
        // Разбиение даты
        $date_elements = explode('-', $dt_elements[0]);
        $time_elements = explode(':', $dt_elements[1]);
        $date_end_only = $date_elements[2] . $date_elements[1] . $date_elements[0] . "T" . $time_elements[0] . $time_elements[1] . $time_elements[2];

        $thisTime = date('Ymd\THis', time());


        $sql = "INSERT INTO `{$this->tableName}`
          (`calendardata`,`uri`,`calendarid`, `firstoccurence`, `lastoccurence`, `lastmodified`,`etag`, `size`, `componenttype`, `uid`)
           VALUES (:calendardata,:uri,:calendarid, :firstoccurence, :lastoccurence, :lastmodified, :etag, :siz, :componenttype, :uid)";

        $blob = 'BEGIN:VCALENDAR
PRODID:-//ownCloud calendar v1.2.2
BEGIN:VEVENT
CREATED:' . $thisTime . '
DTSTAMP:' . $thisTime . '
LAST-MODIFIED:' . $thisTime . '
UID:' . $uid . '
SUMMARY:' . $summary . '
LOCATION:' . $description . '
DTSTART;TZID=Europe/Helsinki:' . $date_start_only . '
DTEND;TZID=Europe/Helsinki:' . $date_end_only . '
END:VEVENT
BEGIN:VTIMEZONE
TZID:Europe/Helsinki
X-LIC-LOCATION:Europe/Helsinki
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE
END:VCALENDAR';
        $result = $this->connect->db->prepare($sql)->execute(array(
            ':calendardata' => $blob,
            ':uri' => $uri,
            ':calendarid' => $calendarId,
            ':firstoccurence' => strtotime($date_start),//дата начала таска (timestamp)
            ':lastoccurence' => strtotime($date_end),//дата окончание таска (timestamp)
            ':lastmodified' => time(),//дата создание таска (timestamp)
            ':etag' => $etag,
            ':siz' => 790,
            ':componenttype' => 'VEVENT',
            ':uid' => $uid,
        ));

        if (!$result)
            return false;

        return $this->connect->db->lastInsertId();

    }

    public function updateFromTasks($id, $uid, $summary, $description, $date_start, $date_end, $uri, $etag, $calendarId)
    {

        $dt_elements = explode(' ', $date_start);
        // Разбиение даты
        $date_elements = explode('-', $dt_elements[0]);
        $time_elements = explode(':', $dt_elements[1]);
        $date_start_only = $date_elements[2] . $date_elements[1] . $date_elements[0] . "T" . $time_elements[0] . $time_elements[1] . $time_elements[2];

        $dt_elements = explode(' ', $date_end);
        // Разбиение даты
        $date_elements = explode('-', $dt_elements[0]);
        $time_elements = explode(':', $dt_elements[1]);
        $date_end_only = $date_elements[2] . $date_elements[1] . $date_elements[0] . "T" . $time_elements[0] . $time_elements[1] . $time_elements[2];

        $thisTime = date('Ymd\THis', time());


        $sql = "UPDATE `{$this->tableName}`
          SET `calendardata` = :calendardata,
           `firstoccurence` = :firstoccurence,
            `lastoccurence` = :lastoccurence,
             `lastmodified` =:lastmodified,
              `uid` = :uid
           WHERE `id` = :id";

        $blob = 'BEGIN:VCALENDAR
PRODID:-//ownCloud calendar v1.2.2
BEGIN:VEVENT
CREATED:' . $thisTime . '
DTSTAMP:' . $thisTime . '
LAST-MODIFIED:' . $thisTime . '
UID:' . $uid . '
SUMMARY:' . $summary . '
LOCATION:' . $description . '
DTSTART;TZID=Europe/Helsinki:' . $date_start_only . '
DTEND;TZID=Europe/Helsinki:' . $date_end_only . '
END:VEVENT
BEGIN:VTIMEZONE
TZID:Europe/Helsinki
X-LIC-LOCATION:Europe/Helsinki
BEGIN:DAYLIGHT
TZOFFSETFROM:+0200
TZOFFSETTO:+0300
TZNAME:EEST
DTSTART:19700329T030000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=3
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0200
TZNAME:EET
DTSTART:19701025T040000
RRULE:FREQ=YEARLY;BYDAY=-1SU;BYMONTH=10
END:STANDARD
END:VTIMEZONE
END:VCALENDAR';
        $result = $this->connect->db->executeUpdate($sql, [
            ':calendardata' => $blob,
            ':firstoccurence' => strtotime($date_start),//дата начала таска (timestamp)
            ':lastoccurence' => strtotime($date_end),//дата окончание таска (timestamp)
            ':lastmodified' => time(),//дата создание таска (timestamp)
            ':uid' => $uid,
            ':id' => $id
        ]);
        if ($result == 0) {
            $sql = "INSERT INTO `{$this->tableName}`
          (`id`,`calendardata`,`uri`,`calendarid`, `firstoccurence`, `lastoccurence`, `lastmodified`,`etag`, `size`, `componenttype`, `uid`)
           VALUES (:id, :calendardata,:uri,:calendarid, :firstoccurence, :lastoccurence, :lastmodified, :etag, :siz, :componenttype, :uid)";

            $result = $this->connect->db->prepare($sql)->execute(array(
                ':id' => $id,
                ':calendardata' => $blob,
                ':uri' => $uri,
                ':calendarid' => $calendarId,
                ':firstoccurence' => strtotime($date_start),//дата начала таска (timestamp)
                ':lastoccurence' => strtotime($date_end),//дата окончание таска (timestamp)
                ':lastmodified' => time(),//дата создание таска (timestamp)
                ':etag' => $etag,
                ':siz' => 790,
                ':componenttype' => 'VEVENT',
                ':uid' => $uid,
            ));
        }
        return $result;

    }


    public function delete(array $ids)
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
                $prep .= "id = :id$i";
                $bind[":id$i"] = $ids[$i];
            }
            $result = $this->connect->delete($this->tableName, $prep, $bind);
            if ($result) {
                $result = $result->rowCount();
            }
        } catch (\AbstractDriverException $error) {
        }
        return $result;
    }

    public function update($uid, $value)
    {
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