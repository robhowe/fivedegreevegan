<?php
/**
 * fdvegan_util.php
 *
 * Implementation of Util class for module fdvegan.
 * Miscellaneous utility and helper functions.
 *
 * PHP version 5.6
 *
 * @category   Util
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */


class fdvegan_Util
{
    // Used by fdvegan_Content::syslog()
    public static $syslog_levels = array('LOG_DEBUG'   => 1,
                                         'LOG_INFO'    => 2,
                                         'LOG_NOTICE'  => 3,
                                         'LOG_WARNING' => 4,
                                         'LOG_ERR'     => 5,
                                         'LOG_CRIT'    => 6,
                                         'LOG_ALERT'   => 7,
                                         'LOG_EMERG'   => 8,
                                         'No Logging'  => 99,
                                        );

    public static $env_levels = array('LOCAL' => 1,
                                      'DEV'   => 2,
                                      'INT'   => 3,
                                      'TEST'  => 4,
                                      'STAGE' => 5,
                                      'PROD'  => 6,
                                     );

    /* These options dictate what featured actors are displayed on the Actor Network graph.
     * Since we have over 200 (275+) actors that are vegan, we don't bother including
     * any vegetarian or veg-friendly actors in the "featured list".
     * We also sort by 'rating' so we only include the most popular actors.
     *
     * @see fdvegan_batch_process.php::fdvegan_recalculate_degrees_batch()
     * @see fdvegan_batch_load_connections.php::fdvegan_recalculate_degrees_batch_process()
     * fdvegan_batch_process.php::fdvegan_recalculate_degrees_batch()
     */
    public static $connections_options = array('HavingTmdbId' => TRUE,  // No sense in trying to load any actors not in TMDb.
                                               'HavingFDVCountGT' => '0',
                                               'HavingTags'   => array('vegan'),
                                               'SortBy'       => 'rating',
                                               'SortByDir'    => 'DESC',
                                               'Limit'        => 150,
                                              );

    private static $_default_fdvegan_syslog_output_file;


    /**
     * Get the maximum # of degrees the current user is allowed to search on.
     *  This is mainly used by the Actor Network & Actor Tree graphs,
     *  since querying for higher degrees is very processor intensive.
     *
     * @return int $degrees    Maximum # of degrees this user may search for.
     */
    public static function getMaxAllowedDegrees()
    {
        $max_degrees = 0;
        if (user_access('view fdvegan')) {
            $max_degrees = 1;
        }
        if (user_access('use fdvegan')) {
            $max_degrees = 2;
        }
        if (user_is_logged_in()) {
            $max_degrees = 3;
        }
        if (user_access('pro fdvegan')) {
            $max_degrees = 5;
        }
        return $max_degrees;
    }


    /**
     * Get the maximum # of nodes the current user is allowed to search to.
     *  This is mainly used by the Actor Tree graph,
     *  since querying to higher node depths is very processor intensive.
     *
     * @return int $node_depth    Maximum # of nodes this user may search to.
     */
    public static function getMaxAllowedNodeDepth()
    {
        return self::getMaxAllowedDegrees() * 2 + 1;
    }


    /**
     * Get the absolute filename of the Fdvegan Syslog Output File.
     * Returns a filesystem dirname that is not URL-friendly.
     *
     * Eg. on Prod this is: /home/mobrksco/private/fivedegreevegan.aprojects.org/files/fdvegan_syslog.txt
     *     on Dev this is: D:\Internet\htdocs\GreenGeeks\private\fivedegreevegan.aprojects.org\files\fdvegan_syslog.txt
     */
    public static function getDefaultFdveganSyslogOutputFile()
    {
        if (!isset(self::$_default_fdvegan_syslog_output_file)) {
            $str = drupal_realpath(variable_get('file_private_path')) . '/fdvegan_syslog.txt';
            self::$_default_fdvegan_syslog_output_file = str_replace('/', DIRECTORY_SEPARATOR, $str);  // works for both Linux and Windows
        }
        return self::$_default_fdvegan_syslog_output_file;
    }


    /**
     * Get the standard URL for images hosted by this site.
     *
     * @param string $filename    Any filename.
     * @return string  URL.
     */
    public static function getStandardImageUrl($filename = '')
    {
        return variable_get('file_public_path', conf_path()) . "/pictures/{$filename}";
    }


