<?php
/**
 * fdvegan_batch_process.php
 *
 * Batch process implementation for module fdvegan.
 * Handles large data imports that would otherwise timeout on a webpage.
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
 * @since      version 0.5
 */

/**
 * The $batch can include the following values. Only 'operations'
 * and 'finished' are required, all others will be set to default values.
 *
 * @param operations
 *   An array of callbacks and arguments for the callbacks.
 *   There can be one callback called one time, one callback
 *   called repeatedly with different arguments, different
 *   callbacks with the same arguments, one callback with no
 *   arguments, etc. (Use an empty array if you want to pass
 *   no arguments.)
 *
 * @param finished
 *   A callback to be used when the batch finishes.
 *
 * @param title
 *   A title to be displayed to the end user when the batch starts.
 *
 * @param init_message
 *   An initial message to be displayed to the end user when the batch starts.
 *
 * @param progress_message
 *   A progress message for the end user. Placeholders are available.
 *   Placeholders note the progression by operation, i.e. if there are
 *   2 operations, the message will look like:
 *    'Processed 1 out of 2.'
 *    'Processed 2 out of 2.'
 *   Placeholders include:
 *     @current, @remaining, @total and @percentage
 *
 * @param error_message
 *   The error message that will be displayed to the end user if the batch
 *   fails.
 *
 * @param file
 *   Path to file containing the callbacks declared above. Always needed when
 *   the callbacks are not in a .module file.
 *
 */
function fdvegan_init_load_batch() {

    $options = array('HavingTmdbId' => TRUE);  // no sense in trying to load any actors not in TMDb
    $person_collection = new fdvegan_PersonCollection($options);
    // To test this batch load code without slamming the TMDb API, uncomment the line below:
    //$person_collection->setLimit(3);  // load only the first 3 actors, using (start, limit)
    $person_collection->loadPersonsArray();  // load every actor in our DB

    $max_num = $person_collection->count();
    $operations = array();
    $loop = 1;
    foreach ($person_collection as $person) {
        $operations[] = array('fdvegan_init_load_batch_process', array($loop++, $max_num, $person->personId));
    }
    fdvegan_Content::syslog('LOG_INFO', "Starting init-load batch for ({$max_num}) persons.");
    $batch = array(
                   'operations' => $operations,
                   'finished' => 'fdvegan_init_load_batch_finished',
                   'title' => t('Executing initial database-load'),
                   'init_message' => t('Initial database-load batch is starting.'),
                   'progress_message' => t('Processed @current out of @total actors.'),
                   'error_message' => t('Initial database load process has encountered an error.'),
                   'file' => drupal_get_path('module', 'fdvegan') . '/fdvegan_batch_process.php',
    );
    batch_set($batch);

    // If this function was called from a form submit handler, stop here,
    // FAPI will handle calling batch_process().
    // If not called from a submit handler, add the following,
    // noting the url the user should be sent to once the batch
    // is finished.
    // IMPORTANT:
    // If you set a blank parameter, the batch_process() will cause an infinite loop
//  batch_process('node/1');
}


/**
 * Batch Operation Callback
 *
 * Each batch operation callback will iterate over and over until
 * $context['finished'] is set to 1. After each pass, batch.inc will
 * check its timer and see if it is time for a new http request,
 * i.e. when more than 1 minute has elapsed since the last request.
 * Note that $context['finished'] is set to 1 on entry - a single pass
 * operation is assumed by default.
 *
 * An entire batch that processes very quickly might only need a single
 * http request even if it iterates through the callback several times,
 * while slower processes might initiate a new http request on every
 * iteration of the callback.
 *
 * This means you should set your processing up to do in each iteration
 * only as much as you can do without a php timeout, then let batch.inc
 * decide if it needs to make a fresh http request.
 *
 * @param options1, options2
 *   If any arguments were sent to the operations callback, they
 *   will be the first arguments available to the callback.
 *
 * @param context
 *   $context is an array that will contain information about the
 *   status of the batch. The values in $context will retain their
 *   values as the batch progresses.
 *
 * @param $context['sandbox']
 *   Use the $context['sandbox'] rather than $_SESSION to store the
 *   information needed to track information between successive calls to
 *   the current operation. If you need to pass values to the next operation
 *   use $context['results'].
 *
 *   The values in the sandbox will be stored and updated in the database
 *   between http requests until the batch finishes processing. This will
 *   avoid problems if the user navigates away from the page before the
 *   batch finishes.
 *
 * @param $context['results']
 *   The array of results gathered so far by the batch processing. This
 *   array is highly useful for passing data between operations. After all
 *   operations have finished, these results may be referenced to display
 *   information to the end-user, such as how many total items were
 *   processed.
 *
 * @param $context['message']
 *   A text message displayed in the progress page.
 *
 * @param $context['finished']
 *   A float number between 0 and 1 informing the processing engine
 *   of the completion level for the operation.
 *
 *   1 (or no value explicitly set) means the operation is finished
 *   and the batch processing can continue to the next operation.
 *
 *   Batch API resets this to 1 each time the operation callback is called.
 */
