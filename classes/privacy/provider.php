<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy provider for mod_taskchain.
 *
 * @package    mod_taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_taskchain\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider as userlist_provider;
use core_privacy\local\request\helper;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * The personal data that a TaskChain activity stores: each student's grade for
 * the chain, their attempts at the chain, their scores and attempts for each
 * task, and the details of their responses to the questions in each task.
 *
 * @package    mod_taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider, plugin_provider, userlist_provider {
    /** The parent type of a chain that belongs to a TaskChain activity (see mod_taskchain::PARENTTYPE_ACTIVITY). */
    const PARENTTYPE_ACTIVITY = 0;

    /**
     * Describe the personal data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $tables = array(
            'taskchain_chain_grades' => array('userid', 'grade', 'status', 'duration', 'timemodified'),
            'taskchain_chain_attempts' => array('userid', 'cnumber', 'grade', 'status', 'duration', 'timemodified'),
            'taskchain_task_scores' => array('userid', 'cnumber', 'score', 'status', 'duration', 'timemodified'),
            'taskchain_task_attempts' => array('userid', 'cnumber', 'tnumber', 'status', 'penalties', 'score', 'duration',
                'timestart', 'timefinish'),
            'taskchain_details' => array('attemptid', 'details'),
            'taskchain_responses' => array('attemptid', 'questionid', 'score', 'weighting', 'hints', 'clues', 'checks',
                'correct', 'wrong', 'ignored'),
        );
        foreach ($tables as $table => $fields) {
            $descriptions = array();
            foreach ($fields as $field) {
                $descriptions[$field] = 'privacy:metadata:' . $table . ':' . $field;
            }
            $collection->add_database_table($table, $descriptions, 'privacy:metadata:' . $table);
        }
        return $collection;
    }

    /**
     * Get the SQL that finds the TaskChain activities in which users have data:
     * their chain grades, chain attempts, task scores and task attempts.
     *
     * @param bool $foruser only one user's data, given by the parameters :userid1 to :userid4.
     * @return string SQL that selects taskchainid and userid.
     */
    protected static function get_user_data_sql(bool $foruser): string {
        $where = function ($alias, $i) use ($foruser) {
            return ($foruser ? "$alias.userid = :userid$i" : '1 = 1');
        };
        return "SELECT cg.parentid AS taskchainid, cg.userid
                  FROM {taskchain_chain_grades} cg
                 WHERE cg.parenttype = :parenttype1 AND " . $where('cg', 1) . "
                 UNION
                SELECT c.parentid AS taskchainid, ca.userid
                  FROM {taskchain_chains} c
                  JOIN {taskchain_chain_attempts} ca ON ca.chainid = c.id
                 WHERE c.parenttype = :parenttype2 AND " . $where('ca', 2) . "
                 UNION
                SELECT c.parentid AS taskchainid, ts.userid
                  FROM {taskchain_chains} c
                  JOIN {taskchain_tasks} t ON t.chainid = c.id
                  JOIN {taskchain_task_scores} ts ON ts.taskid = t.id
                 WHERE c.parenttype = :parenttype3 AND " . $where('ts', 3) . "
                 UNION
                SELECT c.parentid AS taskchainid, ta.userid
                  FROM {taskchain_chains} c
                  JOIN {taskchain_tasks} t ON t.chainid = c.id
                  JOIN {taskchain_task_attempts} ta ON ta.taskid = t.id
                 WHERE c.parenttype = :parenttype4 AND " . $where('ta', 4);
    }

    /**
     * Get the parameters for get_user_data_sql().
     *
     * @param int $userid the user, or 0 for all users.
     * @return array
     */
    protected static function get_user_data_params(int $userid = 0): array {
        $params = array();
        for ($i = 1; $i <= 4; $i++) {
            $params['parenttype' . $i] = self::PARENTTYPE_ACTIVITY;
            if ($userid) {
                $params['userid' . $i] = $userid;
            }
        }
        return $params;
    }

    /**
     * Get the activities in which a user has data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN (" . self::get_user_data_sql(true) . ") d ON d.taskchainid = cm.instance";
        $params = array('contextlevel' => CONTEXT_MODULE, 'modulename' => 'taskchain') + self::get_user_data_params($userid);
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the users who have data in an activity.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        if (!$taskchainid = self::get_taskchainid($userlist->get_context())) {
            return;
        }
        $sql = "SELECT d.userid FROM (" . self::get_user_data_sql(false) . ") d WHERE d.taskchainid = :taskchainid";
        $userlist->add_from_sql('userid', $sql, array('taskchainid' => $taskchainid) + self::get_user_data_params());
    }

    /**
     * Get the id of the TaskChain activity of a context.
     *
     * @param \context $context
     * @return int the id, or 0 if the context is not a TaskChain activity.
     */
    protected static function get_taskchainid(\context $context): int {
        if ($context->contextlevel != CONTEXT_MODULE) {
            return 0;
        }
        $cm = get_coursemodule_from_id('taskchain', $context->instanceid);
        return $cm ? (int) $cm->instance : 0;
    }

    /**
     * Get the chain of an activity, and its tasks.
     *
     * @param int $taskchainid
     * @return array array(chain record or null, task id => task name)
     */
    protected static function get_chain_and_tasks(int $taskchainid): array {
        global $DB;
        $params = array('parenttype' => self::PARENTTYPE_ACTIVITY, 'parentid' => $taskchainid);
        $chain = $DB->get_record('taskchain_chains', $params);
        if (!$chain) {
            return array(null, array());
        }
        $tasks = $DB->get_records_menu('taskchain_tasks', array('chainid' => $chain->id), 'sortorder', 'id, name');
        return array($chain, $tasks);
    }

    /**
     * Export a user's data in some activities.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();
        $userid = (int) $user->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$taskchainid = self::get_taskchainid($context)) {
                continue;
            }
            list($chain, $tasks) = self::get_chain_and_tasks($taskchainid);
            $data = helper::get_context_data($context, $user);

            $params = array('parenttype' => self::PARENTTYPE_ACTIVITY, 'parentid' => $taskchainid, 'userid' => $userid);
            if ($grade = $DB->get_record('taskchain_chain_grades', $params)) {
                $data->chaingrade = (object) array(
                    'grade' => $grade->grade,
                    'status' => $grade->status,
                    'duration' => $grade->duration,
                    'timemodified' => transform::datetime($grade->timemodified),
                );
            }

            $data->chainattempts = array();
            $data->tasks = array();
            if ($chain) {
                $params = array('chainid' => $chain->id, 'userid' => $userid);
                foreach ($DB->get_records('taskchain_chain_attempts', $params, 'cnumber') as $attempt) {
                    $data->chainattempts[] = (object) array(
                        'cnumber' => $attempt->cnumber,
                        'grade' => $attempt->grade,
                        'status' => $attempt->status,
                        'duration' => $attempt->duration,
                        'timemodified' => transform::datetime($attempt->timemodified),
                    );
                }
                foreach ($tasks as $taskid => $taskname) {
                    $task = self::export_task($taskid, $taskname, $userid);
                    if ($task->scores || $task->attempts) {
                        $data->tasks[] = $task;
                    }
                }
            }
            writer::with_context($context)->export_data(array(), $data);
            helper::export_context_files($context, $user);
        }
    }

    /**
     * Get a user's scores and attempts for a task, with their responses.
     *
     * @param int $taskid
     * @param string $taskname
     * @param int $userid
     * @return \stdClass
     */
    protected static function export_task(int $taskid, string $taskname, int $userid): \stdClass {
        global $DB;
        $task = (object) array('task' => $taskname, 'scores' => array(), 'attempts' => array());
        $params = array('taskid' => $taskid, 'userid' => $userid);
        foreach ($DB->get_records('taskchain_task_scores', $params, 'cnumber') as $score) {
            $task->scores[] = (object) array(
                'cnumber' => $score->cnumber,
                'score' => $score->score,
                'status' => $score->status,
                'duration' => $score->duration,
                'timemodified' => transform::datetime($score->timemodified),
            );
        }
        foreach ($DB->get_records('taskchain_task_attempts', $params, 'cnumber, tnumber') as $attempt) {
            $responses = array();
            $sql = "SELECT r.*, q.name AS question
                      FROM {taskchain_responses} r
                 LEFT JOIN {taskchain_questions} q ON q.id = r.questionid
                     WHERE r.attemptid = ?";
            foreach ($DB->get_records_sql($sql, array($attempt->id)) as $response) {
                $responses[] = (object) array(
                    'question' => $response->question,
                    'score' => $response->score,
                    'weighting' => $response->weighting,
                    'hints' => $response->hints,
                    'clues' => $response->clues,
                    'checks' => $response->checks,
                    'correct' => self::get_strings($response->correct),
                    'wrong' => self::get_strings($response->wrong),
                    'ignored' => self::get_strings($response->ignored),
                );
            }
            $task->attempts[] = (object) array(
                'cnumber' => $attempt->cnumber,
                'tnumber' => $attempt->tnumber,
                'status' => $attempt->status,
                'penalties' => $attempt->penalties,
                'score' => $attempt->score,
                'duration' => $attempt->duration,
                'timestart' => $attempt->timestart ? transform::datetime($attempt->timestart) : '',
                'timefinish' => $attempt->timefinish ? transform::datetime($attempt->timefinish) : '',
                'details' => (string) $DB->get_field('taskchain_details', 'details', array('attemptid' => $attempt->id)),
                'responses' => $responses,
            );
        }
        return $task;
    }

    /**
     * Get the texts of a list of strings in the taskchain_strings table.
     *
     * @param string|null $ids string ids, separated by commas.
     * @return string[]
     */
    protected static function get_strings(?string $ids): array {
        global $DB;
        $ids = array_filter(array_map('intval', explode(',', (string) $ids)));
        if (empty($ids)) {
            return array();
        }
        list($insql, $params) = $DB->get_in_or_equal($ids);
        return array_values($DB->get_fieldset_select('taskchain_strings', 'string', "id $insql", $params));
    }

    /**
     * Delete users' data in an activity.
     *
     * @param int $taskchainid
     * @param int[]|null $userids the users, or null for all users.
     */
    protected static function delete_data(int $taskchainid, ?array $userids = null): void {
        global $DB;
        if ($userids !== null && empty($userids)) {
            return;
        }
        $userselect = '';
        $userparams = array();
        if ($userids !== null) {
            list($insql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');
            $userselect = " AND userid $insql";
        }

        $params = array('parenttype' => self::PARENTTYPE_ACTIVITY, 'parentid' => $taskchainid) + $userparams;
        $DB->delete_records_select(
            'taskchain_chain_grades',
            'parenttype = :parenttype AND parentid = :parentid' . $userselect,
            $params
        );

        list($chain, $tasks) = self::get_chain_and_tasks($taskchainid);
        if (!$chain) {
            return;
        }
        $DB->delete_records_select(
            'taskchain_chain_attempts',
            'chainid = :chainid' . $userselect,
            array('chainid' => $chain->id) + $userparams
        );
        if (empty($tasks)) {
            return;
        }
        list($tasksql, $taskparams) = $DB->get_in_or_equal(array_keys($tasks), SQL_PARAMS_NAMED, 'task');
        $DB->delete_records_select('taskchain_task_scores', "taskid $tasksql" . $userselect, $taskparams + $userparams);
        $attemptids = $DB->get_fieldset_select(
            'taskchain_task_attempts',
            'id',
            "taskid $tasksql" . $userselect,
            $taskparams + $userparams
        );
        foreach (array_chunk($attemptids, 1000) as $chunk) {
            $DB->delete_records_list('taskchain_details', 'attemptid', $chunk);
            $DB->delete_records_list('taskchain_responses', 'attemptid', $chunk);
            $DB->delete_records_list('taskchain_task_attempts', 'id', $chunk);
        }
    }

    /**
     * Delete every user's data in an activity.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($taskchainid = self::get_taskchainid($context)) {
            self::delete_data($taskchainid);
        }
    }

    /**
     * Delete a user's data in some activities.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($taskchainid = self::get_taskchainid($context)) {
                self::delete_data($taskchainid, array($userid));
            }
        }
    }

    /**
     * Delete some users' data in an activity.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        if ($taskchainid = self::get_taskchainid($userlist->get_context())) {
            self::delete_data($taskchainid, $userlist->get_userids());
        }
    }
}
