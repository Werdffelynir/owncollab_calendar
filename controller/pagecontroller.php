<?php
/**
 * ownCloud - owncollab_calendar
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bogdan <mail@example.com>
 * @copyright Bogdan 2016
 */

namespace OCA\Owncollab_Calendar\Controller;

use OCA\Owncollab_Chart\Helper;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IGroup;
use OCP\IGroupManager;
use OCA\Owncollab_Calendar\Db\Connect;
use OCA\DAV\CalDAV;
use OCP\IUserManager;
use OCA\DAV\Connector\Sabre\Principal;

class PageController extends Controller
{

    /** @var string $userId
     * current auth user id  */
    private $userId;

    /** @var Connect $connect
     * instance working with database */
    private $connect;

    /** @var IUserManager */
    private $userManager;

    /** @var IGroupManager */
    private $groupManager;

    /**
     * PageController constructor.
     * @param string $appName
     * @param IRequest $request
     * @param $userId
     * @param Connect $connect
     * @param IUserManager $userManager
     * @param IGroupManager $groupManager
     */

    public function __construct($AppName,
                                IRequest $request,
                                $UserId,
                                Connect $connect,
                                IUserManager $userManager,
                                IGroupManager $groupManager
    )
    {
        parent::__construct($AppName, $request);
        $this->userId = $UserId;
        $this->connect = $connect;
        $this->userManager = $userManager;
        $this->groupManager = $groupManager;
    }


