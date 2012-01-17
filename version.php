<?php // $Id: version.php,v 1.128.2.4 2009/01/14 07:03:14 tjhunt Exp $

////////////////////////////////////////////////////////////////////////////////
//  Code fragment to define the version of quiz
//  This fragment is called by moodle_needs_upgrading() and /admin/index.php
////////////////////////////////////////////////////////////////////////////////

$module->version  = 2012011700;   // The (date) version of this module
$module->requires = 2007101509;   // Requires this Moodle version
$module->cron     = 0;            // How often should cron check this module (seconds)?

$module->release = '1.4(Build: 2012011700)';

// To avoid 1.9 Notice
if (!defined('MATURITY_RC')) {
    define('MATURITY_RC', 150);
}

// To avoid the M&P warning (yes, sad but true http://www.youtube.com/watch?v=l8BRbM52gpc)
$module->maturity = MATURITY_RC;

// To avoid M&P warnings
$plugin->version = $module->version;
$plugin->release = $module->release;
$plugin->requires = $module->requires;
$plugin->maturity = $module->maturity;
