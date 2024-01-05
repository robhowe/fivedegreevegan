<?php
/**
 * fdvegan_media.php
 *
 * Implementation of Media class for module fdvegan.
 *
 * PHP version 5.6
 *
 * @category   Media
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.8
 */


class fdvegan_Media extends fdvegan_BaseClass
{
    protected static $_media_rel_dir;  // relative root dir of the media directory

    protected $_media_id       = NULL;
    protected $_movie_id       = NULL;
    protected $_person_id      = NULL;
    protected $_media_type     = NULL;  // person, movie, moviebackdrop, movievideo
    protected $_media_size     = NULL;  // small, medium, large, original
    protected $_order_num      = NULL;
    protected $_local_filename = NULL;
    protected $_external_url   = NULL;
    protected $_source         = NULL;
    protected $_rating         = NULL;
    protected $_media_date     = NULL;
    protected $_descr          = NULL;
    protected $_height         = NULL;
    protected $_width          = NULL;

    protected $_local_path     = NULL;
    protected $_abs_filename   = NULL;


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if (!$this->getRefreshFromTmdb() && !$this->getScrapeFromTmdb()) {
            if (!empty($options['MediaId'])) {
                $this->_loadMediaByMediaId();
            } elseif (substr($this->getMediaType(), 0, 5) === 'movie') {  // movie_id of 0 == default blank image
                $this->_loadMediaByMovieId();
            } elseif (isset($options['PersonId'])) {  // person_id of 0 == default blank image
                $this->_loadMediaByPersonId();
            }
        }
    }


    /**
     * Get the absolute root dir of the media directory.
     * Returns a filesystem rel dir that is not URL-friendly.  You must convert the slashes before using as a rel URL.
     *
     * Eg. on Prod this is: /home/mobrksco/public_html/fivedegreevegan.aprojects.org/drupal/sites/default/files/tmdb
     *     on Dev this is: D:\Internet\htdocs\GreenGeeks\www\fivedegreevegan.aprojects.org\drupal\sites\default\files\tmdb
     */
    public static function getMediaAbsDir()
    {
        $str = DRUPAL_ROOT . '/' . self::getMediaRelDir();
        $str = realpath(str_replace('/', DIRECTORY_SEPARATOR, $str));  // To collapse any superfluous "../.." references
        return str_replace('/', DIRECTORY_SEPARATOR, $str);  // works for both Linux and Windows
    }

    /**
     * Get the relative root dir of the media directory.
     * The dir structure is initially created via the Admin page.
     *
     * @see  self::install_create_dirs()
     */
    public static function getMediaRelDir()
    {
        if (!isset(self::$_media_rel_dir)) {
            $str = variable_get('file_public_path', conf_path() . '/files') . '/tmdb';
            self::$_media_rel_dir = str_replace('/', DIRECTORY_SEPARATOR, $str);  // works for both Linux and Windows
        }
        return self::$_media_rel_dir;
    }


    /**
     * Get the absolute root dir of the copy-local directory.
     * Returns a filesystem rel dir that is not URL-friendly.  You must convert the slashes before using as a rel URL.
     *
     * Eg. on Prod this is: /home/mobrksco/private/fivedegreevegan.aprojects.org/tmdb
     *     on Dev this is: D:\Internet\htdocs\GreenGeeks\private\fivedegreevegan.aprojects.org\tmdb
     */
    public static function getMediaCopyAbsDir()
    {
        $str = DRUPAL_ROOT . '/' . self::getMediaCopyRelDir();
        $str = realpath(str_replace('/', DIRECTORY_SEPARATOR, $str));  // To collapse any superfluous "../.." references
        return str_replace('/', DIRECTORY_SEPARATOR, $str);  // works for both Linux and Windows
    }

    /**
     * Get the relative root dir of the copy-local directory.
     * The dir structure is initially created via the Admin page.
     *
     * @see  self::install_create_copy_dirs()
     */
    public static function getMediaCopyRelDir()
    {
        $str = variable_get('file_private_path') . '/tmdb';
        $media_rel_dir = str_replace('/', DIRECTORY_SEPARATOR, $str);  // works for both Linux and Windows
        return $media_rel_dir;
    }


    public function setMediaId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_media_id = (int)$value;
        }
        return $this;
    }

    public function getMediaId()
    {
        return $this->_media_id;
    }


    public function setMovieId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_movie_id = ($value === NULL) ? NULL : (int)$value;
        }
        return $this;
    }

    public function getMovieId()
    {
        return $this->_movie_id;
    }


    public function setPersonId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_person_id = ($value === NULL) ? NULL : (int)$value;
        }
        return $this;
    }

    public function getPersonId()
    {
        return $this->_person_id;
    }


    public function setMediaType($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_media_type = substr($value, 0, 20);
        }
        return $this;
    }

    public function getMediaType()
    {
        return $this->_media_type;
    }


    public function setMediaSize($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_media_size = substr($value, 0, 1);
        }
        return $this;
    }

    public function getMediaSize()
    {
        return $this->_media_size;
    }


    public function setOrderNum($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_order_num = (int)$value;
        }
        return $this;
    }

    public function getOrderNum()
    {
        if (!isset($this->_order_num)) {
            return 1;
        }
        return $this->_order_num;
    }


    private function setLocalFilename($value)  // private since nothing outside this class should be setting this directly
    {
        $this->_local_filename = substr($value, 0, 254);
        return $this;
    }

    public function getLocalFilename()
    {
        $id = $this->getPersonId();
        if (substr($this->getMediaType(), 0, 5) === 'movie') {  // movie_id of 0 == default blank image
            $id = $this->getMovieId();
        }
        $name = "{$this->getMediaType()}-{$this->getMediaSize()}-{$id}-{$this->getOrderNum()}.jpg";
        return $name;
    }


    public function setExternalUrl($value)
    {
        $this->_external_url = substr($value, 0, 254);
        return $this;
    }

    public function getExternalUrl()
    {
        return $this->_external_url;
    }


    public function setSource($value)
    {
        $this->_source = substr($value, 0, 1023);
        return $this;
    }

    public function getSource()
    {
        return $this->_source;
    }


    public function setRating($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_rating = (int)$value;
        }
        return $this;
    }

    public function getRating()
    {
        return $this->_rating;
    }


    public function setMediaDate($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_media_date = $value;
        }
        return $this;
    }

    public function getMediaDate()
    {
        return fdvegan_Util::isValidDate($this->_media_date) ? $this->_media_date : NULL;
    }


    public function setDescr($value)
    {
        $this->_descr = substr($value, 0, 1023);
        return $this;
    }

    public function getDescr()
    {
        return $this->_descr;
    }


    public function setHeight($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_height = (int)$value;
        }
        return $this;
    }

    public function getHeight()
    {
        return $this->_height;
    }


    public function setWidth($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_width = (int)$value;
        }
        return $this;
    }

    public function getWidth()
    {
        return $this->_width;
    }


    /**
     * "Path" functions are used to return URLs.
     * For filesystem/directory names, use the "Dir" & "Filename" functions.
     *
     * @return string  URL for the local/relative path to this image.
     */
    public function getLocalPath()
    {
        $str = self::getMediaRelDir() . "/{$this->getMediaType()}/{$this->getMediaSize()}/{$this->getLocalFilename()}";
        $this->_local_path = str_replace(DIRECTORY_SEPARATOR, '/', $str);  // convert to URL-style
        return $this->_local_path;
    }


    /**
     * "Path" functions are used to return URLs.
     * For filesystem/directory names, use the "Dir" & "Filename" functions.
     *
     * @see  fdvegan.admin.php::fdvegan_admin_form()
     * @return string  best URL for the (local or external) path to this image.
     */
    public function getPath()
    {
        $localPath    = $this->getLocalPath();
        $externalPath = $this->getExternalUrl();
        if (variable_get('fdvegan_media_files_serve')) {
            return $localPath ?: $externalPath;  // either way, fallback to other serve if first isn't available
        } else {
            return $externalPath ?: $localPath;  // either way, fallback to other serve if first isn't available
        }
    }


    /**
     * "Filename" functions are used to return filesystem directory filenames.
     * For URLs, use the "Path" functions.
     *
     * @return string  Full filename for this image on the filesystem.
     */
    public function getAbsFilename()
    {
        $str = self::getMediaAbsDir() . "/{$this->getMediaType()}/{$this->getMediaSize()}/{$this->getLocalFilename()}";
        $this->_abs_filename = str_replace(DIRECTORY_SEPARATOR, '/', $str);  // works for both Linux and Windows
        return $this->_abs_filename;
   }


    public function storeMedia()
    {
        if (($this->getMovieId() === 0) || ($this->getPersonId() === 0)) {
            // Stop-gap to not actually delete any "default" media loaded.
            fdvegan_Content::syslog('LOG_NOTICE', "Request ignored to store default media record by mediaId={$this->getMediaId()}.");
            return $this->getMediaId();
        }

        if ($this->getMediaId()) {  // Must already exist in our DB, so is an update.

 throw new Exception("storeMedia({$this->getMediaId()}) update not implemented yet");

            $sql = <<<__SQL__
UPDATE {fdvegan_media} SET 
       `movie_id`  = :movie_id, 
       `person_id` = :person_id, 
       `updated`   = now() 
 WHERE `media_id` = :media_id
__SQL__;
            try {
                $sql_params = array(':media_id'  => $this->getMediaId(),
                                    ':movie_id'  => $this->getMovieId(),
                                    ':person_id' => $this->getPersonId(),
                                   );
                //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
                $result = db_query($sql, $sql_params);
            } catch (Exception $e) {
                fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while UPDATing media: '. print_r($this,1));
                throw $e;
            }
            fdvegan_Content::syslog('LOG_DEBUG', 'Updated media in our DB: media_id='. $this->getMediaId() .', type="'. $this->getMediaType() . '"');

        } else {  // Must be a new media record to our DB, so is an insert.

            try {
                $this->_media_id = db_insert('fdvegan_media')
                ->fields(array(
                  'movie_id'       => $this->getMovieId(),
                  'person_id'      => $this->getPersonId(),
                  'media_type'     => $this->getMediaType(),
                  'media_size'     => $this->getMediaSize(),
                  'order_num'      => $this->getOrderNum(),
                  'local_filename' => $this->getLocalFilename(),
                  'external_url'   => $this->getExternalUrl(),
                  'source'         => $this->getSource(),
                  'rating'         => $this->getRating(),
                  'media_date'     => $this->getMediaDate(),
                  'descr'          => $this->getDescr(),
                  'height'         => $this->getHeight(),
                  'width'          => $this->getWidth(),
                  'created'        => $this->getCreated(),
                  'synced'         => $this->getSynced(),
                ))
                ->execute();
            } catch (Exception $e) {
                fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while INSERTing media: '. print_r($this,1));
                throw $e;
            }
            fdvegan_Content::syslog('LOG_DEBUG', "Inserted new media into our DB: media_id={$this->getMediaId()}, type='{$this->getMediaType()}'.");
        }

        return $this->getMediaId();
    }


    /**
     * Delete this record out of the FDV DB!
     * This also deletes the corresponding media file on the filesystem (if present)!
     */
    public function delete()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN delete({$this->getMediaId()}) movieId={$this->getMovieId()}, personId={$this->getPersonId()}.");
        if (($this->getMovieId() === 0) || ($this->getPersonId() === 0)) {
            // Stop-gap to not actually delete any "default" media loaded.
            fdvegan_Content::syslog('LOG_DEBUG', "Request ignored to delete default media record and file by mediaId={$this->getMediaId()}.");
            return TRUE;
        }
        if (empty($this->getMediaId())) {
            fdvegan_Content::syslog('LOG_WARNING', "Could not delete media from FDV DB by media_id={$this->getMediaId()}.");
            return FALSE;
        }

        if (empty($this->getLocalFilename())) {
            fdvegan_Content::syslog('LOG_WARNING', "Could not delete media from filesystem by media_id={$this->getMediaId()}.");
        } else {
            if (variable_get('fdvegan_media_filesystem_overwrite', 0)) {  // set in fdvegan.admin.inc::fdvegan_admin_form()
                $ret_value = file_exists($this->getAbsFilename()) ? unlink($this->getAbsFilename()) : TRUE;  // delete this file from the filesystem
            } else {
                $ret_value = TRUE;
            }
        }

        $sql = <<<__SQL__
