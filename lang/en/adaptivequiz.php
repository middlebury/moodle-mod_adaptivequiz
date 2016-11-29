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

$string['modulenameplural'] = 'Adaptive Quiz';
$string['modulename'] = 'Adaptive Quiz';
$string['modulename_help'] = 'The Adaptive Quiz activity enables a teacher to create quizes that efficiently measure the takers\' abilities. Adaptive quizes are comprised  of questions selected from the question bank that are tagged with a score of their difficulty. The questions are chosen to match the estimated ability level of the  current test-taker. If the test-taker succeeds on a question, a more challenging question is presented next. If the test-taker answers a question incorrectly, a less-challenging question is presented next. This technique will develop into a sequence of questions converging on the test-taker\'s effective ability level. The quiz stops when the test-taker\'s ability is determined to the required accuracy.

This activity is best suited to determining an ability measure along a unidimensional scale. While the scale can be very broad, the questions must all provide a measure of ability or aptitude on the same scale. In a placement test for example, questions low on the scale that novices are able to answer correctly should also be answerable by experts, while questions higher on the scale should only be answerable by experts or a lucky guess. Questions that do not discriminate between takers of different abilities on will make the test ineffective and may provide inconclusive results.

Questions used in the Adaptive Quiz must

 * be automatically scored as correct/incorrect
 * be tagged with their difficulty using \'adpq_\' followed by a positive integer that is within the range for the quiz

The Adaptive Quiz can be configured to

 * define the range of question-difficulties/user-abilities to be measured. 1-10, 1-16, and 1-100 are examples of valid ranges.
 * define the precision required before the quiz is stopped. Often an error of 5% in the ability measure is an appropriate stopping rule
 * require a minimum number of questions to be answered
 * require a maximum number of questions that can be answered

