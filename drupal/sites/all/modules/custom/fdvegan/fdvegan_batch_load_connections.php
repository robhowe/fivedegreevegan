<?php
/**
 * fdvegan_batch_load_connections.php
 *
 * Batch process implementation for module fdvegan.
 * Handles large data imports that would otherwise timeout on a webpage.
 *
 * PHP version 5.6
 *
 * @category   Install
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 1.1
 */


function fdvegan_initial_recalculate_degrees_batch_process($total_max_num, &$context) {
    // Note - if this function outputs anything to stdout, it will mess up the
    // JSON data response expected, so ensure that fdvegan_Content::syslog() is
    // only set to log to a file (not stdout) if you want to enable debugging
    // in this batch module.
    fdvegan_Content::syslog('LOG_INFO', 'Begin fdvegan_initial_recalculate_degrees_batch_process().');

    $connection = new fdvegan_Connection(fdvegan_Util::$connections_options);
    $result  = $connection->recalculateInitTable();
    $result &= $connection->recalculateDegree0();
    $result &= $connection->recalculateDegree1();

    if ($result) {
        $context['message'] = t('Finished recalculating 1 degrees');
        $context['results'][] = check_plain('1 degrees processed.');
    } else {
        $context['success'] = false;
        $context['message'] = t("Error occurred while recalculating degrees");
        $context['results'][] = check_plain("Error occurred while recalculating degrees.");
    }
    fdvegan_Content::syslog('LOG_INFO', 'End fdvegan_initial_recalculate_degrees_batch_process().');
}


function fdvegan_recalculate_degrees_batch_process($degree, $person_number, $loop, $total_max_num, $person_id, &$context) {
    // Note - if this function outputs anything to stdout, it will mess up the
    // JSON data response expected, so ensure that fdvegan_Content::syslog() is
    // only set to log to a file (not stdout) if you want to enable debugging
    // in this batch module.
    fdvegan_Content::syslog('LOG_INFO', "Begin ({$degree},{$person_number},{$loop},{$total_max_num},{$person_id}).");

    $connection = new fdvegan_Connection(fdvegan_Util::$connections_options);
    $method_name = 'recalculateDegree' . $degree;
    $result = $connection->{$method_name}($person_id);

    if ($result) {
        $context['message'] = t("Recalculating {$degree}&deg; connections for person #{$person_number} id={$person_id}");
        $context['results'][] = check_plain("{$person_number} {$degree}-degree connections processed.");
    } else {
        $context['success'] = false;
        $context['message'] = t("Error occurred while recalculating degrees");
        $context['results'][] = check_plain("Error occurred while recalculating degrees.");
    }
    fdvegan_Content::syslog('LOG_INFO', "End ({$degree},{$person_number},{$loop},{$total_max_num},{$person_id}).");
}


function fdvegan_recalculate_degrees_batch_script_process($total_max_num, $context_guid, &$context) {
    // Note - if this function outputs anything to stdout, it will mess up the
    // JSON data response expected, so ensure that fdvegan_Content::syslog() is
    // only set to log to a file (not stdout) if you want to enable debugging
    // in this batch module.
    fdvegan_Content::syslog('LOG_DEBUG', "Begin ({$total_max_num},{$context_guid}).");
    try {
        $process_status = new fdvegan_ProcessStatus();
        $process_status->processName = 'CALC_DEGREES';
        $process_status->loadProcessStatus();
    }
    catch (FDVegan_NotFoundException $e) {  // No record found
        // This is fine.  It means that the script isn't currently running.
        $process_status = new fdvegan_ProcessStatus();
        $process_status->processName   = 'CALC_DEGREES';
        $process_status->status        = 'READY';
        $process_status->verboseStatus = 'Ready to run; initialized on the fly.';
        $process_status->storeProcessStatus();
    }
    catch (Exception $e) {
        throw new FDVegan_PDOException("Caught exception: {$e->getMessage()} while retrieving process status for ({$total_max_num},{$context_guid}): ", $e->getCode(), $e, 'LOG_ERR');
    }

    if (($process_status->status === 'READY') || 
        (($process_status->status === 'DONE') && ($process_status->context !== $context_guid))
       ) {
        // It's ok to start a new process now.
        $process_status->status = 'STARTING';
        $process_status->verboseStatus = 'Recalculate degrees process is being started...';
        $process_status->counter = 1;
        $process_status->context = $context_guid;
        $process_status->storeProcessStatus();

        // Start up the actual script process:
        fdvegan_Util::execInBackground('fdvegan_script_load_connections.php -g ' . $context_guid);
    }

    $context['message'] = t($process_status->verboseStatus);
    $context['results'][] = check_plain($process_status->verboseStatus);
    $context['finished'] = $process_status->counter / $total_max_num;
    if (($process_status->status === 'STARTING') ||
        ($process_status->status === 'RUNNING')) {
        sleep(30);  // pause for 30 seconds before allowing the batch to re-poll again
    } else {
        $context['success'] = false;
    }
    if (($process_status->status === 'DONE') && ($process_status->context === $context_guid)) {
        $context['success'] = true;
        $context['finished'] = $process_status->counter / $total_max_num;
    }
    fdvegan_Content::syslog('LOG_DEBUG', "End ({$total_max_num},{$context_guid}) finished_percent={$context['finished']}.");
}


function fdvegan_recalculate_degrees_batch_finished($success, $results, $operations) {
    $message = '';
    if ($success) {
        // The last $results[] element contains our special last message to the user.
        $message = end($results);
        drupal_set_message($message);
    } else {
        // An error occurred.
        // $operations contains the operations that remained unprocessed.
        $error_operation = reset($operations);
        $message = t('An error occurred while processing %error_operation with arguments: @arguments', array('%error_operation' => $error_operation[0], '@arguments' => print_r($error_operation[1], TRUE)));
        drupal_set_message($message, 'error');
        fdvegan_Content::syslog('LOG_ERR', $message . ': ' . print_r($operations,1));
    }
    fdvegan_Content::syslog('LOG_INFO', "Success={$success}, msg={$message}");
}

