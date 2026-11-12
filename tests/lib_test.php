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

namespace mod_taskchain;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/taskchain/lib.php');
require_once($CFG->dirroot . '/mod/taskchain/locallib.php');
require_once($CFG->dirroot . '/mod/taskchain/source/class.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/completionlib.php');

/**
 * Basic tests for mod_taskchain: features, file types, output formats, activities and completion.
 *
 * @package   mod_taskchain
 * @category  test
 * @copyright 2026 Gordon Bateson <gordon.bateson@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {
    /**
     * Create a stored file from a file in the "tests/fixtures" folder.
     *
     * @param string $filename
     * @return \stored_file
     */
    protected function create_fixture_file($filename) {
        global $CFG;
        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'mod_taskchain',
            'filearea' => 'unittest',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $path = $CFG->dirroot . '/mod/taskchain/tests/fixtures/' . $filename;
        return get_file_storage()->create_file_from_pathname($filerecord, $path);
    }

    /**
     * Test the features supported by TaskChain.
     */
    public function test_supports(): void {
        $this->assertTrue(taskchain_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertTrue(taskchain_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(taskchain_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertNull(taskchain_supports('an_unknown_feature'));
        if (defined('MOD_PURPOSE_ASSESSMENT')) {
            $this->assertEquals(MOD_PURPOSE_ASSESSMENT, taskchain_supports(FEATURE_MOD_PURPOSE));
        }
    }

    /**
     * Test that the type of each source file is detected correctly.
     */
    public function test_source_detection(): void {
        $this->resetAfterTest();
        $expected = [
            'hp6_jquiz.jqz' => ['hp_6_jquiz_xml', 'hp_6_jquiz_xml'],
            'hp7_jquiz.jqz' => ['hp_7_jquiz_xml', 'hp_7_jquiz_xml'],
            'hp7_jcloze.jcl' => ['hp_7_jcloze_xml', 'hp_7_jcloze_xml'],
            'hp7_jmatch.jmt' => ['hp_7_jmatch_xml', 'hp_7_jmatch_xml_v7_plus'],
            'hp7_jmatch.htm' => ['hp_7_jmatch_html', 'hp_7_jmatch_html'],
        ];
        foreach ($expected as $filename => $types) {
            [$sourcetype, $outputformat] = $types;
            $data = new \stdClass();
            $source = \taskchain_source::is('is_taskfile', $this->create_fixture_file($filename), $data);
            $this->assertIsObject($source, $filename);
            $this->assertEquals($sourcetype, $source->get_type(), $filename);
            $this->assertEquals($outputformat, $source->get_best_outputformat(), $filename);
        }
    }

    /**
     * Test the lists of output formats that are available for each type of file.
     */
    public function test_outputformats_list(): void {
        $formats = \taskchain_available::outputformats_list('hp_7_jmatch_xml');
        foreach (['hp_7_jmatch_xml_v7', 'hp_7_jmatch_xml_v7_plus', 'hp_7_jmatch_xml_flashcard'] as $format) {
            $this->assertArrayHasKey($format, $formats);
        }

        // HP6 data files can also be shown using the HP7 output formats.
        $formats = \taskchain_available::outputformats_list('hp_6_jquiz_xml');
        $this->assertArrayHasKey('hp_6_jquiz_xml_v6', $formats);
        $this->assertArrayHasKey('hp_7_jquiz_xml_v7', $formats);
    }

    /**
     * Test the creation of a TaskChain activity with one task.
     */
    public function test_create_instance(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $taskchain = $this->getDataGenerator()->create_module('taskchain', [
            'course' => $course->id,
            'name' => 'My TaskChain',
            'sourcefile' => 'mod/taskchain/tests/fixtures/hp7_jcloze.jcl',
        ]);

        $record = $DB->get_record('taskchain', ['id' => $taskchain->id], '*', MUST_EXIST);
        $this->assertEquals('My TaskChain', $record->name);

        $tasks = $DB->get_records('taskchain_tasks', ['chainid' => $taskchain->chainid], 'sortorder');
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertEquals('hp_7_jcloze_xml', $task->sourcetype);
        $this->assertEquals('/hp7_jcloze.jcl', $task->sourcefile);

        // New tasks use the task name as their title.
        $this->assertEquals(\mod_taskchain::TEXTSOURCE_TASKNAME, $task->title & \mod_taskchain::TITLE_SOURCE);

        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod', 'itemmodule' => 'taskchain', 'iteminstance' => $taskchain->id, 'courseid' => $course->id,
        ]);
        $this->assertNotEmpty($gradeitem);
    }

    /**
     * Test the creation of a chain of several tasks.
     */
    public function test_create_chain(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $taskchain = $this->getDataGenerator()->create_module('taskchain', [
            'course' => $course->id,
            'sourcefile' => [
                'mod/taskchain/tests/fixtures/hp6_jquiz.jqz',
                'mod/taskchain/tests/fixtures/hp7_jcloze.jcl',
                'mod/taskchain/tests/fixtures/hp7_jmatch.jmt',
            ],
        ]);

        $tasks = $DB->get_records('taskchain_tasks', ['chainid' => $taskchain->chainid], 'sortorder');
        $this->assertCount(3, $tasks);
        $types = array_values(array_map(function ($task) {
            return $task->sourcetype;
        }, $tasks));
        $this->assertEquals(['hp_6_jquiz_xml', 'hp_7_jcloze_xml', 'hp_7_jmatch_xml'], $types);
        $sortorders = array_values(array_map(function ($task) {
            return $task->sortorder;
        }, $tasks));
        $this->assertEquals([1, 2, 3], $sortorders);
    }

    /**
     * Test the custom completion rule for a minimum grade.
     */
    public function test_completion_mingrade(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $taskchain = $this->getDataGenerator()->create_module('taskchain', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionmingrade' => 50,
        ]);
        $cm = get_fast_modinfo($course)->get_cm($taskchain->cmid);
        $this->assertEquals(50, $cm->customdata['customcompletionrules']['completionmingrade']);

        $completion = new completion\custom_completion($cm, $student->id);
        $this->assertContains('completionmingrade', $completion->get_available_custom_rules());
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionmingrade'));

        $grade = ['userid' => $student->id, 'rawgrade' => 40];
        grade_update('mod/taskchain', $course->id, 'mod', 'taskchain', $taskchain->id, 0, $grade);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completionmingrade'));

        $grade = ['userid' => $student->id, 'rawgrade' => 60];
        grade_update('mod/taskchain', $course->id, 'mod', 'taskchain', $taskchain->id, 0, $grade);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completionmingrade'));
    }

    /**
     * Test the custom completion rule for "completed" status.
     */
    public function test_completion_completed(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $taskchain = $this->getDataGenerator()->create_module('taskchain', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completioncompleted' => 1,
        ]);
        $cm = get_fast_modinfo($course)->get_cm($taskchain->cmid);
        $completion = new completion\custom_completion($cm, $student->id);
        $this->assertEquals(['completioncompleted'], $completion->get_available_custom_rules());
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completioncompleted'));

        $chaingrade = (object)[
            'parenttype' => \mod_taskchain::PARENTTYPE_ACTIVITY,
            'parentid' => $taskchain->id,
            'userid' => $student->id,
            'grade' => 100,
            'status' => \mod_taskchain::STATUS_INPROGRESS,
            'duration' => 0,
            'timemodified' => time(),
        ];
        $chaingrade->id = $DB->insert_record('taskchain_chain_grades', $chaingrade);
        $this->assertEquals(COMPLETION_INCOMPLETE, $completion->get_state('completioncompleted'));

        $DB->set_field('taskchain_chain_grades', 'status', \mod_taskchain::STATUS_COMPLETED, ['id' => $chaingrade->id]);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_state('completioncompleted'));
    }
}
