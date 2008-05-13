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

class Services_MediaTomb_ExternalLink extends Services_MediaTomb_ItemBase
{
    public $objType = 10;
    public $class = 'object.item';

    public $title = null;
    public $description = null;
    public $url = null;
    public $mimetype = null;
    public $protocol = null;

    public $arCreateProps = array(
        'title',
        'url' => 'location',
        'objType',
        'protocol',
        'class',
        'description',
        'mimetype' => 'mime-type'
    );

    public $arSaveProps = array(
        'title',
        'url' => 'location',
        'protocol',
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
            $this->url         = (string)$item->location;
            $this->mimetype    = (string)$item->{'mime-type'};
            $this->protocol    = (string)$item->protocol;
        }
    }//public function __construct(..)

}//class Services_MediaTomb_ExternalLink extends Services_MediaTomb_ItemBase

?>