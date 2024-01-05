<?php
/**
 * fdvegan_rest_api_data.php
 *
 * Implementation of all "Model" data for sub-module fdvegan_rest_api.
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


class fdvegan_rest_api_Data
{
    private $_visited_ids;  // array of stacks to track visited id's
    private $_connected_names;  // array of person's names


    /**
     * Called from fdvegan_rest_api_Content::getActorNetworkContent()
     * This is the newer much-faster implementation that uses the `fdvegan_connection` "cache" table.
     * @see __DEPRECATED__getActorNetworkObj() for the older slower recursive obj implementation.
     *
     * @param int $degrees    Depth of degrees to search to (degrees = # movies = # other actors).
     * @return object    JSON content
     */
    public function getActorNetworkObj($degrees)
    {
        // Get a list of ALL the actors:

        $connections = new fdvegan_Connection();
        $connections_array = $connections->getConnections($degrees);

        $result = array();
        foreach ($connections_array as $row) {
            $obj = new stdClass();
            $obj->name        = fdvegan_rest_api_Util::getD3SafeName($row->full_name);
            $obj->person_id   = $row->person_id;
            $obj->connections = fdvegan_rest_api_Util::getD3SafeNamesList($row->degree_names);
            if (count($obj->connections)) {  // Only return connected actors
                $result[] = $obj;
            }
            //fdvegan_Content::syslog('LOG_DEBUG', " person={$row->full_name}, connections={$row->degree_names}");
        }

        return (object) array('success' => TRUE,
                              'result'  => $result,
                              'degrees' => $degrees,
                             );
    }


    /**
     * Called from fdvegan_rest_api_Content::getActorNetworkContent()
     *
     * @param int $degrees    Depth of degrees to search to (degrees = # movies = # other actors).
     * @return object    JSON content
     */
    public function __DEPRECATED__getActorNetworkObj($degrees)
    {
        // Get a list of ALL the actors:
        $person_collection = new fdvegan_PersonCollection();
        $person_collection->getMinPersonsNetworkArray();
        //fdvegan_Content::syslog('LOG_DEBUG', "person_collection:" . print_r($person_collection,1));

        $result = array();
        foreach ($person_collection->getItems() as $person_id => $person_name) {
            $obj = new stdClass();
            $obj->name        = fdvegan_rest_api_Util::getD3SafeName($person_name);
            $obj->person_id   = $person_id;
            $obj->connections = $this->_getConnectionsArray($person_id, $degrees);
            if (count($obj->connections)) {  // Only return connected actors
                $result[] = $obj;
            }
            //fdvegan_Content::syslog('LOG_DEBUG', " person={$person_name}, connections=" . print_r($obj->connections,1));
        }

        return (object) array('success' => TRUE,
                              'result'  => $result,
                              'degrees' => $degrees,
                             );
    }


    /**
     * Called from fdvegan_rest_api_Content::getActorTreeContent()
     *
     * @param int $node_depth    Depth to drive to (node_depth = # actor and movie nodes).
     * @return object    JSON content
     */
    public function getActorTreeObj($person, $node_depth)
    {
        $this->_visited_ids = array('person' => array(), 'movie' => array());
        $result = $this->_getRecursiveObj($person, $node_depth-1, 'person');
        // Check for the rare case where an actor is in 0 movies:
        if (!is_object($result) || empty(get_object_vars($result))) {
            $result = new stdClass();
            $result->{$person->name} = $person->id;
        }
        return (object) array('success' => TRUE,
                              'result'  => $result,
                              'depth'   => $node_depth,
                             );
    }



    //////////////////////////////



    private function _getConnectionsArrayHelper(&$item, $key)
    {
        $item = fdvegan_rest_api_Util::getD3SafeName($item);
    }


    /**
     * Called from self::getActorNetworkObj()
     *
     * @param int $degrees    Depth of degrees to search to (degrees = # movies = # other actors).
     * @return object    JSON content
     */
    private function _getConnectionsArray($person_id, $degrees)
    {
        $person_opts = array('PersonId' => $person_id);
        $person = new fdvegan_Person($person_opts);  // Don't bother catching any exceptions.

        $this->_visited_ids = array('person' => array(), 'movie' => array());
        $this->_connected_names = array();
        $this->_getRecursiveConnectionsArray($person, $degrees, 'person');

        $this->_connected_names = array_unique($this->_connected_names);  // Remove any duplicates
        $index = array_search($person->name, $this->_connected_names);
        if ($index !== FALSE){
            unset($this->_connected_names[$index]);
        }
        sort($this->_connected_names, SORT_STRING);
        array_walk($this->_connected_names, array($this, '_getConnectionsArrayHelper'));

        return $this->_connected_names;
    }


    /**
     * Called from self::_getConnectionsArray()
     *
     * @param int $degrees    Depth of degrees to search to (degrees = # movies = # other actors).
     * @return object    JSON content
     */
    private function _getRecursiveConnectionsArray($obj, $degrees, $follow)
    {
        //fdvegan_Content::syslog('LOG_DEBUG', 'BEGIN obj=' . get_class($obj) . ", degrees={$degrees}, follow={$follow}.");
        //fdvegan_Content::syslog('LOG_DEBUG', "this->_visited_ids=" . print_r($this->_visited_ids,1));
        //fdvegan_Content::syslog('LOG_DEBUG', "this->_connected_names=" . print_r($this->_connected_names,1));
        $next_follow = ($follow === 'person') ? 'movie' : 'person';
        switch (get_class($obj)) {
            case 'fdvegan_Movie':
            case 'fdvegan_Person':
//                $ret_obj = new stdClass();
                if ($degrees) {
                    $credits = $obj->getCredits();
                    if ($credits->count()) {
                        $this->_visited_ids[$follow][] = $obj->id;  // push onto tracking stack
                        if ((get_class($obj) === 'fdvegan_Person') &&
                            (!in_array($obj->name, $this->_connected_names))) {
                            $this->_connected_names[] = $obj->name;
                        }
//                        $ret_obj->{$obj->name} = $this->_getRecursiveConnectionsArray($credits, $degrees-($follow === 'movie'), $next_follow);
                        $this->_getRecursiveConnectionsArray($credits, $degrees-($follow === 'movie'), $next_follow);
                        array_pop($this->_visited_ids[$follow]);  // pop off tracking stack
                    }
                } else {
                    if (!in_array($obj->id, $this->_visited_ids[$follow])) {
//                        $ret_obj->{$obj->name} = $obj->id;
//                        $ret_obj->{$obj->name} = 1;
                        if ((get_class($obj) === 'fdvegan_Person') &&
                            (!in_array($obj->name, $this->_connected_names))) {
                            $this->_connected_names[] = $obj->name;
                        }
                    }
                }
                return;
//                return empty($ret_obj) ? NULL : $ret_obj;

            case 'fdvegan_CreditCollection':
//                $ret_obj = new stdClass();
                foreach ($obj as $credit) {
                    if ($degrees) {
                        if (!in_array($credit->{$follow}->id, $this->_visited_ids[$follow])) {
                            $this->_visited_ids[$follow][] = $credit->{$follow}->id;  // push onto tracking stack
                            if (($follow === 'person') &&
                                (!in_array($credit->{$follow}->name, $this->_connected_names))) {
                                $this->_connected_names[] = $credit->{$follow}->name;
                            }
                            $pre_obj = $this->_getRecursiveConnectionsArray($credit->{$follow}->getCredits(), $degrees-($follow === 'movie'), $next_follow);
//                            if (is_object($pre_obj) && count(get_object_vars($pre_obj))) {
                            if (!empty($pre_obj)) {
//                                $ret_obj->{$credit->{$follow}->name} = $pre_obj;
                                if (($follow === 'person') &&
                                    (!in_array($credit->{$follow}->name, $this->_connected_names))) {
                                    $this->_connected_names[] = $credit->{$follow}->name;
                                }
                            } else {
                                // To not even list actors/movies that have no other connections to them,
                                // comment out the lines below:
                                //$ret_obj->{$credit->{$follow}->name} = 1;  // if doesn't need to be unique in overall collection
                                //if ($follow === 'person') {
                                //    $this->_connected_names[] = $credit->{$follow}->name;
                                //}
                            }
                            array_pop($this->_visited_ids[$follow]);  // pop off tracking stack
                        }
                    } else {
                        if (!in_array($credit->{$follow}->id, $this->_visited_ids[$follow])) {
//                            $ret_obj->{$credit->{$follow}->name} = $credit->{$follow}->id;
                            if (($follow === 'person') &&
                                (!in_array($credit->{$follow}->name, $this->_connected_names))) {
                                $this->_connected_names[] = $credit->{$follow}->name;
                            }
                        }
                    }
                }
                return;
//                return empty($ret_obj) ? NULL : $ret_obj;
        }
        throw new FDVegan_NotImplementedException('Error: unknown class "' . get_class($obj) . '"');
    }


    /**
     * Called from self::getActorTreeObj()
     *
     * @param int $node_depth    Depth to drive to (node_depth = # actor and movie nodes).
     * @return object    JSON content
     */
    private function _getRecursiveObj($obj, $node_depth, $follow)
    {
        //fdvegan_Content::syslog('LOG_DEBUG', 'BEGIN obj=' . get_class($obj) . ", node_depth={$node_depth}, follow={$follow}.");
        //fdvegan_Content::syslog('LOG_DEBUG', "this->_visited_ids=" . print_r($this->_visited_ids,1));
        $next_follow = ($follow === 'person') ? 'movie' : 'person';
        switch (get_class($obj)) {
            case 'fdvegan_Movie':
            case 'fdvegan_Person':
                $ret_obj = new stdClass();
                if ($node_depth) {
                    $credits = $obj->getCredits();
                    if ($credits->count()) {
                        $this->_visited_ids[$follow][] = $obj->id;  // push onto tracking stack
                        $ret_obj->{$obj->name} = $this->_getRecursiveObj($credits, $node_depth-1, $next_follow);
                        array_pop($this->_visited_ids[$follow]);  // pop off tracking stack
                    }
                } else {
                    if (!in_array($obj->id, $this->_visited_ids[$follow])) {
                        $ret_obj->{$obj->name} = $obj->id;
                    }
                }
                return empty($ret_obj) ? NULL : $ret_obj;

            case 'fdvegan_CreditCollection':
                $ret_obj = new stdClass();
                foreach ($obj as $credit) {
                    if ($node_depth) {
                        if (!in_array($credit->{$follow}->id, $this->_visited_ids[$follow])) {
                            $this->_visited_ids[$follow][] = $credit->{$follow}->id;  // push onto tracking stack
                            $pre_obj = $this->_getRecursiveObj($credit->{$follow}->getCredits(), $node_depth-1, $next_follow);
//                            if (is_object($pre_obj) && count(get_object_vars($pre_obj))) {
                            if (!empty($pre_obj)) {
                                $ret_obj->{$credit->{$follow}->name} = $pre_obj;
                            } else {
                                // To not even list actors/movies that have no other connections to them,
                                // comment out the line below:
                                //$ret_obj->{$credit->{$follow}->name} = $credit->{$follow}->id;
                            }
                            array_pop($this->_visited_ids[$follow]);  // pop off tracking stack
                        }
                    } else {
                        if (!in_array($credit->{$follow}->id, $this->_visited_ids[$follow])) {
                            $ret_obj->{$credit->{$follow}->name} = $credit->{$follow}->id;
                        }
                    }
                }
                return empty($ret_obj) ? NULL : $ret_obj;
        }
        throw new FDVegan_NotImplementedException('Error: unknown class "' . get_class($obj) . '"');
    }

}

