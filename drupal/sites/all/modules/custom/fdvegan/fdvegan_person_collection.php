<?php
/**
 * fdvegan_person_collection.php
 *
 * Implementation of Person Collection class for module fdvegan.
 * Stores a collection of actors.
 *
 * PHP version 5.6
 *
 * @category   Person
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */


class fdvegan_PersonCollection extends fdvegan_BaseCollection
{
    protected $_full_name     = NULL;  // Filter for FullName lookup
    protected $_having_tmdbid = NULL;  // Bool flag to determine whether to load persons with no Tmdb info
    protected $_having_fdv_count_gt = NULL;  // Int "flag" to decide whether to load the slow `fdv_count` column
    protected $_having_tags   = array();  // Array of tag names to filter for when loading persons


    public function __construct($options = NULL)
    {
        parent::__construct($options);
    }


    public function setFullName($value)
    {
        $this->_full_name = substr(trim($value), 0, 254);
        return $this;
    }

    public function getFullName()
    {
        return $this->_full_name;
    }


    public function setHavingTmdbId($value)
    {
        // Special flag to decide whether to load persons with no Tmdb info.
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


    public function setHavingTags($value)
    {
        // Special array of tag names to filter for when loading persons.
        $this->_having_tags = (array)$value;
        return $this;
    }

    public function getHavingTags()
    {
        return $this->_having_tags;
    }


    /**
     * Load persons (actors) from our database.
     * This is a catch-all convenience function.
     */
    public function loadPersons()
    {
        if (!empty($this->_full_name)) {
            return $this->loadPersonsByFullName();
        }
        if (!empty($this->_having_tags)) {
            return $this->loadPersonsByTags();
        }
        return $this->loadPersonsArray();
    }


    /**
     * Load all persons (actors) from our database;
     * no filters or validations at all.
     */
    public function loadPersonsArray()
    {
        $sql = <<<__SQL__
SELECT `person_id`, `tmdbid`, `tmdb_image_path`, `full_name`, `first_name`, `middle_name`, `last_name`, 
       `gender`, `rating`, `homepage_url`, `biography`, `birthplace`, `birthday`, `deathday`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_person} 
__SQL__;
        if (!empty($this->_having_tmdbid)) {
            $sql .= <<<__SQL__
 WHERE {fdvegan_person}.`tmdbid` IS NOT NULL 
__SQL__;
        }
        $sql .= <<<__SQL__
 ORDER BY `last_name` ASC, `first_name` ASC, `middle_name` ASC 
__SQL__;
        if (($this->_start > 0) || ($this->_limit > 0)) {
            $sql .= <<<__SQL__
 LIMIT {$this->_start}, {$this->_limit}
__SQL__;
        }

        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded all {$result->rowCount()} persons from our DB.");

        return $this->_processLoadPersonsResult($result);
    }


    public function loadPersonsByFullName()
    {
        $full_name_wildcarded = fdvegan_Util::convertSearchStrToWildcarded($this->_full_name);
        $sql = <<<__SQL__
SELECT `person_id`, `tmdbid`, `tmdb_image_path`, `full_name`, `first_name`, `middle_name`, `last_name`, 
       `gender`, `rating`, `homepage_url`, `biography`, `birthplace`, `birthday`, `deathday`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_person} 
 WHERE {fdvegan_person}.`full_name` LIKE :full_name_wildcarded 
__SQL__;
        if (!empty($this->_having_tmdbid)) {
            $sql .= <<<__SQL__
   AND {fdvegan_person}.`tmdbid` IS NOT NULL 
__SQL__;
        }
        $sql .= <<<__SQL__
 ORDER BY `last_name` ASC, `first_name` ASC, `middle_name` ASC 
__SQL__;
        if (($this->_start > 0) || ($this->_limit > 0)) {
            $sql .= <<<__SQL__
 LIMIT {$this->_start}, {$this->_limit}
__SQL__;
        }

        $sql_params = array(':full_name_wildcarded' => $full_name_wildcarded);
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded {$result->rowCount()} persons from our DB by full_name={$this->_full_name}.");

