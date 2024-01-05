<?php
/**
 * fdvegan_notfoundexception.php
 *
 * Implementation of custom Exception class for module fdvegan.
 * All custom FDVegan Exceptions follow the naming convention:
 *   "FDVegan*Exception" found in file "fdvegan_*exception.php"
 *
 * PHP version 5.6
 *
 * @category   Exception
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2016 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 1.0
 */


class FDVegan_NotFoundException extends FDVegan_Exception
{

    public function __toString() {
        return  __CLASS__ . ": [{$this->code}]: {$this->message}";
    }



    //////////////////////////////



}

