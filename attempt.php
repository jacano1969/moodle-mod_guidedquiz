<?php  // $Id: attempt.php,v 1.131.2.20 2010/08/06 11:41:48 tjhunt Exp $
/**
 * This page prints a particular instance of quiz
 *
 * @author Martin Dougiamas and many others. This has recently been completely
 *         rewritten by Alex Smith, Julian Sedding and Gustav Delius as part of
 *         the Serving Mathematics project
 *         {@link http://maths.york.ac.uk/serving_maths}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package guidedquiz
 */

require_once("../../config.php");
require_once("locallib.php");

// remember the current time as the time any responses were submitted
// (so as to make sure students don't get penalized for slow processing on this page)
$timestamp = time();

// Get submitted parameters.
$id = optional_param('id', 0, PARAM_INT);               // Course Module ID
$q = optional_param('q', 0, PARAM_INT);                 // or quiz ID
$page = optional_param('page', 0, PARAM_INT);
$questionids = optional_param('questionids', '');
$finishattempt = optional_param('finishattempt', 0, PARAM_BOOL);
$timeup = optional_param('timeup', 0, PARAM_BOOL); // True if form was submitted by timer.
$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Teacher has requested new preview

if ($id) {
	if (! $cm = get_coursemodule_from_id('guidedquiz', $id)) {
		error("There is no coursemodule with id $id");
	}
	if (! $course = get_record("course", "id", $cm->course)) {
		error("Course is misconfigured");
	}
	if (! $quiz = get_record("guidedquiz", "id", $cm->instance)) {
		error("The quiz with id $cm->instance corresponding to this coursemodule $id is missing");
	}
} else {
	if (! $quiz = get_record("guidedquiz", "id", $q)) {
		error("There is no quiz with id $q");
	}
	if (! $course = get_record("course", "id", $quiz->course)) {
		error("The course with id $quiz->course that the quiz with id $q belongs to is missing");
	}
	if (! $cm = get_coursemodule_from_instance("guidedquiz", $quiz->id, $course->id)) {
		error("The course module for the quiz with id $q is missing");
	}
}

// We treat automatically closed attempts just like normally closed attempts
if ($timeup) {
	$finishattempt = 1;
}

require_login($course->id, false, $cm);

$coursecontext = get_context_instance(CONTEXT_COURSE, $cm->course); // course context
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$ispreviewing = has_capability('mod/guidedquiz:preview', $context);

// if no questions have been set up yet redirect to edit.php
if (!$quiz->questions and has_capability('mod/guidedquiz:manage', $context)) {
	redirect($CFG->wwwroot . '/mod/guidedquiz/edit.php?cmid=' . $cm->id);
}

if (!$ispreviewing) {
	require_capability('mod/guidedquiz:attempt', $context);
}

