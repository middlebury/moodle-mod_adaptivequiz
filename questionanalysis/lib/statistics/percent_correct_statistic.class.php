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
 * Questions-statistic interface
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
class adaptivequiz_percent_correct_statistic implements adaptivequiz_question_statistic {

    /**
     * Answer a display-name for this statistic.
     *
     * @return string
     */
    public function get_display_name () {
        return get_string('percent_correct_display_name', 'adaptivequiz');
    }

    /**
     * Calculate this statistic for a question's results
     *
     * @param adaptivequiz_question_analyser $analyser
     * @return adaptivequiz_question_statistic_result
     */
    public function calculate (adaptivequiz_question_analyser $analyser) {
        $correct = 0;
        $total = 0;
        foreach ($analyser->get_results() as $result) {
            $total++;
            if ($result->correct) {
                $correct++;
            }
        }
        if ($total) {
            return new adaptivequiz_percent_correct_statistic_result ($correct / $total);
        } else {
            return new adaptivequiz_percent_correct_statistic_result (0);
        }
    }
}

/**
 * Questions-statistic-result interface
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
class adaptivequiz_percent_correct_statistic_result implements adaptivequiz_question_statistic_result {

    /** @var float $fraction  */
    protected $fraction = null;

    /**
     * Constructor
     *
     * @param float $fraction
     * @return void
     */
    public function __construct ($fraction) {
        $this->fraction = $fraction;
    }

    /**
     * A sortable version of the result.
     *
     * @return mixed string or numeric
     */
    public function sortable () {
        return $this->fraction;
    }

    /**
     * A printable version of the result.
     *
     * @param numeric $result
     * @return mixed string or numeric
     */
    public function printable () {
        return round($this->fraction * 100).'%';
    }
}
