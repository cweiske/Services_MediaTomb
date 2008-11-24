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
* External link.
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
class Services_MediaTomb_ExternalLink extends Services_MediaTomb_ItemBase
{
    public $obj_type = 'external_url';
    public $class = 'object.item';

    /**
    * Link title
    *
    * @var string
    */
    public $title = null;
    public $description = null;
    public $url = null;
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
        'url' => 'location',
        'obj_type',
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



    public function __construct(stdClass $item = null)
    {
        parent::__construct($item);
        if ($item !== null) {
            $this->title       = (string)$item->title->value;
            $this->description = (string)$item->description->value;
            $this->url         = (string)$item->location->value;
            $this->mimetype    = (string)$item->{'mime-type'}->value;
            $this->protocol    = (string)$item->protocol->value;
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

}//class Services_MediaTomb_ExternalLink extends Services_MediaTomb_ItemBase

?>