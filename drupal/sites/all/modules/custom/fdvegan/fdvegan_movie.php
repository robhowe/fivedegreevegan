<?php
/**
 * fdvegan_movie.php
 *
 * Implementation of Movie class for module fdvegan.
 * Stores all info related to a single movie.
 *
 * PHP version 5.6
 *
 * @category   Movie
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */


class fdvegan_Movie extends fdvegan_BaseClass
{
    protected $_movie_id          = NULL;
    protected $_tmdbid            = NULL;
    protected $_tmdb_image_path   = NULL;  // movie poster image filename on TMDb
    protected $_tmdb_moviebackdrop_image_path = NULL;  // movie backdrop image filename on TMDb
    protected $_imdb_id           = NULL;
    protected $_title             = NULL;
    protected $_release_date      = NULL;  // in format: YYYY-MM-DD
    protected $_adult_rated       = NULL;  // adult-rated flag (0 or 1)
    protected $_rating            = NULL;  // number of upvotes (minus downvotes). Initialized from TMDb "popularity"
    protected $_homepage_url      = NULL;  // URL to externally hosted homepage of movie, if any
    protected $_tagline           = NULL;  // movie tagline/slogan text
    protected $_overview          = NULL;  // movie overview/description text
    protected $_status            = NULL;  // movie status description, eg: "Released"
    protected $_budget            = NULL;  // total cost to make the movie, in USD
    protected $_revenue           = NULL;  // total gross revenue of the movie to-date, in USD
    protected $_runtime           = NULL;  // movie length in minutes

    protected $_genres            = NULL;  // all genres the movie is in
    protected $_credits           = NULL;  // combined cast & crew credits
    protected $_movie_images      = NULL;  // all images of all sizes of type 'movie'
    protected $_moviebackdrop_images = NULL;  // all images of all sizes of type 'moviebackdrop'