    public static function isValidDate($date, $format = 'Y-m-d', $strict = true)
    {
        $dateTime = DateTime::createFromFormat($format, $date);
        if (($dateTime !== FALSE) && $strict) {
            $errors = DateTime::getLastErrors();
            if (!empty($errors['warning_count'])) {
                return FALSE;
            }
        }
        return $dateTime && ($dateTime->format($format) == $date);
    }


    /**
     * Convert a number of minutes into a human-readable string.
     *   E.g.:  125 ==> 2 hours 5 minutes
     *
     * @param int $minutes    Any number of minutes.
     * @return string    A human-readable string ready to display.
     */
    public static function convertMinutesToHumanReadable($minutes)
    {
        $h = (int)($minutes / 60);
        $m = (int)($minutes - $h*60);
        return ($h ? ($h . (($h == 1) ? ' hour ' : ' hours ')) : '') . ($m ? ($m . (($m == 1) ? ' minute' : ' minutes')) : '');
    }


    /**
     * @param $string
     * @return mixed
     */
    public static function escapeLikeString($string)
    {
        $search  = array('%', '_');
        $replace = array('\%', '\_');
        return str_replace($search, $replace, $string);
    }

    /**
     * Convert a search string to a MySQL-ready wildcarded string.
     *   E.g.:  "Nat Portman" ==> "%nat%portman%"
     *
     * @param string $search_str    A person's full name or a movie title.
     * @return string    MySQL-ready wildcarded query string.
     */
    public static function convertSearchStrToWildcarded($search_str)
    {
        $search_str_wildcarded = ' ' . strtolower(trim($search_str)) . ' ';
        $search_str_wildcarded = self::escapeLikeString($search_str_wildcarded);
        if (user_is_logged_in()) {
            // Allow authenticated users to use a "*" as a wildcard.
            $search_str_wildcarded = str_replace('*', '%', $search_str_wildcarded);
        } else {
            $search_str_wildcarded = str_replace('*', '', $search_str_wildcarded);
        }
        $search_str_wildcarded = str_replace(' ', '%', $search_str_wildcarded);
        $search_str_wildcarded = str_replace('%%', '%', $search_str_wildcarded);

        return $search_str_wildcarded;
    }


    /**
     * This method is now deprecated.  It was used by self::getSafeName() when making calls to
     * iconv() which could generate a PHP E_NOTICE
     *
     * Usage:
     *  set_error_handler(array(__CLASS__, 'customErrorHandler'));
     *  [your code]
     *  restore_error_handler();
     */
    public static function customErrorHandler($errno, $errstr) {
        $syslogLevel = 'LOG_ERR';
        if ($errno == E_NOTICE ) {
            $syslogLevel = 'LOG_WARNING';
        }
        $backtrace = self::debug_string_backtrace(3);  // We only need the last 3 levels of backtrace
        fdvegan_Content::syslog($syslogLevel, "PHP ERROR({$errno}) {$errstr}: " . print_r($backtrace,1));
    }


    /**
     * Convert a filename to a filesystem-safe string.
     * Generally for use with fdvegan_Media filenames.
     *
     * @see fdvegan_batch_process.php::fdvegan_copy_local_person_media_batch_process()
     * @param string $filename    A desired filename.
     * @return string  Full filename for use on the filesystem.
     */
    public static function getSafeFilename($filename)
    {
        $filename = self::getSafeName($filename);

        // Convert any slashes to dashes:
        $filename = mb_ereg_replace('\/', '-', $filename);
        // Remove any characters that are invalid in filenames:
        $filename = mb_ereg_replace('\\:*?\"<>|', '', $filename);
        // Remove any runs of periods:
        $filename = mb_ereg_replace("([\.]{2,})", '', $filename);

        // Some filenames (created from movie titles) are just too long.
        // A bit of a hack, but this works for our purposes:
        if (strlen($filename) > 90) {
            $filename = substr($filename, 0, 60) . '...' . substr($filename, -20);
        }

        return $filename;
   }


    /**
     * Convert a person or movie name string to a safe (searchable) string.
     * Basically since TMDb can return unsearchable names like "María Celeste Arrarás" and "Mýa".
     *
     * @param string $name    A desired name.
     * @return string  Safe name for use in our database and for searching.
     */
    public static function getSafeName($name)
    {
        $name = mb_ereg_replace('É', 'E', $name);  // Just one annoying special case, since É translates to 'E
        $name = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $name);  // Remove any accented characters.

