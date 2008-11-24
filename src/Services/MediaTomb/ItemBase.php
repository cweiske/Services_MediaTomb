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
require_once 'Services/MediaTomb/ObjectBase.php';

/**
* Abstract base class for all MediaTomb item object classes
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
abstract class Services_MediaTomb_ItemBase extends Services_MediaTomb_ObjectBase
{
    /**
    * ID of object on the server.
    * As long as the id is null, the object is considered to be non-existing
    * on the server (new).
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



    public function __construct(stdClass $item = null)
    {
        if ($item !== null) {
            if (isset($item->id)) {
                $this->id = (int)$item->id;
            } else if (isset($item->object_id)) {
                $this->id = (int)$item->object_id;
            }

//            $this->class   = (string)$item->class;
//            $this->objType = (string)$item->objType;
        }
    }//public function __construct(..)



    /**
    * Deletes this object in mediatomb
    *
    * @return boolean True if all went well
    */
    public function delete()
    {
        $retval = $this->tomb->deleteItem($this);

        //prevent saving after deletion
        $this->id = null;

        return $retval;
    }//public function delete()



    /**
    * Saves the item on the server.
    * Only existing ($id !== null) objects can be saved.
    * If you want to create a new object, use Services_MediaTomb::create().
    *
    * @see Services_MediaTomb::create()
    * @see Services_MediaTomb::saveItem()
    *
    * @return boolean True if all went well
    */
    public function save()
    {
        return $this->tomb->saveItem($this);
    }//public function save()

}//class Services_MediaTomb_ItemBase extends Services_MediaTomb_ObjectBase

?>