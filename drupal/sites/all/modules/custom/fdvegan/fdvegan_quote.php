<?php
/**
 * fdvegan_quote.php
 *
 * Implementation of Quote class for module fdvegan.
 * Stores all info related to a single person's quote.
 *
 * PHP version 5.6
 *
 * @category   Quote
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.9
 */


class fdvegan_Quote extends fdvegan_BaseClass
{
    protected $_quote_id  = NULL;
    protected $_person_id = NULL;
    protected $_tag_id    = NULL;
    protected $_rating    = NULL;
    protected $_quote     = NULL;
    protected $_source    = NULL;


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if ($this->_quote_id != NULL) {
            $this->loadQuoteByQuoteId();
        }
    }


    public function setQuoteId($value)
    {
        $this->_quote_id = (int)$value;
        return $this;
    }

    public function getQuoteId()
    {
        return $this->_quote_id;
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


    public function setTagId($value)
    {
        $this->_tag_id = (int)$value;
        return $this;
    }

    public function getTagId()
    {
        return $this->_tag_id;
    }


    public function setRating($value)
    {
        $this->_rating = (int)$value;
        return $this;
    }

    public function getRating()
    {
        return $this->_rating;
    }


    public function setQuote($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_quote = substr($value, 0, 4096);
        }
        return $this;
    }

    public function getQuote()
    {
        return $this->_quote;
    }


    public function setSource($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_source = substr($value, 0, 1024);
        }
        return $this;
    }

    public function getSource()
    {
        return $this->_source;
    }


    public function loadQuoteByQuoteId()
    {
        $sql = <<<__SQL__
SELECT {fdvegan_quote}.`quote_id`, {fdvegan_quote}.`person_id`, {fdvegan_quote}.`tag_id`, 
       {fdvegan_quote}.`rating`, {fdvegan_quote}.`quote`, {fdvegan_quote}.`source`, 
       {fdvegan_quote}.`created` 
  FROM {fdvegan_quote} 
 WHERE {fdvegan_quote}.`quote_id` = :quote_id
__SQL__;
        $result = db_query($sql, array(':quote_id' => $this->getQuoteId()));
        foreach ($result as $row) {
            $this->setQuoteId($row->quote_id);
            $this->setPersonId($row->person_id);
            $this->setTagId($row->tag_id);
            $this->setRating($row->rating);
            $this->setQuote($row->quote);
            $this->setSource($row->source);
            $this->setCreated($row->created);
        }

        return $this->getQuoteId();
    }


    public function storeQuote()
    {

fdvegan_Content::syslog('LOG_ERR', 'storeQuote() not implemented yet.');
throw new Exception("storeQuote() not implemented yet.");

        if ($this->getQuoteId()) {  // Must already exist in our DB, so is an update.

        } else {  // Must be a new quote to our DB, so is an insert.

        }

        return $this->getQuoteId();
    }



    //////////////////////////////



}