function fdvegan_init_load_batch_process($current_num, $max, $person_id, &$context) {
    // Note - if this function outputs anything to stdout, it will mess up the
    // JSON data response expected, so ensure that fdvegan_Content::syslog() is
    // only set to log to a file (not stdout) if you want to enable debugging
    // in this batch module.
    fdvegan_Content::syslog('LOG_DEBUG', "BEGIN fdvegan_init_load_batch_process({$current_num},{$max},{$person_id}).");

    $options = array('PersonId' => $person_id);
    // The first progress/loop time through, we only load the person from TMDb.
    if (!isset($context['sandbox']['progress'])) {  // First time through the loop for this person
        $context['sandbox']['progress'] = 1;
        $options['RefreshFromTmdb'] = TRUE;
        $person = new fdvegan_Person($options);
        if (empty($person)) {
            $context['success'] = false;
            $context['finished'] = 1;  // full stop exit!
            $context['message'] = t("Error occurred while processing actor # {$current_num}, person_id={$person_id}");
            $context['results'][] = check_plain("Error processing actor # {$current_num}, person_id={$person_id}");
        } else {
            $message = "Now processing actor # {$current_num} : {$person->getFullName()}";
            $context['message'] = t($message);
            $context['results'][] = check_plain("{$current_num} vegan actors processed");
            $context['finished'] = 1/1000;
        }
        return;
    }

    // The second progress/loop time through, we only load the person's credits and included (minimal) movie data from TMDb.
    if ($context['sandbox']['progress'] == 1) {  // First time through the loop for credits
        $context['sandbox']['progress'] = 2;
        $person = new fdvegan_Person($options);
        if (empty($person)) {
            $context['success'] = false;
            $context['finished'] = 1;  // full stop exit!
            $context['message'] = t("Error occurred while processing actor # {$current_num}, person_id={$person_id}, credits");
            $context['results'][] = check_plain("Error processing actor # {$current_num}, person_id={$person_id}, credits");
        } else {
            // Loop through all credits and store any from TMDb that are missing from our DB.
            $options['TmdbId'] = $person->getTmdbId();
            $options['RefreshFromTmdb'] = TRUE;
            // Note - This does not store the necessary related movie records from TMDb, only partial info for them.
            $credits = new fdvegan_CreditCollection($options);
            if ($credits->count()) {  // Only need to save an actor's credits if they have some
                $context['finished'] = 2/1000;
            } else {
                $context['finished'] = 1;
            }
            $message = "Now processing actor # {$current_num} : {$person->getFullName()} ...";
            $context['message'] = t($message);
            $context['results'][] = check_plain("{$current_num} vegan actors processed");
        }
        return;
    }

    // All subsequent progress/loop times through, we load each credit's movie's full data from TMDb.
    $progress = $context['sandbox']['progress']++;
    $person = new fdvegan_Person($options);
    if (empty($person)) {
        $context['success'] = false;
        $context['finished'] = 1;  // full stop exit!
        $context['message'] = t("Error occurred while processing actor # {$current_num}, person_id={$person_id}");
        $context['results'][] = check_plain("Error processing actor # {$current_num}, person_id={$person_id}");
    } else {
        $credits = new fdvegan_CreditCollection($options);
        $options['RefreshFromTmdb'] = TRUE;
        $credits->loadEachMovie($options, $progress - 2, 1);

        $message = "Now processing actor # {$current_num} : {$person->getFullName()}, credit # {$progress}.";
        $context['message'] = t($message);
        $context['results'][] = check_plain("{$current_num} vegan actors processed");
        $context['finished'] = $context['sandbox']['progress'] / ($credits->count() + 2);
        if ($context['finished'] == 1) {
            $context['success'] = true;
            unset($context['sandbox']['progress']);  // Drupal does not automatically reset 'sandbox' variables
        }
    }
    fdvegan_Content::syslog('LOG_DEBUG', "END fdvegan_init_load_batch_process({$current_num},{$max},{$person_id}) progress={$progress}.");
}