This description and the testing process in this activity are based on <a href="http://www.rasch.org/memo69.pdf">Computer-Adaptive Testing: A Methodology Whose Time Has Come</a> by John Michael Linacre, Ph.D. MESA Psychometric Laboratory - University of Chicago. MESA Memorandum No. 69.';
$string['pluginadministration'] = 'Adaptive Quiz';
$string['pluginname'] = 'Adaptive Quiz';
$string['nonewmodules'] = 'No Adaptive Quiz instances found';
$string['adaptivequizname'] = 'Name';
$string['adaptivequizname_help'] = 'Enter the name of the Adaptive Quiz instance';
$string['adaptivequiz:addinstance'] = 'Add a new adaptive quiz';
$string['adaptivequiz:viewreport'] = 'View adaptive quiz reports';
$string['adaptivequiz:reviewattempts'] = 'Review adaptive quiz submissions';
$string['adaptivequiz:attempt'] = 'Attempt adaptive quiz';
$string['attemptsallowed'] = 'Attempts allowed';
$string['attemptsallowed_help'] = 'The number of times a student may attempt this activity';
$string['requirepassword'] = 'Required password';
$string['requirepassword_help'] = 'Students are required to enter a password before beginning their attempt';
$string['browsersecurity'] = 'Browser security';
$string['browsersecurity_help'] = 'If "Full screen pop-up with some JavaScript security" is selected the quiz will only start if the student has a JavaScript-enabled web-browser, the quiz appears in a full screen popup window that covers all the other windows and has no navigation controls and students are prevented, as far as is possible, from using facilities like copy and paste';
$string['minimumquestions'] = 'Minimum number of questions';
$string['minimumquestions_help'] = 'The minimum number of questions the student must attempt';
$string['maximumquestions'] = 'Maximum number of questions';
$string['maximumquestions_help'] = 'The maximum number of questions the student can attempt';
$string['startinglevel'] = 'Starting level of difficulty';
$string['startinglevel_help'] = 'The the student begins an attempt, the activity will randomly select a question matching the level of difficulty';
$string['lowestlevel'] = 'Lowest level of difficulty';
$string['lowestlevel_help'] = 'The lowest or least difficult level the assessment can select questions from.  During an attempt the activity will not go beyond this level of difficulty';
$string['highestlevel'] = 'Highest level of difficulty';
$string['highestlevel_help'] = 'The highest or most difficult level the assessment can select questions from.  During an attempt the activity will not go beyond this level of difficulty';
$string['questionpool'] = 'Question pool';
$string['questionpool_help'] = 'Select the question category(ies) where the activity will pull questions from during an attempt.';
$string['formelementempty'] = 'Input a positive integer from 1 to 999';
$string['formelementnumeric'] = 'Input a numeric value from 1 to 999';
$string['formelementnegative'] = 'Input a positive number from 1 to 999';
$string['formminquestgreaterthan'] = 'Minimum number of questions must be less than maximum number of questions';
$string['formlowlevelgreaterthan'] = 'Lowest level must be less than highest level';
$string['formstartleveloutofbounds'] = 'The starting level must be a number that is inbetween the lowest and highest level';
$string['standarderror'] = 'Standard Error to stop';
$string['standarderror_help'] = 'When the amount of error in the measure of the user\'s ability drops below this amount, the quiz will stop. Tune this value from the default of 5% to require more or less precision in the ability measure';
$string['formelementdecimal'] = 'Input a decimal number.  Maximum 10 digits long and maximum 5 digits to the right of the decimal point';
$string['attemptfeedback'] = 'Attempt feedback';
$string['attemptfeedback_help'] = 'The attempt feedback is displayed to the user once the attempt is finished';
$string['formquestionpool'] = 'Select at least one question category';
$string['submitanswer'] = 'Submit answer';
$string['startattemptbtn'] = 'Start attempt';
$string['viewreportbtn'] = 'View report';
$string['errorfetchingquest'] = 'Unable to fetch a questions for level {$a->level}';
$string['leveloutofbounds'] = 'Requested level {$a->level} out of bounds for the attempt';
$string['errorattemptstate'] = 'There was an error in determining the state of the attempt';
$string['nopermission'] = 'You don\t have permission to view this resource';
$string['maxquestattempted'] = 'Maximum number of questions attempted';
$string['notyourattempt'] = 'This is not your attempt at the activity';
$string['noattemptsallowed'] = 'No more attempts allowed at this activity';
$string['completeattempterror'] = 'Error trying to complete attempt record';
$string['updateattempterror'] = 'Error trying to update attempt record';
$string['numofattemptshdr'] = 'Number of attempts';
$string['standarderrorhdr'] = 'Standard error';
$string['errorlastattpquest'] = 'Error checking the response value for the last attempted question';
$string['errornumattpzero'] = 'Error with number of questions attempted equals zero, but user submitted an answer to previous question';
$string['errorsumrightwrong'] = 'Sum of correct and incorrect answers does not equal the total number of questions attempted';
$string['calcerrorwithinlimits'] = 'Calculated standard error of {$a->calerror} is within the limits imposed by the activity {$a->definederror}';
$string['missingtagprefix'] = 'Missing tag prefix';
$string['recentactquestionsattempted'] = 'Questions attempted: {$a}';
$string['recentattemptstate'] = 'State of attempt:';
$string['recentinprogress'] = 'In progress';
$string['notinprogress'] = 'This attempt is not in progress.';
$string['recentcomplete'] = 'Completed';
$string['functiondisabledbysecuremode'] = 'That functionality is currently disabled';
$string['enterrequiredpassword'] = 'Enter required password';
$string['requirepasswordmessage'] = 'To attempt this quiz you need to know the quiz password';
$string['wrongpassword'] = 'Password is incorrect';
$string['noattemptrecords'] = 'No attempt records for this student';
$string['attemptstate'] = 'State of attempt';
$string['attemptstopcriteria'] = 'Reason for stopping attempt';
$string['questionsattempted'] = 'Sum of questions attempted';
$string['attemptfinishedtimestamp'] = 'Attempt finish time';
$string['backtomainreport'] = 'Back to main reports';
$string['reviewattempt'] = 'Review attempt';
$string['indvuserreport'] = 'Individual user attempts report for {$a}';
$string['activityreports'] = 'Attempts report';
$string['stopingconditionshdr'] = 'Stopping conditions';
$string['backtoviewattemptreport'] = 'Back to view attempt report';
$string['backtoviewreport'] = 'Back to main reports';
$string['reviewattemptreport'] = 'Reviewing attempt by {$a->fullname} submitted on {$a->finished}';
$string['deleteattemp'] = 'Delete attempt';
$string['confirmdeleteattempt'] = 'Confirming the deletion of attempt from {$a->name} submitted on {$a->timecompleted}';
$string['attemptdeleted'] = 'Attempt deleted for {$a->name} submitted on {$a->timecompleted}';
$string['errordeletingattempt'] = 'Attempt record was not found';
$string['closeattempt'] = 'Close attempt';
$string['confirmcloseattempt'] = 'Are you sure that you wish to close and finalize this attempt of {$a->name}?';
$string['confirmcloseattemptstats'] = 'This attempt was started on {$a->started} and last updated on {$a->modified}.';
$string['confirmcloseattemptscore'] = '{$a->num_questions} questions were answered and the score so far is {$a->measure} {$a->standarderror}.';
$string['attemptclosedstatus'] = 'Manually closed by {$a->current_user_name} (user-id: {$a->current_user_id}) on {$a->now}.';
$string['attemptclosed'] = 'The attempt has been manually closed.';
$string['errorclosingattempt'] = 'Attempt record was not found';
$string['errorclosingattempt_alreadycomplete'] = 'This attempt is already complete, it cannot be manually closed.';
$string['formstderror'] = 'Must enter a percent less than 50 and greater than or equal to 0';
$string['backtoviewattemptreport'] = 'Back to view attempt report';
$string['backtoviewreport'] = 'Back to main reports';
$string['reviewattemptreport'] = 'Reviewing attempt by {$a->fullname} submitted on {$a->finished}';
$string['score'] = 'Score';
$string['bestscore'] = 'Best Score';
$string['bestscorestderror'] = 'Standard Error';
$string['attempt_summary'] = 'Attempt Summary';
$string['scoring_table'] = 'Scoring Tables';
$string['attempt_questiondetails'] = 'Question Details';
$string['attemptstarttime'] = 'Attempt start time';
$string['attempttotaltime'] = 'Total time (hh:mm:ss)';
$string['attempt_user'] = 'User';
$string['attempt_state'] = 'Attempt state';
$string['attemptquestion_num'] = '#';
$string['attemptquestion_level'] = 'Question Level';
$string['attemptquestion_rightwrong'] = 'Right/Wrong';
$string['attemptquestion_ability'] = 'Ability Measure';
$string['attemptquestion_error'] = 'Standard Error (&plusmn;&nbsp;x%)';
$string['attemptquestion_difficulty'] = 'Question Difficulty (logits)';
$string['attemptquestion_diffsum'] = 'Difficulty Sum';
$string['attemptquestion_abilitylogits'] = 'Measured Ability (logits)';
$string['attemptquestion_stderr'] = 'Standard Error (&plusmn;&nbsp;logits)';
$string['graphlegend_target'] = 'Target Level';
$string['graphlegend_error'] = 'Standard Error';
$string['unknownuser'] = 'Unknown user';
$string['answerdistgraph_title'] = 'Answer distribution for {$a->firstname} {$a->lastname}';
$string['answerdistgraph_questiondifficulty'] = 'Question level';
$string['answerdistgraph_numrightwrong'] = 'Num wrong (-)  /  Num right (+)';
$string['numright'] = 'Num right';
$string['numwrong'] = 'Num wrong';
$string['questionnumber'] = 'Question #';
$string['na'] = 'n/a';
$string['downloadcsv'] = 'Download CSV';