    /**
     * @PublicPage
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index()
    {
        $pKey = Helper::post('key');
        $pApp = Helper::post('app');

        if($pKey != 'jasj765Uyt87ouIIfars' || $pApp != 'owncollab_chart')
            return false;

        //название календаря тасков в которых пользователь берет участие как отдельный пользователь
        $userTaskName = 'myChartTasks';

        //все id пользователей
        $allUsers = $this->connect->users->get();

        //таблица тасков
        $allTasks = $this->connect->task()->get();

        //массив тасков, ключами которого является id таска
        $arrIdTasks = [];
        for ($i = 0; $i < count($allTasks); $i++) {
            $arrIdTasks[$allTasks[$i]['id']] = $allTasks[$i];
        }
        //массив id тасков, ключами которого являются id пользователей
        $userTasks = [];
        for ($i = 0; $i < count($allUsers); $i++) {
            $temp_arr = [];
            for ($j = 0; $j < count($allTasks); $j++) {
                $taskUser = json_decode($allTasks[$j]['users'])->users;
                if (in_array($allUsers[$i]['uid'], $taskUser)) {
                    $temp_arr[$allTasks[$j]['id']] = '';
                }

            }

            $userTasks[$allUsers[$i]['uid']] = $temp_arr;
            unset($temp_arr);
        }
        //id текущих пользователей
        $usersId = array_keys($userTasks);

        //id пользователей с таблицы синхронизации
        $usersIdCal = $this->connect->calendar()->getUsersId();

        //добавление в таблицу синхронизации новых пользователей
        $newUserId = array_diff($usersId, $usersIdCal);
        $this->connect->calendar()->insertAllById($newUserId);

        //удаление удаленных пользователей с таблицы синхронизации
        $deleteUserId = array_diff($usersIdCal, $usersId);
        $this->connect->calendar()->deleteAllById($deleteUserId);

        //синхронизация тасков
        //uid  id_tasks (json)
        $tasksIdCalTable = $this->connect->calendar()->get();

        for ($i = 0; $i < count($tasksIdCalTable); $i++) {


            //создание рабочего календаря для каждого пользователя

            $user = $tasksIdCalTable[$i]['uid'];
            if (!$this->userManager->userExists($user)) {
                throw new \InvalidArgumentException("User <$user> in unknown.");
            }
            $principalBackend = new Principal(
                $this->userManager,
                $this->groupManager
            );


            $caldav = new CalDAV\CalDavBackend($this->connect->db, $principalBackend);


            //проверка существования календаря
            if (!$caldav->getCalendarByUri("principals/users/$user", $userTaskName)) {
                //создание календаря если его нет
                $caldav->createCalendar("principals/users/$user", $userTaskName, []);
            }

            //находим id календаря пользователя $calendarId
            $userCalendar = $caldav->getCalendarByUri("principals/users/$user", $userTaskName);


            if (array_key_exists($tasksIdCalTable[$i]['uid'], $userTasks)) {

                //массив тасков одного пользователя с таблицы синхронизации
                $taskArr = (array)(json_decode($tasksIdCalTable[$i]['id_tasks']));
                if ($taskArr == '')
                    $taskArr = [];

                //массив тасков одного пользователя с чартов
                $mainTasksArr = $userTasks[$tasksIdCalTable[$i]['uid']];
                if ($mainTasksArr == '')
                    $mainTasksArr = [];


                //новые таски------------------------------------------------------------------
                $idNewTasks = array_values(array_diff(array_keys($mainTasksArr), array_keys($taskArr)));

                if (count($idNewTasks)) {
                    //создаем новые события календаря
                    //проходим в цыкле по новых тасках пользователя

                    for ($j = 0; $j < count($idNewTasks); $j++) {


                        $uid = $this->connect->calendarObjects()->randhesh(25);
                        $etag = $this->connect->calendarObjects()->randhesh(32);
                        $uri = 'ownCloud-';
                        $uri .= $this->connect->calendarObjects()->randhesh(50);
                        $uri .= '.ics';
                        $description = '';
//                        $key = array_keys($idNewTasks);


                        $this->connect->calendarChanges()->insert($uri, $userCalendar['id']);
                        //id созданого события
                        $insertId = $this->connect->calendarObjects()->insert(
                            $userCalendar['id'],
                            $uri,
                            $etag,
                            $uid,
                            $arrIdTasks[$idNewTasks[$j]]['text'],//название таска
                            $description,
                            $arrIdTasks[$idNewTasks[$j]]['start_date'],
                            $arrIdTasks[$idNewTasks[$j]]['end_date']
                        );


                        //записываем id события в таблицу синхронизации
                        $res = $this->connect->calendar()->getById($tasksIdCalTable[$i]['uid']);
                        $res[$idNewTasks[$j]] = (int)($insertId);

                        $this->connect->calendar()->update($tasksIdCalTable[$i]['uid'], json_encode($res));
                    }
                }

                //удаленные таски----------------------------------------------------------------
                $idDeleteTask = array_values(array_diff(array_keys($taskArr), array_keys($mainTasksArr)));
                if (count($idDeleteTask)) {
                    //удаляем старые события

                    $deleteId = [];

                    foreach ($taskArr as $k => $v) {
                        if (in_array($k, $idDeleteTask))
                            $deleteId[] = $v;
                    }

                    $this->connect->calendarObjects()->delete($deleteId);
                    //удаляем таск с таблицы синхронизации
                    $res = $this->connect->calendar()->getById($tasksIdCalTable[$i]['uid']);

                    foreach ($res as $k => $v) {
                        if (!in_array($k, $idDeleteTask))
                            $newRes[$k] = $v;
                    }


                    $this->connect->calendar()->update($tasksIdCalTable[$i]['uid'], json_encode($newRes));

                }

                //обновляем события текущих тасков-----------------------------------------------
                if (count($taskArr)) {
                    //обновляем события текущих тасков

                    for ($k = 0; $k < count($taskArr); $k++) {
                        $uid = $this->connect->calendarObjects()->randhesh(25);
                        $description = '';
                        $etag = $this->connect->calendarObjects()->randhesh(32);
                        $uri = 'ownCloud-';
                        $uri .= $this->connect->calendarObjects()->randhesh(50);
                        $uri .= '.ics';

                        $result = $this->connect->calendarObjects()->updateFromTasks(array_values($taskArr)[$k],
                            $uid,
                            $arrIdTasks[array_keys($taskArr)[$k]]['text'],
                            $description,
                            $arrIdTasks[array_keys($taskArr)[$k]]['start_date'],
                            $arrIdTasks[array_keys($taskArr)[$k]]['end_date'],
                            $uri,
                            $etag,
                            $userCalendar['id']);

                    }

                }

            }

        }
        //все id пользователей (определяеться в начале метода)
        //$allUsers;

        //таблица тасков (определяеться в начале метода)
        //$allTasks;

        //вызов таска по его id (определяеться в начале метода)
        //$arrIdTasks;

        //массив тасков, ключами которого является id таска (определяеться в начале метода)
        //$arrIdTasks;

        //все id групп
        $allGroups = $this->connect->groups->get();

        //массив id тасков, ключами которого являются id груп
        $groupTasks = [];
        //перебираем группы
        for ($i = 0; $i < count($allGroups); $i++) {
            //юзеры i-той группы
            $usersGroup = $this->connect->groupUser()->getByGid($allGroups[$i]['gid']);
            $temp = [];
            $temp_arr = [];
            for ($k = 0; $k < count($allTasks); $k++) {
                $taskGroup = json_decode($allTasks[$k]['users'])->groups;
                if (in_array($allGroups[$i]['gid'], $taskGroup)) {
                    $temp_arr[$allTasks[$k]['id']] = '';
                }
            }
            for ($j = 0; $j < count($usersGroup); $j++) {
                $temp[$usersGroup[$j]] = $temp_arr;
            }

            $groupTasks[$allGroups[$i]['gid']] = $temp;
            unset($temp_arr);
        }
        //id текущих груп
        $groupId = array_keys($groupTasks);

        //id груп с таблицы синхронизации
        $groupIdCal = $this->connect->calendarGroup()->getGroupsId();

        //добавление в таблицу синхронизации новых груп
        $newGroupId = array_diff($groupId, $groupIdCal);
        $this->connect->calendarGroup()->insertAllById($newGroupId);

        //удаление удаленных груп с таблицы синхронизации
        $deleteGroupId = array_diff($groupIdCal, $groupId);
        $this->connect->calendarGroup()->deleteAllById($deleteGroupId);

        //синхронизация тасков
        //gid[$i]=>admin
        //users (json)["bogdan","oleg","alex","ivan"]
        //id_tasks (json){"2":81,"3":82,"4":83,"5":84}
        $tasksIdCalTable = $this->connect->calendarGroup()->get();

        //$tasksIdCalTable[0]['gid']=admin;
        //$tasksIdCalTable[0]['id_tasks']=json(id_tasks);
        //проходим по всех группах
        for ($i = 0; $i < count($tasksIdCalTable); $i++) {

            $group = $tasksIdCalTable[$i]['gid'];//admin

            //вытаскиваем всех юзеров i-той группы (admin)
            //$userGroup[0]['uid'] = 'bogdan';
            $usersGroup = array_keys($groupTasks[$group]);

            //находим учасников с группы таблицы синхронизации
            $usersGroupTable = json_decode($tasksIdCalTable[$i]['id_tasks'], true);
            if (!$usersGroupTable) {
                $usersGroupTable = [];
            }

            //добавляем новых юзеров в группу--------------------------------------------------
            $newUsers = array_values(array_diff($usersGroup, array_keys($usersGroupTable)));

            if (count($newUsers)) {

                //---------------------------------------------------------------------------------
                //создание рабочего календаря для каждого пользователя
                for ($j = 0; $j < count($newUsers); $j++) {
                    $user = $newUsers[$j];

                    if (!$this->userManager->userExists($user)) {
                        throw new \InvalidArgumentException("User <$user> in unknown.");
                    }
                    $principalBackend = new Principal(
                        $this->userManager,
                        $this->groupManager
                    );


                    $caldav = new CalDAV\CalDavBackend($this->connect->db, $principalBackend);


                    //проверка существования календаря
                    if (!$caldav->getCalendarByUri("principals/users/$user", $group)) {
                        //создание календаря если его нет
                        $caldav->createCalendar("principals/users/$user", $group, []);
                    }

                }
                //---------------------------------------------------------------------------------

                for ($j = 0; $j < count($newUsers); $j++) {
                    $arr = [];
                    $usersGroupTable[$newUsers[$j]] = $arr;
                }

            }

            //удаляем старых юзеров с группы---------------------------------------------------
            $deletedUsers = array_values(array_diff(array_keys($usersGroupTable), $usersGroup));
            if (count($deletedUsers)) {
                for ($j = 0; $j < count($deletedUsers); $j++) {
                    //удаляем календарь и таски этого юзера
                    $userCalendar = $caldav->getCalendarByUri("principals/users/$deletedUsers[$j]", $group);
                    $caldav->deleteCalendar($userCalendar['id']);

                    //удаляем ячейку юзера с таблицы синхронизации
                    unset($usersGroupTable[$deletedUsers[$j]]);
                }

            }


            //проверяем таски каждого юзера
            foreach ($usersGroupTable as $k => $v) {
                $newTasks = array_values(array_diff(array_keys($groupTasks[$group][$k]), array_keys($v)));


                //находим id календаря пользователя $calendarId, если его пользователь удалил - создаем обратно
                $userCalendar = $caldav->getCalendarByUri("principals/users/$k", $group);
                if (!$userCalendar) {
                    $result = $caldav->createCalendar("principals/users/$k", $group, []);
                    $userCalendar = [];
                    $userCalendar['id'] = $result;
                }
                if (count($newTasks)) {
                    for ($n = 0; $n < count($newTasks); $n++) {
                        //-------------------------------------
                        //создание нового события

                        $uid = $this->connect->calendarObjects()->randhesh(25);
                        $etag = $this->connect->calendarObjects()->randhesh(32);
                        $uri = 'ownCloud-';
                        $uri .= $this->connect->calendarObjects()->randhesh(50);
                        $uri .= '.ics';
                        $description = '';

                        $this->connect->calendarChanges()->insert($uri, $userCalendar['id']);
                        //id созданого события
                        $insertId = $this->connect->calendarObjects()->insert(
                            $userCalendar['id'],
                            $uri,
                            $etag,
                            $uid,
                            $arrIdTasks[$newTasks[$n]]['text'],//название таска
                            $description,
                            $arrIdTasks[$newTasks[$n]]['start_date'],
                            $arrIdTasks[$newTasks[$n]]['end_date']
                        );


                        //-------------------------------------
                        $v[$newTasks[$n]] = (int)($insertId);
                    }
                }

                //удаленные таски
                $deleteTasks = array_values(array_diff(array_keys($v), array_keys($groupTasks[$group][$k])));

                if (count($deleteTasks)) {
                    $arr_del = [];
                    for ($n = 0; $n < count($deleteTasks); $n++) {
                        $arr_del[] = $v[$deleteTasks[$n]];

                        unset($v[$deleteTasks[$n]]);
                    }
                    $this->connect->calendarObjects()->delete($arr_del);
                }

                $usersGroupTable[$k] = $v;
                if (!$usersGroupTable)
                    $usersGroupTable = '';

                foreach ($v as $taskId => $eventId) {

                    $uid = $this->connect->calendarObjects()->randhesh(25);
                    $description = '';
                    $etag = $this->connect->calendarObjects()->randhesh(32);
                    $uri = 'ownCloud-';
                    $uri .= $this->connect->calendarObjects()->randhesh(50);
                    $uri .= '.ics';

                    $result = $this->connect->calendarObjects()->updateFromTasks($eventId,
                        $uid,
                        $arrIdTasks[$taskId]['text'],
                        $description,
                        $arrIdTasks[$taskId]['start_date'],
                        $arrIdTasks[$taskId]['end_date'],
                        $uri,
                        $etag,
                        $userCalendar['id']);
                }
            }

            $this->connect->calendarGroup()->updateUsersTasks($group, $usersGroupTable);
        }
    }


}