/**
 * Batch 'finished' callback
 */
function fdvegan_init_load_batch_finished($success, $results, $operations) {
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
    fdvegan_Content::syslog('LOG_INFO', "fdvegan_init_load_batch_finished() success={$success}, msg={$message}.");
}



    //////////////////////////////



/**
 * See fdvegan.module fdvegan_scrape_media_form()
 *
 * @param string $scrape_type    Valid values: 'persons', 'movies', or 'both'
 */
function fdvegan_scrape_media_batch($scrape_type = 'both') {
    $progress_message = '';
    $options = array('HavingTmdbId' => TRUE);  // no sense in trying to load anything not in TMDb
    $operations = array();
    if (($scrape_type === 'persons') || ($scrape_type === 'both')) {
        $person_collection = new fdvegan_PersonCollection($options);
        // To test this batch load code without slamming the TMDb API, uncomment the line below:
        //$person_collection->setLimit(3);  // load only the first 3 actors, using (start, limit)
        $person_collection->loadPersonsArray();  // load every actor in our DB
        $max_num_persons = $person_collection->count();
        $operations[] = array('fdvegan_scrape_person_media_batch_process', array($scrape_type, 0, $max_num_persons));
        $progress_message = t("Scraping {$max_num_persons} actors.");
    }

    if (($scrape_type === 'movies') || ($scrape_type === 'both')) {
        $movie_collection = new fdvegan_MovieCollection($options);
        $movie_collection->loadMoviesArray();  // load every movie in our DB
        // To test this batch load code without slamming the TMDb API,
        // simply hard-code the $max_num_movies = 3;  or something small.
        $max_num_movies = $movie_collection->count();
        $operations[] = array('fdvegan_scrape_movie_media_batch_process', array($scrape_type, 0, $max_num_movies));
        $progress_message = t("Scraping {$max_num_movies} movies.");
    }

    if ($scrape_type === 'both') {
        $progress_message = t("Scraping {$max_num_persons} actors first, then {$max_num_movies} movies.");
    }
    fdvegan_Content::syslog('LOG_INFO', "Starting scrape-media batch for {$progress_message}");
    $batch = array(
                   'operations' => $operations,
                   'finished' => 'fdvegan_scrape_media_batch_finished',
                   'title' => t('Executing scrape-media'),
                   'init_message' => t('Scrape-media batch is starting.'),
                   'progress_message' => $progress_message,
                   'error_message' => t('Scrape-media process has encountered an error.'),
                   'file' => drupal_get_path('module', 'fdvegan') . '/fdvegan_batch_process.php',
    );
    batch_set($batch);
}


function fdvegan_scrape_person_media_batch_process($scrape_type, $start, $max_num, &$context) {
    // Note - if this function outputs anything to stdout, it will mess up the
    // JSON data response expected, so ensure that fdvegan_Content::syslog() is
    // only set to log to a file (not stdout) if you want to enable debugging
    // in this batch module.
    fdvegan_Content::syslog('LOG_DEBUG', "Begin fdvegan_scrape_person_media_batch_process('{$scrape_type}',{$start},{$max_num}).");

    if (!isset($context['sandbox']['current_num'])) {  // First time in
        $context['sandbox']['current_num'] = $start;
    } else {
        $context['sandbox']['current_num']++;
    }
    $current_num = $context['sandbox']['current_num'];

    $options = array('HavingTmdbId' => TRUE,
                     'Start'        => $current_num,
                     'Limit'        => 1
    );
    if ($current_num >= $max_num) {
        $context['message'] = t('Finished processing actors');
        $context['results'][] = check_plain("{$current_num} actors processed.");
    } else {
        $collection = new fdvegan_PersonCollection($options);
        $collection->loadPersonsArray();
        if ($collection->count() < 1) {
            $context['message'] = t('Finished processing actors');
            $context['results'][] = check_plain("{$current_num} actors processed.");
        } else {
            $context['finished'] = $current_num / $max_num;
            $person = $collection[0];
            if (empty($person)) {
                $context['success'] = false;
                $context['finished'] = 1;  // full stop exit!
                $context['message'] = t("Error occurred while processing actor # {$current_num}");
                $context['results'][] = check_plain("Error retrieving actor # {$current_num}.");
            } else {
                $person->setScrapeFromTmdb(TRUE);
                $media_collection = $person->getPersonImages();
                if (empty($media_collection)) {
                    $context['success'] = false;
                    $context['finished'] = 1;  // full stop exit!
                    $context['message'] = t("Error occurred while retrieving images for actor # {$current_num}, person_id={$person->getPersonId()}");
                    $context['results'][] = check_plain("Error scraping actor # {$current_num}, person_id={$person->getPersonId()}.");
                } else {
                    $context['message'] = t("Now processing actor # ". ($current_num + 1) ." : {$person->getFullName()}");
                    $context['results'][] = check_plain("{$current_num} actors processed.");
                    if ($context['finished'] == 1) {
                        $context['success'] = true;
                    }
                }
            }
        }
    }
    fdvegan_Content::syslog('LOG_DEBUG', "End fdvegan_scrape_person_media_batch_process('{$scrape_type}',{$start},{$max_num}).");
}


