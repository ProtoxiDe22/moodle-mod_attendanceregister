<?php
namespace mod_attendanceregister\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;

use core_privacy\local\request\transform;      // QUESTI DOVREBBERO ESSERE INUTILI
use core_privacy\local\request\deletion_criteria;      // QUESTI DOVREBBERO ESSERE INUTILI
use core_privacy\local\request\helper; // QUESTI DOVREBBERO ESSERE INUTILI
use core_privacy\local\request\writer; // QUESTI DOVREBBERO ESSERE INUTILI


defined('MOODLE_INTERNAL') || die();


class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider  {

    public static function get_metadata(collection $items) : collection {
var_dump("get_metadata");

		$items->add_database_table(
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

		$items->add_database_table(
			'attendanceregister_lock',
			 [
				'userid' => 'privacy:metadata:attendanceregister_lock:userid'
			 ],
			'privacy:metadata:attendanceregister_lock'
		);

		$items->add_database_table(
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
var_dump("get_contexts_for_userid");
        $contextlist = new \core_privacy\local\request\contextlist();

        $params = [
            'modname'           => 'attendanceregister',
            'contextlevel'      => CONTEXT_MODULE,
            'registeruserid'    => $userid,
        ];


        $sql = "SELECT c.id
                 FROM {context} c
           INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
           INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
           INNER JOIN {attendanceregister} a ON a.id = cm.instance
            LEFT JOIN {attendanceregister_aggregate} aa ON aa.register = a.id
                WHERE (
                aa.userid        = :registeruserid
                )
        ";

 
        $contextlist->add_from_sql($sql, $params);		// INUTILE CERCARE ANCHE NELLA mdl_attendanceregister_session OLTRE CHE NELLA mdl_attendanceregister_aggregate, NO? I CONTESTI A CUI RIFERISCONO SONO GLI STESSI, NO?
var_dump($userid);
var_dump($contextlist);
// die;
        return $contextlist;


    }

    public static function export_user_data(approved_contextlist $contextlist) {
var_dump("export_user_data");
        global $DB;

        $contexts = array_reduce($contextlist->get_contexts(), function($carry, $context) {
                $carry[] = $context->id;
            return $carry;
        }, []);

        if (empty($contexts)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        foreach ($contexts as $contextid) {
            $context = \context::instance_by_id($contextid);
            $data = helper::get_context_data($context, $user);
            writer::with_context($context)->export_data([], $data);
            // helper::export_context_files($context, $user);
        }

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $params = $contextparams;

        // Aggregate values
        $sql = "SELECT
                    c.id AS contextid,
                    agg.duration AS duration,
                    agg.onlinesess AS onlinesess,
                    agg.total AS total,
                    agg.grandtotal AS grandtotal
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {attendanceregister} a ON a.id = cm.instance
                  JOIN {attendanceregister_aggregate} agg ON agg.register = a.id
                 WHERE (
                    agg.userid = :userid AND
                    c.id {$contextsql}
                )
        ";
        $params['userid'] = $userid;

        $alldata = [];
        $aggregates = $DB->get_recordset_sql($sql, $params);
        foreach ($aggregates as $aiccsession) {
            $alldata[$aiccsession->contextid][] = (object)[
                    'duration' => $aiccsession->duration,
                    'onlinesess' => $aiccsession->onlinesess,
                    'total' => $aiccsession->total,
                    'grandtotal' => $aiccsession->grandtotal,
                ];
        }
        $aggregates->close();

        // The aicc_session data is organised in: {Course name}/{SCORM activity name}/{My AICC sessions}/data.json
        // In this case, the attempt hasn't been included in the json file because it can be null.
        array_walk($alldata, function($data, $contextid) {
            $context = \context::instance_by_id($contextid);
            $subcontext = [
                get_string('myattendanceregisteraggregates', 'attendanceregister')
            ];
            writer::with_context($context)->export_data(
                $subcontext,
                (object)['attendanceregister_aggregates_values' => $data]
            );
        });

        // Sessions values
        $sql = "SELECT
                    c.id AS contextid,
                    sess.duration AS duration,
                    sess.onlinesess AS onlinesess,
                    sess.refcourse AS refcourse,
                    sess.comments AS comments,
                    sess.addedbyuserid AS addedbyuserid
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {attendanceregister} a ON a.id = cm.instance
                  JOIN {attendanceregister_session} sess ON sess.register = a.id
                 WHERE (
                    sess.userid = :userid AND
                    c.id {$contextsql}
                )
        ";

        $alldata = [];
        $aggregates = $DB->get_recordset_sql($sql, $params);
        foreach ($aggregates as $aiccsession) {
            $alldata[$aiccsession->contextid][] = (object)[
                    'duration' => $aiccsession->duration,
                    'onlinesess' => $aiccsession->onlinesess,
                    'refcourse' => $aiccsession->refcourse,
                    'comments' => $aiccsession->comments,
                    'addedbyuserid' => $aiccsession->addedbyuserid
                ];
        }
        $aggregates->close();

        // The aicc_session data is organised in: {Course name}/{SCORM activity name}/{My AICC sessions}/data.json
        // In this case, the attempt hasn't been included in the json file because it can be null.
        array_walk($alldata, function($data, $contextid) {
            $context = \context::instance_by_id($contextid);
            $subcontext = [
                get_string('myattendanceregistersessions', 'attendanceregister')
            ];
            writer::with_context($context)->export_data(
                $subcontext,
                (object)['attendanceregister_sessios_values' => $data]
            );
        });
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
var_dump("delete_data_for_all_users_in_context");
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
var_dump("delete_data_for_users");
    }

    public static function get_users_in_context(userlist $userlist) {
var_dump("get_users_in_context");
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
var_dump("delete_data_for_user");
    }

}
