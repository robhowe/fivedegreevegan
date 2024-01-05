<?php
/**
 * fdvegan_rest_api_content.php
 *
 * Implementation of all output "View" for sub-module fdvegan_rest_api.
 *
 * PHP version 5.6
 *
 * @category   Content
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 1.1
 */


class fdvegan_rest_api_Content
{

    /**
     * Called from fdvegan_rest_api.module::_fdvegan_rest_api_export()
     *
     * @return string    Content
     */
    public static function getActorNetworkContent($options = NULL)
    {
        if (!isset($options['Degrees'])) {
            fdvegan_Content::syslog('LOG_ERR', 'Degrees option not provided: ' . print_r($options,1));
            return self::getRestApiSystemErrorContent($options);
            //throw new FDVegan_InvalidArgumentException('No degrees value provided for content');
        }

        $fdvRestApiData = new fdvegan_rest_api_Data();
        return $fdvRestApiData->getActorNetworkObj($options['Degrees']);
    }


    /**
     * Called from fdvegan_rest_api.module::_fdvegan_rest_api_export()
     *
     * @return string    Content
     */
    public static function getActorTreeContent($options = NULL)
    {
        if (!isset($options['PersonId']) && !isset($options['FullName'])) {
            fdvegan_Content::syslog('LOG_ERR', 'invalid options provided: ' . print_r($options,1));
            return self::getRestApiSystemErrorContent($options);
            //throw new FDVegan_InvalidArgumentException('No actor name provided for content');
        }
        if (!isset($options['Depth'])) {
            fdvegan_Content::syslog('LOG_ERR', 'Depth option not provided: ' . print_r($options,1));
            return self::getRestApiSystemErrorContent($options);
            //throw new FDVegan_InvalidArgumentException('No depth value provided for content');
        }
        try {
            $person = new fdvegan_Person($options);
        }
        catch (FDVegan_NotFoundException $e) {  // No person found
            return self::getRestApiActorNotFoundContent($options);
        }

        $fdvRestApiData = new fdvegan_rest_api_Data();
        return $fdvRestApiData->getActorTreeObj($person, $options['Depth']);
    }


    /**
     * Implementation of view for getRestApiActorNotFoundContent().
     */
    public static function getRestApiActorNotFoundContent($options = NULL)
    {
        $content = array('success' => FALSE,
                         'error' => 'No matching actor found',
                        );
        return $content;
    }



    //////////////////////////////



}

