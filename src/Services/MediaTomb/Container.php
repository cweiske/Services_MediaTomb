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
* Container (directory) on a MediaTomb server.
*
* Containers can be retrieved using Services_MediaTomb::getRootContainer(),
*  or getContainers() of another container.
* Child items can be listed via getItems(), getItemIterator()
*  or getSingleItem().
*
* @see Services_MediaTomb::getRootContainer()
* @see getContainers()
* @see getItems()
* @see getItemIterator()
* @see getSingleItem()
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
class Services_MediaTomb_Container extends Services_MediaTomb_ItemBase
{
    public $objType = 1;
    public $class = 'object.container';

    public $childCount = null;

    /**
    * Container title
    *
    * @var string
    */
    public $title = null;

    public $arCreateProps = array(
        'title',
        'class',
        'objType'
    );

    public $arSaveProps = array(
        'title',
        'class',
    );



    /**
    * Creates a new container object.
    * When an xml container object is passed, the data will be integrated
    * into this object.
    *
    * @param SimpleXMLElement $container XML container object from MediaTomb
    */
    public function __construct(SimpleXMLElement $container = null)
    {
        parent::__construct($container);
        if ($container !== null) {
            $this->childCount = (int)$container['childCount'];
            $this->title      = (string)$container;
        }
    }//public function __construct(..)



    /**
    * Create a new container under this container.
    *
    * @param string  $strTitle Title of new container
    * @param boolean $bReturn  If the newly created object
    *                           shall be returned
    *
    * @return Services_MediaTomb_Container
    */
    public function createContainer($strTitle, $bReturn = true)
    {
        return $this->tomb->createContainer($this->id, $strTitle, $bReturn);
    }//public function createContainer(..)



    /**
    * Create a new external link in this container.
    *
    * @param string  $strTitle       Item title/name
    * @param string  $strUrl         Full URL to link to
    * @param string  $strDescription Description text
    * @param string  $strMimetype    Mime type, e.g. application/ogg
    * @param string  $strProtocol    Protocol of link (e.g. "http-get")
    * @param string  $strClass       UPnP Item class (defaults to "object.item")
    * @param boolean $bReturn        If the newly created object
    *                                 shall be returned
    *
    * @return Services_MediaTomb_ExternalLink Newly created link
    */
    public function createExternalLink(
        $strTitle, $strUrl, $strDescription, $strMimetype,
        $strProtocol = 'http-get', $strClass = 'object.item', $bReturn = true
    ) {
        return $this->tomb->createExternalLink(
            $this->id, $strTitle, $strUrl, $strDescription, $strMimetype,
            $strProtocol, $strClass, $bReturn
        );
    }// public function createExternalLink(..)



    /**
    * Returns an array of children containers for this container.
    *
    * @return Services_MediaTomb_Container[] Array of containers.
    *                                        Key is the container id
    */
    public function getContainers()
    {
        return $this->tomb->getContainers($this->id);
    }//public function getContainers()



    /**
    * Returns an item iterator object to easily loop over the items.
    *
    * @param boolean $bDetailled If the simple item only, or the "real" item
    *                             shall be returned
    *
    * @return Services_MediaTomb_ItemIterator
    */
    public function getItemIterator($bDetailled = true, $nPageSize = null)
    {
        return $this->tomb->getItemIterator($this, $bDetailled, $nPageSize);
    }//public function getItemIterator()



    /**
    * Returns an array of children containers for the given ID
    *
    * @param integer $nStart     Position of first item to retrieve
    * @param integer $nCount     Number of items to retrieve
    * @param boolean $bDetailled If the simple item only, or the "real" item
    *                             shall be returned
    *
    * @return Services_MediaTomb_Item[] Array of items, Services_MediaTomb_Item (detailled)
    *                                   or Services_MediaTomb_SimpleItem (not detailled)
    */
    public function getItems($nStart = 0, $nCount = 25, $bDetailled = true)
    {
        return $this->tomb->getItems($this->id, $nStart, $nCount, $bDetailled);
    }//public function getItems($nStart = 0, $nCount = 25)



    /**
    * Returns a single container item that has the given title
    * and has the given parent id.
    *
    * @param string  $strTitle Title of container that shall be returned
    *
    * @return Services_MediaTomb_Container or null if not found
    */
    public function getSingleContainer($strTitle)
    {
        return $this->tomb->getSingleContainer($this->id, $strTitle);
    }//public function getSingleContainer(..)



    /**
    * Returns a single item item that has the given title
    * and the given parent id.
    *
    * @param string  $strTitle   Title of item that shall be returned
    * @param boolean $bDetailled If the detailled item shall be returned
    *
    * @return Services_MediaTomb_ItemBase or null if not found
    */
    public function getSingleItem($strTitle, $bDetailled = true)
    {
        return $this->tomb->getSingleItem($this->id, $strTitle, $bDetailled);
    }//public function getSingleItem(..)



    /**
    * Returns the title
    *
    * @return string
    */
    public function __toString()
    {
        return $this->title;
    }//public function __toString()

}//class Services_MediaTomb_Container extends Services_MediaTomb_Item

?>