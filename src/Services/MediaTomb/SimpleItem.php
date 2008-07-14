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
require_once 'Services/MediaTomb/ItemBase.php';

/**
* Simple list item with no more additional data
*/
class Services_MediaTomb_SimpleItem extends Services_MediaTomb_ItemBase
{
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
    * Returns the detailled item for this simple list item.
    *
    * @return Services_MediaTomb_ItemBase
    */
    public function getDetailledItem()
    {
        return $this->tomb->getDetailledItem($this->id);
    }//public function getDetailledItem()



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