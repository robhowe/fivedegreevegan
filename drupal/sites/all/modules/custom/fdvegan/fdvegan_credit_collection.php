<?php
/**
 * fdvegan_credit_collection.php
 *
 * Implementation of Credit Collection class for module fdvegan.
 *
 * PHP version 5.6
 *
 * @category   Credit
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */


class fdvegan_CreditCollection extends fdvegan_BaseCollection
{
    protected $_movie_id  = NULL;
    protected $_person_id = NULL;
    protected $_tmdbid    = NULL;  // for either movie or person


    public function __construct($options = NULL)
    {
        parent::__construct($options);
        fdvegan_Content::syslog('LOG_DEBUG', "fdvegan_CreditCollection::__construct() options: " . print_r($options,1));

        if ($this->_person_id) {
            $this->loadCreditsByPerson();
        } elseif ($this->_movie_id) {
            $this->loadCreditsByMovie();
        } else {
            fdvegan_Content::syslog('LOG_WARNING', 'fdvegan_CreditCollection initialized with no person or movie');
        }
    }


    public function setMovieId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($this->_person_id !== NULL) {
            throw new FDVeganException('Credit collection already associated with a Person, so cannot change to Movie');
        }
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_movie_id = (int)$value;
        }
        return $this;
    }


    public function getMovieId()
    {
        return $this->_movie_id;
    }


    public function setPersonId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($this->_movie_id !== NULL) {
            throw new FDVeganException('Credit collection already associated with a Movie, so cannot change to Person');
        }
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_person_id = (int)$value;
        }
        return $this;
    }

    public function getPersonId()
    {
        return $this->_person_id;
    }

    /**
     * Convenience function.
     */
    public function getId()
    {
        return $this->getPersonId() ? $this->getPersonId() : $this->getMovieId();
    }


    public function setTmdbId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_tmdbid = (int)$value;
        }
        return $this;
    }

    public function getTmdbId()
    {
        return $this->_tmdbid;
    }


    public function loadCreditsByPerson()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN loadCreditsByPerson({$this->getPersonId()}).");
        $sql = <<<__SQL__
SELECT {fdvegan_cast_list}.`cast_id`, {fdvegan_cast_list}.`movie_id`, {fdvegan_cast_list}.`person_id`, 
       {fdvegan_cast_list}.`tmdb_order`, {fdvegan_cast_list}.`character`, 
       {fdvegan_cast_list}.`created`, {fdvegan_cast_list}.`updated` 
  FROM {fdvegan_cast_list} 
  JOIN {fdvegan_movie} ON {fdvegan_movie}.`movie_id` = {fdvegan_cast_list}.`movie_id` 
 WHERE {fdvegan_cast_list}.`person_id` = :person_id 
 ORDER BY {fdvegan_movie}.`release_date` DESC, {fdvegan_movie}.`title` ASC, {fdvegan_movie}.`adult_rated` ASC
__SQL__;
        try {
            $sql_params = array(':person_id' => $this->getPersonId());
            //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
            $result = db_query($sql, $sql_params);
        } catch (Exception $e) {
            fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while SELECTing person credits: '. print_r($this,1));
            throw $e;
        }
        $updated_obj = new DateTime($this->getUpdated());  // to calculate if stale
        foreach ($result as $row) {
            $options = array('CastId' => $row->cast_id);
            $this->_items[] = new fdvegan_Credit($options);

            // Note - we calculate this collection's _updated time as
            // the greatest _updated credit in the collection
            $credit_updated_obj = new DateTime($row->updated);
            if ($credit_updated_obj > $updated_obj) {
                $updated_obj = $credit_updated_obj;
                $this->_updated = $updated_obj->format('Y-m-d H:i:s');
            }
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded credits from our DB; person_id={$this->getPersonId()}" .
                                ', count='. $this->count() . '.');
        if ($this->getRefreshFromTmdb()) {
            $this->_loadCreditsFromTmdbByPerson();

            fdvegan_Content::syslog('LOG_DEBUG', "Loaded credits from TMDb by person_id={$this->getPersonId()}" .
                                    ', count='. $this->count() . '.');
        }

        return $this->getItems();
    }


    public function loadCreditsByMovie()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN loadCreditsByMovie({$this->getMovieId()}).");
        $sql = <<<__SQL__
SELECT {fdvegan_cast_list}.`cast_id`, {fdvegan_cast_list}.`movie_id`, {fdvegan_cast_list}.`person_id`, 
       {fdvegan_cast_list}.`tmdb_order`, {fdvegan_cast_list}.`character`, 
       {fdvegan_cast_list}.`created`, {fdvegan_cast_list}.`updated` 
  FROM {fdvegan_cast_list} 
 WHERE {fdvegan_cast_list}.`movie_id` = :movie_id 
 ORDER BY {fdvegan_cast_list}.`tmdb_order` ASC
