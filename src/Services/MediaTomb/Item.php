<?php
require_once 'Services/MediaTomb/ItemBase.php';

class Services_MediaTomb_Item extends Services_MediaTomb_ItemBase
{
    public $objType = 2;
    public $class = 'object.item';

    public $title = null;
    public $description = null;
    public $location = null;
    public $mimetype = null;
    public $protocol = null;


    public $arCreateProps = array(
        'title',
        'location',
        'class',
        'objType',
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

}//class Services_MediaTomb_Item extends Services_MediaTomb_ItemBase

?>