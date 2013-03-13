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

        $attempt = $this->getMock(
                'fetchquestion',
                array('retrieve_question_categories'),
                array($this->activityinstance)
        );

        $attempt->expects($this->exactly(2))
            ->method('retrieve_question_categories')
            ->will($this->returnValue('1,2,3'));

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
                array($this->activityinstance)
        );

        $mockclass->expects($this->exactly(2))
            ->method('retrieve_question_categories')
            ->will($this->returnValue(''));

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

        $mockclass = $this->getMock(
                'fetchquestion',
                array('retrieve_question_categories'),
                array($this->activityinstance)
        );

        $mockclass->expects($this->once())
            ->method('retrieve_question_categories')
            ->will($this->returnValue('1,2,3'));

        $data = $mockclass->find_questions_with_tags(array(1), array(1));
        $this->assertEquals(0, count($data));
    }

    /**
     * This functions tests the accessor methods for the $searchup class variable
     * @return void
     */
    public function test_get_set_searchup() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $fetchquestion = new fetchquestion($dummyclass);

        $this->assertFalse($fetchquestion->get_searchup());
        $fetchquestion->set_searchup(true);
        $this->assertTrue($fetchquestion->get_searchup());
        $fetchquestion->set_searchup(false);
        $this->assertFalse($fetchquestion->get_searchup());
    }

    /**
     * This functions tests the accessor methods for the $level class variable
     * @expectedException coding_exception
     * @return void
     */
    public function test_get_set_level() {
        $this->resetAfterTest(true);
        $dummyclass = new stdClass();

        $fetchquestion = new fetchquestion($dummyclass, 99);
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

        $fetchquestion = new fetchquestion($dummyclass, -11, array('phpunittag_'));
        $this->assertEquals(0, count($fetchquestion->retrieve_tag()));

        $fetchquestion = new fetchquestion($dummyclass, 'asdf', array('phpunittag_'));
        $this->assertEquals(0, count($fetchquestion->retrieve_tag()));

        $fetchquestion2 = new fetchquestion($dummyclass, 0, array('phpunittag_'));
        $this->assertEquals(0, count($fetchquestion2->retrieve_tag()));

        $fetchquestion3 = new fetchquestion($dummyclass, 999, array('phpunittag_'));
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

        $fetchquestion = new fetchquestion($dummyclass, 5, array('phpunittag_'));
        $data = $fetchquestion->retrieve_tag(5);
        $this->assertEquals(2, count($data));
        $this->assertEquals(array(1 => 1, 2 => 2), $data);

        $fetchquestion2 = new fetchquestion($dummyclass, 888, array('phpunittag_'));
        $data = $fetchquestion->retrieve_tag(888);
        $this->assertEquals(1, count($data));
        $this->assertEquals(array(3 => 3), $data);
    }

    /**
     * This functions tests the retires at finding a question associated to a difficulty level
     * @return void
     */
    public function test_fetch_question_fail() {
        $dummyclass = new stdClass();

        // Test calling fetch_question(), returning an empty set of tag ids and reattempting 5 times
        $mockclass = $this->getMock(
                'fetchquestion',
                array('retrieve_tag', 'find_questions_with_tags'),
                array($this->activityinstance, 5)
        );

        $mockclass->expects($this->exactly(5))
            ->method('retrieve_tag')
            ->will($this->returnValue(array()));

        $mockclass->expects($this->never())
            ->method('find_questions_with_tags');

        $this->assertEquals(0, count($mockclass->fetch_questions()));

        // Test calling fetch_question(), return a tag id, but return empty question ids and reattempting 5 times
        $mockclasstwo = $this->getMock(
                'fetchquestion',
                array('retrieve_tag', 'find_questions_with_tags'),
                array($this->activityinstance, 5)
        );

        $mockclasstwo->expects($this->exactly(5))
            ->method('retrieve_tag')
            ->will($this->returnValue(array(1 => 1)));

        $mockclasstwo->expects($this->exactly(5))
            ->method('find_questions_with_tags')
            ->will($this->returnValue(array()));

        $this->assertEquals(0, count($mockclasstwo->fetch_questions()));
    }

    /**
     * This function tests the retries at finding a question associated to a difficulty level
     * @return void
     */
    public function test_fetch_question() {
        $mockclass = $this->getMock(
                'fetchquestion',
                array('retrieve_tag', 'find_questions_with_tags'),
                array($this->activityinstance, 5)
        );

        $mockclass->expects($this->once())
            ->method('retrieve_tag')
            ->will($this->returnValue(array(1 => 1)));

        $mockclass->expects($this->once())
            ->method('find_questions_with_tags')
            ->will($this->returnValue(array(2, 4, 6, 8, 10)));

        $data = array(2, 4, 6, 8, 10);
        $this->assertEquals($data, $mockclass->fetch_questions());
    }

    /**
     * This functions tests re-attempts at finding a question addociated to a difficulty level, forcing function to
     * re-attempt 5 times and return a level 10 question id and  using testing data
     * @return void
     */
    public function test_fetch_question_with_data() {
        $this->resetAfterTest(true);
        $this->setup_test_data_xml();
        $this->setup_generator_data_two();

        $dummyclass = new stdClass();
        $excquestids = array(2, 3, 4);

        // Test searching up from level 5, excluding all question ids except for questions in level 9
        $fetchquestion = new fetchquestion($this->activityinstance, 5);
        $fetchquestion->set_searchup(true);
        $questionids = $fetchquestion->fetch_questions($excquestids);

        $dataobj = new stdClass();
        $dataobj->id = 5;
        $dataobj->name = 'true or false 5';
        $data = array(5 => $dataobj);

        $this->assertEquals(1, count($questionids));
        $this->assertEquals($data, $questionids);

        // Test searching up from level 5, excluding all questions from levels 5 - 9
        $excquestids = array(2, 3, 4, 5);
        $questionids = $fetchquestion->fetch_questions($excquestids);
        $this->assertEquals(0, count($questionids));

        // Test searching down from level 5
        $questionids = $fetchquestion->fetch_questions($excquestids);
        $fetchquestion->set_searchup(false);
        $this->assertEquals(0, count($questionids));
    }
}