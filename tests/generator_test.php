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
 * Adaptive PHPUnit data generator testcase
 *
 * @package    mod
 * @subpackage adaptivequiz
 * @category   phpunit
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group mod_adaptivequiz
 */
class mod_adaptivequiz_generator_testcase extends advanced_testcase {

    /**
     * Unit test for adaptivequiz generator
     */
    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('adaptivequiz'));

        /** @var mod_quiz_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_adaptivequiz');
        $this->assertInstanceOf('mod_adaptivequiz_generator', $generator);
        $this->assertEquals('adaptivequiz', $generator->get_modulename());

        $generator->create_instance(array('course' => $SITE->id));
        $generator->create_instance(array('course' => $SITE->id));
        $adaptivequiz = $generator->create_instance(array('course' => $SITE->id));
        $this->assertEquals(3, $DB->count_records('adaptivequiz'));

        $cm = get_coursemodule_from_instance('adaptivequiz', $adaptivequiz->id);
        $this->assertEquals($adaptivequiz->id, $cm->instance);
        $this->assertEquals('adaptivequiz', $cm->modname);
        $this->assertEquals($SITE->id, $cm->course);

        $context = context_module::instance($cm->id);
        $this->assertEquals($adaptivequiz->cmid, $context->instanceid);
    }
}
