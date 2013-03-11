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
 * @package    mod_adaptivequiz
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
define('ADAPTIVEQUIZ_QUESTION_TAG', 'adpq_');

require_once($CFG->dirroot.'/mod/adaptivequiz/lib.php');
require_once($CFG->dirroot.'/question/editlib.php');
require_once($CFG->dirroot.'/lib/questionlib.php');

/**
 * This function returns an array of question bank categories accessible to the
 * current user in the given context
 * @param object $context: A context object
 * @return array - An array whose keys are the question category ids and values
 * are the name of the question category
 */
function adaptivequiz_get_question_categories($context) {
    if (empty($context)) {
        return array();
    }

    $options      = array();
    $qesteditctx  = new question_edit_contexts($context);
    $contexts     = $qesteditctx->having_one_edit_tab_cap('editq');
    $questioncats = question_category_options($contexts);

    if (!empty($questioncats)) {
        foreach ($questioncats as $questioncatcourse) {
            foreach ($questioncatcourse as $key => $questioncat) {
                // Key format is [question cat id, question cat context id], we need to explode it.
                $questidcontext = explode(',', $key);
                $questid = array_shift($questidcontext);
                $options[$questid] = $questioncat;
            }
        }
    }

    return $options;
}

/**
 * This function is healper method to create default 
 * @param object $context: A context object
 * @return mixed - The default category in the course context or false
 */
function adaptivequiz_make_default_categories($context) {
    if (empty($context)) {
        return false;
    }

    // Create default question categories
    $defaultcategoryobj = question_make_default_categories(array($context));

    return $defaultcategoryobj;
}

/**
 * This function returns an array of question categories that were
 * selected for use for the activity instance
 * @param int $instance: Instance id
 * @return array - an array of question category ids
 */
function adaptivequiz_get_selected_question_cateogires($instance) {
    global $DB;

    $selquestcat = array();

    if (empty($instance)) {
        return array();
    }

    $records = $DB->get_records('adaptivequiz_question', array('instance' => $instance));

    if (empty($records)) {
        return array();
    }

    foreach ($records as $record) {
        $selquestcat[] = $record->questioncategory;
    }

    return $selquestcat;
}
