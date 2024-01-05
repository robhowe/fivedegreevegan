<?php
/**
 * fdvegan_BaseCollection.php
 *
 * Implementation of base collection functionality for module fdvegan.
 *
 * PHP version 5.6
 *
 * @category   Util
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2015-2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.1
 */

abstract class fdvegan_BaseCollection extends fdvegan_BaseClass implements ArrayAccess, IteratorAggregate, Countable
{
    protected $_items          = array();
    protected $_start          = 0;  // Like MySQL, item indices start at 0.
    protected $_limit          = 0;  // Like MySQL.
    protected $_sort_by        = NULL;  // DB column name.
    protected $_sort_by_dir    = NULL;  // Order by direction (ASC or DESC).
    protected $_total_num_rows = 0;  // @TODO - Not implemented yet.
    protected $_metaOnlyFlag   = false;

    private $_meta = array();


    protected function __construct($options = NULL)
    {
        parent::__construct($options);
    }


    public function setStart($value)
    {
        $this->_start = (int)$value;
        return $this;
    }

    public function getStart()
    {
        return $this->_start;
    }


    public function setLimit($value)
    {
        $this->_limit = (int)$value;
        return $this;
    }

    public function getLimit()
    {
        return $this->_limit;
    }


    public function setSortBy($value)
    {
        $this->_sort_by = $value;
        return $this;
    }

    public function getSortBy()
    {
        return $this->_sort_by;
    }


    public function setSortByDir($value)
    {
        $this->_sort_by_dir = $value;
        return $this;
    }

    public function getSortByDir()
    {
        if (empty($this->_sort_by_dir)) {
            return 'ASC';  // Default to Ascending.
        }
        return $this->_sort_by_dir;
    }


    protected function setTotalNumRows($value)
    {
        $this->_total_num_rows = (int)$value;
        return $this;
    }

    public function getTotalNumRows()
    {
        return $this->_total_num_rows;
    }


    public function isMetaOnly()
    {
        return $this->_metaOnlyFlag;
    }

    public function getMetaData()
    {
        return $this->_meta;
    }


    private function assignCheck($value, $type, $paramName)
    {
        return $value;  // @TODO

        if ($value === NULL) {
            return $value;
        }

        switch ($type) {
        case 'array':
            if (is_array($value)) {
                return $value;
            }
            break;

        default:
            if ($value instanceof $type) {
                return $value;
            }
            break;
        }

        throw new FDVegan_InvalidArgumentException('Wrong type for $' . "{$paramName} given in constructor (expected {$type}, got " . get_class($value) . ')');
    }


    public function isModified()
    {
        foreach ($this->_items as $item) {
            if ($item->isModified()) {
                return true;
            }
        }
        return false;
    }


    /**
     * The Meta fields parallel the __get() and __set() functions.
     */

    public function getMeta($name)
    {
        if (isset($this->_meta[$name])) {
            return $this->_meta[$name];
        } else {
            return NULL;
        }
    }

    protected function setMetaValues($values, $metaOnlyFlag=false)
    {
        $this->_meta['total'] = count($values);
        if ($metaOnlyFlag) {
            $this->_meta['first'] = $this->_meta['last'] = -1;
            $this->_metaOnlyFlag = true;
        } else {
            $this->_meta['first'] = count($values) ? 0 : -1;
            $this->_meta['last'] = count($values) - 1;
        }
    }

    public function setMeta($name, $value)
    {
        $this->_meta[$name] = $value;
    }
    
    public function issetMeta($name)
    {
        return isset($this->_meta[$name]);
    }

    public function unsetMeta($name)
    {
        if (isset($this->_meta[$name])) {
            unset($this->_meta[$name]);
        }
    }

    public function __get($name) 
    {
    }

    public function __isset($name) 
    {
        return false;
    }


    /**
     * ArrayAccess Interface
     */

    public function getAt($offset)
    {
        return $this->offsetGet($offset);
    }

    public function getItems()
    {
        return $this->_items;
    }

    public function offsetExists($offset)
    {
        return isset($this->_items[$offset]);
    }

    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return $this->_items[$offset];
        }
    }

    public function offsetSet($offset, $value)
    {
        if (empty($offset)) {
            $this->_items[] = $value;
        } else {
            $this->_items[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->_items[$offset]);
    }

    /**
     * Sort
     */
    public function sort($_fields_)
    {
        throw new FDVegan_NotImplementedException('Sort not available for ' . __CLASS__);
    }


    /**
     * Truncate this collection after the given index limit.
     * This does NOT delete any DB records or corresponding media files on the filesystem!
     */
    public function truncate($trunc_at)
    {
        array_splice($this->_items, $trunc_at, (count($this->_items) - $trunc_at));
        return $this->_items;
    }


    /**
     * deleteAll
     */
    public function deleteAll()
    {
        $this->_items = array();  // effectively unset()'s all existing _items
        return $this->_items;
    }


    /**
     * Countable Interface
     */
    public function count() 
    {
        return count($this->_items);
    }


    /**
     * IteratorAggregate Interface
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_items);
    }


    /**
     * toArray
     */
    public function toArray() 
    {
        $result = $this->getMetaData();
        if ($this->_metaOnlyFlag) {
            $result['data'] = NULL;
        } else {
            $result['data'] = array();
            if (is_array($this->_items)) {
                foreach ($this->_items as $item) {
                    $result['data'][] = $item->toArray();
                }
            }
        }
        return $result;
    }


    /**
     * This is the externally-available function for updating the items
     * in the collection. It ensures that triggers get fired in a standard
     * way for all Collections.
     * 
     * This shouldn't need to be implemented by child classes.
     * Instead, child classes should implement protected function doUpdate()
     * 
     * @return int  The number of items affected.
     */
    final public function update($PM = NULL)
    {
        $affected = $this->doUpdate();
        return $affected;
    }

    /**
     * This is the class-specific, internal-use-only function for bulk updating
     * the items in the collection.
     * This should *only* be called via update()
     * 
     * @return int  The number of items affected.
     */
    protected function doUpdate()
    {
        $affected = 0;
        foreach ($this->_items as $item) {
            if (is_object($item) && method_exists($item, 'update')) {
                $affected += $item->update();
            }
        }
        return $affected;
    }


}

