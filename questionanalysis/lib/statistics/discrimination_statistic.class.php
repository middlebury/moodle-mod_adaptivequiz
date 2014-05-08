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
class adaptivequiz_discrimination_statistic implements adaptivequiz_question_statistic {

    /**
     * Answer a display-name for this statistic.
     *
     * @return string
     */
    public function get_display_name () {
        return get_string('discrimination_display_name', 'adaptivequiz');
    }

    /**
     * Calculate this statistic for a question's results
     *
     * @param adaptivequiz_question_analyser $analyser
     * @return adaptivequiz_question_statistic_result
     */
    public function calculate (adaptivequiz_question_analyser $analyser) {
        // Discrimination is generally defined as comparing the results of two sub-groups,
        // the top 27% of test-takers (the upper group) and the bottom 27% of test-takers (the lower group),
        // assuming a normal distribution of scores).
        //
        // Given that likely have a very sparse data-set we will instead categorize our
        // responses into the upper group if the respondent's overall ability measure minus the measure's standard error
        // is greater than the question's level. Likewise, responses will be categorized into the lower group if the respondent's
        // ability measure plus the measure's standard error is less than the question's level.
        // Responses where the user's ability measure and error-range include the question level will be ignored.

        $level = $analyser->get_question_level_in_logits();
        $uppergroupsize = 0;
        $uppergroupcorrect = 0;
        $lowergroupsize = 0;
        $lowergroupcorrect = 0;

        foreach ($analyser->get_results() as $result) {
            if ($result->score->measured_ability_in_logits() - $result->score->standard_error_in_logits() > $level) {
                // Upper group.
                $uppergroupsize++;
                if ($result->correct) {
                    $uppergroupcorrect++;
                }
            } else if ($result->score->measured_ability_in_logits() + $result->score->standard_error_in_logits() < $level) {
                // Lower Group.
                $lowergroupsize++;
                if ($result->correct) {
                    $lowergroupcorrect++;
                }
            }
        }

        if ($uppergroupsize > 0 && $lowergroupsize > 0) {
            // We need at least one result in the upper and lower groups.
            $upperproportion = $uppergroupcorrect / $uppergroupsize;
            $lowerproportion = $lowergroupcorrect / $lowergroupsize;
            $discrimination = $upperproportion - $lowerproportion;
            return new adaptivequiz_discrimination_statistic_result ($discrimination);
        } else {
            // If we don't have any responses in the upper or lower group, then we don't have a meaningful result.
            return new adaptivequiz_discrimination_statistic_result (null);
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
class adaptivequiz_discrimination_statistic_result implements adaptivequiz_question_statistic_result {

    /** @var float $discrimination  */
    protected $discrimination = null;

    /**
     * Constructor
     *
     * @param float $discrimination
     * @return void
     */
    public function __construct ($discrimination) {
        $this->discrimination = $discrimination;
    }

    /**
     * A sortable version of the result.
     *
     * @return mixed string or numeric
     */
    public function sortable () {
        if (is_null($this->discrimination)) {
            return -2;
        } else {
            return $this->discrimination;
        }
    }

    /**
     * A printable version of the result.
     *
     * @param numeric $result
     * @return mixed string or numeric
     */
    public function printable () {
        if (is_null($this->discrimination)) {
            return 'n/a';
        } else {
            return round($this->discrimination, 3);
        }
    }
}
