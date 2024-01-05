<?php
/**
 * fdvegan_script_init_load.php
 *
 * Implementation of initial load functionality for module fdvegan.
 * This file is now deprecated; see fdvegan.install instead.
 *
 * PHP version 5.6
 *
 * @category   Install
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 * @deprecated version 0.5
 * @see        fdvegan.install
 */


/*
 * This script is no longer necessary since /fdvegan-init-load
 * can be run after installing this module, or at any time to refresh
 * our database.
 * But, if desired, this script can be run manually on the command-line via:  
 *   cd ...\drupal\sites\all\modules\custom\fdvegan; php fdvegan_script_init_load.php
 */


// define Drupal7 default settings
$cmd = 'index.php';
$_SERVER['HTTP_HOST']       = 'default';
if (substr(php_uname(), 0, 7) == "Windows") {
    $_SERVER['PHP_SELF']        = '\\' . basename(__FILE__);
} else {
    $_SERVER['PHP_SELF']        = '/' . basename(__FILE__);
}
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['SERVER_SOFTWARE'] = NULL;
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['QUERY_STRING']    = '';
$_SERVER['PHP_SELF']        = DIRECTORY_SEPARATOR;
$_SERVER['REQUEST_URI']     = DIRECTORY_SEPARATOR;
$_SERVER['HTTP_USER_AGENT'] = 'console';


$path = '';
if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $path = dirname(__FILE__);
    $path = substr($path, 0, -33);  // remove trailing: ...\sites\all\modules\custom\fdvegan
} else {
    $path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'drupal';
}

chdir($path);
//define('DRUPAL_ROOT', getcwd());
define('DRUPAL_ROOT', $path);
require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);


    module_load_include('php', 'fdvegan', 'fdvegan_content');


    fdvegan_Content::syslog('LOG_INFO', 'Starting fdvegan_script_init_load.');

    $start_num = 1;
    $process_num = 10;
    $result = 0;

    while ($result == 0) {
        fdvegan_Content::syslog('LOG_INFO', "Iterating through getInitLoadContent({$start_num},{$process_num}).");
        list($result, $start_num, $content) = fdvegan_Content::getInitLoadContent($start_num, $process_num);
        fdvegan_Content::syslog('LOG_INFO', 'Processing actor # ' . $start_num . '.');
    }
    fdvegan_Content::syslog('LOG_DEBUG', 'Final result in fdvegan_script_init_load: ' . $content);

    if ($result != 1) {
        fdvegan_Content::syslog('LOG_ERR', "Unsuccessful completion of fdvegan_script_init_load after {$start_num} actors.");
    } else {
        fdvegan_Content::syslog('LOG_INFO', "Completed fdvegan_script_init_load of {$start_num} actors.");
    }

