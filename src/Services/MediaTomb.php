<?php
require_once 'Services/MediaTomb/Exception.php';

require_once 'Services/MediaTomb/Container.php';
require_once 'Services/MediaTomb/ExternalLink.php';
require_once 'Services/MediaTomb/Item.php';
require_once 'Services/MediaTomb/SimpleItem.php';

/**
* @TODO:
* - track container path
*/
class Services_MediaTomb
{
    const PROTOCOL_HTTP_GET = 'http-get';

    protected $ip = null;
    protected $port = 49152;

    protected $strInterfaceUrl = null;

    protected $arDefaultParams = array();

    /**
    * Weather to work around the mediatomb timing bug
    * in 0.11.0 (#1962538)
    *
    * @var boolean
    */
    public $bWorkaroundTimingBug = true;



    /**
    * FIXME: use DSN
    */
    public function __construct($username, $password, $ip, $port = null)
    {
        $this->ip = $ip;
        if ($port !== null) {
            $this->port = $port;
        }
        $this->strInterfaceUrl = 'http://' . $this->ip . ':' . $this->port . '/content/interface?';
        $this->login($username, $password);
    }//public function __construct($username, $password, $ip, $port)



    /**
    * Logs into the mediatomb server so the normal API methods can be used.
    *
    * @param string $username Username
    * @param string $password Password
    *
    * @return void
    *
    * @throws Services_MediaTomb_Exception When the login failed
    */
    protected function login($username, $password)
    {
        $xml = $this->sendRequest(array(
            'req_type' => 'auth',
            'sid'      => 'null'
        ));
        $sid   = (string)$xml['sid'];
        $token = (string)$xml->token;

        $this->arDefaultParams['sid'] = $sid;

        try {
            $xml = $this->sendRequest(array(
                'req_type' => 'auth',
                'auth'     => '1',
                'username' => $username,
                'password' => md5($token . $password)
            ));
        } catch (Services_MediaTomb_ServerException $e) {
            throw new Services_MediaTomb_Exception(
                $e->getMessage, Services_MediaTomb_Exception::LOGIN
            );
        }
    }//protected function login($username, $password)



    /**
    * Sends request to server.
    * Automatically checks for errors in the returned xml values.
    *
    *
    * @param array $arParams Array of parameters (key-value pairs) that shall
    *                        be send with the request
    *
    * @return SimpleXMLElement XML object of return value
    *
    * @throws Services_MediaTomb_Exception In case an error is returned
    */
    protected function sendRequest($arParams)
    {
        $arParams = array_merge($this->arDefaultParams, $arParams);
        $arParamStrings = array();
        foreach ($arParams as $strKey => $strValue) {
            $arParamStrings[] = urlencode($strKey) . '=' . urlencode($strValue);
        }

        $xml = new SimpleXMLElement(file_get_contents(
            $this->strInterfaceUrl . implode('&', $arParamStrings)
        ));

        if (isset($xml->error)) {
            throw new Services_MediaTomb_ServerException((string)$xml->error);
        }

        return $xml;
    }//protected function sendRequest($arParams)



    /**
    * Work around the timing bug in mediatomb 0.11.0.
    * After each deletion, we need to wait some time.
    *
    * @see http://sourceforge.net/tracker/index.php?func=detail&aid=1962538&group_id=129766&atid=715780
    */
    protected function workaroundTimingBug()
    {
        if ($this->bWorkaroundTimingBug) {
            usleep(500000);
        }
    }//protected function workaroundBug()



