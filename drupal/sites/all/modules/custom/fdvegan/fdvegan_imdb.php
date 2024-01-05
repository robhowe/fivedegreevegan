<?php
/**
 * fdvegan_imdb.php
 *
 * Implementation of Imdb class for module fdvegan.
 * Pre-emptive wrapper class for a potential future low-level 3rd party IMDb library.
 *
 * Example IMDb Info URL:  https://www.imdb.com/name/nm0000204/
 *
 * PHP version 5.6
 *
 * @category   Imdb
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 0.9
 */


class fdvegan_Imdb
{
    const BASE_URL = 'https://www.imdb.com/';


    /**
     * Returns the IMDb URL for an actor's Info page.
     */
    public static function getImdbPersonInfoUrl($imdb_id)
    {
        return self::BASE_URL . 'name/' . $imdb_id . '/';
    }


    /**
     * Returns the IMDb URL for an actor's Bio page.
     */
    public static function getImdbBioUrl($imdb_id)
    {
        return self::getImdbInfoUrl($imdb_id) . 'bio/';
    }


    /**
     * Returns the IMDb URL for a movie's Info page.
     */
    public static function getImdbMovieInfoUrl($imdb_id)
    {
        return self::BASE_URL . 'title/' . $imdb_id . '/';
    }



    //////////////////////////////



}

