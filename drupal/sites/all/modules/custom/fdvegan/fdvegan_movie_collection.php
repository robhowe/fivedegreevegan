<?php
/**
 * fdvegan_movie_collection.php
 *
 * Implementation of Movie Collection class for module fdvegan.
 * Stores a collection of movies.
 *
 * PHP version 5.6
 *
 * @category   Movie
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */


class fdvegan_MovieCollection extends fdvegan_BaseCollection
{
    protected $_title               = NULL;  // Filter for Title lookup
    protected $_having_tmdbid       = NULL;  // Bool flag to determine whether to load movies with no Tmdb info
    protected $_having_fdv_count_gt = NULL;  // Int "flag" to decide whether to load the slow `fdv_count` column


    public function __construct($options = NULL)
    {
        parent::__construct($options);
    }


    public function setTitle($value)
    {
        $this->_title = substr(trim($value), 0, 254);
        return $this;
    }

    public function getTitle()
    {
        return $this->_title;
    }


    public function setHavingTmdbId($value)
    {
        // Special flag to decide whether to load movies with no Tmdb info.
        $this->_having_tmdbid = (bool)$value;
        return $this;
    }

    public function getHavingTmdbId()
    {
        return $this->_having_tmdbid;
    }


    public function setHavingFDVCountGT($value)
    {
        //  Special int "flag" to decide whether to load the slow `fdv_count` column.
        $this->_having_fdv_count_gt = (int)$value;
        return $this;
    }

    public function getHavingFDVCountGT()
    {
        return $this->_having_fdv_count_gt;
    }


    /**
     * Load all movies from our database.
     */
    public function loadMoviesArray()
    {
        $sql = <<<__SQL__
SELECT {fdvegan_movie}.`movie_id`, {fdvegan_movie}.`tmdbid`, 
       {fdvegan_movie}.`tmdb_image_path`, {fdvegan_movie}.`tmdb_moviebackdrop_image_path`, {fdvegan_movie}.`imdb_id`, 
       {fdvegan_movie}.`title`, {fdvegan_movie}.`release_date`, {fdvegan_movie}.`adult_rated`, {fdvegan_movie}.`rating`, 
       {fdvegan_movie}.`homepage_url`, {fdvegan_movie}.`tagline`, {fdvegan_movie}.`overview`, 
       {fdvegan_movie}.`status`, {fdvegan_movie}.`budget`, {fdvegan_movie}.`revenue`, {fdvegan_movie}.`runtime`, 
       {fdvegan_movie}.`created`, {fdvegan_movie}.`updated`, {fdvegan_movie}.`synced`, 
__SQL__;
        if (is_null($this->_having_fdv_count_gt)) {
            $sql .= <<<__SQL__
       NULL `fdv_count` 
  FROM {fdvegan_movie} 
__SQL__;
            if (!empty($this->_having_tmdbid)) {
                $sql .= <<<__SQL__
 WHERE {fdvegan_movie}.`tmdbid` IS NOT NULL 
__SQL__;
            }
        } else {
            $sql .= <<<__SQL__
       COUNT({fdvegan_cast_list}.`movie_id`) `fdv_count` 
  FROM {fdvegan_cast_list} 
  JOIN {fdvegan_movie} ON {fdvegan_movie}.`movie_id` = {fdvegan_cast_list}.`movie_id` 
__SQL__;
            if (!empty($this->_having_tmdbid)) {
                $sql .= <<<__SQL__
 WHERE {fdvegan_movie}.`tmdbid` IS NOT NULL 
__SQL__;
            }
            $sql .= <<<__SQL__
 GROUP BY {fdvegan_cast_list}.`movie_id` 
HAVING `fdv_count` > {$this->_having_fdv_count_gt} 
__SQL__;
        }
        $sql .= <<<__SQL__
 ORDER BY {fdvegan_movie}.`release_date` DESC, {fdvegan_movie}.`title` ASC, {fdvegan_movie}.`adult_rated` ASC 
__SQL__;
        if (($this->_start > 0) || ($this->_limit > 0)) {
            $sql .= <<<__SQL__
 LIMIT {$this->_start}, {$this->_limit}
__SQL__;
        }

        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded {$result->rowCount()} movies from our DB.");

        return $this->_processLoadMoviesResult($result);
    }


