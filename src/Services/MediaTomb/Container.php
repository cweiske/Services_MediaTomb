<?php
require_once 'Services/MediaTomb/ItemBase.php';


class Services_MediaTomb_Container extends Services_MediaTomb_ItemBase
{
    public $objType = 1;
    public $class = 'object.container';

    public $childCount = null;
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
    * @return Services_MediaTomb_Container
    */
    public function createContainer($strTitle, $bReturn = true)
    {
        return $this->tomb->createContainer($this->id, $strTitle, $bReturn);
    }//public function createContainer(..)



    /**
    * Create a new external link under container $nId
    *
    * @param string  $strTitle       Item title/name
    * @param string  $strUrl         Full URL to link to
    * @param string  $strDescription Description text
    * @param string  $strMimetype    Mime type, e.g. application/ogg
    *
    * @return Services_MediaTomb_Item
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
    * Returns an array of children containers for the given ID.
    *
    * @return Services_MediaTomb_Container[] Array of containers
    */
    public function getContainers()
    {
        //FIXME: check if id is set
        return $this->tomb->getContainers($this->id);
    }//public function getContainers()



    /**
    * Returns an array of children containers for the given ID
    *
    * @return Services_MediaTomb_Item[] Array of items
    */
    public function getItems($nStart = 0, $nCount = 25)
    {
        return $this->tomb->getItems($this->id, $nStart, $nCount);
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

}//class Services_MediaTomb_Container extends Services_MediaTomb_Item

?>