function fdvegan_scrape_movie_media_batch_process($scrape_type, $start, $max_num, &$context) {
    // Note - if this function outputs anything to stdout, it will mess up the
    // JSON data response expected, so ensure that fdvegan_Content::syslog() is
    // only set to log to a file (not stdout) if you want to enable debugging
    // in this batch module.
    fdvegan_Content::syslog('LOG_DEBUG', "Begin fdvegan_scrape_movie_media_batch_process('{$scrape_type}',{$start},{$max_num}).");

    if (!isset($context['sandbox']['current_num'])) {  // First time in
        $context['sandbox']['current_num'] = $start;
    } else {
        $context['sandbox']['current_num']++;
    }
    $current_num = $context['sandbox']['current_num'];

    $options = array('HavingTmdbId' => TRUE,
                     'Start'        => $current_num,
                     'Limit'        => 1
    );
    if ($current_num >= $max_num) {
        $context['message'] = t('Finished processing movies');
        $context['results'][] = check_plain("{$current_num} movies processed.");
    } else {
        $collection = new fdvegan_MovieCollection($options);
        $collection->loadMoviesArray();
        if ($collection->count() < 1) {
            $context['message'] = t('Finished processing movies');
            $context['results'][] = check_plain("{$current_num} movies processed.");
        } else {
            $context['finished'] = $current_num / $max_num;
            $movie = $collection[0];
            if (empty($movie)) {
                $context['success'] = false;
                $context['finished'] = 1;  // full stop exit!
                $context['message'] = t("Error occurred while processing movie # {$current_num}");
                $context['results'][] = check_plain("Error retrieving movie # {$current_num}.");
            } else {
                $movie->setScrapeFromTmdb(TRUE);
                $media_collection = $movie->getMovieImages();
                if (empty($media_collection)) {
                    $context['success'] = false;
                    $context['finished'] = 1;  // full stop exit!
                    $context['message'] = t("Error occurred while retrieving images for movie # {$current_num}, movie_id={$movie->getMovieId()}");
                    $context['results'][] = check_plain("Error scraping movie # {$current_num}, movie_id={$movie->getMovieId()}.");
                } else {
                    $media_collection = $movie->getMoviebackdropImages();
                    // NOTE - Movie backdrop images don't have a default "no movie image found" image like
                    //        regular movie posters have, so the result here can be an empty array.
                    if ($media_collection == NULL) {  // Can be an empty array, but shouldn't be NULL.
                        $context['success'] = false;
                        $context['finished'] = 1;  // full stop exit!
                        $context['message'] = t("Error occurred while retrieving images for moviebackdrop # {$current_num}, movie_id={$movie->getMovieId()}");
                        $context['results'][] = check_plain("Error scraping moviebackdrop # {$current_num}, movie_id={$movie->getMovieId()}.");
                    } else {
                        $context['message'] = t("Now processing movie # ". ($current_num + 1) ." : {$movie->getTitle()}");
                        $context['results'][] = check_plain("{$current_num} movies processed.");
                        if ($context['finished'] == 1) {
                            $context['success'] = true;
                        }
                    }
                }
            }
        }
    }
    fdvegan_Content::syslog('LOG_DEBUG', "End fdvegan_scrape_movie_media_batch_process('{$scrape_type}',{$start},{$max_num}).");
}


