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
* Simple list item with only title and URL.
*
* Simple list item that only contains ID, title and
* its HTTP URL.
* SimpleItems can be retrieved via
* Services_MediaTomb_Container::getItems() and the Services_MediaTomb_ItemIterator,
* when not retrieving detailed items.
*
* The corresponding detailed item can be retrieved using
* Services_MediaTomb_SimpleItem::getDetailedItem().
*
* @see getDetailedItem()
* @see Services_MediaTomb_Container::getItems()
* @see Services_MediaTomb_ItemIterator
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
class Services_MediaTomb_SimpleItem extends Services_MediaTomb_ItemBase
{
    /**
    * Item title
    *
    * @var string
    */
    public $title = null;

    /**
    * URL how to retrieve item via HTTP
    *
    * @var string
    */
    public $url   = null;



    public function __construct(SimpleXMLElement $item = null)
    {
        if ($item !== null) {
            //FIXME: wrong inhertitance relation
            $this->id    = (int)$item['id'];
            $this->title = (string)$item->title;
            $this->url   = (string)$item->res;
        }
    }//public function __construct(..)



    /**
    * Returns the detailed item for this simple list item.
    *
    * @return Services_MediaTomb_ItemBase
    */
    public function getDetailedItem()
    {
        return $this->tomb->getDetailedItem($this->id);
    }//public function getDetailedItem()



    /**
    * You cannot save simple items
    */
    public function save()
    {
        throw new Services_MediaTomb_Exception(
            'You may not save SimpleItems',
            Services_MediaTomb_Exception::NEVER_SAVE_SIMPLE_ITEMS
        );
    }//public function save()



    /**
    * Returns the title
    *
    * @return string
    */
    public function __toString()
    {
        return $this->title;
    }//public function __toString()

}//class Services_MediaTomb_SimpleItem extends Services_MediaTomb_ItemBase

?>