<?php
$string['modulenameplural'] = 'Computer-Adaptive Testing';
$string['modulename'] = 'Computer-Adaptive Testing';
$string['modulename_help'] = 'Computer-adaptive testing (CAT) is the more powerful successor to a series of successful applications of adaptive '.
'testing, starting with Binet in 1905. Adaptive tests are comprised of items selected from a collection of items, known as an item bank. The items '.
'are chosen to match the estimated ability level (or aptitude level, etc.) of the current test-taker. If the test-taker succeeds on an item, a '.
'slightly more challenging item is presented next, and vice-versa. This technique usually quickly converges into sequence of items bracketing, '.
'and converging on, the test-taker\'s effective ability level. The test stops when the test-taker\'s ability is determined to the required accuracy. '.
'The test-taker may then be immediately informed of the test-results, if so desired. Pilot-testing new items for the item bank, and validating the '.
'quality of current items can take place simultaneously with test-administration. Advantages of CAT can include shorter, quicker tests, flexible '.
'testing schedules, increased test security, better control of item exposure, better balancing of test content areas for all ability levels, quicker '.
'test item updating, quicker reporting, and a better test-taking experience for the test-taker. Disadvantages include equipment and facility '.
'expenses, limitations of much current CAT administration software, unfamiliarity of some test-takers with computer equipment, apparent inequities '.
'of different test-takers taking different tests, and difficulties of administering certain types of test in CAT format.  Description was taken from '.
'John Michael Linacre, Ph.D. MESA Psychometric Laboratory - University of Chicago.  MESA Memorandum No. 69';
$string['pluginadministration'] = 'Computer-Adaptive Testing';
$string['pluginname'] = 'Computer-Adaptive Testing';
$string['nonewmodules'] = 'No Computer-Adaptive instances found';
$string['adaptivequizname'] = 'Name';
$string['adaptivequizname_help'] = 'Enter the name of the Computer-Adaptive quiz instance';
$string['adaptivequiz:addinstance'] = 'Add a new adaptive quiz';
$string['adaptivequiz:viewreport'] = 'View adaptive quiz reports';
$string['adaptivequiz:reviewattempts'] = 'Review adaptive quiz submittions';
$string['adaptivequiz:attempt'] = 'Attempt adaptive quiz';
$string['attemptsallowed'] = 'Attempts allowed';
$string['attemptsallowed_help'] = 'The number of times a student may attempt this activity';
$string['requirepassword'] = 'Required password';
$string['requirepassword_help'] = 'Students are required to enter a password before beginning their attempt';
$string['browsersecurity'] = 'Browser security';
$string['browsersecurity_help'] = 'If "Full screen pop-up with some JavaScript security" is selected,

* The quiz will only start if the student has a JavaScript-enabled web-browser
* The quiz appears in a full screen popup window that covers all the other windows and has no navigation controls
* Students are prevented, as far as is possible, from using facilities like copy and paste';
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
$string['standarderror'] = 'Standard error';
$string['standarderror_help'] = 'Standard error is the amount of error allowed in the adaptive calculation before stopping the user\'s attempt';
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