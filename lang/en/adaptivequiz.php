<?php
$string['modulenameplural'] = 'Adaptive Test';
$string['modulename'] = 'Adaptive Test';
$string['modulename_help'] = 'The adaptive test activity enables a teacher to create tests that efficiently measure the takers\' abilities. Adaptive tests are comprised  of questions selected from the question bank that are tagged with a score of their difficulty. The questions are chosen to match the estimated ability level of the  current test-taker. If the test-taker succeeds on a question, a more challenging question is presented next. If the test-taker answers a question incorrectly, a less-challenging question is presented next. This technique will develop into a sequence of questions converging on the test-taker\'s effective ability level. The test stops when the test-taker\'s ability is determined to the required accuracy.

This activity is best suited to determining an ability measure along a unidimensional scale. While the scale can be very broad, the questions must all provide a measure of ability or aptitude on the same scale. In a placement test for example, questions low on the scale that novices are able to answer correctly should also be answerable by experts, while questions higher on the scale should only be answerable by experts or a lucky guess. Questions that do not discriminate between takers of different abilities on will make the test ineffective and may provide inconclusive results.

Questions used in the adaptive test must

 * be automatically scored as correct/incorrect
 * be tagged with their difficulty using \'adpq_\' followed by a positive integer that is within the range for the test

The adaptive test can be configured to

 * define the range of question-difficulties/user-abilities to be measured. 1-10, 1-16, and 1-100 are examples of valid ranges.
 * define the precision required before the test is stopped. Often an error of 5% in the ability measure is an appropriate stopping rule
 * require a minimum number of questions to be answered
 * require a maximum number of questions that can be answered

This description and the testing process in this activity are based on <a href="http://www.rasch.org/memo69.pdf">Computer-Adaptive Testing: A Methodology Whose Time Has Come</a> by John Michael Linacre, Ph.D. MESA Psychometric Laboratory - University of Chicago. MESA Memorandum No. 69.';
$string['pluginadministration'] = 'Computer-Adaptive Testing';
$string['pluginname'] = 'Computer-Adaptive Testing';
$string['nonewmodules'] = 'No Computer-Adaptive instances found';
$string['adaptivequizname'] = 'Name';
$string['adaptivequizname_help'] = 'Enter the name of the Computer-Adaptive test instance';
$string['adaptivequiz:addinstance'] = 'Add a new adaptive test';
$string['adaptivequiz:viewreport'] = 'View adaptive test reports';
$string['adaptivequiz:reviewattempts'] = 'Review adaptive test submissions';
$string['adaptivequiz:attempt'] = 'Attempt adaptive test';
$string['attemptsallowed'] = 'Attempts allowed';
$string['attemptsallowed_help'] = 'The number of times a student may attempt this activity';
$string['requirepassword'] = 'Required password';
$string['requirepassword_help'] = 'Students are required to enter a password before beginning their attempt';
$string['browsersecurity'] = 'Browser security';
$string['browsersecurity_help'] = 'If "Full screen pop-up with some JavaScript security" is selected the test will only start if the student has a JavaScript-enabled web-browser, the test appears in a full screen popup window that covers all the other windows and has no navigation controls and students are prevented, as far as is possible, from using facilities like copy and paste';
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
$string['standarderror_help'] = 'When the amount of error in the measure of the user\'s ability drops below this amount, the test will stop. Tune this value from the default of 5% to require more or less precision in the ability measure';
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
$string['recentcomplete'] = 'Completed';
$string['functiondisabledbysecuremode'] = 'That functionality is currently disabled';
$string['enterrequiredpassword'] = 'Enter required password';
$string['requirepasswordmessage'] = 'To attempt this test you need to know the test password';
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
$string['formstderror'] = 'Must enter a percent less than 50 and greater than or equal to 0';
$string['backtoviewattemptreport'] = 'Back to view attempt report';
$string['backtoviewreport'] = 'Back to main reports';
$string['reviewattemptreport'] = 'Reviewing attempt by {$a->fullname} submitted on {$a->finished}';
$string['score'] = 'Score';
$string['bestscore'] = 'Best Score';
$string['attempt_summary'] = 'Attempt Summary';
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
