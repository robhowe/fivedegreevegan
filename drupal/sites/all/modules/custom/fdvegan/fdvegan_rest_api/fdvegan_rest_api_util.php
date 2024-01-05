<?php
/**
 * fdvegan_rest_api_util.php
 *
 * Implementation of Util class for sub-module fdvegan_rest_api.
 * Miscellaneous utility and helper functions.
 *
 * PHP version 5.6
 *
 * @category   Util
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 1.1
 */


class fdvegan_rest_api_Util
{

    public static function getD3SafeName($name)
    {
        return str_replace('.', '', $name);
        //return 'person.' . str_replace('.', '', $name);
    }


    public static function getD3SafeNamesList($names_list)
    {
        $ret_array = array();
        foreach (explode(',', $names_list) as $name) {
            $ret_array[] = self::getD3SafeName($name);
        }
        return $ret_array;
    }


    /**
     * Get, parse & validate the current (or provided) URL,
     * for purposes of use in our REST API scheme.
     *
     * Example valid URLs:
     *   https://fivedegreevegan.aprojects.org/rest-api/actor-tree/?person_id={person_id}
     *   https://fivedegreevegan.aprojects.org/rest-api/v1.0/actor-tree/?person_id={person_id}&format=json&style=pv&depth=5
     *   https://fivedegreevegan.aprojects.org/rest-api/v1.0/actor-network/?format=json&style=d3
     */
    public static function getRestUrlObj($path = NULL)
    {
        $url_obj = new stdClass();
        if ($path === NULL) {
            $path = current_path();
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Validating REST_API URL: ({$path}).");
        $url_array = explode('/', $path);

        // The URL must begin with "/rest-api" or we shouldn't be here.
        if (empty($url_array[0]) || ($url_array[0] !== 'rest-api')) {
            throw new FDVegan_InvalidArgumentException('Invalid rest-api request');
        }
        array_shift($url_array);

        $url_obj->version = 'v1.0';
        if (!empty($url_array[0])) {
            if ($url_array[0][0] === 'v') {
                $url_obj->version = array_shift($url_array);
            }
        }
        $url_obj->resource = array_shift($url_array);
        $url_obj->id       = array_shift($url_array);
        $url_obj->params   = drupal_get_query_parameters();

        return $url_obj;
    }



    //////////////////////////////



}

