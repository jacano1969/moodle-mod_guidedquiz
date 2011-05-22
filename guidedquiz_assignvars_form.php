<?php 

require_once ($CFG->libdir.'/formslib.php');

class guidedquiz_assignvars_form extends moodleform {

	function definition() {
		
		$mform = & $this->_form;
		
		$mform->addElement('header', 'assignvars', get_string('assignvars', 'guidedquiz'));
		
		foreach ($this->_customdata['args'] as $argid => $arg) {
			$functionparams = programmedresp_unserialize($arg->params);
			$mform->addElement('select', 'arg_'.$argid, $functionparams[$arg->argkey]->description, $this->_customdata['guidedquizvars']);
		}
		
		$mform->addElement('hidden', 'quizid');
		$mform->addElement('hidden', 'questionid');
		
		$this->add_action_buttons();
	}
}