    /**
    * Creates the item in mediatomb
    *
    * @param integer                     $nParentId Parent container ID to add element to
    * @param Services_MediaTomb_ItemBase $item      Item to create
    * @param boolean                     $bReturn   If the saved item shall be fetched and returned
    *
    * @return Services_MediaTomb_ItemBase Newly created item
    */
    public function create($nParentId, Services_MediaTomb_ItemBase $item, $bReturn = true)
    {
        $arParams = array_merge(
            array(
                'req_type'  => 'add_object',
                'parent_id' => $nParentId
            ),
            $this->getSaveProperties($item, false)
        );

        $this->sendRequest($arParams);

        if (!$bReturn) {
            return null;
        }

        $strTitle = $item->title;

        if ($item->class == 'object.container'
            || $item instanceof Services_MediaTomb_Container
        ) {
            $item = $this->getSingleContainer($nParentId, $strTitle);
        } else {
            $item = $this->getSingleItem($nParentId, $strTitle);
        }

        if ($item === null) {
            return false;
        }
        return $item;
    }//public function create(..)



    /**
    * Create a new container under container $nId
    *
    * @return Services_MediaTomb_Container
    */
    public function createContainer($nParentId, $strTitle, $bReturn = true)
    {
        $container = new Services_MediaTomb_Container();
        $container->objType = 1;
        $container->title   = $strTitle;
        $container->class   = 'object.container';

        return $this->create($nParentId, $container, true);
    }//public function createContainer(..)



    /**
    * Creates a container with the given path and returns it.
    *
    * @param string  $strPath  Path, e.g. 'Audio/Artists/Maria Taylor'
    * @param boolean $bParents If parent containers shall be created
    * @param boolean $bReturn  If the newly created container should be returned
    *
    * @return Services_MediaTomb_Container
    */
    public function createContainerByPath($strPath, $bParents = true, $bReturn = true)
    {
        $arParts   = explode('/', $strPath);
        $nParentId = 0;
        foreach ($arParts as $strName) {
            $container = $this->getSingleContainer($nParentId, $strName);
            if ($container === null) {
                //create it
                $container = $this->createContainer($nParentId, $strName, true);
            }

            if (!$container instanceof Services_MediaTomb_Container) {
                throw new Services_MediaTomb_Exception(
                    'Container creation error: ' . $strName . ' (' . $strPath . ')'
                );
            }
            $nParentId = $container->id;
        }

        //TODO: use $bParents and $bReturn

        return $container;
    }//public function createContainerByPath(..)



    /**
    * Create a new external link under container $nId
    *
    * @param integer $nParentId      Parent ID
    * @param string  $strTitle       Item title/name
    * @param string  $strUrl         Full URL to link to
    * @param string  $strDescription Description text
    * @param string  $strMimetype    Mime type, e.g. application/ogg
    *
    * @return Services_MediaTomb_ExternalLink
    */
    public function createExternalLink(
        $nParentId, $strTitle, $strUrl, $strDescription, $strMimetype,
        $strProtocol = 'http-get', $strClass = 'object.item', $bReturn = true
    ) {
        $link = new Services_MediaTomb_ExternalLink();
        $link->objType     = 10;
        $link->title       = $strTitle;
        $link->url         = $strUrl;
        $link->protocol    = $strProtocol;
        $link->class       = 'object.item';
        $link->description = $strDescription;
        $link->mimetype    = $strMimetype;

        return $this->create($nParentId, $link, true);
    }//public function createExternalLink(..)



    /**
    * Deletes an item or container.
    *
    * @param Services_MediaTomb_ItemBase $item Item to delete
    *
    * @return boolean True if all went well
    */
    public function delete(Services_MediaTomb_ItemBase $item)
    {
        $this->sendRequest(array(
            'req_type'  => 'remove',
            'object_id' => $item->id
        ));
        $this->workaroundTimingBug();

        return true;
    }//public function delete(Services_MediaTomb_ItemBase $item)



    /**
    * Returns the container in the given path,
    * e.g. 'Audio/Albums/Maria Taylor'
    *
    * @return Services_MediaTomb_Container Null if not found
    */
    public function getContainerByPath($strPath)
    {
        $arParts   = explode('/', $strPath);
        $nParentId = 0;
        foreach ($arParts as $strName) {
            $container = $this->getSingleContainer($nParentId, $strName);
            if ($container === null) {
                return null;
            }
            $nParentId = $container->id;
        }

        return $container;
    }//public function getContainerByPath(..)



