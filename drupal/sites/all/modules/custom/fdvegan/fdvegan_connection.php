<?php
/**
 * fdvegan_connection.php
 *
 * Implementation of Connection class for module fdvegan.
 * Calculates person connections.
 *
 * PHP version 5.6
 *
 * @category   Person
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 1.1
 */


class fdvegan_Connection extends fdvegan_BaseCollection
{
    protected $_having_tmdbid = NULL;  // Bool flag to determine whether to load persons with no Tmdb info
    protected $_having_tags   = array();  // Array of tag names to filter for when loading persons

    /* Before changing the value of $this->_group_concat_max_len see page:
     *  https://fivedegreevegan.aprojects.org/db-records-info
     *  for an idea of what the minimum value should be.
     */
    protected $_group_concat_max_len = 4000;  // max chars allowed/expected for connections-list text


    public function __construct($options = NULL)
    {
        parent::__construct($options);
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


    public function setHavingTags($value)
    {
        // Special array of tag names to filter for when loading connections.
        $this->_having_tags = (array)$value;
        return $this;
    }

    public function getHavingTags()
    {
        return $this->_having_tags;
    }


    /**
     * getStats()
     *
     * @see fdvegan.admin.php::fdvegan_db_records_info_form()
     *
     * @return array $ret_array    Array of stats values.
     */
    public function getStats()
    {
        $sql = <<<__SQL__
SET SESSION group_concat_max_len = {$this->_group_concat_max_len}
__SQL__;
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL1=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        $sql = <<<__SQL__
SELECT (SELECT LENGTH(degree0_names) AS fl FROM {fdvegan_connections} ORDER BY fl DESC LIMIT 1) AS `length0`, 
       (SELECT LENGTH(degree1_names) AS fl FROM {fdvegan_connections} ORDER BY fl DESC LIMIT 1) AS `length1`, 
       (SELECT LENGTH(degree2_names) AS fl FROM {fdvegan_connections} ORDER BY fl DESC LIMIT 1) AS `length2`, 
       (SELECT LENGTH(degree3_names) AS fl FROM {fdvegan_connections} ORDER BY fl DESC LIMIT 1) AS `length3`, 
       (SELECT LENGTH(degree4_names) AS fl FROM {fdvegan_connections} ORDER BY fl DESC LIMIT 1) AS `length4`, 
       (SELECT LENGTH(degree5_names) AS fl FROM {fdvegan_connections} ORDER BY fl DESC LIMIT 1) AS `length5`, 
       (SELECT LENGTH(GROUP_CONCAT(DISTINCT `full_name` SEPARATOR ',')) FROM {fdvegan_featured_person}) AS `max_possible`
__SQL__;
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL2=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);
        $ret_array = array();
        foreach ($result as $row) {
            for ($loop=0; $loop<6; $loop++) {
                $varname = 'length' . $loop;
                $ret_array[$varname] = $row->{$varname};
            }
            $ret_array['max'] = max($ret_array);
            $ret_array['max_possible'] = $row->max_possible;
            $ret_array['max_limit'] = $this->_group_concat_max_len;
        }
        return $ret_array;
    }


