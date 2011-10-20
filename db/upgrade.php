<?php  // $Id: upgrade.php,v 1.8.2.3 2008/05/01 20:55:32 skodak Exp $


function xmldb_guidedquiz_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($oldversion < 2011062600) {
    	
        /// Define field type to be added to guidedquiz_var_arg
        $table = new XMLDBTable('guidedquiz_var_arg');
        $field = new XMLDBField('type');
        $field->setAttributes(XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, XMLDB_ENUM, array('var', 'concatvar'), 'var', 'quizid');

        /// Launch add field type
        $result = $result && add_field($table, $field);
        
        /// Rename field guidedquizvarid on table guidedquiz_var_arg to instanceid
        $table = new XMLDBTable('guidedquiz_var_arg');
        $field = new XMLDBField('guidedquizvarid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'type');

        /// Launch rename field guidedquizvarid
        $result = $result && rename_field($table, $field, 'instanceid');
    }
    
    if ($result && $oldversion < 2011102001) {
    	
    	// Drop old key
    	$table = new XMLDBTable('guidedquiz_var_arg');
        $key = new XMLDBKey('programmedrespargid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('programmedrespargid'), 'question_programmedresp_arg', array('id'));
        $result = $result && drop_key($table, $key);
        
        // Add new key
    	$table = new XMLDBTable('guidedquiz_var_arg');
        $key = new XMLDBKey('programmedrespargid');
        $key->setAttributes(XMLDB_KEY_FOREIGN, array('programmedrespargid'), 'qtype_programmedresp_arg', array('id'));
        $result = $result && add_key($table, $key);
    }
    
    return $result;
}

?>