    /**
    * Returns an array of children containers for the given ID.
    * 0 is the root id.
    *
    * @param integer $nParentId Parental id
    *
    * @return Services_MediaTomb_Container[] Array of containers
    */
    public function getContainers($nParentId)
    {
        $xmlContainers = $this->sendRequest(array(
            'req_type' => 'containers',
            'parent_id' => $nParentId,
            'select_it' => 0
        ));
        $arContainers = array();
        foreach ($xmlContainers->containers->container as $xmlContainer) {
            $container = new Services_MediaTomb_Container($xmlContainer);
            $container->setTomb($this);
            $arContainers[$container->id] = $container;
        }

        return $arContainers;
    }//public function getContainers($nId)



    public function getDetailledItem($nId)
    {
        $xmlItem = $this->sendRequest(array(
            'req_type'  => 'edit_load',
            'object_id' => $nId
        ));

        $strClass = self::getItemClass((int)$xmlItem->item->objType);
        if ($strClass === null) {
            throw new Services_MediaTomb_Exception(
                'Unsupported object class ' . $xmlItem->item->objType,
                Services_MediaTomb_Exception::UNSUPPORTED_ITEM
            );
        }

        $obj = new $strClass($xmlItem->item);
        $obj->setTomb($this);
        return $obj;
    }//public function getDetailledItem($nId)



    /**
    * Returns the Services_MediaTomb_* class for the given
    * item type.
    *
    * @param integer $nType Type ID
    *
    * @return string class that can be instantiated, or NULL if not found
    */
    public static function getItemClass($nType)
    {
        static $arClasses = array(
            1 => 'Services_MediaTomb_Container',
            2 => 'Services_MediaTomb_Item',
            //6 => 'Services_MediaTomb_ActiveItem',
            10 => 'Services_MediaTomb_ExternalLink',
            //26 => 'Services_MediaTomb_InternalLink',
        );

        if (!isset($arClasses[$nType])) {
            return null;
        }

        return $arClasses[$nType];
    }//public static function getItemClass($nType)



    /**
    * Returns the item in the given path,
    * e.g. 'Audio/Albums/Maria Taylor/All Songs/A Good Start'
    *
    * @return Services_MediaTomb_ItemBase Null if not found
    */
    public function getItemByPath($strPath)
    {
        $nPos = strrpos($strPath, '/');
        if ($nPos === false) {
            return null;
        }

        $strContainerPath = substr($strPath, 0, $nPos);
        $strItemName      = substr($strPath, $nPos + 1);

        $container = $this->getContainerByPath($strContainerPath);
        if ($container === null) {
            return null;
        }

        return $this->getSingleItem($container->id, $strItemName);
    }//public function getContainerByPath(..)



    /**
    * Returns an array of children containers for the given ID
    *
    * @return Services_MediaTomb_Container[] Array of containers
    */
    public function getItems($nId, $nStart = 0, $nCount = 25, $bDetailled = true)
    {
        $xmlItems = $this->sendRequest(array(
            'req_type'  => 'items',
            'parent_id' => $nId,
            'start'     => $nStart,
            'count'     => $nCount
        ));
        $arItems = array();
        foreach ($xmlItems->items->item as $xmlItem) {
            $simpleItem = new Services_MediaTomb_SimpleItem($xmlItem);
            $simpleItem->setTomb($this);
            if ($bDetailled) {
                $arItems[$simpleItem->id] = $simpleItem->getDetailledItem();
            } else {
                $arItems[$simpleItem->id] = $simpleItem;
            }
        }

        return $arItems;
    }//public function getItems(..)



