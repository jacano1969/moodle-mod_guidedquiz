<?php  // $Id: grade.php,v 1.1.2.1 2007/11/02 16:19:56 tjhunt Exp $

    require_once("../../config.php");

    $id   = required_param('id', PARAM_INT);          // Course module ID

    if (! $cm = get_coursemodule_from_id('guidedquiz', $id)) {
        error('Course Module ID was incorrect');
    }

    if (! $quiz = get_record('guidedquiz', 'id', $cm->instance)) {
        error('quiz ID was incorrect');
    }

    if (! $course = get_record('course', 'id', $quiz->course)) {
        error('Course is misconfigured');
    }

    require_login($course->id, false, $cm);

    if (has_capability('mod/guidedquiz:viewreports', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        redirect('report.php?id='.$cm->id);
    } else {
        redirect('view.php?id='.$cm->id);
    }

?>