$string['grademethod'] = 'Grading method';
$string['gradehighest'] = 'Highest grade';
$string['attemptfirst'] = 'First attempt';
$string['attemptlast'] = 'Last attempt';
$string['grademethod_help'] = 'When multiple attempts are allowed, the following methods are available for calculating the final quiz grade:

* Highest grade of all attempts
* First attempt (all other attempts are ignored)
* Last attempt (all other attempts are ignored)';
$string['resetadaptivequizsall'] = 'Delete all Adaptive Quiz attempts';
$string['all_attempts_deleted'] = 'All Adaptive Quiz attempts were deleted';
$string['all_grades_removed'] = 'All Adaptive Quiz grades were removed';

$string['questionanalysisbtn'] = 'Question Analysis';
$string['id'] = 'ID';
$string['name'] = 'Name';
$string['questions_report'] = 'Questions Report';
$string['question_report'] = 'Question Analysis';
$string['times_used_display_name'] = 'Times Used';
$string['percent_correct_display_name'] = '% Correct';
$string['discrimination_display_name'] = 'Discrimination';
$string['back_to_all_questions'] = '&laquo; Back to all questions';
$string['answers_display_name'] = 'Answers';
$string['answer'] = 'Answer';
$string['statistic'] = 'Statistic';
$string['value'] = 'Value';
$string['highlevelusers'] = 'Users above the question-level';
$string['midlevelusers'] = 'Users near the question-level';
$string['lowlevelusers'] = 'Users below the question-level';
$string['user'] = 'User';
$string['result'] = 'Result';
