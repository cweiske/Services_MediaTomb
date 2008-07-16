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

/**
* Include base class
*/
require_once 'PEAR/Exception.php';

/**
* Exception when using Services_MediaTomb
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
class Services_MediaTomb_Exception extends PEAR_Exception
{
    /**
    * Login failed
    */
    const LOGIN = 403;

    /**
    * Item path to be added does not exist on server
    */
    const FILE_NOT_FOUND = 404;

    /**
    * getDetailledItem() fails because the item class is not supported
    *
    * @see Services_MediaTomb_SimpleItem::getDetailledItem()
    */
    const UNSUPPORTED_ITEM = 23;

    /**
    * Cannot extract an ID from the given value
    */
    const NO_ID = 24;

    /**
    * The object to be saved (not created!) has no ID set.
    * If you want to save a completly new item, use create().
    *
    * @see Services_MediaTomb::create()
    */
    const OBJECT_HAS_NO_ID = 25;

    /**
    * Creating a container failed
    */
    const CONTAINER_CREATION_FAILED = 30;

    /**
    * An item object needs to define an array of its own properties.
    * This object didn't. If that happens, it's because the programmer did
    * not follow the rules.
    */
    const NO_SAVE_PROPS = 31;

    /**
    * A property that's needed for saving is NULL, thus has not been set.
    * Empty properties are not allowed.
    */
    const PROP_NOT_SET = 32;

    /**
    * Services_MediaTomb_SimpleItem objects may not be saved.
    */
    const NEVER_SAVE_SIMPLE_ITEMS = 33;

    /**
    * Someone tried to cancel an uncancellable task
    *
    * @see Services_MediaTomb_Task
    */
    const TASK_UNCANCELLABLE = 41;

}//class Services_MediaTomb_Exception extends Exception

/**
* Exception when communicating with a mediatomb the server
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
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