    /**
    * Creates an array of parameters to send to the mediatomb server
    * with all values of the item that can be saved.
    *
    * @param Services_MediaTomb_ItemBase $item  Item to save
    * @param boolean                     $bSave If we save (true) or create (false)
    *
    * @return array Array of key-value pairs (not urlencoded)
    */
    protected function getSaveProperties(Services_MediaTomb_ItemBase $item, $bSave = true)
    {
        $strPropsVar = $bSave ? 'arSaveProps' : 'arCreateProps';

        if (!isset($item->$strPropsVar)) {
            throw new Services_MediaTomb_Exception(
                'Item of class ' . get_class($item)
                . ' defines no properties to be saved.'
            );
        }

        if ($bSave && $item->id == 0) {
            throw new Services_MediaTomb_Exception('Object has no ID');
        }

        $arParams = array();
        foreach ($item->$strPropsVar as $strKey => $strParamKey) {
            if (!is_int($strKey)) {
                $strPropname = $strKey;
            } else {
                $strPropname = $strParamKey;
            }

            if ($item->$strPropname === null) {
                throw new Services_MediaTomb_Exception(
                    'Value ' . $strPropname . ' has not been set'
                );
            }
            $arParams[$strParamKey] = $item->$strPropname;
        }

        return $arParams;
    }//protected function getSaveProperties(..)



    /**
    * Returns a single container item that has the given title
    * and has the given parent id.
    *
    * @param integer $nId      Parent ID
    * @param string  $strTitle Title of container that shall be returned
    *
    * @return Services_MediaTomb_Container or null if not found
    */
    public function getSingleContainer($nId, $strTitle)
    {
        $xmlContainers = $this->sendRequest(array(
            'req_type' => 'containers',
            'parent_id' => $nId,
            'select_it' => 0
        ));
        foreach ($xmlContainers->containers->container as $xmlContainer) {
            if ((string)$xmlContainer == $strTitle) {
                $container = new Services_MediaTomb_Container($xmlContainer);
                $container->setTomb($this);
                return $container;
            }
        }

        return null;
    }//public function getSingleContainer(..)



    /**
    * Returns a single item item that has the given title
    * and the given parent id.
    *
    * @param integer $nId        Parent ID
    * @param string  $strTitle   Title of item that shall be returned
    * @param boolean $bDetailled If the detailled item shall be returned
    *
    * @return Services_MediaTomb_ItemBase or null if not found
    */
    public function getSingleItem($nId, $strTitle, $bDetailled = true)
    {
        $bHaveMore = true;
        $nStart    = 0;
        $nCount    = 25;

        while ($bHaveMore) {
            $xmlItems = $this->sendRequest(array(
                'req_type'  => 'items',
                'parent_id' => $nId,
                'start'     => $nStart,
                'count'     => $nCount
            ));
            $arItems = array();
            foreach ($xmlItems->items->item as $xmlItem) {
                if ((string)$xmlItem->title == $strTitle) {
                    $simpleItem = new Services_MediaTomb_SimpleItem($xmlItem);
                    $simpleItem->setTomb($this);
                    if ($bDetailled) {
                        return $simpleItem->getDetailledItem();
                    } else {
                        return $simpleItem;
                    }
                }
            }

            $nStart += $nCount;
            $nTotal = (int)$xmlItems->items['totalMatches'];
            $bHaveMore = $nTotal >= $nStart;
        }

        return null;
    }//public function getSingleItem(..)



    /**
    * Saves the given item. Does only work if the item exists already.
    *
    * @param Services_MediaTomb_ItemBase $item Item to save.
    *
    * @return void
    *
    * @throw Services_MediaTomb_Exception When something goes wrong
    * @see create()
    */
    public function saveItem(Services_MediaTomb_ItemBase $item)
    {
        $arParams = array_merge(
            array(
                'req_type'  => 'edit_save',
                'object_id' => $item->id
            ),
            $this->getSaveProperties($item, true)
        );

        $this->sendRequest($arParams);
    }//public function saveItem(..)

}//class Services_MediaTomb

?>