    /* Data fields received from external sources (TMDb) that may need to be validated.
     * Array format: field_name => default_value
     * see @fdvegan_BaseClass::_validateFields
     */
    protected $_data_fields = array('poster_path' => NULL, 'backdrop_path' => NULL, 'imdb_id' => NULL,
        'title' => '', 'release_date' => NULL, 'adult' => NULL, 'popularity' => 0,
        'homepage' => NULL, 'tagline' => NULL, 'overview' => NULL, 'status' => NULL,
        'budget' => NULL, 'revenue' => NULL, 'runtime' => NULL);


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if (!empty($options['MovieId'])) {
            $this->_loadMovieByMovieId();
        } elseif (!empty($options['TmdbId'])) {
            $this->_loadMovieByTmdbId();
        } elseif (!empty($options['Title'])) {
            $this->_loadMovieByTitle();
        }
    }


    public function setMovieId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_movie_id = (int)$value;
        }
        return $this;
    }

    public function getMovieId()
    {
        return $this->_movie_id;
    }

    /**
     * Convenience function.
     */
    public function getId()
    {
        return $this->getMovieId();
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


    public function setTmdbMoviebackdropImagePath($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_tmdb_moviebackdrop_image_path = substr($value, 0, 254);
        }
        return $this;
    }

    public function getTmdbMoviebackdropImagePath()
    {
        return $this->_tmdb_moviebackdrop_image_path;
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


    public function setTitle($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_title = substr($value, 0, 254);
        }
        return $this;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Convenience function.
     */
    public function getName()
    {
        return $this->getTitle();
    }


    public function setReleaseDate($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_release_date = substr($value, 0, 10);
        }
        return $this;
    }

    public function getReleaseDate()
    {
        return $this->_release_date;
    }


    public function setAdultRated($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value) || ($value === FALSE)) {
            $this->_adult_rated = (int)$value;
        }
        return $this;
    }

    public function getAdultRated()
    {
        return $this->_adult_rated;
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


    public function setTagline($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_tagline = substr($value, 0, 1024);
        }
        return $this;
    }

    public function getTagline()
    {
        return $this->_tagline;
    }


    public function setOverview($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_overview = substr($value, 0, 4096);
        }
        return $this;
    }

    public function getOverview()
    {
        return $this->_overview;
    }


    public function setStatus($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_status = substr($value, 0, 254);
        }
        return $this;
    }

    public function getStatus()
    {
        return $this->_status;
    }


    public function setBudget($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value) || is_numeric($value)) {
            $this->_budget = (int)$value;
        }
        return $this;
    }

    public function getBudget()
    {
        return $this->_budget;
    }


    public function setRevenue($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value) || is_numeric($value)) {
            $this->_revenue = (int)$value;
        }
        return $this;
    }

    public function getRevenue()
    {
        return $this->_revenue;
    }


    public function setRuntime($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value) || is_numeric($value)) {
            $this->_runtime = (int)$value;
        }
        return $this;
    }

	/**
	 * Returns the movie length in minutes.
	 *
     * @return int    Number of minutes or NULL.
	 */
    public function getRuntime()
    {
        return $this->_runtime;
    }


    /**
     * Get the FDV Level.
     */
    public function getFdvCount()
    {
        return $this->getNumCredits() - 1;
    }


    public function getTmdbInfoUrl()
    {
        return fdvegan_Tmdb::getTmdbMovieInfoUrl($this->getTmdbId());
    }


    public function getImdbInfoUrl()
    {
        return fdvegan_Imdb::getImdbMovieInfoUrl($this->getImdbId());
    }


    public function setCredits($value)
    {
        $this->_credits = (object)$value;
        return $this;
    }

    public function getCredits($refresh_from_tmdb = FALSE)
    {
        if ($this->_credits == NULL) {
            // Lazy-load from our DB
            $options = array('MovieId'         => $this->getMovieId(),
                             'TmdbId'          => $this->getTmdbId(),
                             'RefreshFromTmdb' => $refresh_from_tmdb);
            $this->_credits = new fdvegan_CreditCollection($options);
        }
        return $this->_credits;
    }

    public function getNumCredits()
    {
        return $this->getCredits()->count();
    }


    public function setMovieImages($value)
    {
        $this->_movie_images = (object)$value;
        return $this;
    }

    public function getMovieImages()
    {
        if ($this->_movie_images == NULL) {
            // Lazy-load from our DB
            $options = array('Movie'           => $this,
                             'MediaType'       => 'movie',
                             'RefreshFromTmdb' => $this->getRefreshFromTmdb(),
                             'ScrapeFromTmdb'  => $this->getScrapeFromTmdb(),
                            );
            $this->_movie_images = new fdvegan_MediaSizeCollection($options);
        }
        return $this->_movie_images;
    }


    public function setMoviebackdropImages($value)
    {
        $this->_moviebackdrop_images = (object)$value;
        return $this;
    }

    public function getMoviebackdropImages()
    {
        if ($this->_moviebackdrop_images == NULL) {
            // Lazy-load from our DB
            $options = array('Movie'           => $this,
                             'MediaType'       => 'moviebackdrop',
                             'RefreshFromTmdb' => $this->getRefreshFromTmdb(),
                             'ScrapeFromTmdb'  => $this->getScrapeFromTmdb(),
                            );
            $this->_moviebackdrop_images = new fdvegan_MediaSizeCollection($options);
        }
        return $this->_moviebackdrop_images;
    }


    /**
     * Get the best image URL for this movie.
     *
     * @param string $media_type    Valid values are: "movie" or "moviebackdrop".
     * @param string $media_size    Valid values are: "s,m,l,o" or: 'small', 'medium', 'large', or 'original'.
     * @return string  URL or ''
     *                 e.g.: "https://fivedegreevegan.aprojects.org/pictures/tmdb/person/s/person-s-0-1.jpg"
     * @throws FDVegan_InvalidArgumentException
     */
    public function getImagePath($media_type = 'movie', $media_size = 'medium', $orUseDefault = true)
    {
        $size = substr($media_size, 0, 1);
        if ($media_type === 'movie') {
            $ret = $this->getMovieImages()[$size][0]->getPath();
        } elseif ($media_type === 'moviebackdrop') {
            $ret = $this->getMoviebackdropImages()[$size][0]->getPath();
        } else {
            throw new FDVegan_InvalidArgumentException("fdvegan_movie->getImagePath('{$$media_type}','$media_size',$orUseDefault) invalid type");
        }
        return $ret;
    }


    /**
     * @throws FDVegan_PDOException
     */
    public function storeMovie()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN storeMovie({$this->getMovieId()}), title=\"{$this->getTitle()}\".");
        if ($this->getMovieId()) {  // Must already exist in our DB, so is an update.
            $sql = <<<__SQL__
UPDATE {fdvegan_movie} SET 
       `tmdbid` = :tmdbid, 
       `tmdb_image_path` = :tmdb_image_path, 
       `tmdb_moviebackdrop_image_path` = :tmdb_moviebackdrop_image_path, 
       `imdb_id` = :imdb_id, 
       `title` = :title, 
       `release_date` = :release_date, 
       `adult_rated` = :adult_rated, 
       `rating` = :rating, 
       `homepage_url` = :homepage_url, 
       `tagline` = :tagline, 
       `overview` = :overview, 
       `status` = :status, 
       `budget` = :budget, 
       `revenue` = :revenue, 
       `runtime` = :runtime, 
       `updated` = now(), 
       `synced` = :synced 
 WHERE `movie_id` = :movie_id
__SQL__;
            try {
                $sql_params = array(':movie_id'        => $this->getMovieId(),
                                    ':tmdbid'          => $this->getTmdbId(),
                                    ':tmdb_image_path' => $this->getTmdbImagePath(),
                                    ':tmdb_moviebackdrop_image_path' => $this->getTmdbMoviebackdropImagePath(),
                                    ':imdb_id'         => $this->getImdbId(),
                                    ':title'           => fdvegan_Util::getSafeName($this->getTitle()),
                                    ':release_date'    => $this->getReleaseDate(),
                                    ':adult_rated'     => $this->getAdultRated(),
                                    ':rating'          => $this->getRating(),
                                    ':homepage_url'    => $this->getHomepageUrl(),
                                    ':tagline'         => $this->getTagline(),
                                    ':overview'        => $this->getOverview(),
                                    ':status'          => $this->getStatus(),
                                    ':budget'          => $this->getBudget(),
                                    ':revenue'         => $this->getRevenue(),
                                    ':runtime'         => $this->getRuntime(),
                                    ':synced'          => $this->getSynced(),
                                   );
                //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
                $result = db_query($sql, $sql_params);
            }
            catch (Exception $e) {
                throw new FDVegan_PDOException("Caught exception: {$e->getMessage()} while UPDATing movie: ". print_r($this,1), $e->getCode(), $e, 'LOG_ERR');
            }
            fdvegan_Content::syslog('LOG_DEBUG', "Updated movie in our DB: movie_id={$this->_movie_id}, title=\"{$this->_title}\"");

        } else {  // Must be a new movie to our DB, so is an insert.

            try {
                if (empty($this->getCreated())) {
                    $this->setCreated(date('Y-m-d G:i:s'));
                }
                $this->_movie_id = db_insert('fdvegan_movie')
                ->fields(array(
                  'tmdbid'            => $this->getTmdbId(),
                  'tmdb_image_path'   => $this->getTmdbImagePath(),
                  'tmdb_moviebackdrop_image_path' => $this->getTmdbMoviebackdropImagePath(),
                  'imdb_id'           => $this->getImdbId(),
                  'title'             => fdvegan_Util::getSafeName($this->getTitle()),
                  'release_date'      => $this->getReleaseDate(),
                  'adult_rated'       => $this->getAdultRated(),
                  'rating'            => $this->getRating(),
                  'homepage_url'      => $this->getHomepageUrl(),
                  'tagline'           => $this->getTagline(),
                  'overview'          => $this->getOverview(),
                  'status'            => $this->getStatus(),
                  'budget'            => $this->getBudget(),
                  'revenue'           => $this->getRevenue(),
                  'runtime'           => $this->getRuntime(),
                  'created'           => $this->getCreated(),
                  'synced'            => $this->getSynced(),
                ))
                ->execute();
            }
            catch (Exception $e) {
                throw new FDVegan_PDOException("Caught exception: {$e->getMessage()} while INSERTing movie: ". print_r($this,1), $e->getCode(), $e, 'LOG_ERR');
            }
            fdvegan_Content::syslog('LOG_DEBUG', "Inserted new movie into our DB: movie_id={$this->getMovieId()}, title=\"{$this->getTitle()}\".");
        }

        return $this->getMovieId();
    }


    /**
     * Unlike loadPersonFromTmdbById(), this function needs to be public.
     */
    public function loadMovieFromTmdbById($load_credits = FALSE)
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN loadMovieFromTmdbById({$this->getMovieId()}) TmdbId={$this->getTmdbId()}.");
        if (empty($this->getTmdbId())) {
            fdvegan_Content::syslog('LOG_ERR', "loadMovieFromTmdbById({$this->getMovieId()}) TmdbId unknown for movie.");
            return FALSE;
        }

        if ($this->isStale()) {
            fdvegan_Content::syslog('LOG_DEBUG', "MovieId={$this->getMovieId()} TmdbId={$this->getTmdbId()} data stale, so reloading, updated={$this->getUpdated()}.");
            $tmdb_api = new fdvegan_Tmdb();
            $tmdb_movie = $tmdb_api->getMovie($this->getTmdbId());
            $this->_validateFields($tmdb_person);  // Fix any TMDb data issues.

            $this->setTmdbData($tmdb_movie);
            //fdvegan_Content::syslog('LOG_DEBUG', "TMDb provided TmdbId={$this->getTmdbId()} data: ". print_r($this->getTmdbData(),1));
            if (!empty($this->getTmdbData())) {
                $this->setTmdbImagePath($this->_tmdb_data['poster_path'], FALSE);  // TMDb often doesn't return some fields, so don't set them if empty
                $this->setTmdbMoviebackdropImagePath($this->_tmdb_data['backdrop_path'], FALSE);
                $this->setImdbId($this->_tmdb_data['imdb_id'], FALSE);
                $this->setTitle($this->_tmdb_data['title'], FALSE);
                $this->setReleaseDate($this->_tmdb_data['release_date'], FALSE);
                $this->setAdultRated($this->_tmdb_data['adult'], FALSE);
                $this->setRating($this->_tmdb_data['popularity'] * 1000);  // TMDb popularity is a decimal #. Eg: 5.481698
                $this->setHomepageUrl($this->_tmdb_data['homepage'], FALSE);
                $this->setTagline($this->_tmdb_data['tagline'], FALSE);
                $this->setOverview($this->_tmdb_data['overview'], FALSE);
                $this->setStatus($this->_tmdb_data['status'], FALSE);
                $this->setBudget($this->_tmdb_data['budget'], FALSE);
                $this->setRevenue($this->_tmdb_data['revenue'], FALSE);
                $this->setRuntime($this->_tmdb_data['runtime'], FALSE);
                $this->setUpdated(date('Y-m-d G:i:s'));
                $this->setSynced(date('Y-m-d G:i:s'));
            }
            /* Do NOT storeMovie() here!  All storing of TMDb data flows from the actors not the movies.
             * We only care about vegan actors (which are pre-filled via fdvegan.install
             * Whenever a movie actually needs to be stored, it will be done explicitly (outside of this class).
             */
            //$this->storeMovie();  // even if just for the `updated` & `synced` fields
        } else {
            fdvegan_Content::syslog('LOG_DEBUG', "TmdbId={$this->getTmdbId()} data still fresh, so not reloading, synced={$this->getSynced()}.");
        }

        if ($load_credits) {
            // Next, load this movie's cast_list from TMDb
            $tmdb_credits = $tmdb_api->getMovieCredits($this->getTmdbId());

// @TODO check TMDb return value here.
            //$this->_credits = $tmdb_credits;
            // Check our DB credit_list, and if needed, update our DB here.
            if (count($tmdb_credits) > 0) {
                // Delete all movie's credits in our DB & reload fresh ones.
// @TODO do the delete part.
                // Next, load this movie's cast credits from TMDb, and update our DB.
                $this->getCredits(TRUE);

//@TODO - safeguard for now
                if ($this->getCredits()->count() == 0) {
                    foreach ($tmdb_credits as $tmdb_category => $tmdb_cat_array) {
                        if (in_array($tmdb_category, explode(' ', 'cast crew')) && is_array($tmdb_cat_array)) {
                            foreach ($tmdb_cat_array as $tmdb_row) {
                                $movie_opts = array('MovieId' => $tmdb_row->id,
                                                    'Title'   => $tmdb_row->title);
                                $movie = new fdvegan_Movie($movie_opts);
                                fdvegan_Content::syslog('LOG_DEBUG', "MovieId={$movie->getMovieId()}, title=\"{$tmdb_row['title']}\".");
                            }
                        }
                    }
                }
            }
        }
        if ($this->getScrapeFromTmdb()) {
            $this->getMovieImages();
            fdvegan_Content::syslog('LOG_DEBUG', "Scraped ({$this->getMovieImages()->count()}) movie images for {$this->getTitle()}.");
            $this->getMoviebackdropImages();
            fdvegan_Content::syslog('LOG_DEBUG', "Scraped ({$this->getMoviebackdropImages()->count()}) moviebackdrop images for {$this->getTitle()}.");
        }
        fdvegan_Content::syslog('LOG_DEBUG', "END loadMovieFromTmdbById({$this->getMovieId()}) TmdbId={$this->getTmdbId()}.");

        return $this->getMovieId();
    }



    //////////////////////////////



    /**
     * @throws FDVeganNotFoundException    When movie is not in the FDV DB.
     */
    private function _processLoadMovieResult($result)
    {
        if ($result->rowCount() == 1) {
            foreach ($result as $row) {
                $this->setMovieId($row->movie_id);
                $this->setTmdbId($row->tmdbid);
                $this->setTmdbImagePath($row->tmdb_image_path);
                $this->setTmdbMoviebackdropImagePath($row->tmdb_moviebackdrop_image_path);
                $this->setImdbId($row->imdb_id);
                $this->setTitle($row->title);
                $this->setReleaseDate($row->release_date);
                $this->setAdultRated($row->adult_rated);
                $this->setRating($row->rating);
                $this->setHomepageUrl($row->homepage_url);
                $this->setTagline($row->tagline);
                $this->setOverview($row->overview);
                $this->setStatus($row->status);
                $this->setBudget($row->budget);
                $this->setRevenue($row->revenue);
                $this->setRuntime($row->runtime);
                $this->setCreated($row->created);
                $this->setUpdated($row->updated);
                $this->setSynced($row->synced);
            }
        }
        fdvegan_Content::syslog('LOG_DEBUG', 'Loaded movie from our DB; movie_id='. $this->getMovieId() .
                                ", tmdbid={$this->getTmdbId()}" .
                                ", imdb_id={$this->getImdbId()}" .
                                ", title={$this->getTitle()}."
                               );

        if ($this->getRefreshFromTmdb()) {
            $this->loadMovieFromTmdbById();
        } elseif ($result->rowCount() != 1) {
            throw new FDVegan_NotFoundException("movie_id={$this->getMovieId()} not found");
        }

        return $this->getMovieId();
    }


    private function _loadMovieByMovieId()
    {
        $sql = <<<__SQL__
SELECT `movie_id`, `tmdbid`, `tmdb_image_path`, `tmdb_moviebackdrop_image_path`, `imdb_id`, 
       `title`, `release_date`, `adult_rated`, `rating`, `homepage_url`, `tagline`, `overview`, 
       `status`, `budget`, `revenue`, `runtime`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_movie} 
 WHERE `movie_id` = :movie_id
