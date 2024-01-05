<?php
/**
 * fdvegan_person.php
 *
 * Implementation of Person class for module fdvegan.
 * Stores all info related to a single actor.
 *
 * PHP version 5.6
 *
 * @category   Person
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */


class fdvegan_Person extends fdvegan_BaseClass
{
    protected $_person_id         = NULL;
    protected $_tmdbid            = NULL;
    protected $_tmdb_image_path   = NULL;  // actor image filename on TMDb
    protected $_imdb_id           = NULL;
    protected $_full_name         = NULL;
    protected $_first_name        = NULL;
    protected $_middle_name       = NULL;
    protected $_last_name         = NULL;
    protected $_gender            = NULL;
    protected $_rating            = NULL;
    protected $_homepage_url      = NULL;
    protected $_biography         = NULL;
    protected $_birthplace        = NULL;
    protected $_birthday          = NULL;
    protected $_deathday          = NULL;

    protected $_tags              = NULL;  // all tags applied to the person
    protected $_quotes            = NULL;  // all quotes from the person
    protected $_credits           = NULL;  // combined movie & TV credits
    protected $_person_images     = NULL;  // all images of all sizes of type 'person'

    /* Data fields received from external sources (TMDb) that may need to be validated.
     * Array format: field_name => default_value
     * see @fdvegan_BaseClass::_validateFields
     */
    protected $_data_fields = array('profile_path' => NULL, 'imdb_id' => NULL,
        'name' => '', 'first_name' => '', 'middle_name' => '', 'last_name' => '',
        'gender' => NULL, 'popularity' => 0, 'homepage' => NULL, 'biography' => NULL,
        'place_of_birth' => NULL, 'birthday' => NULL, 'deathday' => NULL);

    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if (!empty($options['PersonId'])) {
            $this->_loadPersonByPersonId();
        } else {
            $this->_loadPersonByFullName();
        }
    }


    public function setPersonId($value, $overwrite_even_with_empty=TRUE)
    {
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
        return $this->getPersonId();
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


    public function setTmdbImagePath($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_tmdb_image_path = substr($value, 0, 254);
        }
        return $this;
    }

    public function getTmdbImagePath()
    {
        return $this->_tmdb_image_path;
    }


    public function setImdbId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            if (empty($value)) {
                $this->_imdb_id = NULL;
            } else {
                $this->_imdb_id = (string)$value;
            }
        }
        return $this;
    }

    public function getImdbId()
    {
        return $this->_imdb_id;
    }


    public function setFullName($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_full_name = substr(trim($value), 0, 254);
        }
        return $this;
    }

    public function getFullName()
    {
        return $this->_full_name;
    }

    /**
     * Convenience function.
     */
    public function getName()
    {
        return $this->getFullName();
    }


    public function setFirstName($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_first_name = substr($value, 0, 254);
        }
        if (is_null($this->_first_name)) {
            $this->_first_name = '';  // NOT NULL enforced
        }
        return $this;
    }

    public function getFirstName()
    {
        return $this->_first_name;
    }


    public function setMiddleName($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_middle_name = substr($value, 0, 254);
        }
        if (is_null($this->_middle_name)) {
            $this->_middle_name = '';  // NOT NULL enforced
        }
        return $this;
    }

    public function getMiddleName()
    {
        return $this->_middle_name;
    }


    public function setLastName($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_last_name = substr($value, 0, 254);
        }
        if (is_null($this->_last_name)) {
            $this->_last_name = '';  // NOT NULL enforced
        }
        return $this;
    }

    public function getLastName()
    {
        return $this->_last_name;
    }


    /**
     * Setter for "gender".
     *
     * @throws FDVegan_InvalidArgumentException
     */
    public function setGender($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            if (!in_array($value, array('M','F','O',NULL))) {  // FDV DB uses enum M,F,O,NULL
                $value = fdvegan_Tmdb::mapTmdbGenderToFDV($value);  // TMDb uses int 0=unknown,1=female,2=male
            }
            if (!in_array($value, array('M','F','O',NULL))) {  // FDV DB uses enum M,F,O,NULL
                throw new FDVegan_InvalidArgumentException("fdvegan_person->setGender('{$value}') invalid value");
            }
            $this->_gender = $value;
        }
        return $this;
    }

    public function getGender()
    {
        return $this->_gender;
    }


    public function setRating($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value) || is_numeric($value)) {
            $this->_rating = (int)$value;
        }
        return $this;
    }

    public function getRating()
    {
        return $this->_rating;
    }


    public function setHomepageUrl($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_homepage_url = substr($value, 0, 254);
        }
        return $this;
    }

    public function getHomepageUrl()
    {
        return $this->_homepage_url;
    }


    public function setBiography($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_biography = substr($value, 0, 4096);
        }
        return $this;
    }

    public function getBiography()
    {
        return $this->_biography;
    }


    public function setBirthplace($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_birthplace = substr($value, 0, 254);
        }
        return $this;
    }

    public function getBirthplace()
    {
        return $this->_birthplace;
    }


    public function setBirthday($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_birthday = substr($value, 0, 10);
        }
        return $this;
    }

    public function getBirthday()
    {
        return $this->_birthday;
    }


    public function setDeathday($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_deathday = substr($value, 0, 10);
        }
        return $this;
    }

    public function getDeathday()
    {
        return $this->_deathday;
    }


    /**
     * Get the FDV Level.
     * This is actually the count of 1-degree connections.
     */
    public function getFdvCount()
    {
        //return $this->getNumCredits();
        $count = 0;
        foreach ($this->getCredits() as $credit) {
            $count += $credit->movie->getFdvCount();
        }
        return $count;
    }


    public function getTmdbInfoUrl()
    {
        return fdvegan_Tmdb::getTmdbPersonInfoUrl($this->getTmdbId());
    }


    public function getImdbInfoUrl()
    {
        return fdvegan_Imdb::getImdbPersonInfoUrl($this->getImdbId());
    }


    public function setTags($value)
    {
        $this->_tags = (object)$value;
        return $this;
    }

    public function getTags()
    {
        if ($this->_tags == NULL) {
            // Lazy-load from our DB
            $options = array('Person' => $this,
                            );
            $this->_tags = new fdvegan_PersonTagCollection($options);
        }
        return $this->_tags;
    }

    /**
     * Get only the one best veg-related tag, if any.
     */
    public function getVegTag()
    {
        $veg_tag = NULL;
        $tags = $this->getTags();
        if (!empty($tags)) {
            foreach ($tags as $tag) {
                // Since the veg-related tags are already ordered by priority,
                // we can simply return the first one in the list.
                if (in_array($tag->tagName, array('vegan', 'vegetarian', 'veg-friendly'))) {
                    $veg_tag = $tag;
                    break;
                }
            }
        }
        return $veg_tag;
    }


    /**
     * Get only the one best veg-related tag name, or empty string.
     */
    public function getVegTagName()
    {
        $veg_tag_name = '';
        $tag = $this->getVegTag();
        if (!empty($tag)) {
            $veg_tag_name = $tag->tagName;
        }
        return $veg_tag_name;
    }


    public function setQuotes($value)
    {
        $this->_quotes = (object)$value;
        return $this;
    }

    public function getQuotes()
    {
        if ($this->_quotes == NULL) {
            // Lazy-load from our DB
            $options = array('Person' => $this,
                            );
            $this->_quotes = new fdvegan_QuoteCollection($options);
        }
        return $this->_quotes;
    }

    public function getQuoteText()
    {
        $ret_val = '';
        if ($this->getQuotes()->count()) {
            $ret_val = $this->getQuotes()[0]->getQuote();
        }
        return $ret_val;
    }


    public function setCredits($value)
    {
        $this->_credits = (object)$value;
        return $this;
    }

   public function getCredits()
    {
        if ($this->_credits == NULL) {
            // Lazy-load from our DB
            $options = array('PersonId'        => $this->getPersonId(),
                             'TmdbId'          => $this->getTmdbId(),
                             'RefreshFromTmdb' => $this->getRefreshFromTmdb(),
                            );
            $this->_credits = new fdvegan_CreditCollection($options);
        }
        return $this->_credits;
    }

    public function getNumCredits()
    {
        return $this->getCredits()->count();
    }


    public function setPersonImages($value)
    {
        $this->_person_images = (object)$value;
        return $this;
    }

    public function getPersonImages()
    {
        if ($this->_person_images == NULL) {
            // Lazy-load from our DB
            $options = array('Person'          => $this,
                             'MediaType'       => 'person',
                             'RefreshFromTmdb' => $this->getRefreshFromTmdb(),
                             'ScrapeFromTmdb'  => $this->getScrapeFromTmdb(),
                            );
            $this->_person_images = new fdvegan_MediaSizeCollection($options);
        }
        return $this->_person_images;
    }


    /**
     * Get the best image URL for this person.
     *
     * @param string $size    Valid values are: "s,m,l,o" or: 'small', 'medium', 'large', or 'original'.
     * @return string  URL or ''
     *                 e.g.: "https://fivedegreevegan.aprojects.org/pictures/tmdb/person/s/123-1.jpg"
     */
    public function getImagePath($media_size = 'medium', $orUseDefault = true)
    {
        $size = substr($media_size, 0, 1);
        return $this->getPersonImages()[$size][0]->getPath();
    }


    public function storePerson()
    {
        if ($this->getPersonId()) {  // Must already exist in our DB, so is an update.
            $sql = <<<__SQL__
UPDATE {fdvegan_person} SET 
       `tmdbid` = :tmdbid, 
       `tmdb_image_path` = :tmdb_image_path, 
       `imdb_id` = :imdb_id, 
       `full_name` = :full_name, 
       `first_name` = :first_name, 
       `middle_name` = :middle_name, 
       `last_name` = :last_name, 
       `gender` = :gender, 
       `rating` = :rating, 
       `homepage_url` = :homepage_url, 
       `biography` = :biography, 
       `birthplace` = :birthplace, 
       `birthday` = :birthday, 
       `deathday` = :deathday, 
       `updated` = now(), 
       `synced` = :synced 
 WHERE `person_id` = :person_id
__SQL__;
            try {
                $sql_params = array(':person_id'       => $this->getPersonId(),
                                    ':tmdbid'          => $this->getTmdbId(),
                                    ':tmdb_image_path' => $this->getTmdbImagePath(),
                                    ':imdb_id'         => $this->getImdbId(),
                                    ':full_name'       => $this->getFullName(),
                                    ':first_name'      => $this->getFirstName(),
                                    ':middle_name'     => $this->getMiddleName(),
                                    ':last_name'       => $this->getLastName(),
                                    ':gender'          => $this->getGender(),
                                    ':rating'          => $this->getRating(),
                                    ':homepage_url'    => $this->getHomepageUrl(),
                                    ':biography'       => $this->getBiography(),
                                    ':birthplace'      => $this->getBirthplace(),
                                    ':birthday'        => $this->getBirthday(),
                                    ':deathday'        => $this->getDeathday(),
                                    ':synced'          => $this->getSynced(),
                                   );
                //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
                $result = db_query($sql, $sql_params);
            }
            catch (Exception $e) {
                throw new FDVegan_PDOException("Caught exception: {$e->getMessage()} while UPDATing person: ". print_r($this,1), $e->getCode(), $e, 'LOG_ERR');
            }
            fdvegan_Content::syslog('LOG_DEBUG', 'Updated person in our DB: person_id='. $this->_person_id .', full_name="'. $this->_full_name . '"');

        } else {  // Must be a new person to our DB, so is an insert.

            try {
                if (empty($this->getCreated())) {
                    $this->setCreated(date('Y-m-d G:i:s'));
                }
                $this->_person_id = db_insert('fdvegan_person')
                ->fields(array(
                  'tmdbid'            => $this->getTmdbId(),
                  'tmdb_image_path'   => $this->getTmdbImagePath(),
                  'imdb_id'           => $this->getImdbId(),
                  'full_name'         => $this->getFullName(),
                  'first_name'        => $this->getFirstName(),
                  'middle_name'       => $this->getMiddleName(),
                  'last_name'         => $this->getLastName(),
                  'gender'            => $this->getGender(),
                  'rating'            => $this->getRating(),
                  'homepage_url'      => $this->getHomepageUrl(),
                  'biography'         => $this->getBiography(),
                  'birthplace'        => $this->getBirthplace(),
                  'birthday'          => $this->getBirthday(),
                  'deathday'          => $this->getDeathday(),
                  'created'           => $this->getCreated(),
                  'synced'            => $this->getSynced(),
                ))
                ->execute();
            }
            catch (Exception $e) {
                throw new FDVegan_PDOException("Caught exception: {$e->getMessage()} while INSERTing person: ". print_r($this,1), $e->getCode(), $e, 'LOG_ERR');
            }
            fdvegan_Content::syslog('LOG_DEBUG', "Inserted new person into our DB: person_id={$this->getPersonId()}, full_name=\"{$this->getFullName()}\".");
        }

        return $this->getPersonId();
    }


    /**
     * Recursive function to getDegreesBetweenPersons
     *
     * @return array $degrees  Custom array of connection descriptions
     */
    public function getDegreesBetweenPersons($persons) {

throw new FDVegan_NotImplementedException('getDegreesBetweenPersons() not implemented yet.');

        if (!isset($persons)) {
            return NULL;
        }
        if (count($persons) > 2) {
            $degrees = array($this->getDegreesBetweenPersons(array_shift($persons)));
            if (isset($degrees) && is_array($degrees)) {
                $degrees[] = $this->getDegreesBetweenPersons($persons);
            }
            return $degrees;
        }
        // So there must only be one $persons
        $person = $persons;
        if (is_array($persons)) { // break out of wrapping array()
            $person = $persons[0];
        }

        $this->getCredits();

        foreach ($this->getCredits() as $credit) {
            $movie = new fdvegan_Movie($credit->movie_id);

            if (isActorInMovie($actor[1], $movie)) {
                break;
            }

            if ($person->getPersonId() == $credit->getPersonId()) {
                // We found a connection!
                $character2 = '@TODO';
                $degrees = array('Person'     => $this->getFullName(),
                                 'Movie'      => $movie->getTitle(),
                                 'Character'  => $credit->getCharacter(),
                                 'Person2'    => $person->getFullName(),
                                 'Character2' => $character2
                                );
                return $degrees;
            }


//        $persons[0]->getCredits();
//        $persons[1]->getCredits();

        }
    }


    public function loadPersonFromTmdbById()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN loadPersonFromTmdbById({$this->getPersonId()}) TmdbId={$this->getTmdbId()}.");
        if (empty($this->getTmdbId())) {
            fdvegan_Content::syslog('LOG_INFO', "loadPersonFromTmdbById({$this->getPersonId()}) TmdbId unknown for person.");
            return FALSE;
        }

        if ($this->isStale()) {
            fdvegan_Content::syslog('LOG_DEBUG', "PersonId={$this->getPersonId()} TmdbId={$this->getTmdbId()} data stale, so reloading, updated={$this->getUpdated()}.");
            $tmdb_api = new fdvegan_Tmdb();
            $tmdb_person = $tmdb_api->getPerson($this->getTmdbId());
            $this->_validateFields($tmdb_person);  // Fix any TMDb data issues.

            $this->setTmdbData($tmdb_person);
            //fdvegan_Content::syslog('LOG_DEBUG', "TMDb provided TmdbId={$this->getTmdbId()} data: ". print_r($this->getTmdbData(),1));
            if (!empty($this->getTmdbData())) {
                $this->setTmdbImagePath($this->_tmdb_data['profile_path'], FALSE);  // TMDb often doesn't return some fields, so don't set them if empty
                $this->setImdbId($this->_tmdb_data['imdb_id'], FALSE);
                $this->setFullName(fdvegan_Util::getSafeName($this->_tmdb_data['name']), FALSE);
                $this->setFirstName(fdvegan_Util::getSafeName($this->_tmdb_data['first_name']), FALSE);
                $this->setMiddleName(fdvegan_Util::getSafeName($this->_tmdb_data['middle_name']), FALSE);
                $this->setLastName(fdvegan_Util::getSafeName($this->_tmdb_data['last_name']), FALSE);
                $this->setGender($this->_tmdb_data['gender'], FALSE);
                $this->setRating($this->_tmdb_data['popularity'] * 1000, FALSE);  // TMDb popularity is a decimal #. Eg: 3.535488
                $this->setHomepageUrl($this->_tmdb_data['homepage'], FALSE);
                $this->setBiography($this->_tmdb_data['biography'], FALSE);
                $this->setBirthplace($this->_tmdb_data['place_of_birth'], FALSE);
                $this->setBirthday($this->_tmdb_data['birthday'], FALSE);
                $this->setDeathday($this->_tmdb_data['deathday'], FALSE);
                $this->setUpdated(date('Y-m-d G:i:s'));
                $this->setSynced(date('Y-m-d G:i:s'));
                // Note - FDV stores actor "adult-rated" as a tag, not an explicit person field,
                //        so if you ever want to implement storing the TMDb "adult" field returned here,
                //        it would be a separate function call to fdvegan_person_tag::($this->_tmdb_data['adult']).
            }
            $this->storePerson();  // even if just for the `updated` & `synced` fields
        } else {
            fdvegan_Content::syslog('LOG_DEBUG', "Tmdb_Id={$this->getTmdbId()} data still fresh, so not reloading, synced={$this->getSynced()}.");
        }

        if ($this->getRefreshFromTmdb()) {
// Note - the following is now being done in fdvegan_batch_process() instead.
            // Next, load this person's movie credits from TMDb, and update our DB.
//            $this->getCredits();
//            fdvegan_Content::syslog('LOG_DEBUG', "Found ({$this->getCredits()->count()}) credits for {$this->getFullName()}.");

            // Note - the following is now being done in fdvegan_batch_process() instead.
            // Next, loop through all credits and load any movies from TMDb that are missing from our DB, and update our DB.
//            $options = array('RefreshFromTmdb' => $this->getRefreshFromTmdb());
//            $this->getCredits()->loadEachMovie($options);
        }
        if ($this->getScrapeFromTmdb()) {
            $this->getPersonImages();
            fdvegan_Content::syslog('LOG_DEBUG', "Scraped ({$this->getPersonImages()->count()}) actor images for {$this->getFullName()}.");
        }
        fdvegan_Content::syslog('LOG_DEBUG', "END loadPersonFromTmdbById({$this->getPersonId()}) TmdbId={$this->getTmdbId()}.");

        return $this->getPersonId();
    }



    //////////////////////////////



    /**
     * @throws FDVeganNotFoundException    When person is not in the FDV DB.
     */
    private function _processLoadPersonResult($result)
    {
        if ($result->rowCount() == 1) {
            foreach ($result as $row) {
                $this->setPersonId($row->person_id);
                $this->setTmdbId($row->tmdbid);
                $this->setTmdbImagePath($row->tmdb_image_path);
                $this->setImdbId($row->imdb_id);
                $this->setFullName($row->full_name);
                $this->setFirstName($row->first_name);
                $this->setMiddleName($row->middle_name);
                $this->setLastName($row->last_name);
                $this->setGender($row->gender);
                $this->setRating($row->rating);
                $this->setHomepageUrl($row->homepage_url);
                $this->setBiography($row->biography);
                $this->setBirthplace($row->birthplace);
                $this->setBirthday($row->birthday);
                $this->setDeathday($row->deathday);
                $this->setCreated($row->created);
                $this->setUpdated($row->updated);
                $this->setSynced($row->synced);
            }
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded person from our DB; person_id={$this->getPersonId()}" .
                                ", tmdbid={$this->getTmdbId()}" .
                                ", imdb_id={$this->getImdbId()}" .
                                ", full_name={$this->getFullName()}."
                               );

        if ($this->getRefreshFromTmdb()) {
            $this->loadPersonFromTmdbById();
        } elseif ($result->rowCount() != 1) {
            throw new FDVegan_NotFoundException("person_id={$this->getPersonId()} not found");
        }

        return $this->getPersonId();
    }


    private function _loadPersonByPersonId()
    {
        $sql = <<<__SQL__
SELECT `person_id`, `tmdbid`, `tmdb_image_path`, `imdb_id`, `full_name`, `first_name`, `middle_name`, `last_name`, 
       `gender`, `rating`, `homepage_url`, `biography`, `birthplace`, `birthday`, `deathday`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_person} 
 WHERE `person_id` = :person_id
__SQL__;
        $sql_params = array(':person_id' => $this->getPersonId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded person from our DB by person_id={$this->getPersonId()}.");
        return $this->_processLoadPersonResult($result);
    }


    private function _loadPersonByFullName()
    {
        $sql = <<<__SQL__
SELECT `person_id`, `tmdbid`, `tmdb_image_path`, `imdb_id`, `full_name`, `first_name`, `middle_name`, `last_name`, 
       `gender`, `rating`, `homepage_url`, `biography`, `birthplace`, `birthday`, `deathday`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_person} 
 WHERE `full_name` = :full_name 
 LIMIT 1
__SQL__;
        $sql_params = array(':full_name' => $this->getFullName());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded person from our DB by full_name={$this->getFullName()}.");
        return $this->_processLoadPersonResult($result);
    }


}

