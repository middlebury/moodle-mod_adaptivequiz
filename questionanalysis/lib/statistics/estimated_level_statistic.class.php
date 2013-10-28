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

require_once(dirname(__FILE__).'/question_statistic.interface.php');

/**
 * Questions-statistic
 *
 * This interface defines the methods required for pluggable statistics that may be added to the question analysis.
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adaptivequiz_estimated_level_statistic implements adaptivequiz_question_statistic {
    
    /**
     * Answer a display-name for this statistic.
     * 
     * @return string
     */
    public function get_display_name () {
    	return get_string('estimated_level_display_name', 'adaptivequiz');
    }
    
    /**
     * Calculate this statistic for a question's results
     * 
     * @param adaptivequiz_question_analyser $question_analyser
     * @return adaptivequiz_question_statistic_result
     */
    public function calculate (adaptivequiz_question_analyser $question_analyser) {
        // This is a first pass at an estimation of what the question level "should be" based on the
        // levels of the respondants answering it correctly and incorrectly.
        // Note that this is a first pass and may not be valid.
        
        $correct_abilities = array();
        $incorrect_abilities = array();
        
        foreach ($question_analyser->get_results() as $result) {
            if ($result->correct) {
                $correct_abilities[] = $result->score->measured_ability_in_logits();
            } else {
                $incorrect_abilities[] = $result->score->measured_ability_in_logits();
            }
        }
        
        // We need at least one correct and incorrect result
        if (count($correct_abilities) && count($incorrect_abilities)) {
            $mean_correct_ability = array_sum($correct_abilities) / count($correct_abilities);
            $mean_incorrect_ability = array_sum($incorrect_abilities) / count($incorrect_abilities);
            // The mean correct ability should be greater than the mean incorrect ability to have a meaningful estimated level.
            if ($mean_correct_ability > $mean_incorrect_ability) {
                $estimated_level_logits = ($mean_correct_ability + $mean_incorrect_ability) / 2;
                $estimated_level_scaled = $question_analyser->map_logit_to_scale($estimated_level_logits);
                return new adaptivequiz_estimated_level_statistic_result ($estimated_level_scaled);
            } else {
                return new adaptivequiz_estimated_level_statistic_result (null);
            }
        }
        // If we don't have any responses in either group, return a null result.
        else {
            return new adaptivequiz_estimated_level_statistic_result (null);
        }
    }
}

/**
 * Questions-statistic-result
 *
 * This interface defines the methods required for pluggable statistic-results that may be added to the question analysis.
 *
 * This module was created as a collaborative effort between Middlebury College
 * and Remote Learner.
 *
 * @package    mod_adaptivequiz
 * @copyright  2013 Middlebury College {@link http://www.middlebury.edu/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adaptivequiz_estimated_level_statistic_result implements adaptivequiz_question_statistic_result {
    
    /** @var float $estimated_level  */
    protected $estimated_level = null;
    
    /**
     * Constructor
     * 
     * @param float $estimated_level
     * @return void
     */
    public function __construct ($estimated_level) {
    	$this->estimated_level = $estimated_level;
    }
    
    /**
     * A sortable version of the result.
     * 
     * @return mixed string or numeric
     */
    public function sortable () {
        if (is_null($this->estimated_level)) {
            return -2;
        } else {
        	return $this->estimated_level;
        }
    }
    
    /**
     * A printable version of the result.
     * 
     * @param numeric $result
     * @return mixed string or numeric
     */
    public function printable () {
        if (is_null($this->estimated_level)) {
            return 'n/a';
        } else {
        	return round(catalgo::$this->estimated_level, 2);
        }
    }
}
