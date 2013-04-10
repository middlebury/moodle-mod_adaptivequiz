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
 * fetch question PHPUnit tests
 *
 * @package    mod_adaptivequiz
 * @category   phpunit
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/fetchquestion.class.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/tests/dummyfetchquestion.class.php');

/**
 * @group mod_adaptivequiz
 */
class mod_adaptivequiz_fetchquestion_testcase extends advanced_testcase {
    /** @var stdClass $activityinstance adaptivequiz activity instance object */
    protected $activityinstance = null;

    /** @var stdClass $cm a partially completed course module object */
    protected $cm = null;

    /** @var stdClass $user a user object */
    protected $user = null;

    /**
     * This function loads data into the PHPUnit tables for testing
     * @return void
     */
    protected function setup_test_data_xml() {
        $this->loadDataSet($this->createXMLDataSet(__DIR__.'/fixtures/mod_adaptivequiz_findquestion.xml'));
    }

    /**
     * This function creates a default user and activity instance using generator classes
     * The activity parameters created are are follows:
     * lowest difficulty level: 1
     * highest difficulty level: 10
     * minimum question attempts: 2
     * maximum question attempts: 10
     * standard error: 1.1
     * starting level: 5
     * question category ids: 1
     * course id: 2
     * @return void
     */
    protected function setup_generator_data() {
        // Create test user
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        $this->setAdminUser();

        // Create activity
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $options = array(
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'minimumquestions' => 2,
                'maximumquestions' => 10,
                'standarderror' => 1.1,
                'startinglevel' => 5,
                'questionpool' => array(1),
                'course' => 2
        );
        $this->activityinstance = $generator->create_instance($options);

        $this->cm = new stdClass();
        $this->cm->id = $this->activityinstance->cmid;
    }

    /**
     * This function creates a default user and activity instance using generator classes (using a different question category)
     * The activity parameters created are are follows:
     * lowest difficulty level: 1
     * highest difficulty level: 10
     * minimum question attempts: 2
     * maximum question attempts: 10
     * standard error: 1.1
     * starting level: 5
     * question category ids: 1
     * course id: 2
     * @return void
     */
    protected function setup_generator_data_two() {
        // Create test user
        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
        $this->setAdminUser();

        // Create activity
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $options = array(
                'highestlevel' => 10,
                'lowestlevel' => 1,
                'minimumquestions' => 2,
                'maximumquestions' => 10,
                'standarderror' => 1.1,
                'startinglevel' => 5,
                'questionpool' => array(4),
                'course' => 2
        );
        $this->activityinstance = $generator->create_instance($options);

        $this->cm = new stdClass();
        $this->cm->id = $this->activityinstance->cmid;
    }

    /**
     * This fuctions tests the retrieval of using illegit tag ids
     * @see setup_generator_data() for detail of activity instance
     * @return void
     */
    public function test_find_questions_fail_tag_ids() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();
        $this->setup_generator_data();

        $attempt = $this->getMock('fetchquestion', array('retrieve_question_categories'), array($this->activityinstance, 1, 1, 100));

        $attempt->expects($this->exactly(2))
                ->method('retrieve_question_categories')
                ->will($this->returnValue(array(1 => 1, 2 => 2, 3 => 3)));

        $data = $attempt->find_questions_with_tags(array(99));
        $this->assertEquals(0, count($data));

