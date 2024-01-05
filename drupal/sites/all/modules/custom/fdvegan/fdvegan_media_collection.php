<?php
/**
 * fdvegan_media_collection.php
 *
 * Implementation of Media Collection class for module fdvegan.
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


class fdvegan_MediaCollection extends fdvegan_BaseCollection
{
    protected $_movie      = NULL;  // Movie object
    protected $_person     = NULL;  // Person object
    protected $_media_type = NULL;  // person, movie, moviebackdrop, movievideo
    protected $_media_size = NULL;  // s, m, l, o (small, medium, large, original)

    // Note - Unlike person & movie, this class' RefreshFromTmdb flag means to ONLY load from TMDb and not our FDV DB.
    //        See fdvegan_MediaSizeCollection for the real "replace images from TMDb" logic.


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if (empty($this->getMovie()) && empty($this->getPerson())) {
            fdvegan_Content::syslog('LOG_WARNING', 'fdvegan_MediaCollection initialized with no person or movie.');
        }
        if ($this->getRefreshFromTmdb() || $this->getScrapeFromTmdb()) {
            $this->_readMediaFromTmdb();
        } else {
            if ($this->getMovie()) {
                $this->_loadMediaByMovie();
            } elseif ($this->getPerson()) {
                $this->_loadMediaByPerson();
            }
        }
    }


    public function setMovie($value)
    {
        if ($this->_person !== NULL) {
            throw new FDVeganException('Media collection already associated with a Person, so cannot change to Movie');
        }
        $this->_movie = $value;
        return $this;
    }

    public function getMovie()
    {
        return $this->_movie;
    }


    public function setPerson($value)
    {
        if ($this->_movie !== NULL) {
            throw new FDVeganException('Media collection already associated with a Movie, so cannot change to Person');
        }
        $this->_person = $value;
        return $this;
    }

    public function getPerson()
    {
        return $this->_person;
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


    /**
     * Currently, there is only one way to sort media: by Rating value descending.
     */
    public function sort($by_field = 'Rating')
    {
        if ($by_field !== 'Rating') {
            fdvegan_Content::syslog('LOG_ERR', "sort() by \"{$by_field}\" not implemented.");
            throw new Exception("sort() by \"{$by_field}\" not implemented.");
        }
        usort($this->_items, function($a, $b) {
                return $a->getRating() - $b->getRating();
            }
        );
        // We also need to update the OrderNum field now.
        for ($loop=0; $loop < $this->count(); $loop++) {
            $this->_items[$loop]->setOrderNum($loop + 1);
        }
        return $this->getItems();
    }


    /**
     * Delete all included records out of the FDV DB!
     * This also deletes any corresponding media files on the filesystem!
     */
    public function deleteAll()
    {
        for ($i=0; $i < $this->count(); $i++) {
            $this->getItems()[$i]->delete();
        }
        parent::deleteAll();  // effectively unset()'s all existing _items

        return $this->getItems();
    }


    /**
     * Scrape all included media files from TMDb.
     * This creates corresponding media files on our filesystem.
     */
    public function scrapeAll()
    {
        for ($i=0; $i < $this->count(); $i++) {
            $this->_items[$i]->scrapeFromTmdb();
        }
        return $this->_items;
    }


    /**
     * Store all included media records to the FDV DB.
     * This does not affect the filesystem in any way.
     */
    public function storeAll()
    {
        for ($i=0; $i < $this->count(); $i++) {
            $this->_items[$i]->storeMedia();
        }
        return $this->_items;
    }



    //////////////////////////////



    private function _readMediaFromTmdb()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN _readMediaFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getRefreshFromTmdb()}).");

        $synced_obj = new DateTime($this->getSynced());  // to calculate if stale
        $base_options = array('MediaType'       => $this->getMediaType(),
                              'MediaSize'       => $this->getMediaSize(),
                              'Source'          => 'Scraped from TMDb.org',
                              'Created'         => $synced_obj->format('Y-m-d H:i:s'),
                              'Synced'          => $synced_obj->format('Y-m-d H:i:s'),
                              'RefreshFromTmdb' => $this->getRefreshFromTmdb(),
                              'ScrapeFromTmdb'  => $this->getScrapeFromTmdb(),
                              );
        $obj = $this->getMovie();
        if (empty($obj)) {
            $obj = $this->getPerson();
            $base_options['PersonId'] = $this->getPerson()->getPersonId();
            $base_options['Descr']    = $this->getPerson()->getFullName();
        } else {
            $base_options['MovieId']   = $this->getMovie()->getMovieId();
            $base_options['Descr']     = $this->getMovie()->getTitle();
            $base_options['MediaDate'] = $this->getMovie()->getReleaseDate();
        }
        $type_ucase = ucfirst($this->getMediaType());
        $tmdb_image_type_index = fdvegan_Tmdb::mapTmdbImageResultType($this->getMediaType());  // for some reason, TMDb adds an 's' to the ImageType name returned.
        $tmdb_image_type = fdvegan_Tmdb::mapTmdbImageType($this->getMediaType());
        $tmdb_image_url = '';
        $tmdb_size = fdvegan_Tmdb::mapTmdbImageSize($this->getMediaType(), $this->getMediaSize());
        if (empty($obj->getTmdbId())) {
            $this->setCreated($synced_obj->format('Y-m-d H:i:s'));
            $this->setUpdated($synced_obj->format('Y-m-d H:i:s'));
        } else {
                $tmdb_api = new fdvegan_Tmdb();
                try {
                    $tmdb_function_name = "get{$type_ucase}Images";  // getPersonImages(), getMovieImages()
                    // Some images for movie posters are tagged for specific languages, or not tagged at all.
                    // So first we search for images in English, then if we find none,
                    // we search again with no language filters.
                    $images = $tmdb_api->$tmdb_function_name($obj->getTmdbId(), NULL);  // NULL will default to language='en'
                    if (!array_key_exists($tmdb_image_type_index, $images) ||
                        !count($images[$tmdb_image_type_index])) {
                        $images = $tmdb_api->$tmdb_function_name($obj->getTmdbId(), FALSE);  // Using FALSE to retrieve results from all languages.
                    }

                    fdvegan_Content::syslog('LOG_DEBUG', "BEGIN _readMediaFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getRefreshFromTmdb()}) {$tmdb_function_name}({$obj->getTmdbId()}) returned data: " . print_r($images,1));
                    // There are often multiple images in seemingly random order, but we'll sort them by vote_count at the end.
                    if (array_key_exists($tmdb_image_type_index, $images)) {
                        $order_num = 1;
                        foreach ($images[$tmdb_image_type_index] as $image_rec) {
                            if (!empty($image_rec['file_path'])) {
                                $tmdb_image_url = $tmdb_api->getImageUrl($image_rec['file_path'], $tmdb_image_type, $tmdb_size);
                            }
                            $options = $base_options;  // copy the defaults for this collection
                            $options['OrderNum']    = $order_num++;  // this will be updated after sorting by Rating
                            $options['Height']      = $image_rec['height'];
                            $options['Width']       = $image_rec['width'];
                            $options['Rating']      = $image_rec['vote_count'];
                            $options['ExternalUrl'] = $tmdb_image_url;
                            $new_media = new fdvegan_Media($options);
                            if (empty($new_media)) {
                                fdvegan_Content::syslog('LOG_WARNING', "_readMediaFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getRefreshFromTmdb()}) {$tmdb_function_name}({$obj->getTmdbId()}) returned unusable media.");
                            } else {
                                $this->_items[] = $new_media;
                            }
                        }
                        $this->sort('Rating');  // Sort all the images by "rating" value.
                        /* Next, we'll delete all media after the max #.  This is simply because during the image-scraping
                         * process we timeout after 120 seconds which equals ~18 movie images.
                         * We really don't need more than the top 15 actor or movie images in each size & type anyway.
                         */
                        $media_files_max_num = variable_get('fdvegan_media_files_max_num');  // 15 or less is strongly recommended.  Set in fdvegan.admin.inc::fdvegan_admin_form()
                        // This does NOT delete any DB records or corresponding media files on the filesystem!
                        $this->truncate($media_files_max_num);  // Delete all media after desired max num.
                    }
                } catch (Exception $e) {
                    fdvegan_Content::syslog('LOG_ERR', "Caught exception:  ". $e->getMessage() ." while _readMediaFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getRefreshFromTmdb()}).");
                    throw $e;
                }
        }
        $this->setSynced($synced_obj->format('Y-m-d H:i:s'));

        fdvegan_Content::syslog('LOG_DEBUG', "END _readMediaFromTmdb('{$this->getMediaType()}','{$this->getMediaSize()}',{$this->getRefreshFromTmdb()}) count={$this->count()}.");
        return $this->getItems();
    }


    private function _processLoadMediaCollectionResult($result)
    {
        if ($result->rowCount() < 1) {
            // Since there are no images in our DB, load up the default image instead:
            if ($this->getPerson()) {
                $options = array('PersonId'  => 0,
                                 'MediaType' => $this->getMediaType(),
                                 'MediaSize' => $this->getMediaSize(),
                                 'OrderNum'  => 1,
                                );
            } elseif ($this->getMovie()) {
                $options = array('MovieId'   => 0,
                                 'MediaType' => $this->getMediaType(),
                                 'MediaSize' => $this->getMediaSize(),
                                 'OrderNum'  => 1,
                                );
            }
            $this->_items[] = new fdvegan_Media($options);
        } else {

            $synced_obj = new DateTime($this->getSynced());  // to calculate if stale
            foreach ($result as $row) {
                $options = array('MediaId' => $row->media_id);
                $this->_items[] = new fdvegan_Media($options);

                // Note - we calculate this collection's _synced time as
                // the greatest _synced media in the collection
                $media_synced_obj = new DateTime($row->synced);
                if ($media_synced_obj > $synced_obj) {
                    $synced_obj = $media_synced_obj;
                    $this->setSynced($synced_obj->format('Y-m-d H:i:s'));
                }
            }
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded media from our DB; media_type='{$this->getMediaType()}'" .
                                ", media_size='{$this->getMediaSize()}'" .
                                ', count='. $this->count() .
                                '.');
        return $this->_items;
    }


    private function _loadMediaByPerson()
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
 ORDER BY {fdvegan_media}.`rating` DESC, {fdvegan_media}.`order_num` DESC
__SQL__;
        try {
            $sql_params = array(':person_id' => $this->getPerson()->getPersonId(),
                                ':media_type' => $this->getMediaType(),
                                ':media_size' => $this->getMediaSize(),
                               );
            //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
            $result = db_query($sql, $sql_params);
        } catch (Exception $e) {
            fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while SELECTing media by person: '. print_r($this,1));
            throw $e;
        }
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded mediaCollection from our DB by person_id={$this->getPerson()->getPersonId()}.");
        return $this->_processLoadMediaCollectionResult($result);
    }


    private function _loadMediaByMovie()
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
 ORDER BY {fdvegan_media}.`rating` DESC, {fdvegan_media}.`order_num` DESC
__SQL__;
        try {
            $sql_params = array(':movie_id'   => $this->getMovie()->getMovieId(),
                                ':media_type' => $this->getMediaType(),
                                ':media_size' => $this->getMediaSize(),
                               );
            //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
            $result = db_query($sql, $sql_params);
        } catch (Exception $e) {
            fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while SELECTing media by movie: '. print_r($this,1));
            throw $e;
        }
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded mediaCollection from our DB by movie_id={$this->getMovie()->getMovieId()}.");
        return $this->_processLoadMediaCollectionResult($result);
    }


}