__SQL__;
        $sql_params = array(':movie_id' => $this->getMovieId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', 'Loaded movie from our DB by movie_id='. $this->getMovieId() .'.');
        return $this->_processLoadMovieResult($result);
    }


    private function _loadMovieByTmdbId()
    {
        $sql = <<<__SQL__
SELECT `movie_id`, `tmdbid`, `tmdb_image_path`, `tmdb_moviebackdrop_image_path`, `imdb_id`, 
       `title`, `release_date`, `adult_rated`, `rating`, `homepage_url`, `tagline`, `overview`, 
       `status`, `budget`, `revenue`, `runtime`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_movie} 
 WHERE `tmdbid` = :tmdbid 
 LIMIT 1
__SQL__;
        $sql_params = array(':tmdbid' => $this->getTmdbId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', 'Loaded movie from our DB by Tmdbid='. $this->getTmdbId() . '.');
        return $this->_processLoadMovieResult($result);
   }


    private function _loadMovieByTitle()
    {
        $sql = <<<__SQL__
SELECT `movie_id`, `tmdbid`, `tmdb_image_path`, `tmdb_moviebackdrop_image_path`, `imdb_id`, 
       `title`, `release_date`, `adult_rated`, `rating`, `homepage_url`, `tagline`, `overview`, 
       `status`, `budget`, `revenue`, `runtime`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_movie} 
 WHERE `title` = :title 
 LIMIT 1
__SQL__;
        $sql_params = array(':title' => $this->getTitle());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', 'Loaded movie from our DB by title='. $this->getTitle() . '.');
        return $this->_processLoadMovieResult($result);
    }


}

