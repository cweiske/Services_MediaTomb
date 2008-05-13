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
* Abstract base class for all MediaTomb item object classes
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
abstract class Services_MediaTomb_ItemBase
{
    /**
    * MediaTomb reference
    *
    * @var Services_MediaTomb
    */
    protected $tomb = null;

    /**
    * ID of object on the server
    *
    * @var integer
    */
    public $id = null;

    /**
    * UPnP item class
    *
    * @var string
    */
    public $class = null;

    /**
    * Internal MediaTomb object type ID
    *
    * @var integer
    */
    public $objType = null;



    public function __construct(SimpleXMLElement $item = null)
    {
        if ($item !== null) {
            if (isset($item['id'])) {
                $this->id = (int)$item['id'];
            } else if (isset($item['object_id'])) {
                $this->id = (int)$item['object_id'];
            }
            $this->class   = (string)$item->class;
            $this->objType = (string)$item->objType;
        }
    }//public function __construct(..)



    public function setTomb(Services_MediaTomb $tomb)
    {
        $this->tomb = $tomb;
    }//public function setTomb(..)



    public function save()
    {
        $this->tomb->saveItem($this);
    }//public function save()

}//class Services_MediaTomb_ItemBase

?>