<?php
/**
 * fdvegan_tag.php
 *
 * Implementation of Tag class for module fdvegan.
 * Stores all info related to a single person's tag.
 * Note - People have tags, movies have genres.
 *
 * PHP version 5.6
 *
 * @category   Tag
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 2.0
 */


class fdvegan_Tag extends fdvegan_BaseClass
{
    protected $_tag_id   = NULL;
    protected $_tag_name = NULL;


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if ($this->_tag_id != NULL) {
            $this->loadTagByTagId();
        }
    }


    public function setTagId($value)
    {
        $this->_tag_id = (int)$value;
        return $this;
    }

    public function getTagId()
    {
        return $this->_tag_id;
    }


    public function setTagName($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_tag_name = substr($value, 0, 254);
        }
        return $this;
    }

    public function getTagName()
    {
        return $this->_tag_name;
    }


    public function loadTagByTagId()
    {
        $sql = <<<__SQL__
SELECT {fdvegan_tag}.`tag_id`, {fdvegan_tag}.`tag_name`, {fdvegan_tag}.`created` 
  FROM {fdvegan_tag} 
 WHERE {fdvegan_tag}.`tag_id` = :tag_id
__SQL__;
        $sql_params = array(':tag_id' => $this->getTagId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        foreach ($result as $row) {
            $this->setTagId($row->tag_id);
            $this->setTagName($row->tag_name);
            $this->setCreated($row->created);
        }

        return $this->getTagId();
    }


    public function storeTag()
    {

fdvegan_Content::syslog('LOG_ERR', 'storeTag() not implemented yet.');
throw new Exception("storeTag() not implemented yet.");

        if ($this->getTagId()) {  // Must already exist in our DB, so is an update.

        } else {  // Must be a new tag to our DB, so is an insert.

        }

        return $this->getTagId();
    }



    //////////////////////////////



}