__SQL__;
        try {
            $sql_params = array(':movie_id' => $this->getMovieId());
            //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
            $result = db_query($sql, $sql_params);
        } catch (Exception $e) {
            fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while SELECTing movie credits: '. print_r($this,1));
            throw $e;
        }
        $updated_obj = new DateTime($this->_updated);  // to calculate if stale
        foreach ($result as $row) {
            $options = array('CastId' => $row->cast_id);
            $this->_items[] = new fdvegan_Credit($options);

            // Note - we calculate this collection's _updated time as
            // the greatest _updated credit in the collection
            $credit_updated_obj = new DateTime($row->updated);
            if ($credit_updated_obj > $updated_obj) {
                $updated_obj = $credit_updated_obj;
                $this->_updated = $updated_obj->format('Y-m-d H:i:s');
            }
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded credits from our DB; movie_id={$this->getMovieId()}" .
                                ', count='. $this->count() . '.');
        if ($this->getRefreshFromTmdb()) {
//@TODO
fdvegan_Content::syslog('LOG_ERR', 'fdvegan_CreditCollection::loadCreditsByMovie() loadCreditsFromTmdbByMovie() unimplemented');
throw new Exception('fdvegan_CreditCollection::loadCreditsByMovie() loadCreditsFromTmdbByMovie() unimplemented.');
//            $this->loadCreditsFromTmdbByMovie();

            fdvegan_Content::syslog('LOG_DEBUG', "Loaded credits from TMDb by movie_id={$this->getMovieId()}" .
                                    ', count='. $this->count() . '.');
        }

        return $this->getItems();
    }


    /**
     * Delete any records in this collection with the given person_id.
     * 
     * Called by: fdvegan_rest_api_Content::_getRecursiveObj()
     */
    public function deletePersonId($personId)
    {
        for ($i=0; $i < $this->count(); $i++) {
            $credit = $this->getAt($i);
            if ($credit->personId == $personId) {
                $this->offsetUnset($i);
            }
        }
        return $this;
    }


    /**
     * Load all movies in this credit collection (mainly used for refreshing from TMDb).
     *
     * @param int $start    Like MySQL, item indices start at 0.
     * @param int $limit    Like MySQL.
     * @return int    # of movies loaded, or FALSE if none.
     */
    public function loadEachMovie($options = NULL, $start = 0, $limit = 0)
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN loadEachMovie({$start},{$limit}).");
        $ret_val = 0;
        if (is_array($options)) {
            $this->setOptions($options);
        }

        // @TODO - Sort this collection to ensure the same order upon any subsequent calls.
        //  Currently, we are simply trusting that TMDb will always return credits in the same order (i.e.: an accident waiting to happen).

        $loop = 0;
        foreach ($this->getItems() as $credit) {

            $loop++;
            if (($start > 0) || ($limit > 0)) {
                if ($loop - 1 < $start) {
                    continue;  // skip to the given $start #
                }
                if ($loop - 1 >= $start + $limit) {
                    break;  // end after the given $limit #
                }
            }
            $movie = $credit->getMovie($this->getRefreshFromTmdb());  // does not store movie like getPerson() does
            $ret_val++;
            if ($this->getRefreshFromTmdb()) {
                $movie->storeMovie();  // store in our DB
            }
        }

        fdvegan_Content::syslog('LOG_DEBUG', "END loadEachMovie({$start},{$limit}).");
        return $ret_val ? $ret_val : FALSE;
    }


    /**
     * Delete all included records out of the FDV DB.
     */
    public function deleteAll()
    {
        for ($i=0; $i < $this->count(); $i++) {
            $this->getItems()[$i]->delete();
        }
        parent::deleteAll();  // effectively unset()'s all existing _items

        return $this->getItems();
    }



    //////////////////////////////



    /**
     * Calculate a "character name" from a "crew" element returned from TMDb.
     *
     * @param array $tmdb_row    A single "crew" JSON array returned from TMDb.
     * @return string    Character name.
     */
    private function _getCharacterDescrFromCrewRow($tmdb_row)
    {
        if (!isset($tmdb_row['department']) && !isset($tmdb_row['job'])) {
            return '';
        }
        if (!isset($tmdb_row['job']) || empty($tmdb_row['job'])) {
            return empty($tmdb_row['department']) ? '' : '[ ' . $tmdb_row['department'] . ' ]';
        }
        if (!isset($tmdb_row['department']) || empty($tmdb_row['department'])) {
            return '[ ' . $tmdb_row['job'] . ' ]';
        }
        // If the dept name is too similar to the job name, then just use the job name.
        //   e.g.:  Directing : Director, Editing : Editor, Writing : Writer
        if (substr($tmdb_row['department'],0,4) == substr($tmdb_row['job'],0,4)) {
            return '[ ' . $tmdb_row['job'] . ' ]';
        }
        return '[ ' . $tmdb_row['department'] . ' : ' . $tmdb_row['job'] . ' ]';
    }


    /* Note - Because the getPersonCredits() API call only returns partial movie data,
     *        you always need to force a load from TMDb for the movie explicitly after
     *        calling this method!
     *        (Then store the true/full record if anything has changed.)
     *
     * @see  fdvegan_batch_process.php::fdvegan_init_load_batch_process()
     *
     * @return  array    A collection of credits.
     */
    private function _loadCreditsFromTmdbByPerson()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "loadCreditsFromTmdbByPerson() personId={$this->getPersonId()}, TmdbId={$this->getTmdbId()}.");

        if (empty($this->getTmdbId())) {
            fdvegan_Content::syslog('LOG_INFO', "loadCreditsFromTmdbByPerson() personId={$this->getPersonId()}, TmdbId unknown for person.");
            return $this->getItems();
        }
        if ($this->isStale()) {
            fdvegan_Content::syslog('LOG_DEBUG', 'TMDb credits data stale, so reloading, updated='. $this->getUpdated() .'.');
            $tmdb_api = new fdvegan_Tmdb();
            $tmdb_credits = $tmdb_api->getPersonCredits($this->getTmdbId());
            $this->setTmdbData($tmdb_credits);
// @TODO check TMDb return value here.

            /* Because the above getPersonCredits() API call only returns partial movie data,
             * you always need to force a load from TMDb for the movie explicitly afterward
             * (and store the true/full record if anything has changed).
             */

            /* Go ahead and delete all existing credits for this person, so we can safely
             * load the latest credits received from TMDb just now.
             */
            $this->deleteAll();

            $this->_items = array();
            foreach ($tmdb_credits as $tmdb_category => $tmdb_cat_array) {
                if (in_array($tmdb_category, explode(' ', 'cast crew')) && is_array($tmdb_cat_array)) {
                    foreach ($tmdb_cat_array as $tmdb_row) {

                        // In case this is an unknown movie to our DB.
                        $options = array('TmdbId'          => $tmdb_row['id'],
                                         'TmdbImagePath'   => $tmdb_row['poster_path'],
                                         'Title'           => $tmdb_row['title'],
                                         'ReleaseDate'     => $tmdb_row['release_date'],
                                         'AdultRated'      => $tmdb_row['adult'],
                                         'Created'         => date('Y-m-d G:i:s'),
                                         'Synced'          => date('Y-m-d G:i:s'),
                                         // Do NOT RefreshFromTmdb, as this is done incrementally via
                                         // fdvegan_batch_process.php::fdvegan_init_load_batch_process()
                                         //'RefreshFromTmdb' => $this->getRefreshFromTmdb(),
                                        );
                        try {
                            $movie = new fdvegan_Movie($options);
                        }
                        catch (FDVegan_NotFoundException $e) {  // No movie found
                            // This is ok.  We'll store the minimal movie data from TMDb now, and later
                            // the caller (see @see) is responsible for updating the movie record with full data.

                            // Create a new movie record:
                            $options = array('TmdbImagePath'   => $tmdb_row['poster_path'],
                                             'ReleaseDate'     => $tmdb_row['release_date'],
                                             'AdultRated'      => $tmdb_row['adult'],
                                             'Created'         => date('Y-m-d G:i:s'),
                                             'Synced'          => date('Y-m-d G:i:s'),
                                            );
                            $movie = new fdvegan_Movie($options);
                            // Must set these 2 fields after instantiation so the movie class doesn't try to load from our DB.
                            $movie->tmdbId = $tmdb_row['id'];
                            $movie->title  = $tmdb_row['title'];
                        }
                        if (!isset($movie) || !is_object($movie)) {
                            throw new FDVegan_Exception("Error finding movie");
                        }
                        $movie->storeMovie();  // store in our DB

                        fdvegan_Content::syslog('LOG_DEBUG', 'person credit data: '. print_r($tmdb_row,1));
                        $credit_opts = array('MovieId'   => $movie->getMovieId(),
                                             'PersonId'  => $this->getPersonId(),
                                             //'TmdbOrder' => NULL,  // This gets calculated properly by fdvegan_Credit::storeCredit()
                                            );
                        $credit = new fdvegan_Credit($credit_opts);
                        // There is only one field that could be different on TMDb from what might already be
                        // in our DB, so set it again now:
                        $new_character = $credit->getCharacterFromTmdbObj($tmdb_row);
                        $credit->appendCharacter($new_character);
                        $credit->storeCredit();  // store in our DB
                        $this->_items[] = $credit;
                    }
                }
            }

        } else {
            fdvegan_Content::syslog('LOG_DEBUG', "TMDb data still fresh, so not reloading, updated={$this->getUpdated()}.");
        }

        return $this->getItems();
    }


}