    /**
     * getConnections()
     *
     * Get all actors' connections data.
     *
     * @return array $ret_array    Array of all actors' connections.
     */
    public function getConnections($degree = NULL)
    {
        if ($degree === NULL) {
            $sql = <<<__SQL__
SELECT * FROM {fdvegan_connections} 
 WHERE {fdvegan_connections}.`degree0_names` IS NOT NULL 
 ORDER BY {fdvegan_connections}.`full_name`
__SQL__;
        } else {
            $field = "degree{$degree}_names";
            $sql = <<<__SQL__
SELECT {fdvegan_connections}.`person_id`, {fdvegan_connections}.`full_name`, 
       {fdvegan_connections}.`{$field}` AS `degree_names`, 
       {fdvegan_connections}.`created`, {fdvegan_connections}.`updated` 
  FROM {fdvegan_connections} 
 WHERE {fdvegan_connections}.`{$field}` IS NOT NULL 
 ORDER BY {fdvegan_connections}.`full_name`
__SQL__;
        }
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);
        $ret_array = array();
        foreach ($result as $row) {
            $ret_array[] = $row;
        }
        return $ret_array;
    }


    /**
     * recalculateInitTable()
     *
     * @see fdvegan_batch_load_connections.php::fdvegan_initial_recalculate_degrees_batch_process()
     * @see fdvegan_batch_process.php::fdvegan_recalculate_degrees_batch()
     *
     * @return bool $success    TRUE or FALSE.
     */
    public function recalculateInitTable()
    {
        $sql = <<<__SQL__
TRUNCATE TABLE {fdvegan_featured_person}
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL1=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        // This must match how it's done in:  fdvegan_batch_process.php::fdvegan_recalculate_degrees_batch()
        $person_collection = new fdvegan_PersonCollection(fdvegan_Util::$connections_options);
        $person_collection->loadPersons();  // load the actors from our DB
        foreach ($person_collection as $person) {
            $sql = <<<__SQL__
INSERT INTO {fdvegan_featured_person} (person_id, full_name, created) 
     VALUES (:person_id, :full_name, CURRENT_TIMESTAMP)
__SQL__;
            $sql_params = array(':person_id' => $person->personId,
                                ':full_name' => $person->fullName
                               );
            fdvegan_Content::syslog('LOG_DEBUG', 'SQL2=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
            $result = db_query($sql, $sql_params);
        }

        $sql = <<<__SQL__
TRUNCATE TABLE {fdvegan_connections}
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL3=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        $sql = <<<__SQL__
INSERT INTO {fdvegan_connections} (person_id, full_name, created) 
SELECT deg0.`person_id`, deg0.`full_name`, CURRENT_TIMESTAMP 
  FROM {fdvegan_featured_person} AS deg0 
 ORDER BY deg0.`full_name` ASC
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL4=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);
        return TRUE;
    }


    /**
     * recalculateDegree0()
     *
     * @see fdvegan_batch_process.php::fdvegan_initial_recalculate_degrees_batch_process()
     *
     * @return bool $success    TRUE or FALSE.
     */
    public function recalculateDegree0()
    {
        $sql = <<<__SQL__
SET SESSION group_concat_max_len = {$this->_group_concat_max_len}
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL1=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        $sql = <<<__SQL__
UPDATE {fdvegan_connections} 
  LEFT JOIN {fdvegan_featured_person} ON {fdvegan_featured_person}.`person_id` = {fdvegan_connections}.`person_id` 
   SET {fdvegan_connections}.`degree0_names` = {fdvegan_connections}.`full_name` 
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL2=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);
        return TRUE;
    }


    /**
     * recalculateDegree1()
     *
     * @see fdvegan_batch_process.php::fdvegan_initial_recalculate_degrees_batch_process()
     *
     * @return bool $success    TRUE or FALSE.
     */
    public function recalculateDegree1()
    {
        $sql = <<<__SQL__
SET SESSION group_concat_max_len = {$this->_group_concat_max_len}
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL1=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        $sql = <<<__SQL__
UPDATE {fdvegan_connections} AS c 
  LEFT JOIN ( 
            SELECT deg0.`full_name`, 
                   deg0.`person_id`, 
                   GROUP_CONCAT(DISTINCT deg1.`full_name` ORDER BY deg1.`full_name` SEPARATOR ',') AS `degree1_names` 
              FROM {fdvegan_featured_person} AS deg0 
              LEFT JOIN {fdvegan_cast_list} AS clp0 ON clp0.`person_id` = deg0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm0 ON clm0.`movie_id` = clp0.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg1 ON deg1.`person_id` = clm0.`person_id` 
             WHERE deg0.`person_id` != deg1.`person_id` 
             GROUP BY deg0.`full_name` 
             ORDER BY deg0.`full_name` ASC 
            ) t2 ON c.`person_id` = t2.`person_id` 
   SET c.`degree1_names` = t2.`degree1_names`
__SQL__;

        fdvegan_Content::syslog('LOG_DEBUG', 'SQL2=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);
        return TRUE;
    }


    /**
     * recalculateDegree2()
     *
     * @see fdvegan_batch_process.php::fdvegan_recalculate_degrees_batch_process()
     *
     * @param int $person_id    (optional) If not provided, this may take ~2 minutes to execute!
     * @return bool $success    TRUE or FALSE.
     */
    public function recalculateDegree2($person_id = NULL)
    {
        $sql = <<<__SQL__
SET SESSION group_concat_max_len = {$this->_group_concat_max_len}
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL1=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        $tag_collection = new fdvegan_TagCollection();
        $tag_ids_list = $tag_collection->getTagIdsListByNames($this->_having_tags);
        $sql = <<<__SQL__
UPDATE {fdvegan_connections} AS c 
  LEFT JOIN ( 
            SELECT deg0.`full_name`, 
                   deg0.`person_id`, 
                   GROUP_CONCAT(DISTINCT deg2.`full_name` ORDER BY deg2.`full_name` SEPARATOR ',') AS `degree2_names` 
              FROM {fdvegan_featured_person} AS deg0 
              LEFT JOIN {fdvegan_cast_list} AS clp0 ON clp0.`person_id` = deg0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm0 ON clm0.`movie_id` = clp0.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg1 ON deg1.`person_id` = clm0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp1 ON clp1.person_id = deg1.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm1 ON clm1.movie_id = clp1.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg2 ON deg2.`person_id` = clm1.`person_id` 
             WHERE deg0.`person_id` != deg2.`person_id` 
               AND deg1.`person_id` != deg2.`person_id` 
__SQL__;
        if ($person_id !== NULL) {
            $sql .= <<<__SQL__
               AND deg0.`person_id` = :person_id 
__SQL__;
        }
        $sql .= <<<__SQL__
             GROUP BY deg0.`full_name` 
             ORDER BY deg0.`full_name` ASC 
__SQL__;
        if (($this->_start > 0) || ($this->_limit > 0)) {
            $sql .= <<<__SQL__
 LIMIT {$this->_start}, {$this->_limit} 
__SQL__;
        }
        $sql .= <<<__SQL__
            ) t2 ON c.`person_id` = t2.`person_id` 
   SET c.`degree2_names` = t2.`degree2_names` 
__SQL__;
        if ($person_id !== NULL) {
            $sql .= <<<__SQL__
             WHERE t2.`person_id` = :person_id
__SQL__;
        }
        $sql_params = array(':person_id' => $person_id);
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL2=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        return TRUE;
    }


    /**
     * recalculateDegree3()
     *
     * @see fdvegan_batch_process.php::fdvegan_recalculate_degrees_batch_process()
     *
     * @param int $person_id    (optional) When provided, this takes ~0.5 seconds to execute.
     *                          If not provided, this may take ~31 hours to execute!
     * @return bool $success    TRUE or FALSE.
     */
    public function recalculateDegree3($person_id = NULL)
    {
        $sql = <<<__SQL__
SET SESSION group_concat_max_len = {$this->_group_concat_max_len}
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL1=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        $sql = <<<__SQL__
UPDATE {fdvegan_connections} AS c 
  LEFT JOIN ( 
            SELECT deg0.`full_name`, 
                   deg0.`person_id`, 
                   GROUP_CONCAT(DISTINCT deg3.`full_name` ORDER BY deg3.`full_name` SEPARATOR ',') AS `degree3_names` 
              FROM {fdvegan_featured_person} AS deg0 
              LEFT JOIN {fdvegan_cast_list} AS clp0 ON clp0.`person_id` = deg0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm0 ON clm0.`movie_id` = clp0.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg1 ON deg1.`person_id` = clm0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp1 ON clp1.person_id = deg1.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm1 ON clm1.movie_id = clp1.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg2 ON deg2.`person_id` = clm1.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp2 ON clp2.person_id = deg2.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm2 ON clm2.movie_id = clp2.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg3 ON deg3.`person_id` = clm2.`person_id` 
             WHERE deg0.`person_id` != deg3.`person_id` 
               AND deg1.`person_id` != deg3.`person_id` 
               AND deg2.`person_id` != deg3.`person_id` 
__SQL__;
        if ($person_id !== NULL) {
            $sql .= <<<__SQL__
               AND deg0.`person_id` = :person_id 
__SQL__;
        }
        $sql .= <<<__SQL__
             GROUP BY deg0.`full_name` 
             ORDER BY deg0.`full_name` ASC 
            ) t2 ON c.`person_id` = t2.`person_id` 
   SET c.`degree3_names` = t2.`degree3_names` 
__SQL__;
        if ($person_id !== NULL) {
            $sql .= <<<__SQL__
             WHERE t2.`person_id` = :person_id
__SQL__;
        }
        $sql_params = array(':person_id' => $person_id);
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL2=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        return TRUE;
    }


    /**
     * recalculateDegree4()
     *
     * @see fdvegan_batch_process.php::fdvegan_recalculate_degrees_batch_process()
     *
     * @param int $person_id    (optional) When provided, this takes ~1-4 minutes to execute.
     *                          If not provided, this may take ~10 days to execute!
     * @return bool $success    TRUE or FALSE.
     */
    public function recalculateDegree4($person_id = NULL)
    {
        $sql = <<<__SQL__
SET SESSION group_concat_max_len = {$this->_group_concat_max_len}
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL1=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        $sql = <<<__SQL__
UPDATE {fdvegan_connections} AS c 
  LEFT JOIN ( 
            SELECT deg0.`full_name`, 
                   deg0.`person_id`, 
                   GROUP_CONCAT(DISTINCT deg4.`full_name` ORDER BY deg4.`full_name` SEPARATOR ',') AS `degree4_names` 
              FROM {fdvegan_featured_person} AS deg0 
              LEFT JOIN {fdvegan_cast_list} AS clp0 ON clp0.`person_id` = deg0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm0 ON clm0.`movie_id` = clp0.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg1 ON deg1.`person_id` = clm0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp1 ON clp1.person_id = deg1.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm1 ON clm1.movie_id = clp1.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg2 ON deg2.`person_id` = clm1.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp2 ON clp2.person_id = deg2.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm2 ON clm2.movie_id = clp2.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg3 ON deg3.`person_id` = clm2.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp3 ON clp3.person_id = deg3.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm3 ON clm3.movie_id = clp3.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg4 ON deg4.`person_id` = clm3.`person_id` 
             WHERE deg0.`person_id` != deg4.`person_id` 
               AND deg1.`person_id` != deg4.`person_id` 
               AND deg2.`person_id` != deg4.`person_id` 
               AND deg3.`person_id` != deg4.`person_id` 
__SQL__;
        if ($person_id !== NULL) {
            $sql .= <<<__SQL__
               AND deg0.`person_id` = :person_id 
__SQL__;
        }
        $sql .= <<<__SQL__
             GROUP BY `full_name` 
             ORDER BY `full_name` ASC 
            ) t2 ON c.`person_id` = t2.`person_id` 
   SET c.`degree4_names` = t2.`degree4_names` 
__SQL__;
        if ($person_id !== NULL) {
            $sql .= <<<__SQL__
             WHERE t2.`person_id` = :person_id
__SQL__;
        }
        $sql_params = array(':person_id' => $person_id);
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL2=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        return TRUE;
    }


    /**
     * recalculateDegree5()
     *
     * @see fdvegan_batch_process.php::fdvegan_recalculate_degrees_batch_process()
     *
     * @param int $person_id    (optional) When provided, this takes ~1.5 hours to execute.
     *                          If not provided, this may take ~15 days to execute!
     * @return bool $success    TRUE or FALSE.
     */
    public function recalculateDegree5($person_id = NULL)
    {
        $sql = <<<__SQL__
SET SESSION group_concat_max_len = {$this->_group_concat_max_len}
__SQL__;
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL1=' . fdvegan_Util::getRenderedQuery($sql));
        $result = db_query($sql);

        $sql = <<<__SQL__
UPDATE {fdvegan_connections} AS c 
  LEFT JOIN ( 
            SELECT deg0.`full_name`, 
                   deg0.`person_id`, 
                   GROUP_CONCAT(DISTINCT deg5.`full_name` ORDER BY deg5.`full_name` SEPARATOR ',') AS `degree5_names` 
              FROM {fdvegan_featured_person} AS deg0 
              LEFT JOIN {fdvegan_cast_list} AS clp0 ON clp0.`person_id` = deg0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm0 ON clm0.`movie_id` = clp0.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg1 ON deg1.`person_id` = clm0.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp1 ON clp1.person_id = deg1.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm1 ON clm1.movie_id = clp1.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg2 ON deg2.`person_id` = clm1.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp2 ON clp2.person_id = deg2.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm2 ON clm2.movie_id = clp2.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg3 ON deg3.`person_id` = clm2.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp3 ON clp3.person_id = deg3.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm3 ON clm3.movie_id = clp3.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg4 ON deg4.`person_id` = clm3.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clp4 ON clp4.person_id = deg4.`person_id` 
              LEFT JOIN {fdvegan_cast_list} AS clm4 ON clm4.movie_id = clp4.`movie_id` 
              LEFT JOIN {fdvegan_featured_person} AS deg5 ON deg5.`person_id` = clm4.`person_id` 
             WHERE deg0.`person_id` != deg5.`person_id` 
               AND deg1.`person_id` != deg5.`person_id` 
               AND deg2.`person_id` != deg5.`person_id` 
               AND deg3.`person_id` != deg5.`person_id` 
               AND deg4.`person_id` != deg5.`person_id` 
__SQL__;
        if ($person_id !== NULL) {
            $sql .= <<<__SQL__
               AND deg0.`person_id` = :person_id 
__SQL__;
        }
        $sql .= <<<__SQL__
             GROUP BY `full_name` 
             ORDER BY `full_name` ASC 
            ) t2 ON c.`person_id` = t2.`person_id` 
   SET c.`degree5_names` = t2.`degree5_names` 
__SQL__;
        if ($person_id !== NULL) {
            $sql .= <<<__SQL__
             WHERE t2.`person_id` = :person_id
__SQL__;
        }
        $sql_params = array(':person_id' => $person_id);
        fdvegan_Content::syslog('LOG_DEBUG', 'SQL2=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        return TRUE;
    }



/*
-- Find 3 degree connections:

SET SESSION group_concat_max_len = 4000;
SELECT deg0.`full_name`, 
       deg0.`person_id`, 
       GROUP_CONCAT(DISTINCT deg3.`full_name` ORDER BY deg3.`full_name` SEPARATOR ',') AS connections 
  FROM dr_fdvegan_featured_person AS deg0 
  LEFT JOIN dr_fdvegan_cast_list AS clp0 ON clp0.person_id = deg0.`person_id` 
  LEFT JOIN dr_fdvegan_cast_list AS clm0 ON clm0.movie_id = clp0.`movie_id` 
  LEFT JOIN dr_fdvegan_featured_person AS deg1 ON deg1.`person_id` = clm0.`person_id` 
  LEFT JOIN dr_fdvegan_cast_list AS clp1 ON clp1.person_id = deg1.`person_id` 
  LEFT JOIN dr_fdvegan_cast_list AS clm1 ON clm1.movie_id = clp1.`movie_id` 
  LEFT JOIN dr_fdvegan_featured_person AS deg2 ON deg2.`person_id` = clm1.`person_id` 
  LEFT JOIN dr_fdvegan_cast_list AS clp2 ON clp2.person_id = deg2.`person_id` 
  LEFT JOIN dr_fdvegan_cast_list AS clm2 ON clm2.movie_id = clp2.`movie_id` 
  LEFT JOIN dr_fdvegan_featured_person AS deg3 ON deg3.`person_id` = clm2.`person_id` 
 WHERE deg0.`person_id` != deg3.`person_id` 
 GROUP BY `full_name` 
 ORDER BY `full_name` ASC;

SELECT DISTINCT 
       deg0.`full_name` AS deg0_name, 
       deg0.`person_id` AS deg0_id, 
       deg1.`full_name` AS deg1_name, 
       deg1.`person_id` AS deg1_id 
  FROM dr_fdvegan_featured_person AS deg0 
  LEFT JOIN dr_fdvegan_cast_list AS clp0 ON clp0.person_id = deg0.`person_id` 
  LEFT JOIN dr_fdvegan_cast_list AS clm0 ON clm0.movie_id = clp0.`movie_id` 
  LEFT JOIN dr_fdvegan_featured_person AS deg1 ON deg1.`person_id` = clm0.`person_id` 
 WHERE deg0.`tmdbid` IS NOT NULL 
   AND deg0.person_id != deg1.person_id 
 ORDER BY deg0.`full_name` ASC, deg1.`full_name` ASC;

SELECT DISTINCT 
       deg0.`full_name` AS deg0_name, 
       deg0.`person_id` AS deg0_id, 
       deg1.`full_name` AS deg1_name, 
       deg1.`person_id` AS deg1_id, 
       deg2.`full_name` AS deg2_name, 
       deg2.`person_id` AS deg2_id 
  FROM dr_fdvegan_featured_person AS deg0 
  LEFT JOIN dr_fdvegan_cast_list AS clp0 ON clp0.person_id = deg0.`person_id` 
  LEFT JOIN dr_fdvegan_cast_list AS clm0 ON clm0.movie_id = clp0.`movie_id` 
  LEFT JOIN dr_fdvegan_featured_person AS deg1 ON deg1.`person_id` = clm0.`person_id` 

  LEFT JOIN dr_fdvegan_cast_list AS clp1 ON clp1.person_id = deg1.`person_id` 
  LEFT JOIN dr_fdvegan_cast_list AS clm1 ON clm1.movie_id = clp1.`movie_id` 
  LEFT JOIN dr_fdvegan_featured_person AS deg2 ON deg2.`person_id` = clm1.`person_id` 

 WHERE deg0.person_id != deg1.person_id 
--       AND deg0.`full_name` = 'Danny DeVito' 
   AND deg0.person_id != deg2.person_id 
   AND deg1.person_id != deg2.person_id 
 ORDER BY deg0.`full_name` ASC, deg1.`full_name` ASC, deg2.`full_name` ASC;

*/



    //////////////////////////////



}

