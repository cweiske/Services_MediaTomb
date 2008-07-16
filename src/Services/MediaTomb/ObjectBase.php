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
* Abstract base class for all MediaTomb object classes
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
abstract class Services_MediaTomb_ObjectBase
{
    /**
    * MediaTomb reference
    *
    * @var Services_MediaTomb
    */
    protected $tomb = null;



    /**
    * Sets the internal mediatomb object
    *
    * @param Services_MediaTomb $tomb MediaTomb object
    *
    * @return void
    */
    public function setTomb(Services_MediaTomb $tomb)
    {
        $this->tomb = $tomb;
    }//public function setTomb(..)

}//class Services_MediaTomb_ObjectBase

?>