        // Remove anything which isn't a word, whitespace, number
        // or any of the following characters -_`~,;.'!@#$%^&=+/[]{}()
        // If you don't need to handle multi-byte characters
        // you can use preg_replace rather than mb_ereg_replace
        $name = mb_ereg_replace("([^\w\s\d\-_`~,;.'!@#$%^&=+/\[\]\{\}\(\)])", '', $name);

        return $name;
   }


    /**
     * Get a human-readable string given a SQL query string.
     * This method is used for debugging output to syslog.
     *
     * @param  string $sql  A Drupal-ready query string
     * @return  string
     */
    public static function getRenderedQuery($sql, $args_array=NULL) {
        global $databases;
        $db_prefix = isset($databases['default']['default']['prefix']) ? $databases['default']['default']['prefix'] : '';
        $rendered_sql = str_replace("{", $db_prefix, $sql);
        $rendered_sql = str_replace("}", '', $rendered_sql);
        if (!empty($args_array)) {
            foreach($args_array as $key => $val) {
                $val = $val ? $val : 'NULL';
                $rendered_sql = str_replace($key, "'".$val."'", $rendered_sql);
            }
        }
        return $rendered_sql . ';';
    }


    /**
     * Check if this process is being run from a user's browser (then Apache),
     * or if it's a batch / cronjob / command-line script.
     */
    public static function isRunningAsScript() {
        return ((!strcmp($_SERVER['REMOTE_ADDR'], '127.0.0.1')) && 
                (!strcmp($_SERVER['HTTP_USER_AGENT'], 'console'))
               );
    }


    /**
     * Check proposed environment level against current env level.
     */
    public static function isEnvLTE($env_level) {
        $env_int = self::$env_levels[$env_level];
        if (!$env_int) {
            throw new FDVegan_InvalidArgumentException("Invalid Env Level \"{$env_level}\"");
        }
        return $env_int <= self::$env_levels[variable_get('fdvegan_env_level', 'PROD')];
    }


    /**
     * Create a unique GUID (Globally Unique IDentifier).
     */
    public static function createGUID() {
        if (function_exists('com_create_guid')){
            $uuid = com_create_guid();
        } else {
            mt_srand((double)microtime()*10000);  // optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);  // "-"
            $uuid = chr(123)  // "{"
                . substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid,12, 4) . $hyphen
                . substr($charid,16, 4) . $hyphen
                . substr($charid,20,12)
                . chr(125);  // "}"
        }
        return trim($uuid, '{}');
    }


    /**
     * Run a process in the background without waiting for any output
     * or hanging a user's browser.
     * This should be called when executing particularly long-running
     * batch processes that would otherwise time-out a user's browser.
     *
     * Example usage:  fdvegan_Util::execInBackground('./myScript.php');
     * Example usage:  fdvegan_Util::execInBackground('fdvegan_script_init_load.php');
     * Example usage:  fdvegan_Util::execInBackground('fdvegan_script_load_connections.php');
     */
    public static function execInBackground($cmd) {
        $cmdPath = DRUPAL_ROOT . '/' . drupal_get_path('module', 'fdvegan') . '/';
        $full_cmd = $cmdPath . $cmd;
        $cmdArray = explode(' ', trim($cmd));
        // Executable file must be chmod 754 i.e.: -rwxr-xr--
        //clearstatcache();
        $octalPerms = decoct(fileperms($cmdPath . $cmdArray[0]) & 0777);
        if ($octalPerms !== 754) {
            fdvegan_Content::syslog('LOG_ERR', "cmd '{$cmdArray[0]}' needs permissions({$octalPerms}) set to 754.");
        }

        // if (substr(php_uname('s'), 0, 7) === "Windows") {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('where php.exe', $php_exec_path, $err);
            if ($err || empty($php_exec_path)) {
                fdvegan_Content::syslog('LOG_ERR', "Could not find path to php.exe on Windows.");
//                $php_exec_path = 'D:\Internet\Bitnami\wampstack-5.6.23-1\apache2\bin\php.exe';
            }
//            $cmd = $php_exec_path . ' D:\Internet\htdocs\GreenGeeks\www\fivedegreevegan.aprojects.org\drupal\sites\all\modules\custom\fdvegan\fdvegan_script_init_load.php';
//            $cmd = $php_exec_path . ' ' . DRUPAL_ROOT . DIRECTORY_SEPARATOR . drupal_get_path('module', 'fdvegan') . DIRECTORY_SEPARATOR . 'fdvegan_script_init_load.php';
            $final_cmd = $php_exec_path[0] . ' ' . $full_cmd;
            $final_cmd = str_replace('/', DIRECTORY_SEPARATOR, $final_cmd);  // works for both Linux and Windows

            fdvegan_Content::syslog('LOG_NOTICE', "Initiated execInBackground('{$final_cmd}') for Windows.");
            pclose(popen("start /B ". $final_cmd, "r"));
        } else {
            $final_cmd = 'php ' . $full_cmd;
            fdvegan_Content::syslog('LOG_NOTICE', "Initiated execInBackground('{$final_cmd}') for Linux.");
            exec($final_cmd . " > /dev/null &");
        }
    }


    /**
     * Called from fdvegan.install::hook_install()
     */
    public static function installVariables()
    {
        // Make sure these match what's in self::uninstallVariables()

        variable_set('fdvegan_env_level', 'PROD');
        variable_set('fdvegan_syslog_output_level', 'LOG_ERR');
        variable_set('fdvegan_syslog_output_file', self::getDefaultFdveganSyslogOutputFile());
        variable_set('fdvegan_syslog_output_to_screen', 0);
        variable_set('fdvegan_media_files_serve', 1);
        variable_set('fdvegan_media_files_dir', fdvegan_Media::getMediaAbsDir());  // includes the initial "/tmdb" part
        variable_set('fdvegan_media_files_max_num', 10);  // 15 or less is strongly recommended
        variable_set('fdvegan_media_filesystem_overwrite', 0);
        variable_set('fdvegan_tmdb_config', array());
        variable_set('fdvegan_min_movies_array', NULL);
        variable_set('fdvegan_min_persons_array', NULL);
        variable_set('fdvegan_all_tags', NULL);
    }

    /**
     * Called from fdvegan.install::hook_uninstall()
     */
    public static function uninstallVariables()
    {
        // Make sure these match what's in self::installVariables()

        variable_del('fdvegan_env_level');
        variable_del('fdvegan_syslog_output_level');
        variable_del('fdvegan_syslog_output_file');
        variable_del('fdvegan_syslog_output_to_screen');
        variable_del('fdvegan_media_files_serve');
        variable_del('fdvegan_media_files_dir');
        variable_del('fdvegan_media_files_max_num');
        variable_del('fdvegan_media_filesystem_overwrite');
        variable_del('fdvegan_tmdb_config');
        variable_del('fdvegan_min_movies_array');
        variable_del('fdvegan_min_persons_array');
        variable_del('fdvegan_all_tags');
    }


    /**
     * Convenience function for use with drupal_match_path()
     *
     * @see best_responsive_sub/template.php::fdvegan_is_current_page()
     *
     * @param mixed $path    A string of one path, or array of path strings.
     * @throws FDVegan_InvalidArgumentException
     * @return bool    TRUE if current page is in given path list.
     */
    public static function isCurrentPage($path) {
        if (is_string($path)) {
            $pattern_string = $path;
        } else if (is_array($path)) {
            $pattern_string = implode(PHP_EOL, $path);
        } else {
            throw new FDVegan_InvalidArgumentException("fdvegan_Util::isCurrentPage() invalid type");
        }
        return drupal_match_path(current_path(), $pattern_string);
    }


    public static function pinfo() {
        ob_start();
        phpinfo();
        $data = ob_get_contents();
        ob_end_clean();
        return $data;
    }


    public static function debug_string_backtrace($limit=0) {
        ob_start();
        debug_print_backtrace(0, $limit);
        $trace = ob_get_contents();
        ob_end_clean();

        // Remove first item from backtrace as it's this function, which is redundant.
        $trace = preg_replace('/^#0\s+' . __METHOD__ . "[^\n]*\n/", '', $trace, 1);
        // Renumber backtrace items.
        $trace = preg_replace_callback('/^#(\d+)/m',
                                       function ($matches) { return '#' . ($matches[1] - 1); },
                                       $trace);
        return $trace;
    }



    //////////////////////////////



}

