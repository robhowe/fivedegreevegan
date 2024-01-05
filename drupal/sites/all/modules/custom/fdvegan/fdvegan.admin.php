<?php
/**
 * fdvegan.admin.php
 *
 * Admin page implementation for module fdvegan.
 * Used by file fdvegan.module
 * Even though this file is only included, never auto-loaded, it still has a .php extension for editor clarity.
 *
 * PHP version 5.6
 *
 * @category   Admin
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.5
 * @see        fdvegan.module
 */


    /**
     * Make some system status checks to ensure FDVegan module dependencies are setup correctly.
     */
    function _fdvegan_admin_checks() {

        // Note - since forms are usually built twice (once for display and once for submit)
        //        we need to ensure the drupal_set_message() is only displayed once.

        if (!variable_get('clean_url')) {  // checks that Clean URLs are enabled
            $link = l('Enable clean URLs', 'admin/config/search/clean-urls');
            $msg = "You need to {$link} for some FDVegan links to work.";
            drupal_set_message($msg, 'warning', FALSE);  // set $repeat to FALSE to avoid double messages
        }
        if (!module_exists('chosen')) {  // checks that module is installed and enabled
            $link = l('module "Chosen"',
                      'https://www.drupal.org/project/chosen',
                      array('attributes' => array('target'=>'_blank', 'rel'=>'external')));
            $msg = "It is recommended for you to install {$link} to make select options much more user-friendly.";
            drupal_set_message($msg, 'warning', FALSE);  // set $repeat to FALSE to avoid double messages
        }
        if (!module_exists('tablesorter')) {  // checks that module is installed and enabled
            $link = l('module "TableSorter"',
                      'https://www.drupal.org/project/tablesorter',
                      array('attributes' => array('target'=>'_blank', 'rel'=>'external')));
            $msg = "It is recommended for you to install {$link} to make table output sortable.";
            drupal_set_message($msg, 'warning', FALSE);  // set $repeat to FALSE to avoid double messages
        }
        if (!module_exists('addtoany')) {  // checks that module is installed and enabled
            $link = l('module "AddToAny"',
                      'https://www.drupal.org/project/addtoany',
                      array('attributes' => array('target'=>'_blank', 'rel'=>'external')));
            $msg = "It is recommended for you to install {$link} to make the \/share page work.";
            drupal_set_message($msg, 'warning', FALSE);  // set $repeat to FALSE to avoid double messages
        }
        if (!module_exists('metatag')) {  // checks that module is installed and enabled
            $link = l('module "MetaTag"',
                      'https://www.drupal.org/project/metatag',
                      array('attributes' => array('target'=>'_blank', 'rel'=>'external')));
            $msg = "It is recommended for you to install {$link} to make AddToAny work better, as well as improve SEO.";
            drupal_set_message($msg, 'warning', FALSE);  // set $repeat to FALSE to avoid double messages
        }
        return TRUE;
    }


    /**
     * Implementation of hook_form() for fdvegan_admin_form().
     * Configuration Settings page for FDVegan Admin
     */
    function fdvegan_admin_form($form, &$form_state) {

        // Perform some checks first, and warn if any issues are found.
        _fdvegan_admin_checks();


        //
        // Display the admin links content.
        //

        $content = <<<EOT
<h2>Administration options:</h2><br />
<br />
EOT;
        $content .= l(t('Initial Load'), 'init-load');
        $content .= ' - load and store TMDb data for all actors and movies that pre-exist in the FDV database. This is safe to run multiple times.';
        $content .= '<br /><br />';
        $content .= l(t('Recalculate Degrees'), 'recalculate-degrees');
        $content .= ' - re-index all 5&deg; data connections for the Actor Network graph.  This is safe to run multiple times.';
        $content .= '<br /><br />';
        $content .= l(t('Scrape Media'), 'scrape-media');
        $content .= ' - scrape all images from TMDb for all actors and movies that fully-exist in the FDV database. This is safe to run multiple times.';
        $content .= '<br /><br />';
        $content .= l(t('Copy Local Media'), 'copy-local-media');
        $content .= ' - copy all pre-existing images for actors and movies to a separate human-readable dir. This is safe to run multiple times.';
        $content .= '<br /><br />';
        $content .= l(t('Load Actor from TMDb'), 'actor-load');
        $content .= ' - search for a pre-existing actor in the FDV database, then if no TMDb info exists, it will be loaded and stored in the FDV database';
        $content .= '<br /><br />';
        $content .= l(t('Create Initial Content'), 'init-content-load');
        $content .= ' - creates all pages (Home, About, FAQ, Share Us, etc.)';
        $content .= '<br /><br />';
        $content .= l(t('Create Initial Dirs'), 'init-dir-creation');
        $content .= ' - creates all needed directories for media files';
        $content .= '<br /><br />';
        $content .= l(t('Clear Caches'), 'clear-caches');
        $content .= ' - clear all caches, including fdvegan variables';
        $content .= '<br /><br />';
        $content .= l(t('Database Records Info'), 'db-records-info');
        $content .= ' - general counts and stats of records in the FDV database';
        $content .= '<br /><br />';
        $content .= l(t('Debug / Test Page'), 'debug-page');
        $content .= ' - a place to see output from your own experiments; see <span class="code">function fdvegan_debug_view()</span> in file <span class="code">fdvegan.module</span>';
        $content .= '<br /><br />';
        $form['intro'] = array(
            '#markup' => $content,
        );


        //
        // Display the Environment selection.
        // This setting is used by fdvegan_Util::isDevEnv()
        //

        // The values for the env dropdown box
        // Must match $env_levels in fdvegan_util.php
        $form['env_level_options'] = array(
              '#type' => 'value',
              '#value' => array('LOCAL' => 'LOCAL',
                                'DEV'   => 'DEV',
                                'INT'   => 'INT',
                                'TEST'  => 'TEST',
                                'STAGE' => 'STAGE',
                                'PROD'  => 'PROD',
                               ),
        );
        $current_env_level = variable_get('fdvegan_env_level', 'PROD');
        $form['env_level'] = array('#type'          => 'select',
                                   '#title'         => t('Environment Level'),
                                   '#default_value' => $current_env_level,
                                   '#options' => $form['env_level_options']['#value'],
//                                   '#required'      => TRUE,
                                   '#description'   => t('May affect some functionality'),
                                  );


        //
        // Display the Media Filesystem fields.
        // These settings are used by fdvegan_Media::delete() & fdvegan_Media::scrapeFromTmdb()
        //

        $form['mf'] = array('#type'        => 'fieldset',
                            '#title'       => t('Media Filesystem Settings'),
                            '#collapsible' => TRUE,
                            '#collapsed'   => TRUE,
                           );

        $current_media_files_serve = variable_get('fdvegan_media_files_serve', 1);
        $form['mf']['media_files_serve'] = array('#type'          => 'checkbox',
                                                 '#title'         => t('Serve media from local filesystem'),
                                                 '#default_value' => $current_media_files_serve,
                                                 '#required'      => FALSE,
                                                 '#description'   => t('Disable this to serve all media by linking to external websites and ignore any local (scraped) media.'),
                                                 '#weight'        => 10,
                                                );

        $current_media_files_dir = variable_get('fdvegan_media_files_dir', fdvegan_Media::getMediaAbsDir());  // includes the initial "/tmdb" part
        $mf_content = "The settings below only affect the filesystem's image files during the ". l(t('Scrape Media'), 'scrape-media') . " process.<br />\n";
        if (is_dir($current_media_files_dir)) {
            $dfs = disk_free_space($current_media_files_dir);
            $dts = disk_total_space($current_media_files_dir);
            $dfs_gbs = number_format(($dfs / (1024*1024*1024)), 2);  // calculate in gigabytes
            $dts_gbs = number_format(($dts / (1024*1024*1024)), 2);  // calculate in gigabytes
            $mf_content .= "You currently have {$dfs_gbs} GB free out of a total {$dts_gbs} GB.";
        } else {
            $mf_content .= "<span style=\"color: red;\">You need to somehow make your Media Files Dir valid.</span>";
            drupal_set_message('Invalid Media Files Dir');
        }
        $form['mf']['description'] = array('#markup' => $mf_content,
                                           '#weight' => 20,
                                          );

        $form['mf']['media_files_dir'] = array('#type'          => 'textfield',
                                               '#title'         => t('Media Files Dir'),
                                               '#default_value' => $current_media_files_dir,
                                               '#size'          => 96,
                                               '#maxlength'     => 255,
                                               '#required'      => FALSE,
//                                               '#disabled'      => TRUE,  // does not allow user to copy & paste this field
                                               '#attributes'    => array('readonly' => 'readonly'),  // allows user to copy & paste this field
                                               '#description'   => t('You cannot update this.'),
                                               '#weight'        => 30,
                                              );

        $current_media_files_max_num = variable_get('fdvegan_media_files_max_num');  // 15 or less is strongly recommended
        $form['mf']['media_files_max_num'] = array('#type'          => 'textfield',
                                                   '#title'         => t('Max # of Media Files Per Actor or Movie'),
                                                   '#default_value' => $current_media_files_max_num,
                                                   '#size'          => 8,
                                                   '#maxlength'     => 8,
                                                   '#required'      => FALSE,
                                                   '#description'   => t('Max # of 15 or less is strongly recommended to avoid timeouts during scraping. Set to "-1" for no limit.'),
                                                   '#weight'        => 40,
                                                  );

        $current_media_filesystem_overwrite = variable_get('fdvegan_media_filesystem_overwrite', 0);
        $form['mf']['media_filesystem_overwrite'] = array('#type'          => 'checkbox',
                                                          '#title'         => t('Overwrite Pre-existing Files'),
                                                          '#default_value' => $current_media_filesystem_overwrite,
                                                          '#required'      => FALSE,
                                                          '#description'   => t('Leaving this disabled can save initial scraping time. But if you suspect that images are stale (compared to TMDb) enable this to totally refresh all images.'),
                                                          '#weight'        => 50,
                                                         );


        //
        // Display the Syslog Output fields.
        // These settings are used by fdvegan_Content::syslog()
        //

        $form['so'] = array('#type'        => 'fieldset',
                            '#title'       => t('Syslog Output Settings'),
                            '#collapsible' => TRUE,
                            '#collapsed'   => TRUE,
                           );

        // The values for the syslog_output_level dropdown box
        // For all valid priority values see:  https://www.php.net/manual/en/function.syslog.php
        // Must match $syslog_levels in fdvegan_util.php
        $form['so']['syslog_output_level_options'] = array(
              '#type' => 'value',
              '#value' => array('LOG_DEBUG'   => 'LOG_DEBUG',
                                'LOG_INFO'    => 'LOG_INFO',
                                'LOG_NOTICE'  => 'LOG_NOTICE',
                                'LOG_WARNING' => 'LOG_WARNING',
                                'LOG_ERR'     => 'LOG_ERR',
                                'LOG_CRIT'    => 'LOG_CRIT',
                                'LOG_ALERT'   => 'LOG_ALERT',
                                'LOG_EMERG'   => 'LOG_EMERG',
                                'No Logging'  => 'No Logging',
                               ),
        );
        $current_syslog_output_level = variable_get('fdvegan_syslog_output_level', 'LOG_ERR');
        $form['so']['syslog_output_level'] = array('#type'          => 'select',
                                                   '#title'         => t('Syslog Output Level'),
                                                   '#default_value' => $current_syslog_output_level,
                                                   '#options' => $form['so']['syslog_output_level_options']['#value'],
//                                                   '#required'      => TRUE,
                                                   '#description'   => t('Select lowest level to output to log'),
                                                   '#weight'        => 10,
                                                  );

        $default_file = fdvegan_Util::getDefaultFdveganSyslogOutputFile();
        $current_syslog_output_file = variable_get('fdvegan_syslog_output_file', $default_file);
        $form['so']['syslog_output_file'] = array('#type'          => 'textfield',
                                                  '#title'         => t('Syslog Output Filename'),
// Note - Never use a relative dirname here (like "../../syslog.txt") or #default_value will silently
//        fail, causing hook_validate() & hook_submit() to not be called, causing you hours of bug-hunting.
                                                  '#default_value' => $current_syslog_output_file,
                                                  '#size'          => 96,
                                                  '#maxlength'     => 255,
                                                  '#required'      => FALSE,
                                                  '#description'   => t('Defaults to: ') . $default_file . '<br>Must be absolute, not relative dir.',
                                                  '#weight'        => 20,
                                                 );

        $current_syslog_output_to_screen = variable_get('fdvegan_syslog_output_to_screen', 0);
        $form['so']['syslog_output_to_screen'] = array('#type'          => 'checkbox',
                                                       '#title'         => t('Syslog Also Output To Screen'),
                                                       '#default_value' => $current_syslog_output_to_screen,
                                                       '#required'      => FALSE,
                                                       '#description'   => t('You should never enable this in a Production environment!'),
                                                       '#weight'        => 30,
                                                      );

        $form['submit'] = array('#type'   => 'submit',
                                '#value'  => t('Save Configuration Settings'),
                                '#weight' => 50,
                               );

        return $form;
    }


    /**
     * Validation handler for fdvegan_admin_form().
     */
    function fdvegan_admin_form_validate($form, &$form_state) {

        if ($form_state['values']['media_files_max_num'] === '') {
            form_set_error('media_files_max_num', t('You must enter a valid number, or -1 for "no limit"'));
            return;
        }
        if ($form_state['values']['media_files_max_num'] < -1) {
            form_set_error('media_files_max_num', t('You must enter a positive number, or -1 for "no limit"'));
            return;
        }

        if (array_key_exists('syslog_output_file', $form_state['values'])) {
            if (!empty($form_state['values']['syslog_output_file'])) {
                if (!is_writable($form_state['values']['syslog_output_file'])) {
                    form_set_error('syslog_output_file', t('You must enter a valid Syslog Output Filename, or leave blank for default'));
                    return;
                }
            }
        }

        return TRUE;
    }


    /**
     * Submit handler for the fdvegan_admin form.
     *
     * @see fdvegan_admin_form()
     */
    function fdvegan_admin_form_submit($form, &$form_state) {

        $env_level = $form_state['values']['env_level'];
        $current_env_level = variable_get('fdvegan_env_level', 'PROD');
        if ($env_level !== $current_env_level) {
            variable_set('fdvegan_env_level', $env_level);
            $msg = "Env level successfully updated to \"{$env_level}\".";
            drupal_set_message($msg);
        }

        $mf_serve = $form_state['values']['media_files_serve'];
        $current_media_files_serve = variable_get('fdvegan_media_files_serve', 1);
        if ($mf_serve !== $current_media_files_serve) {
            variable_set('fdvegan_media_files_serve', $mf_serve);
            $msg = $mf_serve ? 'Media Files Local Serve enabled.' : 'Media Files Local Serve disabled.';
            drupal_set_message($msg);
        }

        $mf_max_num = (int)$form_state['values']['media_files_max_num'];
        $current_media_files_max_num = variable_get('fdvegan_media_files_max_num');  // 15 or less is strongly recommended
        if ($mf_max_num !== $current_media_files_max_num) {
            variable_set('fdvegan_media_files_max_num', $mf_max_num);
            $msg = "Media Files Max # set to {$mf_max_num}.";
            drupal_set_message($msg);
        }

        $mf_overwrite = $form_state['values']['media_filesystem_overwrite'];
        $current_media_filesystem_overwrite = variable_get('fdvegan_media_filesystem_overwrite', 0);
        if ($mf_overwrite !== $current_media_filesystem_overwrite) {
            variable_set('fdvegan_media_filesystem_overwrite', $mf_overwrite);
            $msg = $mf_overwrite ? 'Media Filesystem Overwrite enabled.' : 'Media Filesystem Overwrite disabled.';
            drupal_set_message($msg);
        }

        $level = $form_state['values']['syslog_output_level'];
        $current_syslog_output_level = variable_get('fdvegan_syslog_output_level') ?: 'LOG_ERR';
        if ($level !== $current_syslog_output_level) {
            variable_set('fdvegan_syslog_output_level', $level);
            $msg = "Syslog output level successfully updated to \"{$level}\".";
            drupal_set_message($msg);
        }

        $default_file = fdvegan_Util::getDefaultFdveganSyslogOutputFile();
        $file = $form_state['values']['syslog_output_file'];
        if (empty($file)) {
            $file = $default_file;
        }
        $current_syslog_output_file = variable_get('fdvegan_syslog_output_file', $default_file);
        if ($file !== $current_syslog_output_file) {
            variable_set('fdvegan_syslog_output_file', $file);
            $msg = "Syslog output file successfully updated to \"{$file}\".";
            drupal_set_message($msg);
        }

        $to_screen = $form_state['values']['syslog_output_to_screen'];
        $current_syslog_output_to_screen = variable_get('fdvegan_syslog_output_to_screen', 0);
        if ($to_screen !== $current_syslog_output_to_screen) {
            variable_set('fdvegan_syslog_output_to_screen', $to_screen);
            $msg = $to_screen ? 'Syslog enabled to also output to screen.' : 'Syslog disabled from outputting to screen.';
            drupal_set_message($msg);
        }
    }



    //////////////////////////////



    /**
     * Implementation of hook_form() for fdvegan_db_records_info_form().
     * Displays general counts and stats of records in the fdvegan database.
     */
    function fdvegan_db_records_info_form($form, &$form_state) {

        $content = <<<EOT
<h2>General data counts:</h2><br />
EOT;
        //
        // Run some useful queries.
        //

        // Count number of actors.

        $persons_collection = new fdvegan_PersonCollection();
        $persons_collection->loadPersons();
        $total_num_actors = $persons_collection->count();

        $options = array('HavingTmdbId' => TRUE);  // no sense in trying to load any actors not in TMDb
        $persons_collection = new fdvegan_PersonCollection($options);
        $persons_collection->loadPersonsArray();
        $num_tmdb_actors = $persons_collection->count();

        $content .= '<span class="data-label">Total number of actors in the FDV DB:</span>';
        $content .= '<span class="data-value">' . $total_num_actors . '</span><br />' . "\n";
        $content .= '<span class="data-label">Number of actors with TMDb info:</span>';
        $content .= '<span class="data-value">' . $num_tmdb_actors . '</span><br />' . "\n";
        $content .= "<br />\n";

        $movies_collection = new fdvegan_MovieCollection();
        $movies_collection->loadMoviesArray();
        $total_num_movies = $movies_collection->count();

        $movies_collection = new fdvegan_MovieCollection($options);
        $movies_collection->loadMoviesArray();
        $num_tmdb_movies = $movies_collection->count();
        $content .= '<span class="data-label">Total number of movies in the FDV DB:</span>';
        $content .= '<span class="data-value">' . $total_num_movies . '</span><br />' . "\n";
        $content .= '<span class="data-label">Number of movies with TMDb info:</span>';
        $content .= '<span class="data-value">' . $num_tmdb_movies . '</span><br />' . "\n";
        $options = array('HavingFDVCountGT' => 1);  // only load movies with fdv_count of 2 or more
        $movies_collection2 = new fdvegan_MovieCollection($options);
        $movies_collection2->loadMoviesArray();
        $num_fdv_movies = $movies_collection2->count();
        $content .= '<span class="data-label">Number of movies with multiple veg*n actors:</span>';
        $content .= '<span class="data-value">' . $num_fdv_movies . '</span><br />' . "\n";
        $content .= "<br />\n";

        $connection = new fdvegan_Connection($options);
        $result = $connection->getStats();
        $content .= '<span class="data-label">Longest Actor Network connections list:</span>';
        $content .= '<span class="data-value">' . $result['max'] .
                    ' chars of max ' . $result['max_possible'] . ' chars</span><br />' . "\n";
        $class = '';
        if ($result['max_possible'] > $result['max_limit'] - 100) {
            $content .= "<span class=\"red\">You may need to increase the hard-limit setting in fdvegan_PersonCollection soon!</span>";
            $content .= "<br />\n";
            $class = ' bold red';
        }
        $content .= "<span class=\"data-label{$class}\">Hard limit setting:</span>";
        $content .= "<span class=\"data-value{$class}\">{$result['max_limit']} chars</span><br />\n";
        $content .= "<br />\n";

        $content .= '<br /><br />';
        $form['intro'] = array(
            '#markup' => $content,
        );

        return $form;
    }


    /**
     * Validation handler for fdvegan_db_records_info_form().
     */
    function fdvegan_db_records_info_form_validate($form, &$form_state) {
        // print "\n<br><br>DEBUG(".__FILE__."):<br>\n<pre>" . print_r($form_state['values'],1) . "</pre>\n";  die();
        return TRUE;
    }


    /**
     * Submit handler for the fdvegan_db_records_info form.
     *
     * @see fdvegan_db_records_info_form()
     */
    function fdvegan_db_records_info_form_submit($form, &$form_state) {
    }



    //////////////////////////////



    /**
     * Implementation of hook_form() for fdvegan_initial_dir_creation_form().
     * Creates the FDVegan TMDB public file directories, if they don't already exist.
     */
    function fdvegan_initial_dir_creation_form($form, &$form_state) {

        // Create the FDVegan user_picture_path public photo directory, if it doesn't already exist.
        $ret = _fdvegan_install_create_profile_dir();

        // Create the FDVegan TMDB public file directories, if they don't already exist.
        $ret = fdvegan_Media::install_create_dirs();
        if ($ret === 2) {
            $content = 'Initial TMDb directories already exist properly.';
        } elseif ($ret === TRUE) {
            $content = 'Initial TMDb directories created successfully.';
        } else {
            $content = 'Could not create all TMDb directories and/or files.';
        }
        $content .= '<br /><br />';

        // Create the FDVegan copy-local file directories, if they don't already exist.
        $ret = fdvegan_Media::install_create_copy_dirs();
        if ($ret === 2) {
            $content .= 'Initial copy-local directories already exist properly.';
        } elseif ($ret === TRUE) {
            $content .= 'Initial copy-local directories created successfully.';
        } else {
            $content .= 'Could not create all copy-local directories.';
        }
        $content .= '<br /><br />';

        $form['intro'] = array(
            '#markup' => $content,
        );

        return $form;
    }


    /**
     * Validation handler for fdvegan_initial_dir_creation_form().
     */
    function fdvegan_initial_dir_creation_form_validate($form, &$form_state) {
        // print "\n<br><br>DEBUG(".__FILE__."):<br>\n<pre>" . print_r($form_state['values'],1) . "</pre>\n";  die();
        return TRUE;
    }


    /**
     * Submit handler for the fdvegan_initial_dir_creation form.
     *
     * @see fdvegan_admin_form()
     */
    function fdvegan_initial_dir_creation_form_submit($form, &$form_state) {
    }



    //////////////////////////////



    /**
     * Implementation of hook_form() for fdvegan_clear_caches_form().
     * Clears all Drupal caches, including fdvegan variables.
     *
     * This fdvegan functionality was created since Drupal 7 has no hook for clearing caches.
     * However, Drupal 8 does.
     *
     * @see /admin/config/development/performance
     */
    function fdvegan_clear_caches_form($form, &$form_state) {

        // Invoke the normal Admin -> Performance -> Clear all caches
        drupal_flush_all_caches();

        // Also, clear the necessary fdvegan variables:
        variable_set('fdvegan_min_movies_array', NULL);
        variable_set('fdvegan_min_persons_array', NULL);
        variable_set('fdvegan_all_tags', NULL);

        $content = 'Standard Drupal caches flushed, and FDVegan variables cleared successfully.';
        $content .= '<br /><br />';
        $form['intro'] = array(
            '#markup' => $content,
        );

        return $form;
    }


    /**
     * Validation handler for fdvegan_clear_caches_form().
     */
    function fdvegan_clear_caches_form_validate($form, &$form_state) {
        // print "\n<br><br>DEBUG(".__FILE__."):<br>\n<pre>" . print_r($form_state['values'],1) . "</pre>\n";  die();
        return TRUE;
    }


    /**
     * Submit handler for the fdvegan_clear_caches form.
     *
     * @see fdvegan_admin_form()
     */
    function fdvegan_clear_caches_form_submit($form, &$form_state) {
    }



    //////////////////////////////



    /**
     * Ensure the FDVegan user_picture_path public photo directory is created properly.
     * This directory is where all users' profile images are stored.
     * This function is safe to call multiple times before and after the dir is created.
     * @return int    FALSE = dir failure, TRUE = successfully created, 2 = dir already existed correctly
     */
    function _fdvegan_install_create_profile_dir() {

        $ret_val = 2;

        /* Special "install" case:
         * We need to set the global user_picture_path once.
         * We arbitrarily do this here, instead of in fdvegan.install::fdvegan_install()
         */
        $prev_path = variable_get('user_picture_path');
        $new_path = 'pictures/users';
        if ($prev_path !== $new_path) {
            $msg = "Updated user_picture_path from \"{$prev_path}\" to \"{$new_path}\"";
            drupal_set_message($msg, 'warning');
            fdvegan_Content::syslog('LOG_WARNING', $msg);
        }
        variable_set('user_picture_path', $new_path);

        //
        // Now, actually create the dir if it doesn't already exist:
        //
        $abs_profile_dir = DRUPAL_ROOT . '/' .
                           variable_get('file_public_path', conf_path() . '/files') .
                           '/' . $new_path;
        $abs_profile_dir = str_replace('/', DIRECTORY_SEPARATOR, $abs_profile_dir);  // works for both Linux and Windows
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN abs_profile_dir=\"{$abs_profile_dir}\".");
        $ret = file_prepare_directory($abs_profile_dir, 0);
        if (!$ret) {
            $ret = file_prepare_directory($abs_profile_dir, FILE_CREATE_DIRECTORY);
            if ($ret) {
                $ret_val = TRUE;
                $msg = "user_picture_path dir created successfully at \"{$abs_profile_dir}\".";
                drupal_set_message($msg, 'status');
                fdvegan_Content::syslog('LOG_NOTICE', $msg);
            } else {
                $ret_val = FALSE;
                $msg = "Could not create user_picture_path dir \"{$abs_profile_dir}\".";
                drupal_set_message($msg, 'error');
                fdvegan_Content::syslog('LOG_ERR', $msg);
            }
        }

        fdvegan_Content::syslog('LOG_DEBUG', "END abs_profile_dir=\"{$abs_profile_dir}\".");
        return $ret_val;
    }
