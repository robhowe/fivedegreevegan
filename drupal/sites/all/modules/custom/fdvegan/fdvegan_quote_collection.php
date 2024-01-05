<?php
/**
 * fdvegan_quote_collection.php
 *
 * Implementation of Quote Collection class for module fdvegan.
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


class fdvegan_QuoteCollection extends fdvegan_BaseCollection
{
    protected $_person = NULL;  // Person object


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if ($this->_person) {
            $this->loadQuotesByPerson();
        } else {
            fdvegan_Content::syslog('LOG_WARN', 'fdvegan_QuoteCollection initialized with no person');
        }
    }


    public function setPerson($value)
    {
        $this->_person = $value;
        return $this;
    }

    public function getPerson()
    {
        return $this->_person;
    }


    public function loadQuotesByPerson()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN loadQuotesByPerson({$this->getPerson()->getPersonId()}).");
        $sql = <<<__SQL__
SELECT {fdvegan_quote}.`quote_id`, {fdvegan_quote}.`person_id`, {fdvegan_quote}.`tag_id`, 
       {fdvegan_quote}.`rating`, {fdvegan_quote}.`quote`, {fdvegan_quote}.`source`, 
       {fdvegan_quote}.`created` 
  FROM {fdvegan_quote} 
 WHERE {fdvegan_quote}.`person_id` = :person_id 
 ORDER BY {fdvegan_quote}.`rating` DESC, {fdvegan_quote}.`created` DESC
__SQL__;
        try {
            $result = db_query($sql, array(':person_id' => $this->getPerson()->getPersonId()));
        } catch (Exception $e) {
            fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while SELECTing person quotes: '. print_r($this,1));
            throw $e;
        }
        foreach ($result as $row) {
            $options = array('QuoteId' => $row->quote_id);
            $this->_items[] = new fdvegan_Quote($options);
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded quotes from our DB; person_id={$this->getPerson()->getPersonId()}" .
                                ', count='. count($this->_items) .'.');

        return $this->getItems();
    }



    //////////////////////////////



}