function fdvegan_scrape_media_batch_finished($success, $results, $operations) {
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
    fdvegan_Content::syslog('LOG_INFO', "fdvegan_scrape_media_batch_finished() success={$success}, msg={$message}");
}



    //////////////////////////////



/**
 * See fdvegan.module fdvegan_copy_local_media_form()
 */
function fdvegan_copy_local_media_batch() {
    $progress_message = '';
    $options = array('HavingTmdbId' => TRUE,  // no sense in trying to load anything not in TMDb
                     'HavingFDVCountGT' => 1);  // only load movies with fdv_count of 2 or more
    $operations = array();

    $person_collection = new fdvegan_PersonCollection($options);
    $person_collection->loadPersonsArray();  // load every actor in our DB
    $max_num_persons = $person_collection->count();
    $operations[] = array('fdvegan_copy_local_person_media_batch_process', array(0, $max_num_persons));

    $movie_collection = new fdvegan_MovieCollection($options);
    $movie_collection->loadMoviesArray();  // load every movie in our DB
    // To test this batch load code without slamming the TMDb API,
    // simply hard-code the $max_num_movies = 3;  or something small.
    $max_num_movies = $movie_collection->count();
    $operations[] = array('fdvegan_copy_local_movie_media_batch_process', array(0, $max_num_movies));

    $progress_message = t("Copying local {$max_num_persons} actors first, then {$max_num_movies} movies.");

    fdvegan_Content::syslog('LOG_INFO', "Starting copy-local-media batch for {$progress_message}");
    $batch = array(
                   'operations' => $operations,
                   'finished' => 'fdvegan_copy_local_media_batch_finished',
                   'title' => t('Executing copy-local-media'),
                   'init_message' => t('Copy-local-media batch is starting.'),
                   'progress_message' => $progress_message,
                   'error_message' => t('Copy-local-media process has encountered an error.'),
                   'file' => drupal_get_path('module', 'fdvegan') . '/fdvegan_batch_process.php',
    );
    batch_set($batch);
}


function fdvegan_copy_local_person_media_batch_process($start, $max_num, &$context) {
    // Note - if this function outputs anything to stdout, it will mess up the
    // JSON data response expected, so ensure that fdvegan_Content::syslog() is
    // only set to log to a file (not stdout) if you want to enable debugging
    // in this batch module.
    fdvegan_Content::syslog('LOG_DEBUG', "Begin fdvegan_copy_local_person_media_batch_process({$start},{$max_num}).");

    if (!isset($context['sandbox']['current_num'])) {  // First time in
        $context['sandbox']['current_num'] = $start;
    } else {
        $context['sandbox']['current_num']++;
    }
    $current_num = $context['sandbox']['current_num'];

    $options = array('HavingTmdbId' => TRUE,
                     'Start'        => $current_num,
                     'Limit'        => 1
    );
    if ($current_num >= $max_num) {
        $context['message'] = t('Finished copying local actors');
        $context['results'][] = check_plain("{$current_num} actors copied local.");
    } else {
        $collection = new fdvegan_PersonCollection($options);
        $collection->loadPersonsArray();
        if ($collection->count() < 1) {
            $context['message'] = t('Finished copying local actors');
            $context['results'][] = check_plain("{$current_num} actors copied local.");
        } else {
            $context['finished'] = $current_num / $max_num;
            $person = $collection[0];
            if (empty($person)) {
                $context['success'] = false;
                $context['finished'] = 1;  // full stop exit!
                $context['message'] = t("Error occurred while copying actor # {$current_num}");
                $context['results'][] = check_plain("Error copying actor # {$current_num}.");
            } else {
                $media_collection = $person->getPersonImages();
                if (empty($media_collection)) {
                    $context['success'] = false;
                    $context['finished'] = 1;  // full stop exit!
                    $context['message'] = t("Error occurred while copying images for actor # {$current_num}, person_id={$person->getPersonId()}");
                    $context['results'][] = check_plain("Error copying actor # {$current_num}, person_id={$person->getPersonId()}.");
                } else {
                    // Now do the actual local copy:
                    $copy_dir = fdvegan_Media::getMediaCopyAbsDir() . '/person/l/';
                    $copy_dir = str_replace('/', DIRECTORY_SEPARATOR, $copy_dir);  // works for both Linux and Windows
                    $num_images = $media_collection['l']->count();
                    $success = true;
                    for ($loop=1; $loop <= $num_images; $loop++) {
                        $local_media = $media_collection['l'][$loop-1];  // Only copy the "large" images.
                        if ($local_media->getPersonId()) {  // Ignore default image id of 0.
                            $local_file = $local_media->getPath();
                            $copy_filename = $person->getFullName() . ' ' . /* $person->id . ' ' . */ $loop;
                            $copy_filename .= '.' . pathinfo($local_file, PATHINFO_EXTENSION);  // add the .jpg extension
                            $copy_filename = fdvegan_Util::getSafeFilename($copy_filename);
                            $copy_file = $copy_dir . $copy_filename;
                            fdvegan_Content::syslog('LOG_DEBUG', "copying local file \"{$local_file}\" to \"{$copy_file}\".");
                            $success = copy($local_file, $copy_file);
                            if (!$success) {
                                $success = false;
                                $context['success'] = false;
                                $context['finished'] = 1;  // full stop exit!
                                $context['message'] = t("Error occurred while copying image {$loop} for actor # {$current_num}, person_id={$person->getPersonId()}");
                                $context['results'][] = check_plain("Error copying actor # {$current_num}, person_id={$person->getPersonId()}, image # {$loop}.");
                                fdvegan_Content::syslog('LOG_ERR', "Error while copying local file \"{$local_file}\" to \"{$copy_file}\".");
                                break;
                            }
                        }
                    }

                    if ($success) {
                        $context['message'] = t("Now copying actor # ". ($current_num + 1) ." : {$person->getFullName()}");
                        $context['results'][] = check_plain("{$current_num} actors copied local.");
                    }
                }
            }
        }
    }
    fdvegan_Content::syslog('LOG_DEBUG', "End fdvegan_copy_local_person_media_batch_process({$start},{$max_num}).");
}