        return $this->_processLoadPersonsResult($result);
    }


    public function loadPersonsByTags()
    {
        $tag_collection = new fdvegan_TagCollection();
        $tag_ids_list = $tag_collection->getTagIdsListByNames($this->_having_tags);
        $sql = <<<__SQL__
SELECT p.`person_id`, p.`tmdbid`, p.`tmdb_image_path`, 
       p.`full_name`, p.`first_name`, p.`middle_name`, p.`last_name`, 
       p.`gender`, p.`rating`, p.`homepage_url`, p.`biography`, 
       p.`birthplace`, p.`birthday`, p.`deathday`, 
       p.`created`, p.`updated`, p.`synced`, 
__SQL__;
        if (is_null($this->_having_fdv_count_gt)) {
            $sql .= <<<__SQL__
       NULL `fdv_count` 
  FROM {fdvegan_person} p 
 RIGHT JOIN {fdvegan_person_tag} ON {fdvegan_person_tag}.`person_id` = p.`person_id` 
                                AND {fdvegan_person_tag}.`tag_id` IN ($tag_ids_list) 
__SQL__;
            if (!empty($this->_having_tmdbid)) {
                $sql .= <<<__SQL__
 WHERE p.`tmdbid` IS NOT NULL 
__SQL__;
            }
        } else {
            $sql .= <<<__SQL__
       COUNT(cl2.`person_id`) `fdv_count` 
  FROM {fdvegan_person} p 
  JOIN {fdvegan_person_tag} ON {fdvegan_person_tag}.`person_id` = p.`person_id` 
                           AND {fdvegan_person_tag}.`tag_id` IN ($tag_ids_list) 
  JOIN {fdvegan_cast_list} cl ON p.`person_id` = cl.`person_id` 
  JOIN dr_fdvegan_cast_list cl2 ON cl2.`movie_id` = cl.`movie_id` 
                               AND cl2.`person_id` != p.`person_id` 
__SQL__;
            if (!empty($this->_having_tmdbid)) {
                $sql .= <<<__SQL__
 WHERE p.`tmdbid` IS NOT NULL 
   AND p.`person_id` = cl.`person_id` 
__SQL__;
            }
            $sql .= <<<__SQL__
 GROUP BY cl.`person_id` 
HAVING `fdv_count` > {$this->_having_fdv_count_gt} 
__SQL__;
        }
        if (is_null($this->getSortBy())) {
            $sql .= <<<__SQL__
 ORDER BY p.`full_name` ASC 
__SQL__;
        } else {
            $sql .= <<<__SQL__
 ORDER BY p.`{$this->getSortBy()}` {$this->getSortByDir()} 
__SQL__;
        }
        if (($this->_start > 0) || ($this->_limit > 0)) {
            $sql .= <<<__SQL__
 LIMIT {$this->_start}, {$this->_limit}
__SQL__;
        }

        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);
        //fdvegan_Content::syslog('LOG_DEBUG', 'Loaded {$result->rowCount()} persons from our DB by tags='. implode($this->_having_tags, ',') .'.');

        return $this->_processLoadPersonsResult($result);
    }


    /**
     * Retrieve all persons from our database, but only return minimal data for them.
     *
     * This function is optimized for use by fdvegan.module::fdvegan_actor_form() so the
     *  select-dropdown is generated quickly.  The data here is stored via variable_set()
     *  so it doesn't have to be regenerated constantly.
     *
     * @return array  An assoc array of PersonId => FullName for all persons in the DB.
     */
    public function getMinPersonsArray($options = NULL)
    {
// @TODO need to add an isStale() check to this eventually!
// @TODO this is deprecated in Drupal 8, and should be done differently (per page, not site-wide) anyway.
        $this->_items = variable_get('fdvegan_min_persons_array', NULL);
        if (empty($this->_items)) {
            $sql = <<<__SQL__
SELECT {fdvegan_person}.`person_id`, {fdvegan_person}.`full_name` 
  FROM {fdvegan_person} 
 WHERE {fdvegan_person}.`tmdbid` IS NOT NULL 
 ORDER BY {fdvegan_person}.`last_name` ASC, {fdvegan_person}.`first_name` ASC, {fdvegan_person}.`middle_name` ASC
__SQL__;
            fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql));
            $result = db_query($sql);
            if (!empty($options['PrependEmptyItem'])) {
                $this->_items[''] = '';
            }
            foreach ($result as $row) {
                $this->_items[$row->person_id] = $row->full_name;
            }
            variable_set('fdvegan_min_persons_array', $this->_items);
        } else {
            fdvegan_Content::syslog('LOG_DEBUG', 'getMinPersonsArray() using cached array.');
        }
        return $this->getItems();
    }


    /**
     * Retrieve all persons from our database, but only return minimal data for them.
     *
     * This function is optimized for use by fdvegan_rest_api_Data::getActorNetworkObj().
     *
     * @return array  An assoc array of PersonId => FullName for all persons in the DB.
     */
    public function getMinPersonsNetworkArray()
    {
// @TODO need to add an isStale() check to this eventually!
        if (empty($this->_items)) {
            $sql = <<<__SQL__
SELECT {fdvegan_person}.`person_id`, {fdvegan_person}.`full_name` 
  FROM {fdvegan_person} 
 WHERE {fdvegan_person}.`tmdbid` IS NOT NULL 
 ORDER BY {fdvegan_person}.`full_name` ASC
__SQL__;
            fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql));
            $result = db_query($sql);
            foreach ($result as $row) {
                $this->_items[$row->person_id] = $row->full_name;
            }
        } else {
            fdvegan_Content::syslog('LOG_DEBUG', 'getMinPersonsNetworkArray() using cached array.');
        }
        return $this->getItems();
    }



    //////////////////////////////



    /**
     * @throws FDVeganNotFoundException    When no matching persons are found in the FDV DB.
     */
    private function _processLoadPersonsResult($result)
    {
        if ($result->rowCount() < 1) {
            fdvegan_Content::syslog('LOG_ERR', "No matching persons found: " . print_r($result,1));
            throw new FDVegan_NotFoundException("no matching persons found");
        }
        foreach ($result as $row) {
            $options = array('PersonId'        => $row->person_id,
                             'TmdbId'          => $row->tmdbid,
                             'TmdbImagePath'   => $row->tmdb_image_path,
                             'FullName'        => $row->full_name,
                             'FirstName'       => $row->first_name,
                             'MiddleName'      => $row->middle_name,
                             'LastName'        => $row->last_name,
                             'Gender'          => $row->gender,
                             'Rating'          => $row->rating,
                             'HomepageUrl'     => $row->homepage_url,
                             'Biography'       => $row->biography,
                             'Birthplace'      => $row->birthplace,
                             'Birthday'        => $row->birthday,
                             'Deathday'        => $row->deathday,
                             'Created'         => $row->created,
                             'Updated'         => $row->updated,
                             'Synced'          => $row->synced,
                            );
            $this->_items[] = new fdvegan_Person($options);
        }
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded '{$result->rowCount()}' persons from our DB.");

        return $this->getItems();
    }


}

