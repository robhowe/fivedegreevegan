<?php
/**
 * fdvegan_tag_collection.php
 *
 * Implementation of Tag Collection class for module fdvegan.
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


class fdvegan_TagCollection extends fdvegan_BaseCollection
{
    public function __construct($options = NULL)
    {
        parent::__construct($options);
    }


    public function getTagIdByName($tag_name)
    {
        foreach ($this->loadAllTags() as $tag) {
            if ($tag->tagName == $tag_name) {
                return $tag->tagId;
            }
        }
        return NULL;
    }


    public function getTagIdsListByNames($tag_names_array)
    {
        $tag_id_array = array();
        foreach ($tag_names_array as $tag_name) {
            $tag_id_array[] = $this->getTagIdByName($tag_name);
        }
        return implode($tag_id_array, ',');
    }


    public function loadAllTags()
    {
        fdvegan_Content::syslog('LOG_DEBUG', "BEGIN loadAllTags().");
        $this->_items = variable_get('fdvegan_all_tags', NULL);
        if (empty($this->_items)) {
            $sql = <<<__SQL__
            SELECT {fdvegan_tag}.`tag_id`, {fdvegan_tag}.`tag_name`, {fdvegan_tag}.`created`, {fdvegan_tag}.`updated` 
              FROM {fdvegan_tag} 
             ORDER BY {fdvegan_tag}.`tag_id`
__SQL__;
            try {
                $result = db_query($sql);
            } catch (Exception $e) {
                fdvegan_Content::syslog('LOG_ERR', 'Caught exception:  '. $e->getMessage() .' while SELECTing tags: '. print_r($this,1));
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
            variable_set('fdvegan_all_tags', $this->_items);
            fdvegan_Content::syslog('LOG_DEBUG', 'Loaded all tags from our DB; count='. $this->count() . '.');
        }

        return $this->getItems();
    }



    //////////////////////////////



}

