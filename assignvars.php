<?php 

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/guidedquiz/guidedquiz_assignvars_form.php');
require_once($CFG->dirroot.'/mod/guidedquiz/locallib.php');

$title = get_string('assignvars', 'guidedquiz');
print_header_simple($title, $title);

$quizid = required_param('quizid', PARAM_INT);
$questionid = required_param('questionid', PARAM_INT);

// Arguments
$args = guidedquiz_get_question_guidedquiz_args($questionid);
if (!$args) {
	print_error('errornoargs', 'guidedquiz');
}

// Getting stored data
foreach ($args as $key => $arg) {
	if ($vararg = get_record('guidedquiz_var_arg', 'quizid', $quizid, 'programmedrespargid', $arg->id)) {
	    $toform->{'arg_'.$arg->id} = $vararg->guidedquizvarid;
	}
}

// Guidedquiz vars
$guidedquizvars = get_records('guidedquiz_var', 'quizid', $quizid);
if (!$guidedquizvars) {
	print_error('errornoquizvars', 'guidedquiz');
}

// Preprocess quiz vars -> options
foreach ($guidedquizvars as $guidedquizvar) {
	$options[$guidedquizvar->id] = $guidedquizvar->varname;
}

$toform->quizid = $quizid;
$toform->questionid = $questionid;

$url = $CFG->wwwroot.'/mod/guidedquiz/assignvars.php';
$customdata['guidedquizvars'] = $options;
$customdata['args'] = $args;
$form = new guidedquiz_assignvars_form($url, $customdata);

// Cancelled?
if ($form->is_cancelled()) {
	echo '<script type="text/javascript">window.close();</script>';
}

// Submitted
if ($values = $form->get_data()) {

	$obj->quizid = $quizid;
	
	// Inserting new ones
	foreach ($values as $key => $value) {
		if (substr($key, 0, 4) == 'arg_') {
			
			$argdata = explode('_', $key);
			
			// Deleting old values
            delete_records('guidedquiz_var_arg', 'quizid', $quizid, 'programmedrespargid', $argdata[1]);
            
            $obj->guidedquizvarid = $value;
            $obj->programmedrespargid = $argdata[1];
            
            if (!$obj->id = insert_record('guidedquiz_var_arg', $obj)) {
            	print_error('errordb', 'qtype_programmedresp');
            }
		}
	}
	echo '<script type="text/javascript">window.close();</script>';
}

$form->set_data($toform);
$form->display();

print_footer();
