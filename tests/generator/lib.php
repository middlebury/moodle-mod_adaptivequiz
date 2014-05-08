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
 * Adaptive generator file
 *
 * @package    mod_adaptivequiz
 * @category   phpunit
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_adaptivequiz_generator extends testing_module_generator {
    /**
     * Create new quiz module instance.
     * @param array|stdClass $record
     * @param array $options (mostly course_module properties)
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once("$CFG->dirroot/mod/adaptivequiz/locallib.php");

        $this->instancecount++;
        $i = $this->instancecount;

        $record = (object)(array)$record;
        $options = (array)$options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }
        if (isset($options['idnumber'])) {
            $record->cmidnumber = $options['idnumber'];
        } else {
            $record->cmidnumber = '';
        }

        $defaultadaptivequizsettings = array(
            'name'                   => get_string('pluginname', 'adaptivequiz').' '.$i,
            'intro'                  => 'Test adaptivequiz '.$i,
            'introformat'            => FORMAT_MOODLE,
            'attempts'               => 0,
            'password'               => '',
            'attemptfeedback'        => 'Attempt Feedback',
            'attemptfeedbackformat'  => FORMAT_MOODLE,
            'attemptonlast'          => 0,
            'highestlevel'           => 111,
            'lowestlevel'            => 1,
            'minimumquestions'       => 1,
            'maximumquestions'       => 111,
            'standarderror'          => 1.1,
            'startinglevel'          => 11,
            'timecreated'            => time(),
            'timemodified'           => time(),
            'questionpool'           => array(11, 22, 33, 44),
        );

        foreach ($defaultadaptivequizsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        $record->coursemodule = $this->precreate_course_module($record->course, $options);
        $id = adaptivequiz_add_instance($record);
        return $this->post_add_instance($id, $record->coursemodule);

    }
}
