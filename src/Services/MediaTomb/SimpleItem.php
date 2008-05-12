<?php
require_once 'Services/MediaTomb/ItemBase.php';

/**
* Simple list item with no more additional data
*/
class Services_MediaTomb_SimpleItem extends Services_MediaTomb_ItemBase
{
    public $title = null;
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

}//class Services_MediaTomb_SimpleItem extends Services_MediaTomb_ItemBase


?>