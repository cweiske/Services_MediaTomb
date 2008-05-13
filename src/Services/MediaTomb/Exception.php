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
    const LOGIN = 404;
    const UNSUPPORTED_ITEM = 23;
}//class Services_MediaTomb_Exception extends Exception

/**
* Exception thrown by the server
*/
class Services_MediaTomb_ServerException extends Services_MediaTomb_Exception
{
}

?>