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

namespace mod_taskchain\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Tests for the privacy provider of the TaskChain activity.
 *
 * @package    mod_taskchain
 * @copyright  2010 Gordon Bateson (gordon.bateson@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \mod_taskchain\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var \stdClass the activity, with cmid and chainid. */
    protected $taskchain;

    /** @var \context_module */
    protected $context;

    /**
     * Make a TaskChain activity, with a task.
     */
    protected function make_activity(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course();
        $this->taskchain = $this->getDataGenerator()->create_module('taskchain', ['course' => $course->id]);
        $this->context = \context_module::instance($this->taskchain->cmid);
        $this->assertTrue($DB->record_exists('taskchain_tasks', ['chainid' => $this->taskchain->chainid]));
    }

    /**
     * Give a student a grade, attempts and responses in the activity.
     *
     * @param \stdClass $student
     * @param int $score
     */
    protected function make_attempt(\stdClass $student, int $score): void {
        global $DB;
        $time = time();
        $DB->insert_record('taskchain_chain_grades', ['parenttype' => provider::PARENTTYPE_ACTIVITY,
            'parentid' => $this->taskchain->id, 'userid' => $student->id, 'grade' => $score, 'status' => 4,
            'duration' => 60, 'timemodified' => $time]);
        $DB->insert_record('taskchain_chain_attempts', ['chainid' => $this->taskchain->chainid, 'cnumber' => 1,
            'userid' => $student->id, 'grade' => $score, 'status' => 4, 'duration' => 60, 'timemodified' => $time]);
        $task = $DB->get_record('taskchain_tasks', ['chainid' => $this->taskchain->chainid], 'id', IGNORE_MULTIPLE);
        $DB->insert_record('taskchain_task_scores', ['taskid' => $task->id, 'cnumber' => 1, 'userid' => $student->id,
            'score' => $score, 'status' => 4, 'duration' => 60, 'timemodified' => $time]);
        $attemptid = $DB->insert_record('taskchain_task_attempts', ['taskid' => $task->id, 'userid' => $student->id,
            'cnumber' => 1, 'tnumber' => 1, 'status' => 4, 'penalties' => 0, 'score' => $score, 'duration' => 60,
            'timestart' => $time - 60, 'timefinish' => $time]);
        $DB->insert_record('taskchain_details', ['attemptid' => $attemptid, 'details' => '<details>' . $score . '</details>']);
        $questionid = $DB->insert_record('taskchain_questions', ['taskid' => $task->id, 'name' => 'Q1', 'md5key' => md5('Q1'),
            'type' => 1, 'text' => 0]);
        $correct = $DB->insert_record('taskchain_strings', ['string' => 'apple', 'md5key' => md5('apple')]);
        $wrong = $DB->insert_record('taskchain_strings', ['string' => 'banana', 'md5key' => md5('banana')]);
        $DB->insert_record('taskchain_responses', ['attemptid' => $attemptid, 'questionid' => $questionid, 'score' => $score,
            'weighting' => 100, 'hints' => 0, 'clues' => 1, 'checks' => 2, 'correct' => "$correct", 'wrong' => "$wrong",
            'ignored' => '']);
    }

    /**
     * Count a student's records.
     *
     * @param \stdClass $student
     * @return int[] chain grades, chain attempts, task scores, task attempts, details, responses.
     */
    protected function count_data(\stdClass $student): array {
        global $DB;
        $attemptids = $DB->get_fieldset_select('taskchain_task_attempts', 'id', 'userid = ?', [$student->id]);
        $count = function ($table) use ($DB, $attemptids) {
            if (empty($attemptids)) {
                return 0;
            }
            [$insql, $params] = $DB->get_in_or_equal($attemptids);
            return $DB->count_records_select($table, "attemptid $insql", $params);
        };
        return [
            $DB->count_records('taskchain_chain_grades', ['userid' => $student->id]),
            $DB->count_records('taskchain_chain_attempts', ['userid' => $student->id]),
            $DB->count_records('taskchain_task_scores', ['userid' => $student->id]),
            count($attemptids),
            $count('taskchain_details'),
            $count('taskchain_responses'),
        ];
    }

    /**
     * The personal data is described.
     */
    public function test_get_metadata(): void {
        $items = provider::get_metadata(new collection('mod_taskchain'))->get_collection();
        $names = array_map(function ($item) {
            return $item->get_name();
        }, $items);
        $this->assertEqualsCanonicalizing(['taskchain_chain_grades', 'taskchain_chain_attempts', 'taskchain_task_scores',
            'taskchain_task_attempts', 'taskchain_details', 'taskchain_responses'], $names);
    }

    /**
     * The activities in which a user has data, and the users who have data in an activity, are found.
     */
    public function test_get_contexts_and_users(): void {
        $this->resetAfterTest();
        $this->make_activity();
        $student = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->make_attempt($student, 80);
        $this->assertEquals([$this->context->id], provider::get_contexts_for_userid((int) $student->id)->get_contextids());
        $this->assertSame([], provider::get_contexts_for_userid((int) $other->id)->get_contextids());

        $userlist = new userlist($this->context, 'mod_taskchain');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$student->id], $userlist->get_userids());
    }

    /**
     * A student's grade, attempts, scores and responses are exported.
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();
        $this->make_activity();
        $student = $this->getDataGenerator()->create_user();
        $this->make_attempt($student, 80);
        $this->export_context_data_for_user((int) $student->id, $this->context, 'mod_taskchain');
        $data = writer::with_context($this->context)->get_data([]);
        $this->assertEquals(80, $data->chaingrade->grade);
        $this->assertCount(1, $data->chainattempts);
        $this->assertCount(1, $data->tasks);
        $attempt = $data->tasks[0]->attempts[0];
        $this->assertEquals(80, $attempt->score);
        $this->assertSame('<details>80</details>', $attempt->details);
        $this->assertSame(['apple'], $attempt->responses[0]->correct);
        $this->assertSame(['banana'], $attempt->responses[0]->wrong);
    }

    /**
     * One student's data, some students' data, or all the data in an activity, can be deleted.
     */
    public function test_delete(): void {
        global $DB;
        $this->resetAfterTest();
        $this->make_activity();
        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();
        $this->make_attempt($first, 80);
        $this->make_attempt($second, 60);
        $this->assertSame([1, 1, 1, 1, 1, 1], $this->count_data($first));

        provider::delete_data_for_user(new approved_contextlist($first, 'mod_taskchain', [$this->context->id]));
        $this->assertSame([0, 0, 0, 0, 0, 0], $this->count_data($first));
        $this->assertSame([1, 1, 1, 1, 1, 1], $this->count_data($second));

        provider::delete_data_for_users(new approved_userlist($this->context, 'mod_taskchain', [$second->id]));
        $this->assertSame([0, 0, 0, 0, 0, 0], $this->count_data($second));

        $this->make_attempt($first, 70);
        provider::delete_data_for_all_users_in_context($this->context);
        $this->assertSame([0, 0, 0, 0, 0, 0], $this->count_data($first));
        // The activity and its tasks are kept.
        $this->assertTrue($DB->record_exists('taskchain_tasks', ['chainid' => $this->taskchain->chainid]));
    }
}
