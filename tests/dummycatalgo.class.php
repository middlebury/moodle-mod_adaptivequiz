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
 * A dummy class that extands catalgo class.  The purpose of this class is to expose the protected method of return_current_diff_level()
 *
 * @package    mod_adaptivequiz
 * @category   phpunit
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_adaptivequiz_mock_catalgo extends catalgo {

    /**
     * This function calculates the currently difficulty level of the attempt by calling the parent class' method
     * @param question_usage_by_activity $quba a question usage by activity set to an attempt id
     * @param int $level the starting level of difficulty for the attempt
     * @param stdClass $attemptobj an object with the following properties: lowestlevel and highestlevel
     * @return int the current level of difficulty
     */
    public function return_current_diff_level($quba, $level, $attemptobj) {
        return parent::return_current_diff_level($quba, $level, $attemptobj);
    }
}