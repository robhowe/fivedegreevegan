<?php
/**
 * fdvegan_exception.php
 *
 * Implementation of custom Exception base class for module fdvegan.
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


class FDVegan_Exception extends Exception
{

    public function __construct($message = null, $code = 0, Exception $previous = null, $priority = 'LOG_INFO')
    {
        $errStr = $this;
        // To include verbose backtrace info, uncomment the following line:
        $errStr .= "\n  Backtrace:\n" . fdvegan_Util::debug_string_backtrace();

        if (!is_int($code)) {
            $errStr .= "\n  ERROR: Exception code \"{$code}\" is not an int";
            if ($code === 'HY000') {
                // This can happen if a long blocking process (like rebuild-connections) is still running.
                // In Windows 10, this requires a reboot of the DB and/or HTTP server.
                $errStr .= "\n  HINT: The DB timed out.  Try restarting the DB server.";
            }
            $code = intval($code);
        }

        fdvegan_Content::syslog($priority, $errStr);

        parent::__construct($message, $code, $previous);
    }


    public function __toString() {
        return  __CLASS__ . ": [{$this->code}]: {$this->message}";
    }



    //////////////////////////////



}

