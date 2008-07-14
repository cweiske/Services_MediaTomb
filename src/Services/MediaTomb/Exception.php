<?php
/**
* Part of Services_MediaTomb
*
* PHP version 5
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
require_once 'PEAR/Exception.php';

class Services_MediaTomb_Exception extends PEAR_Exception
{
    /**
    * Login failed
    */
    const LOGIN = 403;

    /**
    * getDetailledItem() fails because the item class is not supported
    */
    const UNSUPPORTED_ITEM = 23;

    /**
    * Cannot extract an ID from the given value
    */
    const NO_ID = 24;

    /**
    * The object to be saved (not created!) has no ID set.
    * If you want to save a completly new item, use create().
    */
    const OBJECT_HAS_NO_ID = 25;

    /**
    * Creating a container failed
    */
    const CONTAINER_CREATION_FAILED = 30;

    /**
    * An item object needs to define an array of its own properties.
    * This one didn't. If that happens, it's because the programmer did
    * not follow the rules.
    */
    const NO_SAVE_PROPS = 31;

    /**
    * A property that's needed for saving is NULL, thus has not been set.
    * Empty properties are not allowed.
    */
    const PROP_NOT_SET = 32;

}//class Services_MediaTomb_Exception extends Exception

/**
* Exception thrown by the server
*/
class Services_MediaTomb_ServerException extends Services_MediaTomb_Exception
{
    /**
    * Reading the XML response from the HTTP request failed.
    * E.g. your network could be down, or MediaTomb crashed while responding.
    */
    const CANNOT_READ_XML = 50;

    /**
    * The server properly returned a response, but it contains an error message.
    */
    const NORMAL_ERROR = 51;
}

?>