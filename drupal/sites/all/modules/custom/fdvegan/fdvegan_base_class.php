<?php
/**
 * fdvegan_baseclass.php
 *
 * Base class implementation for module fdvegan.
 * Extended by most other classes in fdvegan to handle common methods.
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
 * @since      version 0.5
 */


abstract class fdvegan_BaseClass
{
    protected $_created           = NULL;
    protected $_updated           = NULL;
    protected $_synced            = NULL;

    protected $_refresh_from_tmdb = FALSE;
    protected $_scrape_from_tmdb  = FALSE;
    protected $_tmdb_data         = NULL;  // all data received from TMDb API

    /* Data fields received from external sources (TMDb) that may need to be validated.
     * Array format: field_name => default_value
     * see @fdvegan_Person, fdvegan_Movie
     */
    protected $_data_fields = array();

    protected function __construct($options = NULL)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }


    public function __set($name, $value)
    {
        $method = "set$name";

        if (($name == 'mapper') || !method_exists($this, $method)) {
            throw new FDVegan_InvalidArgumentException("Invalid mapper method \"{$method}\"");
        }

        $this->$method($value);
    }

    public function __get($name)
    {
        $method = "get$name";

        if (($name == 'mapper') || !method_exists($this, $method)) {
            throw new FDVegan_InvalidArgumentException("Invalid mapper method \"{$method}\"");
        }

        return $this->$method();
    }


    public function setOptions($options = array())
    {
        $methods = get_class_methods($this);

        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);

            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }

        return $this;
    }


    public function setCreated($value)
    {
        $this->_created = $value;
        return $this;
    }

    public function getCreated()
    {
        return $this->_created;
    }


    public function setUpdated($value)
    {
        $this->_updated = $value;
        return $this;
    }

    public function getUpdated()
    {
        return $this->_updated;
    }


    public function setSynced($value)
    {
        $this->_synced = $value;
        return $this;
    }

    public function getSynced()
    {
        return $this->_synced;
    }


    public function setRefreshFromTmdb($value)
    {
        $this->_refresh_from_tmdb = (bool)$value;
        return $this;
    }

    public function getRefreshFromTmdb()
    {
        return $this->_refresh_from_tmdb;
    }


    public function setScrapeFromTmdb($value)
    {
        $this->_scrape_from_tmdb = (bool)$value;
        return $this;
    }

    public function getScrapeFromTmdb()
    {
        return $this->_scrape_from_tmdb;
    }


    public function setTmdbData($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_tmdb_data = $value;
        }
        return $this;
    }

    public function getTmdbData()
    {
        return $this->_tmdb_data;
    }


    public function isStale()
    {
        $stale_flag = TRUE;
/* TODO - research why this doesn't always work correctly during init-load
        if ($this->getSynced() !== NULL) {
            $synced_obj = new DateTime($this->getSynced());
            $threshold  = new DateTime('yesterday');
            // While debugging, you may want to uncomment the following line:
            $threshold  = new DateTime('today -1 minutes');
            if ($synced_obj > $threshold) {  // Don't bother refreshing from TMDb if less than 1 day old (or rather, $threshold)
                $stale_flag = FALSE;
            }
        }
*/
        return $stale_flag;
    }


    function throwException($message = NULL, $code = NULL, Exception $previous = NULL, $priority = NULL) {
        throw new FDVegan_Exception($message, $code, $previous, $priority);
    }



    //////////////////////////////



    /**
     * Validate data fields fetched from TMDb.
     *
     * @param array $tmdb_data	TMDb data
     * @return TMDb result array
     */
    protected function _validateFields(&$tmdb_data)
    {
        // TMDb often doesn't return some fields, so if missing, create them:
        foreach ($this->_data_fields as $key => $val) {
            if (!isset($tmdb_data[$key])) {
                $tmdb_data[$key] = $val;
            }
        }
        return $tmdb_data;
    }


}

