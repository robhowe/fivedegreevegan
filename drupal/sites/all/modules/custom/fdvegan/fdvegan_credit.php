<?php
/**
 * fdvegan_credit.php
 *
 * Implementation of Credit class for module fdvegan.
 * Stores all info related to a single movie credit (from a cast list).
 *
 * PHP version 5.6
 *
 * @category   Credit
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */


class fdvegan_Credit extends fdvegan_BaseClass
{
    protected $_cast_id    = NULL;
    protected $_movie_id   = NULL;
    protected $_person_id  = NULL;
    protected $_tmdb_order = NULL;
    protected $_character  = NULL;

    protected $_person     = NULL;  // Person object
    protected $_movie      = NULL;  // Movie object


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if ($this->_cast_id != NULL) {
            $this->_loadCreditByCastId();
        } elseif (!empty($options['MovieId']) && !empty($options['PersonId'])) {
            $this->_loadCreditByMoviePersonId();
        }
    }


    public function setCastId($value)
    {
        $this->_cast_id = (int)$value;
        return $this;
    }

    public function getCastId()
    {
        return $this->_cast_id;
    }


    public function setMovieId($value)
    {
        $this->_movie_id = (int)$value;
        return $this;
    }

    public function getMovieId()
    {
        return $this->_movie_id;
    }


    public function setPersonId($value)
    {
        $this->_person_id = (int)$value;
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


    public function setTmdbOrder($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_tmdb_order = $value;
        }
        return $this;
    }

    public function getTmdbOrder()
    {
        return $this->_tmdb_order;
    }


    public function setCharacter($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_character = substr($value, 0, 254);
        }
        return $this;
    }

    public function getCharacter()
    {
        return (string)$this->_character;
    }

    /**
     * Calculate a "character name" from a "crew" element and append it to the current character name.
     *
     * @param string $value    A new "cast" character name or "crew" credit.
     */
    public function appendCharacter($value)
    {
        if (empty($value)) {
            return $this;
        }
        if (empty($this->getCharacter())) {
            $this->setCharacter($value);
        } else {
            $this->setCharacter($this->getCharacter() . ', ' . $value);
        }
        return $this;
    }


    public function setPerson($value)
    {
        $this->_person = (object)$value;
        return $this;
    }

    public function getPerson($refresh_from_tmdb = FALSE)
    {
        if (($this->_person == NULL) || $refresh_from_tmdb) {
            // Lazy-load from our DB
            module_load_include('php', 'fdvegan', 'fdvegan_person');

            $person_opts = array('PersonId'=>$this->_person_id,
                                'RefreshFromTmdb'=>(bool)$refresh_from_tmdb);
            $this->_person = new fdvegan_Person($person_opts);
        }

        return $this->_person;
    }


    public function setMovie($value)
    {
        $this->_movie = (object)$value;
        return $this;
    }

    public function getMovie($refresh_from_tmdb = FALSE)
    {
        if (($this->_movie == NULL) || $refresh_from_tmdb) {
            // Lazy-load from our DB
            module_load_include('php', 'fdvegan', 'fdvegan_movie');

            $movie_opts = array('MovieId' => $this->getMovieId(),
                                'RefreshFromTmdb' => (bool) $refresh_from_tmdb);
            $this->_movie = new fdvegan_Movie($movie_opts);
        }

        return $this->_movie;
    }


    /**
     * Calculate a "character name" from a "cast" or "crew" TMDb element.
     *
     * @param array $tmdb_obj    A single "cast" or "crew" JSON array (usually returned from TMDb).
     * @return string    the character/credit name.
     */
    public function getCharacterFromTmdbObj($tmdb_obj)
    {
        if (!empty($tmdb_obj['character'])) {
            return $tmdb_obj['character'];
        }
        if (!isset($tmdb_obj['department']) && !isset($tmdb_obj['job'])) {
            return '';
        }
        if (!isset($tmdb_obj['job']) || empty($tmdb_obj['job'])) {
            return empty($tmdb_obj['department']) ? '' : '[' . $tmdb_obj['department'] . ']';
        }
        if (!isset($tmdb_obj['department']) || empty($tmdb_obj['department'])) {
            return '[' . $tmdb_obj['job'] . ']';
        }
        // If the dept name is too similar to the job name, then just use the job name.
        //   e.g.:  Directing : Director, Editing : Editor, Writing : Writer
        if (substr($tmdb_obj['department'],0,4) == substr($tmdb_obj['job'],0,4)) {
            return '[' . $tmdb_obj['job'] . ']';
        }
        return '[' . $tmdb_obj['department'] . ':' . $tmdb_obj['job'] . ']';
    }


    public function storeCredit()
    {
        if ($this->getCastId()) {  // Must already exist in our DB, so is an update.

            $transaction = db_transaction('storeCredit_upd');
            try {
                $this->setTmdbOrder($this->_getNextTmdbOrder());

                $sql = <<<__SQL__
UPDATE {fdvegan_cast_list} SET 
       `movie_id`   = :movie_id, 
       `person_id`  = :person_id, 
       `tmdb_order` = :tmdb_order, 
       `character`  = :character, 
       `updated`    = now() 
 WHERE `cast_id` = :cast_id
__SQL__;
                $sql_params = array(':cast_id'    => $this->getCastId(),
                                    ':movie_id'   => $this->getMovieId(),
                                    ':person_id'  => $this->getPersonId(),
                                    ':tmdb_order' => $this->getTmdbOrder(),
                                    ':character'  => $this->getCharacter(),
                                   );
                //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
                $result = db_query($sql, $sql_params);
            } catch (Exception $e) {
                $transaction->rollback();
                fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while UPDATing credit: '. print_r($this,1));
                throw $e;
            }
            fdvegan_Content::syslog('LOG_DEBUG', "Updated credit in our DB: cast_id={$this->getCastId()}, person_id={$this->getPersonId()}.");

        } else {  // Must be a new credit to our DB, so is an insert.

// @TODO - There has to be a better way to order these records, if needed at all...
            $transaction = db_transaction('storeCredit_ins');
            try {
                $this->setTmdbOrder($this->_getNextTmdbOrder());

                $this->_cast_id = db_insert('fdvegan_cast_list')
                ->fields(array(
                  '`movie_id`'   => $this->getMovieId(),
                  '`person_id`'  => $this->getPersonId(),
                  '`tmdb_order`' => $this->getTmdbOrder(),
                  '`character`'  => $this->getCharacter(),
                ))
                ->execute();
            } catch (Exception $e) {
                $transaction->rollback();
                fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while INSERTing credit: '. print_r($this,1));
                throw $e;
            }
            fdvegan_Content::syslog('LOG_DEBUG', "Inserted new credit into our DB: ({$this->getCastId()},{$this->getMovieId()},{$this->getPersonId()},{$this->getTmdbOrder()},\"{$this->getCharacter()}\".");
        }

        return $this->getCastId();
    }


    /**
     * Delete this record out of the FDV DB.
     */
    public function delete()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN delete({$this->getCastId()}) movieId={$this->getMovieId()}, personId={$this->getPersonId()}.");
        if (empty($this->getCastId())) {
            fdvegan_Content::syslog('LOG_WARNING', "Could not delete credit from FDV DB by credit_id={$this->getCastId()}.");
            return FALSE;
        }

        $sql = <<<__SQL__
