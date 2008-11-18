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
require_once 'Services/MediaTomb/ItemBase.php';

/**
* "Normal" item on a MediaTomb server that links to a file on the harddisk.
*
* SimpleItems can be retrieved via
*  Services_MediaTomb_Container::getItems() and
*  the Services_MediaTomb_ItemIterator,
*  when retrieving detailed items.
*
* @see Services_MediaTomb_Container::getItems()
* @see Services_MediaTomb_ItemIterator
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
class Services_MediaTomb_Item extends Services_MediaTomb_ItemBase
{
    public $obj_type = 'item';
    public $class = 'object.item';

    /**
    * Item title
    *
    * @var string
    */
    public $title = null;
    public $description = null;

    /**
    * Location of file on disc.
    *
    * @var string
    */
    public $location = null;
    public $mimetype = null;

    /**
    * Protocol to use to access the item
    *
    * @var string
    * @see Services_MediaTomb::PROTOCOL_HTTP_GET
    */
    public $protocol = null;


    public $arCreateProps = array(
        'title',
        'location',
        'class',
        'obj_type',
        'description',
        'mimetype' => 'mime-type'
    );

    public $arSaveProps = array(
        'title',
        'class',
        'description',
        'mimetype' => 'mime-type'
    );



    public function __construct(SimpleXMLElement $item = null)
    {
        parent::__construct($item);
        if ($item !== null) {
            $this->title       = (string)$item->title;
            $this->description = (string)$item->description;
            $this->location    = (string)$item->location;
            $this->mimetype    = (string)$item->{'mime-type'};
        }
    }//public function __construct(..)



    /**
    * Returns the title
    *
    * @return string
    */
    public function __toString()
    {
        return $this->title;
    }//public function __toString()

}//class Services_MediaTomb_Item extends Services_MediaTomb_ItemBase

?>