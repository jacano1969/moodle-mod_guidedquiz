<?php // $Id: pagelib.php,v 1.14.4.1 2007/11/02 16:19:58 tjhunt Exp $

require_once($CFG->libdir.'/pagelib.php');
require_once($CFG->dirroot.'/course/lib.php'); // needed for some blocks

define('PAGE_GUIDEDQUIZ_VIEW',   'mod-guidedquiz-view');

page_map_class(PAGE_GUIDEDQUIZ_VIEW, 'page_guidedquiz');

$DEFINEDPAGES = array(PAGE_GUIDEDQUIZ_VIEW);

/**
 * Class that models the behavior of a quiz
 *
 * @author Jon Papaioannou
 * @package pages
 */

class page_guidedquiz extends page_generic_activity {

    function init_quick($data) {
        if(empty($data->pageid)) {
            error('Cannot quickly initialize page: empty course id');
        }
        $this->activityname = 'guidedquiz';
        parent::init_quick($data);
    }
  
    function get_type() {
        return PAGE_GUIDEDQUIZ_VIEW;
    }
}

?>