DELETE FROM {fdvegan_media} WHERE {fdvegan_media}.`media_id` = :media_id
__SQL__;
        $sql_params = array(':media_id' => $this->getMediaId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        fdvegan_Content::syslog('LOG_DEBUG', "END delete({$this->getMediaId()}) movieId={$this->getMovieId()}, personId={$this->getPersonId()}.");

        return $ret_value;
    }


    /**
     * Scrape this record's media file from TMDb.
     * This creates the media file on our filesystem.
     */
    public function scrapeFromTmdb()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN scrapeFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getOrderNum()}).");

        $overwrite = variable_get('fdvegan_media_filesystem_overwrite', 0);  // set in fdvegan.admin.inc::fdvegan_admin_form()
        if (!$overwrite) {
            $file_exists = file_exists($this->getAbsFilename());  // don't create it, just check if local file exists already
            if ($file_exists) {
                fdvegan_Content::syslog('LOG_INFO', "END scrapeFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getOrderNum()}) not overwriting local file: {$this->getAbsFilename()}.");
                return $this->getAbsFilename();
            }
        }

        fdvegan_Content::syslog('LOG_DEBUG', "scrapeFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getOrderNum()}) scraping from URL={$this->getExternalUrl()}");
        // Although file_get_contents() is simpler, is has no timeout-control, so
        // if TMDb hangs for 2 mins our script will timeout and fail.
        // Therefore, we switched to using cURL instead.
        // Also, file_get_contents() only works if you have allow_url_fopen set to true.
        // If that's not possible, you also must use cURL to scrape instead.
        //$image_data = file_get_contents($this->getExternalUrl(), FALSE, NULL, 0);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getExternalUrl());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);  // # seconds to wait
        //curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $image_data = curl_exec($ch);
        curl_close($ch);
        if ($image_data === FALSE) {
            fdvegan_Content::syslog('LOG_WARNING', "scrapeFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getOrderNum()}) failed to scrape URL={$this->getExternalUrl()}");
        }
        // See:  https://api.drupal.org/api/drupal/includes!file.inc/function/file_unmanaged_save_data/7.x
        $replace = $overwrite ? FILE_EXISTS_REPLACE : FILE_EXISTS_ERROR;
        $new_local_filename = file_unmanaged_save_data($image_data, $this->getAbsFilename(), $replace);
        if ($new_local_filename !== $this->getAbsFilename()) {
            fdvegan_Content::syslog('LOG_ERR', "scrapeFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getOrderNum()}) new_file={$new_local_filename} != intended_file={$this->getAbsFilename()}");
        }
        fdvegan_Content::syslog('LOG_DEBUG', "END scrapeFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getOrderNum()}) scraped to file={$new_local_filename}");
        return $new_local_filename;
    }


    /**
     * Ensure the FDVegan TMDB public file directories are created properly.
     * These directories are where the movie and person images and media files are stored.
     * This function is safe to call multiple times before and after the dirs are created.
     *
     * @return int    -1 = file copy failure, FALSE = dir failure, TRUE = successfully created, 2 = dirs already existed correctly
     */
     public static function install_create_dirs() {
        $ret_val = 2;
        $media_dir = self::getMediaAbsDir();  // includes the initial "/tmdb" part
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN install_create_dirs() at \"{$media_dir}\".");
        $abs_dir = '';
        $dir_array = array('',  // since $media_dir already includes the initial "/tmdb" part
                           '/movie', '/movie/l', '/movie/m', '/movie/o', '/movie/s',
                           '/moviebackdrop', '/moviebackdrop/l', '/moviebackdrop/m', '/moviebackdrop/o', '/moviebackdrop/s',
                           '/movievideo', '/movievideo/l', '/movievideo/m', '/movievideo/o', '/movievideo/s',
                           '/person', '/person/l', '/person/m', '/person/o', '/person/s',
                          );
        foreach ($dir_array as $dir) {
            $abs_dir = $media_dir . $dir;
            $abs_dir = str_replace('/', DIRECTORY_SEPARATOR, $abs_dir);  // works for both Linux and Windows
            $ret = file_prepare_directory($abs_dir, 0);
            if (!$ret) {
                $ret = file_prepare_directory($abs_dir, FILE_CREATE_DIRECTORY);
                if (!$ret) {
                    $ret_val = FALSE;
                    break;
                }
                $ret_val = TRUE;
                fdvegan_Content::syslog('LOG_NOTICE', "install_create_dirs() created {$media_dir}{$dir}");
            }
        }


        /*
         * Next, copy the default movie/person image files.
         */
        if ($ret_val) {  // Only try to copy if dirs exist properly now.
            $base_dir = realpath(drupal_get_path('module', 'fdvegan') . '/images') . '/';
            $base_dir = str_replace('/', DIRECTORY_SEPARATOR, $base_dir);  // works for both Linux and Windows
            $copied = TRUE;
            $default_files_array = array(
                                         'movie-l-0-1.jpg'  => '/movie/l/movie-l-0-1.jpg',
                                         'movie-m-0-1.jpg'  => '/movie/m/movie-m-0-1.jpg',
                                         'movie-s-0-1.jpg'  => '/movie/s/movie-s-0-1.jpg',
                                         'person-l-0-1.jpg' => '/person/l/person-l-0-1.jpg',
                                         'person-m-0-1.jpg' => '/person/m/person-m-0-1.jpg',
                                         'person-s-0-1.jpg' => '/person/s/person-s-0-1.jpg',
                                        );
            foreach ($default_files_array as $src => $dest) {
                $abs_dest = str_replace('/', DIRECTORY_SEPARATOR, $media_dir . $dest);  // works for both Linux and Windows
                $copied &= copy($base_dir . $src,  $abs_dest);
            }
            $ret_val = $copied ? $ret_val : -1;
        }


        if ($ret_val === 2) {
            $msg = "Initial TMDb directories already exist properly at \"{$media_dir}\".";
            drupal_set_message($msg, 'status', FALSE);  // set $repeat to FALSE to avoid double messages
        } elseif ($ret_val === TRUE) {
            $msg = "Initial TMDb directories created successfully at \"{$media_dir}\".";
            drupal_set_message($msg, 'status', FALSE);  // set $repeat to FALSE to avoid double messages
        } elseif ($ret_val === FALSE) {
            $msg = "Could not create public TMDb dir \"{$abs_dir}\".";
            fdvegan_Content::syslog('LOG_ERR', "install_create_dirs() {$msg}");
            drupal_set_message($msg, 'error', FALSE);  // set $repeat to FALSE to avoid double messages
        } else {
            $msg = "Could not copy default movie/person image files to \"{$media_dir}\".";
            fdvegan_Content::syslog('LOG_ERR', "install_create_dirs() {$msg}");
            drupal_set_message($msg, 'error', FALSE);  // set $repeat to FALSE to avoid double messages
        }

        fdvegan_Content::syslog('LOG_DEBUG', "END install_create_dirs() at \"{$media_dir}\".");
        return $ret_val;
     }


    /**
     * Ensure the FDVegan TMDB copy-local file directories are created properly.
     * These directories are where the movie and person images and media files can be copied to.
     * This function is safe to call multiple times before and after the dirs are created.
     *
     * @return int    FALSE = dir failure, TRUE = successfully created, 2 = dirs already existed correctly
     */
     public static function install_create_copy_dirs() {
        $ret_val = 2;
        $media_dir = self::getMediaCopyAbsDir();  // includes the initial "/tmdb" part
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN install_create_copy_dirs() at \"{$media_dir}\".");
        $abs_dir = '';
        $dir_array = array('',  // since $media_dir already includes the initial "/tmdb" part
                           '/movie', '/movie/l',
                           '/person', '/person/l',
                          );
        foreach ($dir_array as $dir) {
            $abs_dir = $media_dir . $dir;
            $abs_dir = str_replace('/', DIRECTORY_SEPARATOR, $abs_dir);  // works for both Linux and Windows
            $ret = file_prepare_directory($abs_dir, 0);
            if (!$ret) {
                $ret = file_prepare_directory($abs_dir, FILE_CREATE_DIRECTORY);
                if (!$ret) {
                    $ret_val = FALSE;
                    break;
                }
                $ret_val = TRUE;
                fdvegan_Content::syslog('LOG_NOTICE', "install_create_copy_dirs() created {$media_dir}{$dir}");
            }
        }


        if ($ret_val === 2) {
            $msg = "Initial copy directories already exist properly at \"{$media_dir}\".";
            drupal_set_message($msg, 'status', FALSE);  // set $repeat to FALSE to avoid double messages
        } elseif ($ret_val === TRUE) {
            $msg = "Initial copy directories created successfully at \"{$media_dir}\".";
            drupal_set_message($msg, 'status', FALSE);  // set $repeat to FALSE to avoid double messages
        } else {
            $msg = "Could not create copy-local dir \"{$abs_dir}\".";
            fdvegan_Content::syslog('LOG_ERR', "install_create_copy_dirs() {$msg}");
            drupal_set_message($msg, 'error', FALSE);  // set $repeat to FALSE to avoid double messages
        }

        fdvegan_Content::syslog('LOG_DEBUG', "END install_create_copy_dirs() at \"{$media_dir}\".");
        return $ret_val;
     }



    //////////////////////////////



	/**
	 * Alternative to PHP's file_get_contents() function.
	 * Currently not being used.
	 */
    private function _curlGetFileContents($url)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c, CURLOPT_URL, $url);
        $contents = curl_exec($c);
        curl_close($c);

        if ($contents) {
            return $contents;
        } else {
            return FALSE;
        }
    }


    private function _processLoadMediaResult($result)
    {
        if ($result->rowCount() != 1) {
            return NULL;
        }

        foreach ($result as $row) {
            $this->setMediaId($row->media_id);
            $this->setMovieId($row->movie_id);
            $this->setPersonId($row->person_id);
            $this->setMediaType($row->media_type);
            $this->setMediaSize($row->media_size);
            $this->setOrderNum($row->order_num);
            $this->setLocalFilename($row->local_filename);
            $this->setExternalUrl($row->external_url);
            $this->setSource($row->source);
            $this->setRating($row->rating);
            $this->setMediaDate($row->media_date);
            $this->setDescr($row->descr);
            $this->setHeight($row->height);
            $this->setWidth($row->width);
            $this->setCreated($row->created);
            $this->setUpdated($row->updated);
            $this->setSynced($row->synced);
        }

        fdvegan_Content::syslog('LOG_DEBUG', 'Loaded media from our DB; media_id='. $this->getMediaId() .
                                ', movie_id='. $this->getMovieId() .
                                ', person_id='. $this->getPersonId() .
//                                ', row='. print_r($row,1) .
                                '.');

        if ($this->getRefreshFromTmdb()) {
            $this->setRefreshFromTmdb(FALSE);  // reset after refreshing
        }
        if ($this->getScrapeFromTmdb()) {
            $this->setScrapeFromTmdb(FALSE);  // reset after refreshing
        }

        return $this->getMediaId();
    }


    private function _loadMediaByMediaId()
    {
        $sql = <<<__SQL__
SELECT {fdvegan_media}.`media_id`, {fdvegan_media}.`movie_id`, {fdvegan_media}.`person_id`, 
       {fdvegan_media}.`media_type`, {fdvegan_media}.`media_size`, {fdvegan_media}.`order_num`, 
       {fdvegan_media}.`local_filename`, {fdvegan_media}.`external_url`, 
       {fdvegan_media}.`source`, {fdvegan_media}.`rating`, {fdvegan_media}.`media_date`, 
       {fdvegan_media}.`descr`, {fdvegan_media}.`height`, {fdvegan_media}.`width`, 
       {fdvegan_media}.`created`, {fdvegan_media}.`updated`, {fdvegan_media}.`synced` 
  FROM {fdvegan_media} 
 WHERE {fdvegan_media}.`media_id` = :media_id 
__SQL__;
        $sql_params = array(':media_id' => $this->getMediaId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded media from our DB by media_id={$this->getMediaId()}.");
        return $this->_processLoadMediaResult($result);
    }


    private function _loadMediaByMovieId()
    {
        $sql = <<<__SQL__
SELECT {fdvegan_media}.`media_id`, {fdvegan_media}.`movie_id`, {fdvegan_media}.`person_id`, 
       {fdvegan_media}.`media_type`, {fdvegan_media}.`media_size`, {fdvegan_media}.`order_num`, 
       {fdvegan_media}.`local_filename`, {fdvegan_media}.`external_url`, 
       {fdvegan_media}.`source`, {fdvegan_media}.`rating`, {fdvegan_media}.`media_date`, 
       {fdvegan_media}.`descr`, {fdvegan_media}.`height`, {fdvegan_media}.`width`, 
       {fdvegan_media}.`created`, {fdvegan_media}.`updated`, {fdvegan_media}.`synced` 
  FROM {fdvegan_media} 
 WHERE {fdvegan_media}.`movie_id`   = :movie_id 
   AND {fdvegan_media}.`media_type` = :media_type 
   AND {fdvegan_media}.`media_size` = :media_size 
   AND {fdvegan_media}.`order_num`  = :order_num 
__SQL__;
        $sql_params = array(':movie_id'   => $this->getMovieId(),
                            ':media_type' => $this->getMediaType(),
                            ':media_size' => $this->getMediaSize(),
                            ':order_num'  => $this->getOrderNum(),
                           );
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded media from our DB by movie_id={$this->getMovieId()}.");
        return $this->_processLoadMediaResult($result);
    }


    private function _loadMediaByPersonId()
    {
        $sql = <<<__SQL__
SELECT {fdvegan_media}.`media_id`, {fdvegan_media}.`movie_id`, {fdvegan_media}.`person_id`, 
       {fdvegan_media}.`media_type`, {fdvegan_media}.`media_size`, {fdvegan_media}.`order_num`, 
       {fdvegan_media}.`local_filename`, {fdvegan_media}.`external_url`, 
       {fdvegan_media}.`source`, {fdvegan_media}.`rating`, {fdvegan_media}.`media_date`, 
       {fdvegan_media}.`descr`, {fdvegan_media}.`height`, {fdvegan_media}.`width`, 
       {fdvegan_media}.`created`, {fdvegan_media}.`updated`, {fdvegan_media}.`synced` 
  FROM {fdvegan_media} 
 WHERE {fdvegan_media}.`person_id`  = :person_id 
   AND {fdvegan_media}.`media_type` = :media_type 
   AND {fdvegan_media}.`media_size` = :media_size 
   AND {fdvegan_media}.`order_num`  = :order_num 
__SQL__;
        $sql_params = array(':person_id'  => $this->getPersonId(),
                            ':media_type' => $this->getMediaType(),
                            ':media_size' => $this->getMediaSize(),
                            ':order_num'  => $this->getOrderNum(),
                           );
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded media from our DB by person_id={$this->getPersonId()}.");
        return $this->_processLoadMediaResult($result);
    }


}

