<?php
/**
 * fdvegan_process_status.php
 *
 * Implementation of Process Status class for module fdvegan.
 * Stores all info related to an ongoing or finished process.
 *
 * PHP version 5.6
 *
 * @category   Process
 * @package    fdvegan
 * @author     Rob Howe <rob@robhowe.com>
 * @copyright  2017 Rob Howe
 * @license    This file is proprietary and subject to the terms defined in file LICENSE.txt
 * @version    Bitbucket via git: $Id$
 * @link       https://fivedegreevegan.aprojects.org
 * @since      version 1.1
 */


class fdvegan_ProcessStatus extends fdvegan_BaseClass
{
    protected $_process_id     = NULL;
    protected $_process_name   = NULL;
    protected $_status         = NULL;
    protected $_verbose_status = NULL;
    protected $_movie_id       = NULL;
    protected $_person_id      = NULL;
    protected $_counter        = NULL;
    protected $_context        = NULL;


    public function __construct($options = NULL)
    {
        parent::__construct($options);

        if (!empty($options['ProcessId'])) {
            $this->_loadProcessStatusByProcessId();
        } else if (!empty($options['ProcessName'])) {
            $this->_loadProcessStatusByProcessName();
        }
    }


    public function setProcessId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_process_id = (int)$value;
        }
        return $this;
    }

    public function getProcessId()
    {
        return $this->_process_id;
    }

    /**
     * Convenience function.
     */
    public function getId()
    {
        return $this->getProcessId();
    }


    public function setProcessName($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_process_name = substr($value, 0, 127);
        }
        return $this;
    }

    public function getProcessName()
    {
        return $this->_process_name;
    }


    public function setStatus($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_status = substr($value, 0, 127);
        }
        return $this;
    }

    public function getStatus()
    {
        return $this->_status;
    }


    public function setVerboseStatus($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_verbose_status = (string)$value;
        }
        return $this;
    }

    public function getVerboseStatus()
    {
        return $this->_verbose_status;
    }


    public function setMovieId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_movie_id = (int)$value;
        }
        return $this;
    }

    public function getMovieId()
    {
        return $this->_movie_id;
    }


    public function setPersonId($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_person_id = (int)$value;
        }
        return $this;
    }

    public function getPersonId()
    {
        return $this->_person_id;
    }


    public function setCounter($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_counter = (int)$value;
        }
        return $this;
    }

    public function getCounter()
    {
        return $this->_counter;
    }


    public function setContext($value, $overwrite_even_with_empty=TRUE)
    {
        if ($overwrite_even_with_empty || !empty($value)) {
            $this->_context = $value;
        }
        return $this;
    }

    public function getContext()
    {
        return $this->_context;
    }


    public function storeProcessStatus()
    {
        if ($this->getProcessId()) {  // Must already exist in our DB, so is an update.
            $sql = <<<__SQL__
UPDATE {fdvegan_process_status} SET 
       `process_name` = :process_name, 
       `status` = :status, 
       `verbose_status` = :verbose_status, 
       `movie_id` = :movie_id, 
       `person_id` = :person_id, 
       `counter` = :counter, 
       `context` = :context, 
       `updated` = now(), 
       `synced` = :synced 
 WHERE `process_id` = :process_id
__SQL__;
            try {
                $sql_params = array(':process_id'     => $this->getProcessId(),
                                    ':process_name'   => $this->getProcessName(),
                                    ':status'         => $this->getStatus(),
                                    ':verbose_status' => $this->getVerboseStatus(),
                                    ':movie_id'       => $this->getMovieId(),
                                    ':person_id'      => $this->getPersonId(),
                                    ':counter'        => $this->getCounter(),
                                    ':context'        => $this->getContext(),
                                    ':synced'         => $this->getSynced(),
                                   );
                //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
                $result = db_query($sql, $sql_params);
            }
            catch (Exception $e) {
                throw new FDVegan_PDOException("Caught exception: {$e->getMessage()} while UPDATing process_status: ". print_r($this,1), $e->getCode(), $e, 'LOG_ERR');
            }
            fdvegan_Content::syslog('LOG_DEBUG', "Updated process_status in our DB: process_id={$this->getProcessId()}, process_name=\"{$this->getProcessName()}\".");

        } else {  // Must be a new process_status to our DB, so is an insert.

            try {
                if (empty($this->getCreated())) {
                    $this->setCreated(date('Y-m-d G:i:s'));
                }
                $this->_process_id = db_insert('fdvegan_process_status')
                ->fields(array(
                    'process_name'   => $this->getProcessName(),
                    'status'         => $this->getStatus(),
                    'verbose_status' => $this->getVerboseStatus(),
                    'movie_id'       => $this->getMovieId(),
                    'person_id'      => $this->getPersonId(),
                    'counter'        => $this->getCounter(),
                    'context'        => $this->getContext(),
                    'created'        => $this->getCreated(),
                    'synced'         => $this->getSynced(),
                ))
                ->execute();
            }
            catch (Exception $e) {
                throw new FDVegan_PDOException("Caught exception: {$e->getMessage()} while INSERTing process_status: ". print_r($this,1), $e->getCode(), $e, 'LOG_ERR');
            }
            fdvegan_Content::syslog('LOG_DEBUG', "Inserted new process_status into our DB: process_id={$this->getProcessId()}, process_name=\"{$this->getProcessName()}\".");
        }

        return $this->getProcessId();
    }


    /**
     * Load a Process Status from our database.
     * This is a catch-all convenience function.
     */
    public function loadProcessStatus()
    {
        if (!empty($this->_process_id)) {
            return $this->_loadProcessStatusByProcessId();
        } else if (!empty($this->_process_name)) {
            return $this->_loadProcessStatusByProcessName();
        }

        return NULL;
    }



    //////////////////////////////



    /**
     * @throws FDVeganNotFoundException    When process_status is not in the FDV DB.
     */
    private function _processLoadProcessStatusResult($result)
    {
        if ($result->rowCount() == 1) {
            foreach ($result as $row) {
                $this->setProcessId($row->process_id);
                $this->setProcessName($row->process_name);
                $this->setStatus($row->status);
                $this->setVerboseStatus($row->verbose_status);
                $this->setMovieId($row->movie_id);
                $this->setPersonId($row->person_id);
                $this->setCounter($row->counter);
                $this->setContext($row->context);
                $this->setCreated($row->created);
                $this->setUpdated($row->updated);
                $this->setSynced($row->synced);
            }
        }
        fdvegan_Content::syslog('LOG_DEBUG', "Loaded process_status from our DB; process_id={$this->getProcessId()}" .
                                ", process_name={$this->getProcessName()}."
                               );

        if ($result->rowCount() != 1) {
            throw new FDVegan_NotFoundException("process_id={$this->getProcessId()} not found");
        }

        return $this->getProcessId();
    }


    private function _loadProcessStatusByProcessId()
    {
        $sql = <<<__SQL__
SELECT `process_id`, `process_name`, `status`, `verbose_status`, 
       `movie_id`, `person_id`, `counter`, `context`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_process_status} 
 WHERE `process_id` = :process_id
__SQL__;
        $sql_params = array(':process_id' => $this->getProcessId());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded process_status from our DB by process_id={$this->getProcessId()}.");
        return $this->_processLoadProcessStatusResult($result);
    }


    private function _loadProcessStatusByProcessName()
    {
        $sql = <<<__SQL__
SELECT `process_id`, `process_name`, `status`, `verbose_status`, 
       `movie_id`, `person_id`, `counter`, `context`, 
       `created`, `updated`, `synced` 
  FROM {fdvegan_process_status} 
 WHERE `process_name` = :process_name 
 LIMIT 1
__SQL__;
        $sql_params = array(':process_name' => $this->getProcessName());
        //fdvegan_Content::syslog('LOG_DEBUG', 'SQL=' . fdvegan_Util::getRenderedQuery($sql, $sql_params));
        $result = db_query($sql, $sql_params);
        //fdvegan_Content::syslog('LOG_DEBUG', "Loaded process_status from our DB by process_name={$this->getProcessName()}.");
        return $this->_processLoadProcessStatusResult($result);
    }


}