/// Get number for the next or unfinished attempt
if(!$attemptnumber = (int)get_field_sql('SELECT MAX(attempt)+1 FROM ' .
            "{$CFG->prefix}guidedquiz_attempts WHERE quiz = '{$quiz->id}' AND " .
            "userid = '{$USER->id}' AND timefinish > 0 AND preview != 1")) {
$attemptnumber = 1;
            }

            $strattemptnum = get_string('attempt', 'quiz', $attemptnumber);
            $strquizzes = get_string("modulenameplural", "quiz");
            $popup = $quiz->popup && !$ispreviewing; // Controls whether this is shown in a javascript-protected window or with a safe browser.

            /// We intentionally do not check open and close times here. Instead we do it lower down.
            /// This is to deal with what happens when someone submits close to the exact moment when the quiz closes.

            /// Check number of attempts
            $numberofpreviousattempts = count_records_select('guidedquiz_attempts', "quiz = '{$quiz->id}' AND " .
        "userid = '{$USER->id}' AND timefinish > 0 AND preview != 1");
            if (!empty($quiz->attempts) and $numberofpreviousattempts >= $quiz->attempts) {
            	print_error('nomoreattempts', 'quiz', "view.php?id={$cm->id}");
            }

            /// Check safe browser
            if (!$ispreviewing && $quiz->popup == 2 && !guidedquiz_check_safe_browser()) {
            	print_error('safebrowsererror', 'quiz', "view.php?id={$cm->id}");
            }

            /// Check subnet access
            if (!$ispreviewing && !empty($quiz->subnet) && !address_in_subnet(getremoteaddr(), $quiz->subnet)) {
            	print_error("subneterror", "quiz", "view.php?id=$cm->id");
            }

            /// Check password access
            if ($ispreviewing && $forcenew) {
            	unset($SESSION->passwordcheckedquizzes[$quiz->id]);
            }

            if (!empty($quiz->password) and empty($SESSION->passwordcheckedquizzes[$quiz->id])) {
            	$enteredpassword = optional_param('quizpassword', '', PARAM_RAW);
            	if (optional_param('cancelpassword', false)) {
            		// User clicked cancel in the password form.
            		redirect($CFG->wwwroot . '/mod/guidedquiz/view.php?q=' . $quiz->id);
            	} else if (strcmp($quiz->password, $enteredpassword) === 0) {
            		// User entered the correct password.
            		$SESSION->passwordcheckedquizzes[$quiz->id] = true;
            	} else {
            		// User entered the wrong password, or has not entered one yet.
            		$url = $CFG->wwwroot . '/mod/guidedquiz/attempt.php?q=' . $quiz->id;

            		print_header('', '', '', 'quizpassword');

            		// guidedquiz mod
//            		if (trim(strip_tags($quiz->intro))) {
//            			$formatoptions->noclean = true;
//            			print_box(format_text($quiz->intro, FORMAT_MOODLE, $formatoptions), 'generalbox', 'intro');
//            		}
            		// guidedquiz mod end
            		print_box_start('generalbox', 'passwordbox');
            		if (!empty($enteredpassword)) {
            			echo '<p class="notifyproblem">', get_string('passworderror', 'quiz'), '</p>';
            		}
            		?>
<p><?php print_string('requirepasswordmessage', 'quiz'); ?></p>
<form id="passwordform" method="post" action="<?php echo $url; ?>"
    onclick="this.autocomplete='off'">
<div><label for="quizpassword"><?php print_string('password'); ?></label>
<input name="quizpassword" id="quizpassword" type="password" value="" />
<input type="submit" value="<?php print_string('ok'); ?>" /> <input
    type="submit" name="cancelpassword"
    value="<?php print_string('cancel'); ?>" /></div>
</form>
            		<?php
            		print_box_end();
            		print_footer('empty');
            		exit;
            	}
            }

            if (!empty($quiz->delay1) or !empty($quiz->delay2)) {
            	//quiz enforced time delay
            	if ($attempts = guidedquiz_get_user_attempts($quiz->id, $USER->id)) {
            		$numattempts = count($attempts);
            	} else {
            		$numattempts = 0;
            	}
            	$timenow = time();
            	$lastattempt_obj = get_record_select('guidedquiz_attempts',
                "quiz = $quiz->id AND attempt = $numattempts AND userid = $USER->id",
                'timefinish, timestart');
            	if ($lastattempt_obj) {
            		$lastattempt = $lastattempt_obj->timefinish;
            		if ($quiz->timelimit > 0) {
            			$lastattempt = min($lastattempt, $lastattempt_obj->timestart + $quiz->timelimit*60);
            		}
            	}
            	if ($numattempts == 1 && !empty($quiz->delay1)) {
            		if ($timenow - $quiz->delay1 < $lastattempt) {
            			print_error('timedelay', 'quiz', 'view.php?q='.$quiz->id);
            		}
            	} else if($numattempts > 1 && !empty($quiz->delay2)) {
            		if ($timenow - $quiz->delay2 < $lastattempt) {
            			print_error('timedelay', 'quiz', 'view.php?q='.$quiz->id);
            		}
            	}
            }

            /// Load attempt or create a new attempt if there is no unfinished one

            if ($ispreviewing and $forcenew) { // teacher wants a new preview
            	// so we set a finish time on the current attempt (if any).
            	// It will then automatically be deleted below
            	set_field('guidedquiz_attempts', 'timefinish', $timestamp, 'quiz', $quiz->id, 'userid', $USER->id);
            }

            $attempt = guidedquiz_get_user_attempt_unfinished($quiz->id, $USER->id);

            $newattempt = false;
            if (!$attempt) {
            	// Delete any previous preview attempts belonging to this user.
            	if ($oldattempts = get_records_select('guidedquiz_attempts', "quiz = '$quiz->id'
                AND userid = '$USER->id' AND preview = 1")) {
            	foreach ($oldattempts as $oldattempt) {
            		guidedquiz_delete_attempt($oldattempt, $quiz);
            	}
                }
                $newattempt = true;
                // Start a new attempt and initialize the question sessions
                $attempt = guidedquiz_create_attempt($quiz, $attemptnumber);
                // If this is an attempt by a teacher mark it as a preview
                if ($ispreviewing) {
                	$attempt->preview = 1;
                }
                // Save the attempt
                if (!$attempt->id = insert_record('guidedquiz_attempts', $attempt)) {
                	error('Could not create new attempt');
                }
                // make log entries
                if ($ispreviewing) {
                	add_to_log($course->id, 'guidedquiz', 'preview',
                           "attempt.php?id=$cm->id",
                           "$quiz->id", $cm->id);
                } else {
                	add_to_log($course->id, 'guidedquiz', 'attempt',
                           "review.php?attempt=$attempt->id",
                           "$quiz->id", $cm->id);
                }
            } else {
            	add_to_log($course->id, 'guidedquiz', 'continue attemp', // this action used to be called 'continue attempt' but the database field has only 15 characters
                       'review.php?attempt=' . $attempt->id, $quiz->id, $cm->id);
            }
            if (!$attempt->timestart) { // shouldn't really happen, just for robustness
            	debugging('timestart was not set for this attempt. That should be impossible.', DEBUG_DEVELOPER);
            	$attempt->timestart = $timestamp - 1;
            }

            /// Load all the questions and states needed by this script

            // list of questions needed by page
            $pagelist = guidedquiz_questions_on_page($attempt->layout, $page);

            if ($newattempt) {
            	$questionlist = guidedquiz_questions_in_quiz($attempt->layout);
            } else {
            	$questionlist = $pagelist;
            }

            // add all questions that are on the submitted form
            if ($questionids) {
            	$questionlist .= ','.$questionids;
            }

            if (!$questionlist) {
            	print_error('noquestionsfound', 'quiz', 'view.php?q='.$quiz->id);
            }

            // guidedquiz mod
            // Getting the actual question
            $questionidsarray = explode(',', $pagelist);
            $lastquestionindex = count($questionidsarray) - 1;
            $lastquestionid = $questionidsarray[$lastquestionindex];
            // guidedquiz mod end
            
            // guidedquiz mod
            $sql = "SELECT q.*, i.grade AS maxgrade, i.nattempts, i.penalty as questioninstancepenalty, i.id AS instance".
           "  FROM {$CFG->prefix}question q,".
           "       {$CFG->prefix}guidedquiz_question_instance i".
           " WHERE i.quiz = '$quiz->id' AND q.id = i.question".
           "   AND q.id IN ($questionlist)";
            // guidedquiz mod end 

            // Load the questions
            if (!$questions = get_records_sql($sql)) {
            	print_error('noquestionsfound', 'quiz', 'view.php?q='.$quiz->id);
            }

            // Load the question type specific information
            if (!get_question_options($questions)) {
            	error('Could not load question options');
            }

            // If the new attempt is to be based on a previous attempt find its id
            $lastattemptid = false;
            if ($newattempt and $attempt->attempt > 1 and $quiz->attemptonlast and !$attempt->preview) {
            	// Find the previous attempt
            	if (!$lastattemptid = get_field('guidedquiz_attempts', 'uniqueid', 'quiz', $attempt->quiz, 'userid', $attempt->userid, 'attempt', $attempt->attempt-1)) {
            		error('Could not find previous attempt to build on');
            	}
            }

            // Restore the question sessions to their most recent states
            // creating new sessions where required
            if (!$states = get_question_states($questions, $quiz, $attempt, $lastattemptid)) {
            	error('Could not restore question sessions');
            }

            // Save all the newly created states
            if ($newattempt) {
            	foreach ($questions as $i => $question) {

            		// guidedquiz mod
            		// Taking into account the question attempt penalty
            		if ($quiz->penaltyscheme) {
            			$states[$i]->penalty = $questions[$i]->penalty + $questions[$i]->questioninstancepenalty;
            		}
            		// guidedquiz mod end
                    
                    save_question_session($questions[$i], $states[$i]);
            		
                    // guidedquiz mod
                    $remainingattempts = guidedquiz_update_question_remaining_attempts($attempt->uniqueid, $questions[$i]->id, $questions[$i]->nattempts);
            		$questions[$i]->remainingattempts = $remainingattempts->remainingattempts;
            		// guidedquiz mod end
            	}
            }

            // guidedquiz mod
            // Getting remaining attempts info
            if ($questions) {
            	foreach ($questions as $i => $question) {
            		if (empty($questions[$i]->remainingattempts)) {
            		    $questions[$i]->remainingattempts = get_field('guidedquiz_remaining_attempt', 'remainingattempts', 'attemptid', $attempt->uniqueid, 'question', $question->id);
            		}
            	}
            }
            // guidedquiz mod end

            /// Process form data /////////////////////////////////////////////////

            if ($responses = data_submitted() and empty($responses->quizpassword)) {

            	// set the default event. This can be overruled by individual buttons.
            	$event = (array_key_exists('markall', $responses)) ? QUESTION_EVENTSUBMIT :
            	($finishattempt ? QUESTION_EVENTCLOSE : QUESTION_EVENTSAVE);

            	// guidedquiz mod
            	// It's only the default event for question_extract_responses
            	// If someone tries you access the next questions without answer all the previous the 
            	// all the previous questions will be graded with '' response 
                $event = QUESTION_EVENTCLOSE;
                // guidedquiz mod end

            	// Unset any variables we know are not responses
            	unset($responses->id);
            	unset($responses->q);
            	unset($responses->oldpage);
            	unset($responses->newpage);
            	unset($responses->review);
            	unset($responses->questionids);
            	unset($responses->saveattempt); // responses get saved anway
            	unset($responses->finishattempt); // same as $finishattempt
            	unset($responses->markall);
            	unset($responses->forcenewattempt);

            	// extract responses
            	// $actions is an array indexed by the questions ids
            	$actions = question_extract_responses($questions, $responses, $event);

            	// guidedquiz mod
            	// Ensure there is a $actions[$lastquestionid] (multichoice)
            	if (empty($actions) && empty($actions[$lastquestionid])) {
            		$actions[$lastquestionid]->responses[0] = '';
            	}
                // guidedquiz mod end 
                
            	// Process each question in turn

            	$questionidarray = explode(',', $questionids);
            	
                // guidedquiz mod
                
            	// Is the question response ok? the question grading is repeated on question_process_responses
            	// Cloning instances to not interfer with the usual process
            	$tmpquestion = clone $questions[$lastquestionid];
            	$tmpstate = clone $states[$lastquestionid];
                $tmpstate->responses = $actions[$lastquestionid]->responses;
            	$QTYPES[$questions[$lastquestionid]->qtype]->grade_responses($tmpquestion, $tmpstate, $quiz);

                // If it's marked as nextquestionwithoutanswer:
                // - Leave the response blank
                // - Close the event 
                // - Go to next question
                if (optional_param('nextquestionwithoutanswer', false, PARAM_RAW)) {

                    if (!empty($actions[$lastquestionid]) && $actions[$lastquestionid]->responses) {
                        foreach ($actions[$lastquestionid]->responses as $key => $response) {
                            $actions[$lastquestionid]->responses[$key] = '';
                        }
                        $actions[$lastquestionid]->event = QUESTION_EVENTCLOSE;
                        $remainingattempts = 0;
                        $redirectnextpage = true;
                    }

                // If the question response is OK go to the next question
                } else if ($tmpstate->raw_grade == $tmpquestion->maxgrade) {
                	$actions[$lastquestionid]->event = QUESTION_EVENTCLOSE;
                	$remainingattempts = 0;
                    $redirectnextpage = true;

                // If this is the last attempt close question and go to the next question
                } else if ($questions[$lastquestionid]->remainingattempts <= 1) {
                	$actions[$lastquestionid]->event = QUESTION_EVENTCLOSE;
                	$remainingattempts = 0;
                	$redirectnextpage = true;

                // If is just another attempt update the remaining attempts
                } else {
                	$actions[$lastquestionid]->event = QUESTION_EVENTSUBMIT;
                	$remainingattempts = $questions[$lastquestionid]->remainingattempts - 1; // One less
                }

                // Update the remaining attempts on DB
                $questionremainingattempt = guidedquiz_update_question_remaining_attempts($attempt->uniqueid, $lastquestionid, $remainingattempts);
                $questions[$lastquestionid]->remainingattempts = $questionremainingattempt->remainingattempts;
                
                // Apply the penalty
                $questions[$lastquestionid]->penalty = $questions[$lastquestionid]->penalty + $questions[$lastquestionid]->questioninstancepenalty;
                $states[$lastquestionid]->penalty = $questions[$lastquestionid]->penalty;
                // guidedquiz mod end
                
            	$success = true;
            	foreach($questionidarray as $i) {
            		if (!isset($actions[$i])) {
            			$actions[$i]->responses = array('' => '');
            			$actions[$i]->event = QUESTION_EVENTOPEN;
            		}

            		$actions[$i]->timestamp = $timestamp;
            		if (question_process_responses($questions[$i], $states[$i], $actions[$i], $quiz, $attempt)) {
            			save_question_session($questions[$i], $states[$i]);
            		} else {
            			$success = false;
            		}
            	}

            	if (!$success) {
            		$pagebit = '';
            		if ($page) {
            			$pagebit = '&amp;page=' . $page;
            		}
            		print_error('errorprocessingresponses', 'question',
            		$CFG->wwwroot . '/mod/guidedquiz/attempt.php?q=' . $quiz->id . $pagebit);
            	}

            	$attempt->timemodified = $timestamp;

            	// We have now finished processing form data
            }
            
            // One page per question
            $numpages = guidedquiz_number_of_pages($attempt->layout);
            
            // If it's the last question redirect to finish the attempt
            if (!empty($redirectnextpage) && ($page + 1) == $numpages) {
                $finishattempt = true;
            }
            
            /// Finish attempt if requested
            if ($finishattempt) {

            	// Set the attempt to be finished
            	$attempt->timefinish = $timestamp;

            	// load all the questions
            	$closequestionlist = guidedquiz_questions_in_quiz($attempt->layout);
            	$sql = "SELECT q.*, i.grade AS maxgrade, i.id AS instance".
               "  FROM {$CFG->prefix}question q,".
               "       {$CFG->prefix}guidedquiz_question_instance i".
               " WHERE i.quiz = '$quiz->id' AND q.id = i.question".
               "   AND q.id IN ($closequestionlist)";
            	if (!$closequestions = get_records_sql($sql)) {
            		error('Questions missing');
            	}

            	// Load the question type specific information
            	if (!get_question_options($closequestions)) {
            		error('Could not load question options');
            	}

            	// Restore the question sessions
            	if (!$closestates = get_question_states($closequestions, $quiz, $attempt)) {
            		error('Could not restore question sessions');
            	}

            	$success = true;
            	foreach($closequestions as $key => $question) {
            		$action->event = QUESTION_EVENTCLOSE;
            		$action->responses = $closestates[$key]->responses;
            		$action->timestamp = $closestates[$key]->timestamp;

            		if (question_process_responses($question, $closestates[$key], $action, $quiz, $attempt)) {
            			save_question_session($question, $closestates[$key]);
            		} else {
            			$success = false;
            		}
            	}

            	if (!$success) {
            		$pagebit = '';
            		if ($page) {
            			$pagebit = '&amp;page=' . $page;
            		}
            		print_error('errorprocessingresponses', 'question',
            		$CFG->wwwroot . '/mod/guidedquiz/attempt.php?q=' . $quiz->id . $pagebit);
            	}

            	add_to_log($course->id, 'guidedquiz', 'close attempt', 'review.php?attempt=' . $attempt->id, $quiz->id, $cm->id);
            }

            /// Update the quiz attempt and the overall grade for the quiz
            if ($responses || $finishattempt) {
            	if (!update_record('guidedquiz_attempts', $attempt)) {
            		error('Failed to save the current quiz attempt!');
            	}
            	if (($attempt->attempt > 1 || $attempt->timefinish > 0) and !$attempt->preview) {
            		guidedquiz_save_best_grade($quiz);
            	}
            }

            /// Send emails to those who have the capability set
            if ($finishattempt && !$attempt->preview) {
            	guidedquiz_send_notification_emails($course, $quiz, $attempt, $context, $cm);
            }

            // guidedquiz mod
            // Redirecting to the next page if it's not the end and this question has been closed and graded
            if (!$finishattempt && !empty($redirectnextpage)) {
                redirect($CFG->wwwroot.'/mod/guidedquiz/attempt.php?q='.$quiz->id.'&amp;page='.($page + 1), get_string('nextquestion', 'guidedquiz'), 2);
            }
            // guidedquiz mod end
            
            if ($finishattempt) {
            	if (!empty($SESSION->passwordcheckedquizzes[$quiz->id])) {
            		unset($SESSION->passwordcheckedquizzes[$quiz->id]);
            	}
            	redirect($CFG->wwwroot . '/mod/guidedquiz/review.php?attempt='.$attempt->id.'&page='.($numpages - 1), 0);
            }

            // Now is the right time to check the open and close times.
            if (!$ispreviewing && ($timestamp < $quiz->timeopen || ($quiz->timeclose && $timestamp > $quiz->timeclose))) {
            	print_error('notavailable', 'quiz', "view.php?id={$cm->id}");
            }

            // guidedquiz mod
            
            // Trying to access a previous question ehhh
            if ($states[$lastquestionid]->event == QUESTION_EVENTCLOSEANDGRADE || 
                $states[$lastquestionid]->event == QUESTION_EVENTCLOSE) {
                	redirect($CFG->wwwroot.'/mod/guidedquiz/attempt.php?q='.$quiz->id.'&page='.($page + 1), get_string('previousquestionaccess', 'guidedquiz'), 2);
            }
            
            // Checking if the user has advanced to a next question
            foreach ($questionidsarray as $i => $questionid) {
            	if ($states[$questionid]->event != QUESTION_EVENTCLOSEANDGRADE &&
            	    $states[$questionid]->event != QUESTION_EVENTCLOSE && 
            	    $i < $lastquestionindex) {
            	    	redirect($CFG->wwwroot.'/mod/guidedquiz/attempt.php?q='.$quiz->id.'&page='.($page - 1), get_string('nextquestionaccess', 'guidedquiz'), 2);
            	}
            }
            // guidedquiz mod
            
            /// Print the quiz page ////////////////////////////////////////////////////////

            // Print the page header
            require_js($CFG->wwwroot . '/mod/quiz/quiz.js');
            $pagequestions = explode(',', $pagelist);
            $headtags = get_html_head_contributions($pagequestions, $questions, $states);
            if (!$ispreviewing && $quiz->popup) {
            	define('MESSAGE_WINDOW', true);  // This prevents the message window coming up
            	print_header($course->shortname.': '.format_string($quiz->name), '', '', '', $headtags, false, '', '', false, ' class="securewindow"');
            	if ($quiz->popup == 1) {
            		include('protect_js.php');
            	}
            } else {
            	$strupdatemodule = has_capability('moodle/course:manageactivities', $coursecontext)
            	? update_module_button($cm->id, $course->id, get_string('modulename', 'quiz'))
            	: "";
            	$navigation = build_navigation($strattemptnum, $cm);
            	print_header_simple(format_string($quiz->name), "", $navigation, "", $headtags, true, $strupdatemodule);
            }

            echo '<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>'; // for overlib

            // Print the quiz name heading and tabs for teacher, etc.
            if ($ispreviewing) {
            	$currenttab = 'preview';
            	include('tabs.php');

            	print_heading(get_string('previewquiz', 'quiz', format_string($quiz->name)));
            	unset($buttonoptions);
            	$buttonoptions['q'] = $quiz->id;
            	$buttonoptions['forcenew'] = true;
            	echo '<div class="controls">';
            	print_single_button($CFG->wwwroot.'/mod/guidedquiz/attempt.php', $buttonoptions, get_string('startagain', 'quiz'));
            	echo '</div>';
            	/// Notices about restrictions that would affect students.
            	if ($quiz->popup == 1) {
            		notify(get_string('popupnotice', 'quiz'));
            	} else if ($quiz->popup == 2) {
            		notify(get_string('safebrowsernotice', 'quiz'));
            	}
            	if ($timestamp < $quiz->timeopen || ($quiz->timeclose && $timestamp > $quiz->timeclose)) {
            		notify(get_string('notavailabletostudents', 'quiz'));
            	}
            	if ($quiz->subnet && !address_in_subnet(getremoteaddr(), $quiz->subnet)) {
            		notify(get_string('subnetnotice', 'quiz'));
            	}
            } else {
            	if ($quiz->attempts != 1) {
            		print_heading(format_string($quiz->name).' - '.$strattemptnum);
            	} else {
            		print_heading(format_string($quiz->name));
            	}
            }

            // guidedquiz mod

            // Print formulation
            $formatoptions->noclean = true;
            $questiontext = format_text($quiz->intro, FORMAT_MOODLE, $formatoptions);

            // Replacing vars for random values
            $vars = get_records('guidedquiz_var', 'quizid', $quiz->id);
            if ($vars) {
                foreach ($vars as $var) {
    
                    // If this attempt doesn't have yet a value
                    if (!$values = get_field('guidedquiz_val', 'varvalues', 'attemptid', $attempt->uniqueid, 'guidedquizvarid', $var->id)) {
    
                        // Add a new random value
                        $val->attemptid = $attempt->uniqueid;
                        $val->guidedquizvarid = $var->id;
                        $val->varvalues = programmedresp_serialize(programmedresp_get_random_value($var));
                        if (!insert_record('guidedquiz_val', $val)) {
                            print_error('errordb', 'qtype_programmedresp');
                        }
                        $values = $val->varvalues;
                    }
                    $values = programmedresp_unserialize($values);
    
                    $valuetodisplay = implode(', ', $values);
    
                    $questiontext = str_replace('{$'.$var->varname.'}', $valuetodisplay, $questiontext);
                }
            }
            print_box($questiontext, 'generalbox', 'intro');
            
            // Show the question number
            $qnumbers->this = $page + 1;
            $qnumbers->of = $numpages;
            print_box(get_string("questionnumberof", "guidedquiz", $qnumbers), 'generalbox', 'intro');
            // guidedquiz mod end

            // Start the form
            $quiz->thispageurl = $CFG->wwwroot . '/mod/guidedquiz/attempt.php?q=' . s($quiz->id) . '&amp;page=' . s($page);
            $quiz->cmid = $cm->id;
            echo '<form id="responseform" method="post" action="', $quiz->thispageurl . '" enctype="multipart/form-data"' .
            ' onkeypress="return check_enter(event);" accept-charset="utf-8">', "\n";
            echo '<script type="text/javascript">', "\n",
            'document.getElementById("responseform").setAttribute("autocomplete", "off")', "\n",
            "</script>\n";
            if ($quiz->timelimit > 0) {
            	// Make sure javascript is enabled for time limited quizzes
            	?>
<script type="text/javascript">
            // Do nothing, but you have to have a script tag before a noscript tag.
        </script>
<noscript>
<div><?php print_heading(get_string('noscript', 'quiz')); ?></div>
</noscript>
            	<?php
            }
            echo '<div>';

            /// Print the navigation panel if required
            // guidedquiz mod
            // No navigation
//            $numpages = guidedquiz_number_of_pages($attempt->layout);
//            if ($numpages > 1) {
//            	guidedquiz_print_navigation_panel($page, $numpages);
//            }
            // guidedquiz mod end

            /// Print all the questions
            $number = guidedquiz_first_questionnumber($attempt->layout, $pagelist);
            // guidedquiz mod
//            echo get_string()
            foreach ($pagequestions as $key => $i) {
                
                // Take into account showpreviousquestions
                if (!$quiz->viewpreviousquestions && ($page!= $key)) {
                	$number += $questions[$i]->length;
                    continue;
                }
            // guidedquiz mod end
            	$options = guidedquiz_get_renderoptions($quiz->review, $states[$i]);
            	// Print the question
            	print_question($questions[$i], $states[$i], $number, $quiz, $options);
                // guidedquiz mod
                // display correct response if it's not the latests question
                if ($quiz->showcorrectresponses && $page != $key) {
                	
                	$correctresponses = $QTYPES[$questions[$i]->qtype]->get_correct_responses($questions[$i], $states[$i]);
                	if ($correctresponses) {
                		
                		$htmlresponsecontent = '<strong>'.get_string('correctanswer', 'guidedquiz').'</strong>: ';
                		if ($questions[$i]->qtype == 'multichoice') {
                			
                			// Only one response
                			$singlecorrectresponse = reset($correctresponses);
                			$htmlresponsecontent .= $questions[$i]->options->answers[$singlecorrectresponse]->answer;
                		} else {
                			$htmlresponsecontent .= implode(', ', $correctresponses);
                		}
                		
                		print_box($htmlresponsecontent);
                	}
                }
                // guidedquiz mod end
            	save_question_session($questions[$i], $states[$i]);
            	$number += $questions[$i]->length;
            }
            
            /// Print the submit buttons
            $strconfirmattempt = addslashes(get_string("confirmclose", "quiz"));
            $onclick = "return confirm('$strconfirmattempt')";
            echo "<div class=\"submitbtns mdl-align\">\n";

            // guidedquiz mod
            if (($page + 1) < $numpages) {
                echo '<input type="submit" name="nextquestion" value="'.get_string('mark', 'quiz').'" 
                    onclick="javascript:navigate('.$page.');" class="submit btn"/>';
                echo '&nbsp;'.get_string('remainingattempts', 'guidedquiz').': '.$questions[$lastquestionid]->remainingattempts.'<br/><br/>';
                echo '<input type="submit" name="nextquestionwithoutanswer" value="'.get_string("nextquestionwithoutanswer", "guidedquiz").'" 
                    onclick="javascript:navigate('.$page.');" class="submit btn"/>';
            } else {
                echo '<input type="submit" name="nextquestion" value="'.get_string("sendandfinish", "guidedquiz").'" 
                    onclick="javascript:navigate('.$page.');" class="submit btn"/>';
                echo '&nbsp;'.get_string('remainingattempts', 'guidedquiz').': '.$questions[$lastquestionid]->remainingattempts.'<br/><br/>';
                echo '<input type="submit" name="nextquestionwithoutanswer" value="'.get_string("nextquestionwithoutanswer", "guidedquiz").'" 
                    onclick="javascript:navigate('.$page.');" class="submit btn"/>';
            }

            // To finish the attempt
            $strconfirmattempt = addslashes(get_string("confirmclose", "quiz"));
            $onclick = "return confirm('$strconfirmattempt')";
            echo "<input type=\"submit\" name=\"finishattempt\" value=\"".get_string("finishattempt", "quiz")."\" onclick=\"$onclick\" />\n";
            // guidedquiz mod end

            echo "</div>";

            // Print the navigation panel if required
            // guidedquiz mod
//            if ($numpages > 1) {
//            	guidedquiz_print_navigation_panel($page, $numpages);
//    }
                // guidedquiz mod end

    // Finish the form
    echo '</div>';
    echo '<input type="hidden" name="timeup" id="timeup" value="0" />';

    // Add a hidden field with questionids. Do this at the end of the form, so 
    // if you navigate before the form has finished loading, it does not wipe all
    // the student's answers.
    echo '<input type="hidden" name="questionids" value="'.$pagelist."\" />\n";

    echo "</form>\n";

    // If the quiz has a time limit, or if we are close to the close time, include a floating timer.
    $showtimer = false;
    $timerstartvalue = 999999999999;
    if ($quiz->timeclose) {
        $timerstartvalue = min($timerstartvalue, $quiz->timeclose - time());
        $showtimer = $timerstartvalue < 60*60; // Show the timer if we are less than 60 mins from the deadline.
    }
    if ($quiz->timelimit > 0 && !has_capability('mod/guidedquiz:ignoretimelimits', $context, NULL, false)) {
        $timerstartvalue = min($timerstartvalue, $attempt->timestart + $quiz->timelimit*60- time());
        $showtimer = true;
    }
    if ($showtimer && (!$ispreviewing || $timerstartvalue > 0)) {
        $timerstartvalue = max($timerstartvalue, 1); // Make sure it starts just above zero.
        require('jstimer.php');
    }

    // Finish the page
    if (empty($popup)) {
        print_footer($course);
    }
?>
