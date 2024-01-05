<?php
/**
 * fdvegan_media_size_collection.php
 *
 * Implementation of Media Size Collection class for module fdvegan.
 * Stores images for all actors & movies in all 4 possible sizes.
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


class fdvegan_MediaSizeCollection extends fdvegan_BaseCollection
{
    protected static $_media_sizes_array = array('s', 'm', 'l', 'o');  // must be mapped to get TMDb sizes

    protected $_movie       = NULL;  // Movie object
    protected $_person      = NULL;  // Person object
    protected $_media_type  = NULL;  // person, movie, moviebackdrop, movievideo


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        $this->loadMediaSizeCollection();
    }


    public function setMovie($value)
    {
        if ($this->_person !== NULL) {
            throw new FDVeganException('MediaSize collection already associated with a Person, so cannot change to Movie');
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
            throw new FDVeganException('MediaSize collection already associated with a Movie, so cannot change to Person');
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



    public function loadMediaSizeCollection()
    {
        // Load up the 4 sizes arrays:
        $base_options = array('MediaType' => $this->getMediaType());
        if ($this->getPerson()) {
            $base_options['Person'] = $this->getPerson();
            fdvegan_Content::syslog('LOG_DEBUG', "loadMediaSizeCollection('{$this->getMediaType()}',{$this->getPerson()->getPersonId()}) loading mediaSizeCollection from our DB.");
        } elseif ($this->getMovie()) {
            $base_options['Movie']  = $this->getMovie();
            fdvegan_Content::syslog('LOG_DEBUG', "loadMediaSizeCollection('{$this->getMediaType()}',{$this->getMovie()->getMovieId()}) loading mediaSizeCollection from our DB.");
        } else {
            fdvegan_Content::syslog('LOG_ERR', 'loadMediaSizeCollection() called with no person or movie.');
            throw new Exception("loadMediaSizeCollection() called with no person or movie.");
        }

        foreach (self::$_media_sizes_array as $media_size) {
            $options = $base_options;
            $options['MediaSize'] = $media_size;
            $media_collection = new fdvegan_MediaCollection($options);
            $this->_items[$media_size] = $media_collection;

            // @TODO - If this is to be a Scrape from TMDb, then compare what we have currently in FDV DB with what's on TMDb, then decide if scraping is needed.
            //   But instead, it's easier now to simply delete everything we (might) have and scrape from TMDb.
            if ($this->getScrapeFromTmdb()) {
                $options['ScrapeFromTmdb'] = $this->getScrapeFromTmdb();
                $tmdb_media_collection = new fdvegan_MediaCollection($options);
                if ($tmdb_media_collection->count()) {
                    $this->_items[$media_size]->deleteAll();  // delete the existing records from the FDV DB
                    $this->_items[$media_size] = $tmdb_media_collection;
                    $this->_items[$media_size]->scrapeAll();  // scrape the TMDb images to our filesystem
                    $this->_items[$media_size]->storeAll();  // store the new records in the FDV DB
                }
            }
        }
        return $this->getItems();
    }



    //////////////////////////////



}

