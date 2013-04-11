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
 * A dummy class that extands fetchquestion class.  The purpose of this class is to expose the protected method of retrieve_question_categories()
 *
 * @package    mod_adaptivequiz
 * @category   phpunit
 * @copyright  2013 onwards Remote-Learner {@link http://www.remote-learner.ca/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_adaptivequiz_mock_fetchquestion extends fetchquestion {
    /**
     * Constructor
     */
    public function __construct($adaptivequiz, $level = 1, $minimumlevel, $maximumlevel, $tags = array()) {
        parent::__construct($adaptivequiz, $level, $minimumlevel, $maximumlevel, $tags);
    }

    /**
     * This function retrieves the question categories associated with the activity instance
     * @return array whose keys and values are question categoriy ids
     */
    public function return_retrieve_question_categories() {
        return parent::retrieve_question_categories();
    }
}