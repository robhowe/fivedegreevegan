<?php
/**
 * fdvegan_person_tag_collection.php
 *
 * Implementation of Person Tag Collection class for module fdvegan.
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


class fdvegan_PersonTagCollection extends fdvegan_BaseCollection
{
    protected $_person = NULL;  // Person object.


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if ($this->_person) {
            $this->loadPersonTagsByPerson();
        } else {
            fdvegan_Content::syslog('LOG_WARNING', 'fdvegan_PersonTagCollection initialized with no person');
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


    public function loadPersonTagsByPerson()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN loadPersonTagsByPerson({$this->getPerson()->getPersonId()}.");
        $sql = <<<__SQL__
SELECT {fdvegan_person_tag}.`person_id`, {fdvegan_person_tag}.`tag_id`, {fdvegan_tag}.`tag_name`, {fdvegan_person_tag}.`created` 
  FROM {fdvegan_person_tag} 
                JOIN {fdvegan_tag} ON {fdvegan_tag}.`tag_id` = {fdvegan_person_tag}.`tag_id` 
 WHERE {fdvegan_person_tag}.`person_id` = :person_id 
 ORDER BY {fdvegan_tag}.`tag_id` ASC, {fdvegan_person_tag}.`created` ASC
__SQL__;
        try {
            $sql_params = array(':person_id' => $this->getPerson()->getPersonId());
            //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
            $result = db_query($sql, $sql_params);
        } catch (Exception $e) {
            fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while SELECTing person tags: '. print_r($this,1));
            throw $e;
        }
        foreach ($result as $row) {
            /* This isn't standard "collection behavior":
             * We don't want to load the tag from the DB again since it's such
             * a trivial bit of data.  Instead we'll create the tag obj manually.
             */
            $tag = new fdvegan_Tag();
            $tag->tagId   = $row->tag_id;
            $tag->tagName = $row->tag_name;
            $this->_items[] = $tag;
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded personTags from our DB; person_id={$this->getPerson()->getPersonId()}" .
                                ', count='. $this->count() . '.');

        return $this->getItems();
    }



    //////////////////////////////



}

