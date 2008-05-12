<?php

abstract class Services_MediaTomb_ItemBase
{
    protected $tomb = null;
    public $id = null;
    public $class = null;
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