        $data = $attempt->find_questions_with_tags(array());
        $this->assertEquals(0, count($data));
    }

    /**
     * This fuction tests the retrieval of questions using an empty set of question categories
     * @see setup_generator_data() for detail of activity instance
     * @return void
     */
    public function test_find_questions_fail_question_cat() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();
        $this->setup_generator_data();

        $mockclass = $this->getMock(
                'fetchquestion',
                array('retrieve_question_categories'),
                array($this->activityinstance, 1, 1, 100)
        );

        $mockclass->expects($this->exactly(2))
            ->method('retrieve_question_categories')
            ->will($this->returnValue(array()));

        // Call class method with illegit tagid
        $data = $mockclass->find_questions_with_tags(array(99));
        $this->assertEquals(0, count($data));

        // Call calss method with legit tagid
        $data = $mockclass->find_questions_with_tags(array(1));
        $this->assertEquals(0, count($data));
    }

    /**
     * This function tests the retrieval of questions using the exclude parameter
     * @see setup_generator_data() for detail of activity instance
     * @return void
     */
    public function test_find_questions_exclude() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();
        $this->setup_generator_data();

        $mockclass = $this->getMock('fetchquestion', array('retrieve_question_categories'), array($this->activityinstance, 1, 1, 100));

        $mockclass->expects($this->once())
                ->method('retrieve_question_categories')
                ->will($this->returnValue(array(1 => 1, 2 => 2, 3 => 3)));

        $data = $mockclass->find_questions_with_tags(array(1), array(1));
        $this->assertEquals(0, count($data));
    }

    /**
     * This functions tests the accessor methods for the $level class variable
     * @expectedException coding_exception
     * @return void
     */
    public function test_get_set_level() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $fetchquestion = new fetchquestion($dummyclass, 99, 1, 100);
        $this->assertEquals(99, $fetchquestion->get_level());

        $fetchquestion->set_level(22);
        $this->assertEquals(22, $fetchquestion->get_level());

        $fetchquestion->set_level(-22);
        $this->assertEquals(99, $fetchquestion->get_level());
    }

    /**
     * This functions tests the retrevial of tag ids with an associated difficulty level
     * but using illegit data
     * @expectedException coding_exception
     * @return void
     */
    public function test_retrieve_tag_fail() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $fetchquestion = new fetchquestion($dummyclass, -11, 1, 100, array('phpunittag_'));
        $this->assertEquals(0, count($fetchquestion->retrieve_tag()));

        $fetchquestion = new fetchquestion($dummyclass, 'asdf', 1, 100, array('phpunittag_'));
        $this->assertEquals(0, count($fetchquestion->retrieve_tag()));

        $fetchquestion2 = new fetchquestion($dummyclass, 0, 1, 100, array('phpunittag_'));
        $this->assertEquals(0, count($fetchquestion2->retrieve_tag()));

        $fetchquestion3 = new fetchquestion($dummyclass, 999, 1, 100, array('phpunittag_'));
        $this->assertEquals(0, count($fetchquestion3->retrieve_tag()));
    }

    /**
     * This functions tests the retrevial of tag ids with an associated difficulty level
     * but using legit data
     * @return void
     */
    public function test_retrieve_tag() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $dummyclass = new stdClass();

        $fetchquestion = new fetchquestion($dummyclass, 5, 1, 100, array('phpunittag_'));
        $data = $fetchquestion->retrieve_tag(5);
        $this->assertEquals(2, count($data));
        $this->assertEquals(array(1 => 1, 2 => 2), $data);

        $fetchquestion2 = new fetchquestion($dummyclass, 888, 1, 100, array('phpunittag_'));
        $data = $fetchquestion->retrieve_tag(888);
        $this->assertEquals(1, count($data));
        $this->assertEquals(array(3 => 3), $data);
    }

    /**
     * This function test output from fetch_question() where initalize_tags_with_quest_count() returns an empty array
     */
    public function test_fetch_question_initalize_tags_with_quest_count_return_empty_array() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $functions = array('initalize_tags_with_quest_count', 'retrieve_tag', 'find_questions_with_tags');
        $mockclass = $this->getMock('fetchquestion', $functions, array($dummyclass, 5, 1, 100));

        $mockclass->expects($this->once())
                ->method('initalize_tags_with_quest_count')
                ->will($this->returnValue(array()));

        $mockclass->expects($this->never())
                ->method('retrieve_tag');

        $mockclass->expects($this->never())
                ->method('find_questions_with_tags');

        $result = $mockclass->fetch_questions();
        $expected = array();
        $this->assertEquals($expected, $result);
    }

    /**
     * This function test output from fetch_question() where the initial requested level has available questions
     */
    public function test_fetch_question_requested_level_has_questions() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $functions = array('initalize_tags_with_quest_count', 'retrieve_tag', 'find_questions_with_tags');
        $mockclass = $this->getMock('fetchquestion', $functions, array($dummyclass, 5, 1, 100));

        $tagquestsum = array(5 => 2);
        $mockclass->expects($this->once())
                ->method('initalize_tags_with_quest_count')
                ->with(array(), array('adpq_'), '1', '100')
                ->will($this->returnValue($tagquestsum));

        $mockclass->expects($this->once())
                ->method('retrieve_tag')
                ->with(5)
                ->will($this->returnValue(array(11)));

        $mockclass->expects($this->once())
                ->method('find_questions_with_tags')
                ->with(array(11), array())
                ->will($this->returnValue(array(22)));

        $result = $mockclass->fetch_questions();
        $expected = array(22);
        $this->assertEquals($expected, $result);
    }

    /**
     * This function test output from fetch_question() where one level higher than requested level has avaialbe questions
     */
    public function test_fetch_question_one_level_higher_has_questions() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $functions = array('initalize_tags_with_quest_count', 'retrieve_tag', 'find_questions_with_tags');
        $mockclass = $this->getMock('fetchquestion', $functions, array($dummyclass, 5, 1, 100));

        $tagquestsum = array(5 => 0, 6 => 1);
        $mockclass->expects($this->once())
                ->method('initalize_tags_with_quest_count')
                ->with(array(), array('adpq_'), '1', '100')
                ->will($this->returnValue($tagquestsum));

        $mockclass->expects($this->once())
                ->method('retrieve_tag')
                ->with(6)
                ->will($this->returnValue(array(11)));

        $mockclass->expects($this->once())
                ->method('find_questions_with_tags')
                ->with(array(11), array())
                ->will($this->returnValue(array(22)));

        $result = $mockclass->fetch_questions();
        $expected = array(22);
        $this->assertEquals($expected, $result);
    }

    /**
     * This function test output from fetch_question() where five levels higher than requested level has avaialbe questions
     */
    public function test_fetch_question_five_levels_higher_has_questions() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $functions = array('initalize_tags_with_quest_count', 'retrieve_tag', 'find_questions_with_tags');
        $mockclass = $this->getMock('fetchquestion', $functions, array($dummyclass, 5, 1, 100));

        $tagquestsum = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 2);
        $mockclass->expects($this->once())
                ->method('initalize_tags_with_quest_count')
                ->with(array(), array('adpq_'), '1', '100')
                ->will($this->returnValue($tagquestsum));

        $mockclass->expects($this->once())
                ->method('retrieve_tag')
                ->with(10)
                ->will($this->returnValue(array(11)));

        $mockclass->expects($this->once())
                ->method('find_questions_with_tags')
                ->with(array(11), array())
                ->will($this->returnValue(array(22)));

        $result = $mockclass->fetch_questions();
        $expected = array(22);
        $this->assertEquals($expected, $result);
    }

    /**
     * This function test output from fetch_question() where four levels lower than requested level has avaialbe questions
     */
    public function test_fetch_question_four_levels_lower_has_questions() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $functions = array('initalize_tags_with_quest_count', 'retrieve_tag', 'find_questions_with_tags');
        $mockclass = $this->getMock('fetchquestion', $functions, array($dummyclass, 5, 1, 100));

        $tagquestsum = array(1 => 1, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0);
        $mockclass->expects($this->once())
                ->method('initalize_tags_with_quest_count')
                ->with(array(), array('adpq_'), '1', '100')
                ->will($this->returnValue($tagquestsum));

        $mockclass->expects($this->once())
                ->method('retrieve_tag')
                ->with(1)
                ->will($this->returnValue(array(11)));

        $mockclass->expects($this->once())
                ->method('find_questions_with_tags')
                ->with(array(11), array())
                ->will($this->returnValue(array(22)));

        $result = $mockclass->fetch_questions();
        $expected = array(22);
        $this->assertEquals($expected, $result);
    }

    /**
     * This function test output from fetch_question() where seraching for a question goes outside the min and max boundries and stops the searching
     */
    public function test_fetch_question_search_outside_min_max_bounds() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $functions = array('initalize_tags_with_quest_count', 'retrieve_tag', 'find_questions_with_tags');
        $mockclass = $this->getMock('fetchquestion', $functions, array($dummyclass, 50, 49, 51));

        $tagquestsum = array(48 => 1, 52 => 1);
        $mockclass->expects($this->once())
                ->method('initalize_tags_with_quest_count')
                ->with(array(), array('adpq_'), 49, 51)
                ->will($this->returnValue($tagquestsum));

        $mockclass->expects($this->never())
                ->method('retrieve_tag');

        $mockclass->expects($this->never())
                ->method('find_questions_with_tags');

        $result = $mockclass->fetch_questions();
        $expected = array();
        $this->assertEquals($expected, $result);
    }

    /**
     * This function tests the output from retrieve_all_tag_ids()
     */
    public function test_retrieve_all_tag_ids_one_to_one_hundred_default_tag_prefix() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $dummyclass = new stdClass();
        $fetchquestion = new fetchquestion($dummyclass, 5, 1, 100);

        $result = $fetchquestion->retrieve_all_tag_ids(1, 100, 'adpq_');
        $expected = array(5 => '1', 6 => '4', 7 => '5', 8 => '6', 9 => '7', 10 => '8');
        $this->assertEquals($expected, $result);
    }


    /**
     * This function tests the output from retrieve_all_tag_ids()
     * @expectedException dml_read_exception
     */
    public function test_retrieve_all_tag_ids_throw_dml_read_exception() {
        $this->resetAfterTest(true);

        $dummyclass = new stdClass();
        $fetchquestion = new fetchquestion($dummyclass, 5, 1, 100);

        $result = $fetchquestion->retrieve_all_tag_ids(1, 5, '');
    }

    /**
     * This function tests the output from retrieve_tags_with_question_count()
     */
    public function test_retrieve_tags_with_question_count_using_default_tag_prefix() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $dummyclass = new stdClass();
        $tagids = array(1, 4, 5, 6, 7, 8);
        $categoryids = '5';
        $fetchquestion = new fetchquestion($dummyclass, 5, 1, 100);
        $result = $fetchquestion->retrieve_tags_with_question_count($tagids, $categoryids, 'adpq_');

        $expected = array();
        $expected[5] = '1';
        $expected[10] = '2';

        $this->assertEquals($expected, $result);
    }

    /**
     * This is a data provider for
     * @return $data - an array with arrays of data
     */
    public function constructor_throw_coding_exception_provider() {
        $data = array(
            array(0, 1, 100),
            array(1, 100, 100),
            array(1, 100, 99)
        );

        return $data;
    }

    /**
     * This function tests throwing an exception by passing incorrect parameters
     * @dataProvider constructor_throw_coding_exception_provider
     * @expectedException coding_exception
     * @param int $level the difficulty level
     * @param int $min the minimum level of the attempt
     * @param int $max the maximum level of the attempt
     */
    public function test_constructor_throw_coding_exception($level, $min, $max) {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();
        $fetchquestion = new fetchquestion($dummyclass, $level, $min, $max);
    }

    /**
     * This function tests the output from initalize_tags_with_quest_count()
     */
    public function test_initalize_tags_with_quest_count() {
        $this->resetAfterTest(true);

        $dummyclass = new stdClass();
        $mockclass = $this->getMock(
                'fetchquestion',
                array('retrieve_question_categories', 'retrieve_all_tag_ids', 'retrieve_tags_with_question_count'),
                array($dummyclass, 1, 1, 100)
        );

        $mockclass->expects($this->once())
            ->method('retrieve_question_categories')
            ->will($this->returnValue(array(1 => 1, 2 => 2, 3 => 3)));

        $mockclass->expects($this->exactly(2))
            ->method('retrieve_all_tag_ids')
            ->withAnyParameters()
            ->will($this->returnValue(array(4 => 4, 5 => 5, 6 => 6)));

        $mockclass->expects($this->exactly(2))
            ->method('retrieve_tags_with_question_count')
            ->withAnyParameters()
            ->will($this->returnValue(array(1 => 8, 2 => 3, 5 => 10)));

        $tags = array('test1_', 'test2_');
        $result = array();
        $result = $mockclass->initalize_tags_with_quest_count($result, $tags, 1, 100);

        $expected = array(1 => 16, 2 => 6, 5 => 20);
        $this->assertEquals($expected, $result);
    }

    /**
     * This function tests the output from initalize_tags_with_quest_count() passing an already built difficulty question sum structure
     */
    public function test_initalize_tags_with_quest_count_pre_built_quest_sum_struct_rebuild_false() {
        $this->resetAfterTest(true);

        $dummyclass = new stdClass();
        $mockclass = $this->getMock(
                'fetchquestion',
                array('retrieve_question_categories', 'retrieve_all_tag_ids', 'retrieve_tags_with_question_count'),
                array($dummyclass, 1, 1, 100)
        );

        $mockclass->expects($this->never())
            ->method('retrieve_question_categories');

        $mockclass->expects($this->never())
            ->method('retrieve_all_tag_ids');

        $mockclass->expects($this->never())
            ->method('retrieve_tags_with_question_count');

        $tags = array('test1_', 'test2_');
        $result = array(1, 2, 3, 4);
        $result = $mockclass->initalize_tags_with_quest_count($result, $tags, 1, 100, false);
    }

    /**
     * This function tests the output from initalize_tags_with_quest_count(), passing an already built difficulty question sum structure, forcing a rebuild
     */
    public function test_initalize_tags_with_quest_count_pre_built_quest_sum_struct_rebuild_true() {
        $this->resetAfterTest(true);

        $dummyclass = new stdClass();
        $mockclass = $this->getMock(
                'fetchquestion',
                array('retrieve_question_categories', 'retrieve_all_tag_ids', 'retrieve_tags_with_question_count'),
                array($dummyclass, 1, 1, 100)
        );

        $mockclass->expects($this->once())
            ->method('retrieve_question_categories')
            ->will($this->returnValue(array(1 => 1, 2 => 2, 3 => 3)));

        $mockclass->expects($this->exactly(2))
            ->method('retrieve_all_tag_ids')
            ->withAnyParameters()
            ->will($this->returnValue(array(4 => 4, 5 => 5, 6 => 6)));

        $mockclass->expects($this->exactly(2))
            ->method('retrieve_tags_with_question_count')
            ->withAnyParameters()
            ->will($this->returnValue(array(1 => 8, 2 => 3, 5 => 10)));

        $tags = array('test1_', 'test2_');
        $result = array(1, 2, 3, 4);
        $result = $mockclass->initalize_tags_with_quest_count($result, $tags, 1, 100, true);

        $expected = array(1 => 16, 2 => 6, 5 => 20);
        $this->assertEquals($expected, $result);
    }

    /**
     * This function tests the output from decrement_question_sum_from_difficulty()
     */
    public function test_decrement_question_sum_from_difficulty() {
        $this->resetAfterTest(true);

        $dummyclass = new stdClass();
        $result = array(1 => 12);
        $expected = array(1 => 11);

        $fetchquestion = new fetchquestion($dummyclass, 1, 1, 2);
        $result = $fetchquestion->decrement_question_sum_from_difficulty($result, 1);
        $this->assertEquals($expected, $result);
    }

    /**
     * This function tests the output from decrement_question_sum_from_difficulty(), using a key that doesn't exist
     */
    public function test_decrement_question_sum_from_difficulty_user_missing_key() {
        $this->resetAfterTest(true);

        $dummyclass = new stdClass();
        $result = array(1 => 12);
        $expected = array(1 => 12);

        $fetchquestion = new fetchquestion($dummyclass, 1, 1, 2);
        $result = $fetchquestion->decrement_question_sum_from_difficulty($result, 2);
        $this->assertEquals($expected, $result);
    }

    /**
     * This function tests the output of find_questions_with_tags() when multiple question categories contain multiple questions using XML data
     */
    public function test_find_questions_with_tags_with_multiple_quest_in_quest_category() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();
        $this->setup_generator_data();

        $mockclass = $this->getMock('fetchquestion', array('retrieve_question_categories'), array(new stdClass(), 1, 1, 100));

        $mockclass->expects($this->once())
                ->method('retrieve_question_categories')
                ->will($this->returnValue(array(6 => 6, 7 => 7, 8 => 8)));

        $data = $mockclass->find_questions_with_tags(array(22, 23, 24));
        $this->assertEquals(3, count($data));

        $dummyone = new stdClass();
        $dummyone->id = '11';
        $dummyone->name = 'multiple_quest_in_quest_category 1';
        $dummytwo = new stdClass();
        $dummytwo->id = '12';
        $dummytwo->name = 'multiple_quest_in_quest_category 2';
        $dummythree = new stdClass();
        $dummythree->id = '13';
        $dummythree->name = 'multiple_quest_in_quest_category 3';
        $expected = array(11 => $dummyone, 12 => $dummytwo, 13 => $dummythree);
        $this->assertEquals($expected, $data);
    }

    /**
     * This function tests the output of retrieve_tags_with_question_count() when multiple question categories contain multiple questions using XML data
     */
    public function test_retrieve_tags_with_question_count_with_multiple_quest_in_quest_category() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();
        $this->setup_generator_data();

        $fetchquestion = new fetchquestion(new stdClass(), 1, 1, 100);

        $data = $fetchquestion->retrieve_tags_with_question_count(array(22, 23, 24), array(7, 6, 8), 'test1_');
        $this->assertEquals(3, count($data));
        $expected = array(22 => '1', 23 => '1', 24 => '1');
        $this->assertEquals($expected, $data);
    }

    /**
     * This function tests the return value of retrieve_question_categories()
     */
    public function test_retrieve_question_categories() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();

        $dummy = new stdClass();
        $dummy->id = 1;

        $fetchquestion = new mod_adaptivequiz_mock_fetchquestion($dummy, 1, 1, 100);
        $data = $fetchquestion->return_retrieve_question_categories();
        $this->assertEquals(2, count($data));
        $expected = array(1 => '11', 2 => '22');
        $this->assertEquals($expected, $data);
    }
}