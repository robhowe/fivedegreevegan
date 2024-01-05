<?php
/**
 * fdvegan_script_load_connections.php
 *
 * Implementation of initial load functionality for module fdvegan.
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
 * @since      version 1.1
 * @see        fdvegan_batch_load_connections.php
 */


/*
 * If desired, this script can be run manually on the command-line via:  
 *   cd ...\drupal\sites\all\modules\custom\fdvegan; php fdvegan_script_load_connections.php
 */


// define Drupal7 default settings
$_SERVER['HTTP_HOST']       = 'default';
if (substr(php_uname(), 0, 7) == "Windows") {
    $_SERVER['PHP_SELF']    = '\\' . basename(__FILE__);
} else {
    $_SERVER['PHP_SELF']    = '/' . basename(__FILE__);
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
    module_load_include('php', 'fdvegan', 'fdvegan_person_collection.php');
    module_load_include('php', 'fdvegan', 'fdvegan_process_status.php');
    module_load_include('php', 'fdvegan', 'fdvegan_util.php');


    $continue = FALSE;
    $context_guid = NULL;
    $force_restart = FALSE;

    // Handle command-line arguments, if any.
    $cmd_opts = getopt('cfg:h');
    if ($cmd_opts === FALSE) {
        $err_mesg = 'Unknown cmd-line args provided.';
        fdvegan_Content::syslog('LOG_WARNING', $err_mesg);
        die($err_mesg);
    }
    foreach (array_keys($cmd_opts) as $opt) {
        switch ($opt) {
            case 'c':
              $continue = TRUE;
              break;

            case 'f':
              $force_restart = TRUE;
              break;

            case 'g':
              $context_guid = $cmd_opts['g'];
              break;

            case 'h':
              echo "Example usage:\n";
              echo "  fdvegan_script_load_connections.php [-c] [-g 123ABC...]\n";
              echo "    -c = continue from last point\n";
              echo "    -f = force restart from scratch\n";
              echo "    -g = use GUID (must provide)\n";
              exit(1);
      }
    }
    if ($continue && $force_restart) {
        $err_mesg = 'Invalid cmd-line options provided. Cannot continue AND force restart!';
        fdvegan_Content::syslog('LOG_WARNING', $err_mesg);
        die($err_mesg);
    }


    fdvegan_Content::syslog('LOG_INFO', 'Starting fdvegan_script_load_connections.');

    $options = array('ProcessName' => 'CALC_DEGREES');
    $process_status = new fdvegan_ProcessStatus($options);
    if (($process_status->status === 'READY') || 
        ($process_status->status === 'DONE') ||
        ($process_status->status === 'STARTING') ||
         $force_restart
       ) {
        // It's ok to start a new process now.
        $process_status->status = 'RUNNING';
        $process_status->verboseStatus = 'Recalculating 4 and 5 degrees process is running.';
        $process_status->counter = 1;
        if (!$context_guid) {
            $context_guid = fdvegan_Util::createGUID();
        }
        $process_status->context = $context_guid;
        $process_status->storeProcessStatus();
    } else if ($continue) {
        // Attempt to continue running this script from where it last left off.
        if ($process_status->status !== 'RUNNING') {
            $err_mesg = 'Conflicting process state! Cannot continue fdvegan_script_load_connections currently.';
            fdvegan_Content::syslog('LOG_ERR', $err_mesg);
            die($err_mesg);
        }
        // It's ok to continue this process now.
        $process_status->status = 'RUNNING';
        $process_status->verboseStatus = 'Continuing the recalculate 4 and 5 degrees process.';
        if (!$context_guid) {
            $context_guid = fdvegan_Util::createGUID();
        }
        $process_status->context = $context_guid;
        $process_status->storeProcessStatus();
    } else {
        // This script is already running, so bail out now.
        $err_mesg = 'Conflicting process already running! Cannot start fdvegan_script_load_connections currently.';
        fdvegan_Content::syslog('LOG_ERR', $err_mesg);
        $process_status->status = 'ERROR';
        $process_status->verboseStatus = $err_mesg;
        $process_status->storeProcessStatus();
        die($err_mesg);
    }


    $person_collection = new fdvegan_PersonCollection(fdvegan_Util::$connections_options);
    $person_collection->loadPersons();  // load the actors from our DB
    // To test this batch load code without slamming our FDV DB,
    // comment out the line above and uncomment the 2 lines below:
//    $person_collection->setLimit(3);  // load only the first 3 actors, using (start, limit)
//    $person_collection->loadPersonsArray();

    $max_num = $person_collection->count();
    $loop = $max_num * 3;
    $total_max_num = $max_num * 5;
    if ($continue) {
        $log_mesg = "Continuing from #{$process_status->counter}/{$total_max_num}";
    } else if ($force_restart) {
        $log_mesg = 'Restarting';
    } else {
        $log_mesg = 'Starting';
    }
    $log_mesg .= " recalculate-degrees batch for ({$max_num}) persons.";
    fdvegan_Content::syslog('LOG_INFO', $log_mesg);
    $connection = new fdvegan_Connection(fdvegan_Util::$connections_options);

    /* Since the 0-to-3 degree connections take < 2 minutes each to calculate,
     * they're easily handled via the batch functionality (see fdvegan_batch_load_connections.php).
     * So we only need to load 4 & 5 degree connections via this script.
     */

    // Next, load all actors' connections starting with the smallest degree first and working to degree 5.
    for ($degree=4; $degree < 6; $degree++) {
        $person_number = 1;
        foreach ($person_collection as $person) {
            $loop++;
            $person_number++;
            if ($continue) {
                if ($loop == $process_status->counter) {
                    $continue = FALSE;
                } else {
                    continue;
                }
            }
            $method_name = 'recalculateDegree' . $degree;
            fdvegan_Content::syslog('LOG_DEBUG', " Calling {$method_name}({$person->personId}) for #{$person_number} ({$loop}/{$total_max_num}).");

            // It may have been hours, so re-poll our process_status record in the DB to ensure we're still good.
            $options = array('ProcessName' => 'CALC_DEGREES');
            $process_status = new fdvegan_ProcessStatus($options);
            if (($process_status->status !== 'RUNNING') || ($process_status->context !== $context_guid)) {
                // Something external to this script must've happened, so bail out now.
                $err_mesg = "Unknown external error occurred. Conflicting processes running! #{$loop} id={$person->personId} vs. {$process_status->personId}, GUID={$context_guid}";
                fdvegan_Content::syslog('LOG_ERR', $err_mesg);
                $process_status->status = 'ERROR';
                $process_status->verboseStatus = $err_mesg;
                $process_status->storeProcessStatus();
                die($err_mesg);
            }
            // Looks ok to continue processing.
            $process_status->verboseStatus = "Recalculating {$degree}-degree process is running for #{$person_number} ({$loop}/{$total_max_num}) id={$person->personId}.";
            $process_status->counter = $loop;
            $process_status->personId = $person->personId;
            $process_status->storeProcessStatus();

            $result = $connection->{$method_name}($person->personId);
            if (!$result) {
                $err_mesg = "ERROR {$method_name}({$person->personId}) process failed.";
                fdvegan_Content::syslog('LOG_ERR', $err_mesg);
                die($err_mesg);
            }
        }
    }


    //
    // All finished!  So update the process_status record and this script is done.
    //
    $mesg = "Completed recalculate-degrees batch for ({$max_num}) persons.";
    $process_status->status = 'DONE';
    $process_status->verboseStatus = $mesg;
    $process_status->storeProcessStatus();

    fdvegan_Content::syslog('LOG_INFO', $mesg . " GUID={$context_guid}");