function fdvegan_copy_local_movie_media_batch_process($start, $max_num, &$context) {
    // Note - if this function outputs anything to stdout, it will mess up the
    // JSON data response expected, so ensure that fdvegan_Content::syslog() is
    // only set to log to a file (not stdout) if you want to enable debugging
    // in this batch module.
    fdvegan_Content::syslog('LOG_DEBUG', "Begin fdvegan_copy_local_movie_media_batch_process({$start},{$max_num}).");

    if (!isset($context['sandbox']['current_num'])) {  // First time in
        $context['sandbox']['current_num'] = $start;
    } else {
        $context['sandbox']['current_num']++;
    }
    $current_num = $context['sandbox']['current_num'];

    $options = array('HavingTmdbId' => TRUE,
                     'HavingFDVCountGT' => 1,  // only load movies with fdv_count of 2 or more
                     'Start'        => $current_num,
                     'Limit'        => 1
    );
    if ($current_num >= $max_num) {
        $context['message'] = t('Finished copying local movies');
        $context['results'][] = check_plain("{$current_num} movies copied local.");
    } else {
        $collection = new fdvegan_MovieCollection($options);
        $collection->loadMoviesArray();
        if ($collection->count() < 1) {
            $context['message'] = t('Finished copying local movies');
            $context['results'][] = check_plain("{$current_num} movies copied local.");
        } else {
            $context['finished'] = $current_num / $max_num;
            $movie = $collection[0];
            if (empty($movie)) {
                $context['success'] = false;
                $context['finished'] = 1;  // full stop exit!
                $context['message'] = t("Error occurred while copying movie # {$current_num}");
                $context['results'][] = check_plain("Error copying movie # {$current_num}.");
            } else {
                $media_collection = $movie->getMovieImages();
                if (empty($media_collection)) {
                    $context['success'] = false;
                    $context['finished'] = 1;  // full stop exit!
                    $context['message'] = t("Error occurred while copying images for movie # {$current_num}, movie_id={$movie->getMovieId()}");
                    $context['results'][] = check_plain("Error copying movie # {$current_num}, movie_id={$movie->getMovieId()}.");
                } else {
                    // Now do the actual local copy:
                    $copy_dir = fdvegan_Media::getMediaCopyAbsDir() . '/movie/l/';
                    $copy_dir = str_replace('/', DIRECTORY_SEPARATOR, $copy_dir);  // works for both Linux and Windows
                    $num_images = $media_collection['l']->count();
                    $success = true;
                    for ($loop=1; $loop <= $num_images; $loop++) {
                        $local_media = $media_collection['l'][$loop-1];  // Only copy the "large" images.
                        if ($local_media->getMovieId()) {  // Ignore default image id of 0.
                            $local_file = $local_media->getPath();
                            $copy_filename = $movie->getTitle() . ' ' . /* $movie->id . ' ' . */ $loop;
                            $copy_filename .= '.' . pathinfo($local_file, PATHINFO_EXTENSION);  // add the .jpg extension
                            $copy_filename = fdvegan_Util::getSafeFilename($copy_filename);
                            $copy_file = $copy_dir . $copy_filename;
                            fdvegan_Content::syslog('LOG_DEBUG', "copying local file \"{$local_file}\" to \"{$copy_file}\".");
                            $success = copy($local_file, $copy_file);
                            if (!$success) {
                                $success = false;
                                $context['success'] = false;
                                $context['finished'] = 1;  // full stop exit!
                                $context['message'] = t("Error occurred while copying image {$loop} for movie # {$current_num}, movie_id={$movie->getMovieId()}");
                                $context['results'][] = check_plain("Error copying movie # {$current_num}, movie_id={$movie->getMovieId()}, image # {$loop}.");
                                fdvegan_Content::syslog('LOG_ERR', "Error while copying local file \"{$local_file}\" to \"{$copy_file}\".");
                                break;
                            }
                        }
                    }
                    if ($success) {
                        $context['message'] = t("Now copying movie # ". ($current_num + 1) ." : {$movie->getTitle()}");
                        $context['results'][] = check_plain("{$current_num} movies copied local.");
                    }
                }
            }
        }
    }
    fdvegan_Content::syslog('LOG_DEBUG', "End fdvegan_copy_local_movie_media_batch_process({$start},{$max_num}).");
}