DELETE FROM {fdvegan_cast_list} WHERE {fdvegan_cast_list}.`cast_id` = :cast_id
__SQL__;
        $sql_params = array(':cast_id' => $this->getCastId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        try {
            $result = db_query($sql, $sql_params);
        } catch (Exception $e) {
            throw new FDVegan_PDOException("Caught Exception: {$e->getMessage()} while DELETing credit_id={$this->getCastId()}.", $e->getCode(), $e, 'LOG_ERR');
        }
        fdvegan_Content::syslog('LOG_DEBUG', "END delete({$this->getCastId()}) movieId={$this->getMovieId()}, personId={$this->getPersonId()}.");

        return $result;
    }



    //////////////////////////////



    /**
     * Helper function for storeCredit().
     *
     * @return int    The next (max+1) tmbd_order # to use for this credit, (tmdb_order is per movie_id).
     */
    private function _getNextTmdbOrder()
    {
        $sql = <<<__SQL__
SELECT MAX(`tmdb_order`) AS `max_order` 
  FROM {fdvegan_cast_list} 
 WHERE {fdvegan_cast_list}.`movie_id` = :movie_id 
__SQL__;
        $sql_params = array(':movie_id' => $this->getMovieId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);

        $tmdb_order = 0;
        if ($result->rowCount()) {
            foreach ($result as $row) {
                $tmdb_order = ($row->max_order == NULL) ? 0 : $row->max_order + 1;
            }
        }
        return $tmdb_order;
    }


    private function _processLoadCreditResult($result)
    {
        if ($result->rowCount() != 1) {
            return NULL;
        }

        foreach ($result as $row) {
            $this->setCastId($row->cast_id);
            $this->setMovieId($row->movie_id);
            $this->setPersonId($row->person_id);
            $this->setTmdbOrder($row->tmdb_order);
            $this->setCharacter($row->character);
            $this->setCreated($row->created);
            $this->setUpdated($row->updated);
            $this->setSynced($row->synced);
        }

        fdvegan_content::syslog('LOG_DEBUG', "Loaded credit from our DB; cast_id={$this->getCastId()}" .
                                ", movie_id={$this->getMovieId()}" .
                                ", person_id={$this->getPersonId()}" .
                                ", tmdb_order={$this->getTmdbOrder()}" .
                                ", character={$this->getCharacter()}."
                               );

        return $this->getCastId();
    }


    private function _loadCreditByCastId()
    {
        $sql = <<<__SQL__
SELECT `cast_id`, `movie_id`, `person_id`, `tmdb_order`, `character`, `created`, `updated`, `synced` 
  FROM {fdvegan_cast_list} 
 WHERE `cast_id` = :cast_id
__SQL__;
        $sql_params = array(':cast_id' => $this->getCastId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        fdvegan_content::syslog('LOG_DEBUG', "Loaded credit from our DB by cast_id={$this->getCastId()}.");
        return $this->_processLoadCreditResult($result);
    }


    /**
     * We only store one credit per person per movie, even if the person appeared in
     * the cast list numerous times as numerous different characters.
     * The first credit listed on TMDb for this person in this movie will be the only one we track.
     */
    private function _loadCreditByMoviePersonId()
    {
        $sql = <<<__SQL__
SELECT `cast_id`, `movie_id`, `person_id`, `tmdb_order`, `character`, `created`, `updated`, `synced` 
  FROM {fdvegan_cast_list} 
 WHERE {fdvegan_cast_list}.`movie_id` = :movie_id 
   AND {fdvegan_cast_list}.`person_id` = :person_id 
ORDER BY {fdvegan_cast_list}.`tmdb_order` ASC 
 LIMIT 1
__SQL__;
        $sql_params = array(':movie_id' => $this->getMovieId(),
                            ':person_id' => $this->getPersonId(),
                           );
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        fdvegan_content::syslog('LOG_DEBUG', "Loaded credit from our DB by movie_id={$this->getMovieId()}, person_id={$this->getPersonId()}.");
        return $this->_processLoadCreditResult($result);
    }


}

