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
 * Adaptive testing version information.
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/adaptivequiz/locallib.php');

/**
 * Module instance settings form
 */
class mod_adaptivequiz_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('adaptivequizname', 'adaptivequiz'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'adaptivequizname', 'adaptivequiz');

        // Adding the standard "intro" and "introformat" fields...
        // Use the non deprecated function if it exists.
        if (method_exists($this, 'standard_intro_elements')) {
            $this->standard_intro_elements();
        } else {
            // Deprecated as of Moodle 2.9
            $this->add_intro_editor();
        }

        // Number of attempts.
        $attemptoptions = array('0' => get_string('unlimited'));
        for ($i = 1; $i <= ADAPTIVEQUIZMAXATTEMPT; $i++) {
            $attemptoptions[$i] = $i;
        }
        $mform->addElement('select', 'attempts', get_string('attemptsallowed', 'adaptivequiz'), $attemptoptions);
        $mform->setDefault('attempts', 0);
        $mform->addHelpButton('attempts', 'attemptsallowed', 'adaptivequiz');

        // Require password to begin adaptivequiz attempt.
        $mform->addElement('passwordunmask', 'password', get_string('requirepassword', 'adaptivequiz'));
        $mform->setType('password', PARAM_TEXT);
        $mform->addHelpButton('password', 'requirepassword', 'adaptivequiz');

        // Browser security choices.
        $options = array(get_string('no'), get_string('yes'));
        $mform->addElement('select', 'browsersecurity', get_string('browsersecurity', 'adaptivequiz'), $options);
        $mform->addHelpButton('browsersecurity', 'browsersecurity', 'adaptivequiz');
        $mform->setDefault('browsersecurity', 0);

        // Retireve a list of available course categories.
        adaptivequiz_make_default_categories($this->context);
        $options = adaptivequiz_get_question_categories($this->context);
        $selquestcat = adaptivequiz_get_selected_question_cateogires($this->_instance);

        $select = $mform->addElement('select', 'questionpool', get_string('questionpool', 'adaptivequiz'), $options);
        $mform->addHelpButton('questionpool', 'questionpool', 'adaptivequiz');
        $select->setMultiple(true);
        $mform->addRule('questionpool', null, 'required', null, 'client');
        $mform->getElement('questionpool')->setSelected($selquestcat);

        $mform->addElement('text', 'startinglevel', get_string('startinglevel', 'adaptivequiz'),
            array('size' => '3', 'maxlength' => '3'));
        $mform->addHelpButton('startinglevel', 'startinglevel', 'adaptivequiz');
        $mform->addRule('startinglevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('startinglevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('startinglevel', PARAM_INT);

        $mform->addElement('text', 'lowestlevel', get_string('lowestlevel', 'adaptivequiz'),
            array('size' => '3', 'maxlength' => '3'));
        $mform->addHelpButton('lowestlevel', 'lowestlevel', 'adaptivequiz');
        $mform->addRule('lowestlevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('lowestlevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('lowestlevel', PARAM_INT);

        $mform->addElement('text', 'highestlevel', get_string('highestlevel', 'adaptivequiz'),
            array('size' => '3', 'maxlength' => '3'));
        $mform->addHelpButton('highestlevel', 'highestlevel', 'adaptivequiz');
        $mform->addRule('highestlevel', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('highestlevel', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('highestlevel', PARAM_INT);

        $mform->addElement('textarea', 'attemptfeedback', get_string('attemptfeedback', 'adaptivequiz'),
            'wrap="virtual" rows="10" cols="50"');
        $mform->addHelpButton('attemptfeedback', 'attemptfeedback', 'adaptivequiz');
        $mform->setType('attemptfeedback', PARAM_NOTAGS);

        $mform->addElement('header', 'stopingconditionshdr', get_string('stopingconditionshdr', 'adaptivequiz'));

        $mform->addElement('text', 'minimumquestions', get_string('minimumquestions', 'adaptivequiz'),
            array('size' => '3', 'maxlength' => '3'));
        $mform->addHelpButton('minimumquestions', 'minimumquestions', 'adaptivequiz');
        $mform->addRule('minimumquestions', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('minimumquestions', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('minimumquestions', PARAM_INT);

        $mform->addElement('text', 'maximumquestions', get_string('maximumquestions', 'adaptivequiz'),
            array('size' => '3', 'maxlength' => '3'));
        $mform->addHelpButton('maximumquestions', 'maximumquestions', 'adaptivequiz');
        $mform->addRule('maximumquestions', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('maximumquestions', get_string('formelementnumeric', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setType('maximumquestions', PARAM_INT);

        $mform->addElement('text', 'standarderror', get_string('standarderror', 'adaptivequiz'),
            array('size' => '10', 'maxlength' => '10'));
        $mform->addHelpButton('standarderror', 'standarderror', 'adaptivequiz');
        $mform->addRule('standarderror', get_string('formelementempty', 'adaptivequiz'), 'required', null, 'client');
        $mform->addRule('standarderror', get_string('formelementdecimal', 'adaptivequiz'), 'numeric', null, 'client');
        $mform->setDefault('standarderror', 5.0);
        $mform->setType('standarderror', PARAM_FLOAT);

        // Grade settings.
        $this->standard_grading_coursemodule_elements();
        $mform->removeElement('grade');

        // Grading method.
        $mform->addElement('select', 'grademethod', get_string('grademethod', 'adaptivequiz'),
                adaptivequiz_get_grading_options());
        $mform->addHelpButton('grademethod', 'grademethod', 'adaptivequiz');
        $mform->setDefault('grademethod', ADAPTIVEQUIZ_GRADEHIGHEST);
        $mform->disabledIf('grademethod', 'attempts', 'eq', 1);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * Perform extra validation.  @see validation() - in moodleform_mod.php
     * @param array $data: array of submitted form values
     * @param array $files: array of file data
     * @return array - array of form elements that didn't pass validation
     */
    public function validation($data, $files) {
        $errors = array();

        if (empty($data['questionpool'])) {
            $errors['questionpool'] = get_string('formquestionpool', 'adaptivequiz');
        }

        // Validate for positivity.
        if (0 >= $data['minimumquestions']) {
            $errors['minimumquestions'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0 >= $data['maximumquestions']) {
            $errors['maximumquestions'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0 >= $data['startinglevel']) {
            $errors['startinglevel'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0 >= $data['lowestlevel']) {
            $errors['lowestlevel'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if (0 >= $data['highestlevel']) {
            $errors['highestlevel'] = get_string('formelementnegative', 'adaptivequiz');
        }

        if ((float) 0 > (float) $data['standarderror'] || (float) 50 <= (float) $data['standarderror']) {
            $errors['standarderror'] = get_string('formstderror', 'adaptivequiz');
        }

        // Validate higher and lower values.
        if ($data['minimumquestions'] >= $data['maximumquestions']) {
            $errors['minimumquestions'] = get_string('formminquestgreaterthan', 'adaptivequiz');
        }

        if ($data['lowestlevel'] >= $data['highestlevel']) {
            $errors['lowestlevel'] = get_string('formlowlevelgreaterthan', 'adaptivequiz');
        }

        if (!($data['startinglevel'] >= $data['lowestlevel'] && $data['startinglevel'] <= $data['highestlevel'])) {
            $errors['startinglevel'] = get_string('formstartleveloutofbounds', 'adaptivequiz');
        }

        return $errors;
    }
}