function fdvegan_copy_local_media_batch_finished($success, $results, $operations) {
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
    fdvegan_Content::syslog('LOG_INFO', "fdvegan_scrape_media_batch_finished() success={$success}, msg={$message}");
}



    //////////////////////////////



/**
 * Recalculate the "All Actors Network" data.
 *
 * @see fdvegan.module fdvegan_recalculate_degrees_form()
 * @see fdvegan_batch_load_connections.php
 * @see fdvegan_connection.php::recalculateInitTable()
 */
function fdvegan_recalculate_degrees_batch() {

    // This must match how it's done in:  fdvegan_connection.php::recalculateInitTable()
    $person_collection = new fdvegan_PersonCollection(fdvegan_Util::$connections_options);
    $person_collection->loadPersons();  // load the actors from our DB
    // To test this batch load code without slamming our FDV DB,
    // comment out the line above and uncomment the 2 lines below:
    //$person_collection->setLimit(3);  // load only the first 3 actors, using (start, limit)
    //$person_collection->loadPersons();

    $max_num = $person_collection->count();
    $total_max_num = $max_num * 5;
    fdvegan_Content::syslog('LOG_INFO', "Starting recalculate-degrees batch for ({$max_num}) persons.");
    $progress_message = t("Recalculating 5&deg; connections for {$max_num} featured actors.");
    $operations = array();

    // Since the 0 and 1-degree connections (take < 1 second), we simply run them as a single function call.
    $operations[] = array('fdvegan_initial_recalculate_degrees_batch_process', array($total_max_num));

    // Next, load all actors' connections starting with the smallest degree first and working to degree 5.
    $loop = $max_num * 2;
    for ($degree=2; $degree < 4; $degree++) {
        $person_number = 1;
        foreach ($person_collection as $person) {
            $operations[] = array('fdvegan_recalculate_degrees_batch_process',
                                  array($degree, $person_number++, $loop++, $total_max_num, $person->personId)
                                 );
        }
    }

    /* Lastly, load degrees 4 and 5 via a special PHP script that takes > 10 hours to complete.
     * The script doesn't return for 10+ hours, so it uses the process_status table to communicate
     * its progress to the front end.
     */
    $operations[] = array('fdvegan_recalculate_degrees_batch_script_process',
                          array($total_max_num, fdvegan_Util::createGUID())
                         );

    $batch = array(
                   'operations' => $operations,
                   'finished' => 'fdvegan_recalculate_degrees_batch_finished',
                   'title' => t('Executing recalculate-degrees'),
                   'init_message' => t('Recalculate-degrees batch is starting.'),
                   'progress_message' => $progress_message,
                   'error_message' => t('Recalculate-degrees process has encountered an error.'),
                   'file' => drupal_get_path('module', 'fdvegan') . '/fdvegan_batch_load_connections.php',
    );
    batch_set($batch);
}