    public function loadMoviesByTitle()
    {
        $title_wildcarded = fdvegan_Util::convertSearchStrToWildcarded($this->_title);
        $sql = <<<__SQL__
SELECT {fdvegan_movie}.`movie_id`, {fdvegan_movie}.`tmdbid`, 
       {fdvegan_movie}.`tmdb_image_path`, {fdvegan_movie}.`tmdb_moviebackdrop_image_path`, {fdvegan_movie}.`imdb_id`, 
       {fdvegan_movie}.`title`, {fdvegan_movie}.`release_date`, {fdvegan_movie}.`adult_rated`, {fdvegan_movie}.`rating`, 
       {fdvegan_movie}.`homepage_url`, {fdvegan_movie}.`tagline`, {fdvegan_movie}.`overview`, 
       {fdvegan_movie}.`status`, {fdvegan_movie}.`budget`, {fdvegan_movie}.`revenue`, {fdvegan_movie}.`runtime`, 
__SQL__;
        if (is_null($this->_having_fdv_count_gt)) {
            $sql .= <<<__SQL__
       NULL `fdv_count`, 
       {fdvegan_movie}.`created`, {fdvegan_movie}.`updated`, {fdvegan_movie}.`synced` 
  FROM {fdvegan_movie} 
 WHERE {fdvegan_movie}.`title` LIKE :title_wildcarded 
__SQL__;
            if (!empty($this->_having_tmdbid)) {
                $sql .= <<<__SQL__
   AND {fdvegan_movie}.`tmdbid` IS NOT NULL 
__SQL__;
            }
        } else {
            $sql .= <<<__SQL__
       COUNT({fdvegan_cast_list}.`movie_id`) `fdv_count`, 
       {fdvegan_movie}.`created`, {fdvegan_movie}.`updated`, {fdvegan_movie}.`synced` 
  FROM {fdvegan_cast_list} 
  JOIN {fdvegan_movie} ON {fdvegan_movie}.`movie_id` = {fdvegan_cast_list}.`movie_id` 
  JOIN {fdvegan_person} ON {fdvegan_person}.`person_id` = {fdvegan_cast_list}.`person_id` 
 WHERE {fdvegan_movie}.`title` LIKE :title_wildcarded 
__SQL__;
            if (!empty($this->_having_tmdbid)) {
                $sql .= <<<__SQL__
   AND {fdvegan_movie}.`tmdbid` IS NOT NULL 
__SQL__;
            }
            $sql .= <<<__SQL__
 GROUP BY {fdvegan_cast_list}.`movie_id` 
HAVING `fdv_count` > {$this->_having_fdv_count_gt} 
__SQL__;
        }
        $sql .= <<<__SQL__
 ORDER BY {fdvegan_movie}.`release_date` DESC, {fdvegan_movie}.`title` ASC, {fdvegan_movie}.`adult_rated` ASC 
__SQL__;
        if (($this->_start > 0) || ($this->_limit > 0)) {
            $sql .= <<<__SQL__
 LIMIT {$this->_start}, {$this->_limit}
__SQL__;
        }

        $sql_params = array(':title_wildcarded' => $title_wildcarded);
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded {$result->rowCount()} movies from our DB by title={$this->_title}.");

        return $this->_processLoadMoviesResult($result);
    }


    /**
     * Retrieve all movies from our database, but only return minimal data for them.
     *
     * This function is optimized for use by fdvegan.module::fdvegan_movie_form() so the
     *  select-dropdown is generated quickly.  The data here is stored via variable_set()
     *  so it doesn't have to be regenerated constantly.
     *
     * @return array  An assoc array of MovieId => Title for all movies in the DB.
     */
    public function getMinMoviesArray()
    {
// @TODO need to add an isStale() check to this eventually!
// @TODO this is deprecated in Drupal 8, and should be done differently (per page, not site-wide) anyway.
        $this->_items = variable_get('fdvegan_min_movies_array', NULL);
        if (empty($this->_items)) {
            $sql = <<<__SQL__
SELECT {fdvegan_movie}.`movie_id`, {fdvegan_movie}.`title` 
  FROM {fdvegan_movie} 
 WHERE {fdvegan_movie}.`tmdbid` IS NOT NULL 
 ORDER BY {fdvegan_movie}.`title` ASC, {fdvegan_movie}.`release_date` DESC, {fdvegan_movie}.`adult_rated` ASC
__SQL__;
            //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql));
            $result = db_query($sql);
            //fdvegan_Content::syslog('LOG_DEBUG', "Loaded {$result->rowCount()} min movies from our DB.");
            $this->_items[''] = '';
            foreach ($result as $row) {
                $this->_items[$row->movie_id] = $row->title;
            }
            variable_set('fdvegan_min_movies_array', $this->_items);
        } else {
            fdvegan_Content::syslog('LOG_DEBUG', 'using cached array.');
        }
        return $this->getItems();
    }



    //////////////////////////////



    /**
     * @throws FDVeganNotFoundException    When no matching movies are found in the FDV DB.
     */
    private function _processLoadMoviesResult($result)
    {
        if ($result->rowCount() < 1) {
            throw new FDVegan_NotFoundException("no matching movies found");
        }
        foreach ($result as $row) {
            $options = array('MovieId'       => $row->movie_id,
                             'TmdbId'        => $row->tmdbid,
                             'TmdbImagePath' => $row->tmdb_image_path,
                             'TmdbMoviebackdropImagePath' => $row->tmdb_moviebackdrop_image_path,
                             'ImdbId'        => $row->imdb_id,
                             'Title'         => $row->title,
                             'ReleaseDate'   => $row->release_date,
                             'AdultRated'    => $row->adult_rated,
                             'Rating'        => $row->rating,
                             'HomepageUrl'   => $row->homepage_url,
                             'Tagline'       => $row->tagline,
                             'Overview'      => $row->overview,
                             'Status'        => $row->status,
                             'Budget'        => $row->budget,
                             'Revenue'       => $row->revenue,
                             'Runtime'       => $row->runtime,
                             'FDVCount'      => $row->fdv_count,
                             'Created'       => $row->created,
                             'Updated'       => $row->updated,
                             'Synced'        => $row->synced,
                            );
            $this->_items[] = new fdvegan_Movie($options);
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded '{$result->rowCount()}' movies from our DB.");

        return $this->getItems();
    }


}

