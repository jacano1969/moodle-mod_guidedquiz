##
# Moodle - Modules - Guided quiz
#
# Core quiz module modified to display questions sequentially
#
# @package mod
# @subpackage guidedquiz
# @copyright 2011 David Monllaó
# @license http://www.gnu.org/licenses/gpl-2.0.txt
##

Quiz module modification to display questions sequentially, there is a number of attempts and an  
attempt penalization for each question instance. Users can only pass to the next question when they 
finds the correct answer, skip the question (max penalization applied) or reach the maximum number 
of attempts per question.


REQUIREMENTS
* Compatible with Moodle 1.9.x releases
* Users with Javascript/AJAX support enabled


PROGRAMMED RESPONSES QUESTION TYPE LINKAGE
There is a linkage between this module and the programmed responses question type, this module 
can define "global" variables and they can be referenced from the function arguments of the 
programmed responses question instances of the quiz.  

The variables can be added to the quiz text following the next format: {$varname} the variables 
names only accepts alphanumeric characters, each variable has four attributes, maximum value, 
minimum value, increment and the number of values, to define both scalar and vectorial variables. 

INSTALL
Follow the usual installation instructions


CREDITS
Tool designed by Josep Maria Mateo, Carme Olivé and Dolors Puigjaner, members of DEQ 
<http://www.etseq.urv.es/DEQ/> and DEIM <http://deim.urv.cat> departments of the 
Universitat Rovira i Virgili.

Consultoria Mosaic <http://www.consultoriamosaic.net>


CORE QUIZ CHANGES LOG

backuplib.php
- New fields added
- New question instances fields added
- Vars table added
- Arguments table added
- Vars assigned to questions arguments

restorelib.php
- New fields added
- New question instances fields added
- Vars table added
- Arguments table added

mod_form.php
- Added programmedresp dependencies
- Added quiz question
- Added vars section
- Shuffle questions always off
- Just one question per page
- No adaptive mode
- New field viewcorrectresponses
- New field show previous questions

lib.php
- Added guidedquiz_vars inserting / edition
- Added guidedquiz extra tables data deletion

attempt.php
- Display the question text and replace vars for random values
- Hide navigation pages
- Change submit buttons
- Change data_submitted() process
- The event state should always be QUESTION_EVENTCLOSE
- Respect show previous questions setting value
- Respect the show correct values setting value
- Allow users to pass to the next question (question value overwrite by a '') it depends on the 
show previous questions and show correct values values to display the correct response after 
answering
- Take into account the spent nattempts
- Take into account the penalty
- Check the page the user tries to access
- Hide quiz question when asking for password
- Ensure there is a $actions[$lastquestionid] 

db/install.xml
- New tables

edit.php
- New guidedquiz columns

editlib.php
- Added the button to assign guided quiz vars to question function arguments
- Guided quiz vars assign to the programmedresp function arguments also deleted 
- New guidedquiz columns
- Hide programmedresp questions preview button 

assignvars.php
- New file

guidedquiz_assignvars_form.php
- New file

locallib.php
- Added get_question_args() function
- Skip the shufflequestions requirement to apply the questionsperpage value
- Showing already answered questions (guidedquiz_questions_on_page)
- Changing preview questions link
- Added a function to manage the remaining attempts of a question inside a quiz attempt

review.php
- Page links hidden
- Redirect to the last page to display all the questions on the same page
- Show correct responses
- Add the guided quiz intro 

view.php
- Don't show the quiz main question until the attempt begins
