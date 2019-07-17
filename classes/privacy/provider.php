<?php
namespace mod_attendanceregister\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider  {

    public static function get_metadata(collection $items) : collection {

		$collection->add_database_table(
			'attendanceregister_aggregate',
			 [
				'userid' => 'privacy:metadata:attendanceregister_aggregate:userid',
				'duration' => 'privacy:metadata:attendanceregister_aggregate:duration',
				'onlinesess' => 'privacy:metadata:attendanceregister_aggregate:onlinesess',
				'total' => 'privacy:metadata:attendanceregister_aggregate:total',
				'grandtotal' => 'privacy:metadata:attendanceregister_aggregate:grandtotal',
				'refcourse' => 'privacy:metadata:attendanceregister_aggregate:refcourse',
				'lastsessionlogout' => 'privacy:metadata:attendanceregister_aggregate:lastsessionlogout'
			 ],
			'privacy:metadata:attendanceregister_aggregate'
		);

		$collection->add_database_table(
			'attendanceregister_lock',
			 [
				'userid' => 'privacy:metadata:attendanceregister_lock:userid'
			 ],
			'privacy:metadata:attendanceregister_lock'
		);

		$collection->add_database_table(
			'attendanceregister_session',
			 [
				'userid' => 'privacy:metadata:attendanceregister_session:userid',
				'login' => 'privacy:metadata:attendanceregister_session:login',
				'logout' => 'privacy:metadata:attendanceregister_session:logout',
				'duration' => 'privacy:metadata:attendanceregister_session:duration',
				'useridIndex' => 'privacy:metadata:attendanceregister_session:useridIndex',
				'onlinesess' => 'privacy:metadata:attendanceregister_session:onlinesess',
				'refcourse' => 'privacy:metadata:attendanceregister_session:refcourse',
				'comments' => 'privacy:metadata:attendanceregister_session:comments',
				'addedbyuserid' => 'privacy:metadata:attendanceregister_session:addedbyuserid'
			 ],
			'privacy:metadata:attendanceregister_session'
		);

        return $items;
    }


    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT c.id
                 FROM {context} c
           INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
           INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
           INNER JOIN {attendanceregister} a ON a.id = cm.instance
            LEFT JOIN {attendanceregister_aggregate} aa ON aa.register = a.id
                WHERE (
                d.userid        = :registeruserid
                )
        ";
 
        $params = [
            'modname'           => 'attendanceregister',
            'contextlevel'      => CONTEXT_MODULE,
            'registeruserid'    => $userid,
        ];
 
        $contextlist->add_from_sql($sql, $params);
 
        return $contextlist;


    }

    public static function export_user_data(approved_contextlist $contextlist) {
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
    }



    public static function get_users_in_context(userlist $userlist) {
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
    }


}

?>