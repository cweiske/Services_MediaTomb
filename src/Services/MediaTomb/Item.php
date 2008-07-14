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