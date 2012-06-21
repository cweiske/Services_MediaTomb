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
require_once 'Services/MediaTomb/Exception.php';

require_once 'Services/MediaTomb/Container.php';
require_once 'Services/MediaTomb/ExternalLink.php';
require_once 'Services/MediaTomb/Item.php';
require_once 'Services/MediaTomb/ItemIterator.php';
require_once 'Services/MediaTomb/SimpleItem.php';
require_once 'Services/MediaTomb/Task.php';

/**
* Library to access a MediaTomb server remotely.
* Provides methods to list, create, edit and delete items
* and containers on the server.
*
* Internally, MediaTomb's AJAX API is utilized.
* Since that may change without warning, the class here might
* need to be adjusted in the future.
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*
* @todo
* - track container path
*/
class Services_MediaTomb
{
    /**
    * HTTP GET protocol for external items
    *
    * @var string
    */
    const PROTOCOL_HTTP_GET = 'http-get';

    /**
    * Server IP
    *
    * @var string
    */
    protected $ip = null;

    /**
    * Server port
    *
    * @var integer
    */
    protected $port = 49152;

    /**
    * Full path to the AJAX interface on the server,
    * including protocol, hostname, port and path.
    *
    * E.g. http://myhost:49152/content/interface?
    *
    * @var string
    */
    protected $strInterfaceUrl = null;

    /**
    * Array of key-value pairs that define parameters
    * that are sent with each request.
    *
    * Session ID will be in here after login.
    *
    * @var array
    */
    protected $arDefaultParams = array();

    /**
    * Weather to work around the mediatomb timing bug
    * in 0.11.0 (#1962538).
    *
    * Since this happens on slow server machines only, you can
    * disable it when you don't delete many things in a row or have a
    * fast server.
    *
    * Affects deletion only.
    *
    * @var boolean
    *
    * @see workaroundTimingBug()
    */
    public $bWorkaroundTimingBug = true;



    /**
    * Create a new Services_MediaTomb instance and
    * logs into the server.
    *
    * @param string  $username Server username
    * @param string  $password Password
    * @param string  $ip       IP address or hostname of server
    * @param integer $port     Server port number. May be omitted
    *
    * @throws Services_MediaTomb_Exception When login() fails.
    */
    public function __construct($username, $password, $ip, $port = null)
    {
        $this->ip = $ip;
        if ($port !== null) {
            $this->port = $port;
        }
        $this->strInterfaceUrl =
            'http://' . $this->ip . ':' . $this->port
            . '/content/interface?';
        $this->login($username, $password);
    }//public function __construct(..)



    /**
    * Decodes a path transmitted via an URL into a readable
    * name.
    *
    * For example,
    *  2f706174682f66696c652e657874
    * would return
    *  /path/file.ext
    *
    * @param string $path Encoded file or directory path
    *
    * @return string Plain path string
    */
    protected static function decodePath($encpath)
    {
        $strDecoded = '';
        foreach (str_split($encpath, 2) as $num) {
            $strDecoded .= chr(hexdec($num));
        }
        return $strDecoded;
    }//protected static function decodePath(..)



    /**
    * Encodes a file or directory path into a string that can be used as
    * parameter for sendRequest().
    *
    * For example,
    *  /path/file.ext
    * would return
    *  2f706174682f66696c652e657874
    *
    * @param string $path File or directory path
    *
    * @return string Encoded path string
    */
    protected static function encodePath($path)
    {
        $strEncoded = '';
        foreach (str_split($path, 1) as $char) {
            $strEncoded .= dechex(ord($char));
        }
        return $strEncoded;
    }//protected static function encodePath(..)



    /**
    * Returns the ID for the given item.
    * If the passed $item parameter is already an integer ID, it will
    * be returned without modification.
    *
    * @param mixed $item Item object or integer ID
    *
    * @return integer ID
    *
    * @throws Services_MediaTomb_Exception When $item is no item nor an ID
    */
    protected function extractId($item)
    {
        if (is_int($item)) {
            return $item;
        } else if (is_object($item)
            && (   $item instanceof Services_MediaTomb_ItemBase
                || $item instanceof Services_MediaTomb_Task
            )
        ) {
            return $item->id;
        } else {
            throw new Services_MediaTomb_Exception(
                'Passed value ' . gettype($item) . ' is neither item nor ID.',
                Services_MediaTomb_Exception::NO_ID
            );
        }
    }//protected function extractId(..)



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
        $json = $this->sendRequest(array(
            'req_type' => 'auth',
            'action'   => 'get_sid',
            'sid'      => 'null',
        ));
        $sid = $json->sid;

        $json = $this->sendRequest(array(
            'req_type' => 'auth',
            'action'   => 'get_token',
            'sid'      => $sid,
        ));
        $token = (string)$json->token;

        $this->arDefaultParams['sid'] = $sid;

        try {
            $json = $this->sendRequest(array(
                'req_type' => 'auth',
                'action'   => 'login',
                'username' => $username,
                'password' => md5($token . $password),
            ));
        } catch (Services_MediaTomb_ServerException $e) {
            throw new Services_MediaTomb_Exception(
                $e->getMessage(), Services_MediaTomb_Exception::LOGIN
            );
        }
    }//protected function login(..)



    /**
    * Sends request to server.
    * Automatically checks for errors in the returned json values.
    *
    * @param array $arParams Array of parameters (key-value pairs) that shall
    *                        be send with the request
    *
    * @return stdClass json object of return value
    *
    * @throws Services_MediaTomb_Exception In case an error occurs
    */
    protected function sendRequest($arParams)
    {
        $arParams       = array_merge($this->arDefaultParams, $arParams);
        $arParamStrings = array();
        foreach ($arParams as $strKey => $strValue) {
            $arParamStrings[] = urlencode($strKey) . '=' . urlencode($strValue);
        }

        $strJson = file_get_contents(
            $this->strInterfaceUrl . implode('&', $arParamStrings)
        );
        if ($strJson === false) {
            throw new Services_MediaTomb_ServerException(
                'Connection to MediaTomb server failed.',
                Services_MediaTomb_ServerException::CANNOT_READ_DATA
            );
        }
        $json = json_decode($strJson);

        if ($json === false) {
            throw new Services_MediaTomb_ServerException(
                'MediaTomb response cannot be decoded.',
                Services_MediaTomb_ServerException::CANNOT_DECODE_DATA
            );
        }


        if (isset($json->error)) {
            $strMessage = (string)$json->error->text;
            $nCode      = Services_MediaTomb_ServerException::NORMAL_ERROR;

            //currently there are no mediatomb error status codes :/
            if ($strMessage == 'Error: file not found') {
                $nCode = Services_MediaTomb_Exception::FILE_NOT_FOUND;
            }

            throw new Services_MediaTomb_ServerException(
                $strMessage, $nCode
            );
        }

        return $json;
    }//protected function sendRequest(..)



    /**
    * Work around the timing bug in mediatomb 0.11.0.
    * After each deletion, we need to wait some time.
    *
    * @return void
    *
    * @see $bWorkaroundTimingBug
    * @see http://sourceforge.net/tracker/index.php?func=detail&aid=1962538&group_id=129766&atid=715780
    */
    protected function workaroundTimingBug()
    {
        if ($this->bWorkaroundTimingBug) {
            usleep(500000);
        }
    }//protected function workaroundBug()



    /**
    * Add a file or directory to the mediatomb database.
    *
    * @param string $path Full absolute path of file/directory to add
    *
    * @return boolean If all went well
    */
    public function add($path)
    {
        $arParams = array(
            'req_type'  => 'add',
            'object_id' => self::encodePath($path)
        );

        $this->sendRequest($arParams);

        //as of version 0.11, mediatomb returns the same status for
        //non-existing files and directories as for existing ones.
        return true;
    }//public function add(..)



    /**
    * Cancels the given task
    *
    * @param mixed $task Task object or integer task id
    *
    * @return boolean True if all went well
    */
    public function cancelTask($task)
    {
        $nTaskId = $this->extractId($task);

        $arParams = array(
            'req_type'  => 'tasks',
            'action'    => 'cancel',
            'task_id'    => $nTaskId
        );

        $this->sendRequest($arParams);

        return true;
    }//public function cancelTask($task)



    /**
    * Creates the item in mediatomb
    *
    * @param mixed                       $parent  Parent container item (or ID)
    *                                              to add element to
    * @param Services_MediaTomb_ItemBase $item    Item to create
    * @param boolean                     $bReturn If the saved item shall
    *                                              be fetched and returned
    *
    * @return Services_MediaTomb_ItemBase Newly created item, null if $bReturn
    *                                     false
    */
    public function create(
        $parent, Services_MediaTomb_ItemBase $item, $bReturn = true
    ) {
        $nParentId = $this->extractId($parent);

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
            $newitem = $this->getSingleContainer($nParentId, $strTitle);
        } else {
            $newitem = $this->getSingleItem($nParentId, $strTitle);
        }

        if ($newitem === null) {
            return false;
        }
        return $newitem;
    }//public function create(..)



    /**
    * Create a new container under container $nId
    *
    * @param mixed   $parent   Parent container (or ID) to create container in
    * @param string  $strTitle Title of new container
    * @param boolean $bReturn  If the created container shall be returned
    *
    * @return Services_MediaTomb_Container
    */
    public function createContainer($parent, $strTitle, $bReturn = true)
    {
        $container = new Services_MediaTomb_Container();

        $container->obj_type = 'container';
        $container->title    = $strTitle;
        $container->class    = 'object.container';

        return $this->create($parent, $container, $bReturn);
    }//public function createContainer(..)



    /**
    * Creates a container with the given path and returns it.
    *
    * @param string  $strPath  Path, e.g. 'Audio/Artists/Maria Taylor'
    * @param boolean $bParents If non-existent parent containers
    *                           shall be created
    * @param boolean $bReturn  If the newly created container should be returned
    *
    * @return Services_MediaTomb_Container Newly created container, or NULL
    *                                      if $bReturn is false
    *
    * @throws Services_MediaTomb_Exception When container creation fails
    */
    public function createContainerByPath(
        $strPath, $bParents = true, $bReturn = true
    ) {
        if ($strPath == '' || $strPath == '/') {
            return $bReturn ? $this->getRootContainer() : null;
        }

        if ($strPath{0} == '/') {
            $strPath = substr($strPath, 1);
        }
        if (substr($strPath, -1) == '/') {
            $strPath = substr($strPath, 0, -1);
        }

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
                    'Container creation error: ' . $strName . ' (' . $strPath . ').',
                    Services_MediaTomb_Exception::CONTAINER_CREATION_FAILED
                );
            }
            $nParentId = $container->id;
        }

        //TODO: use $bParents

        if (!$bReturn) {
            return null;
        }

        return $container;
    }//public function createContainerByPath(..)



    /**
    * Create a new external link under container $parent.
    *
    * @param mixed   $parent         Parent object (or ID)
    * @param string  $strTitle       Item title/name
    * @param string  $strUrl         Full URL to link to
    * @param string  $strDescription Description text
    * @param string  $strMimetype    Mime type, e.g. application/ogg
    * @param string  $strProtocol    Protocol of link (e.g. "http-get")
    * @param string  $strClass       UPnP Item class (defaults to "object.item")
    * @param boolean $bReturn        If the newly created object
    *                                 shall be returned
    *
    * @return Services_MediaTomb_ExternalLink
    */
    public function createExternalLink(
        $parent, $strTitle, $strUrl, $strDescription, $strMimetype,
        $strProtocol = 'http-get', $strClass = 'object.item', $bReturn = true
    ) {
        $link = new Services_MediaTomb_ExternalLink();

        $link->obj_type    = 'external_url';
        $link->title       = $strTitle;
        $link->url         = $strUrl;
        $link->protocol    = $strProtocol;
        $link->class       = 'object.item';
        $link->description = $strDescription;
        $link->mimetype    = $strMimetype;

        return $this->create($parent, $link, true);
    }//public function createExternalLink(..)



    /**
    * Deletes an item or container.
    *
    * @param mixed $item Item object or item ID to delete
    *
    * @return boolean True if all went well
    */
    public function deleteItem($item)
    {
        $this->sendRequest(array(
            'req_type'  => 'remove',
            'object_id' => $this->extractId($item)
        ));
        $this->workaroundTimingBug();

        return true;
    }//public function deleteItem(..)



    /**
    * Returns the container in the given path,
    * e.g. 'Audio/Albums/Maria Taylor'
    *
    * @param string $strPath Full path
    *
    * @return Services_MediaTomb_Container Null if not found
    */
    public function getContainerByPath($strPath)
    {
        if ($strPath == '' || $strPath == '/') {
            return $this->getRootContainer();
        }

        if ($strPath{0} == '/') {
            $strPath = substr($strPath, 1);
        }
        if (substr($strPath, -1) == '/') {
            $strPath = substr($strPath, 0, -1);
        }

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
    * Returns an array of containers for the given path,
    * e.g. 'Audio/Albums/Maria Taylor' would return an array
    * of containers containing
    * - the root container
    * - Audio
    * - Albums
    * - Maria Taylor
    *
    * First array value is the root container, last the most deeply nested one.
    *
    * @param string  $strPath         Full path
    * @param boolean $bIgnoreNotFound If the full path cannot be found,
    *                                 return found path parts.
    *
    * @return Services_MediaTomb_Container[] Array of containers,
    *                                        null if not found
    */
    public function getContainersByPath($strPath, $bIgnoreNotFound = false)
    {
        $arContainers = array(
            $this->getRootContainer()
        );

        if ($strPath == '' || $strPath == '/') {
            return $arContainers;
        }

        if ($strPath{0} == '/') {
            $strPath = substr($strPath, 1);
        }

        $arParts   = explode('/', $strPath);
        $nParentId = 0;
        foreach ($arParts as $strName) {
            $container = $this->getSingleContainer($nParentId, $strName);
            if ($container === null) {
                if ($bIgnoreNotFound) {
                    return $arContainers;
                } else {
                    return null;
                }
            }
            $arContainers[] = $container;
            $nParentId      = $container->id;
        }

        return $arContainers;
    }//public function getContainersByPath(..)



    /**
    * Returns an array of children containers for the given ID/item.
    * 0 is the root id.
    *
    * @param mixed $parent Parental object (or ID)
    *
    * @return Services_MediaTomb_Container[] Array of containers.
    *                                        Key is the container id
    */
    public function getContainers($parent)
    {
        $jsonContainers = $this->sendRequest(array(
            'req_type'  => 'containers',
            'parent_id' => $this->extractId($parent),
            'select_it' => 0
        ));

        $arContainers = array();
        foreach ($jsonContainers->containers->container as $jsonContainer) {
            $container = new Services_MediaTomb_Container($jsonContainer);
            $container->setTomb($this);
            $arContainers[$container->id] = $container;
        }

        return $arContainers;
    }//public function getContainers($nId)



    /**
    * Returns the "real" object for the given item id.
    *
    * @param mixed $item Item ID or item object
    *
    * @return Services_MediaTomb_ItemBase Item
    *
    * @throws Services_MediaTomb_Exception When $item is of an unknown class
    */
    public function getDetailedItem($item)
    {
        $jsonItem = $this->sendRequest(array(
            'req_type'  => 'edit_load',
            'object_id' => $this->extractId($item)
        ));

        $strClass = self::getItemClass((string)$jsonItem->item->obj_type);
        if ($strClass === null) {
            throw new Services_MediaTomb_Exception(
                'Unsupported object class ' . $jsonItem->item->obj_type . '.',
                Services_MediaTomb_Exception::UNSUPPORTED_ITEM
            );
        }

        $obj = new $strClass($jsonItem->item);
        $obj->setTomb($this);
        return $obj;
    }//public function getDetailedItem($nId)



    /**
    * Returns the item in the given path,
    * e.g. 'Audio/Albums/Maria Taylor/All Songs/A Good Start'
    *
    * @param string $strPath Full path to the item
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
    * Returns the Services_MediaTomb_* class for the given
    * item type.
    *
    * @param string $strType Type string
    *
    * @return string class that can be instantiated, or NULL if not found
    */
    public static function getItemClass($strType)
    {
        static $arClasses = array(
            'container'    => 'Services_MediaTomb_Container',
            'item'         => 'Services_MediaTomb_Item',
            //'active_item'  => 'Services_MediaTomb_ActiveItem',
            'external_url' => 'Services_MediaTomb_ExternalLink',
            //'internal_url' => 'Services_MediaTomb_InternalLink',
        );

        if (!isset($arClasses[$strType])) {
            return null;
        }

        return $arClasses[$strType];
    }//public static function getItemClass($nType)



    /**
    * Creates and returns an item iterator to easily loop over the items
    *
    * @param mixed   $container  Parental container
    * @param boolean $bDetailed  If the simple item only, or the "real" item
    *                             shall be returned
    *
    * @return Services_MediaTomb_ItemIterator
    */
    public function getItemIterator(
        $container, $bDetailed = true, $nPageSize = null
    ) {
        //FIXME: load for path and id, too
        return new Services_MediaTomb_ItemIterator(
            $this, $this->extractId($container), $bDetailed, $nPageSize
        );
    }//public function getItemIterator($container)



    /**
    * Returns an array of children containers for the given parent item.
    *
    * Returning full (real, detailed) items costs one request per item.
    *
    * @param mixed   $parent     Parent item (or item id) to get containers for
    * @param integer $nStart     Position of first item to retrieve
    * @param integer $nCount     Number of items to retrieve
    * @param boolean $bDetailed  If the simple item only, or the "real" item
    *                             shall be returned
    *
    * @return Services_MediaTomb_Item[] Array of items, Services_MediaTomb_Item (detailed)
    *                                   or Services_MediaTomb_SimpleItem (not detailed)
    */
    public function getItems($parent, $nStart = 0, $nCount = 25, $bDetailed = true)
    {
        $jsonItems = $this->sendRequest(array(
            'req_type'  => 'items',
            'parent_id' => $this->extractId($parent),
            'start'     => $nStart,
            'count'     => $nCount
        ));

        $arItems = array();
        foreach ($jsonItems->items->item as $jsonItem) {
            $simpleItem = new Services_MediaTomb_SimpleItem($jsonItem);
            $simpleItem->setTomb($this);
            if ($bDetailed) {
                $arItems[$simpleItem->id] = $simpleItem->getDetailedItem();
            } else {
                $arItems[$simpleItem->id] = $simpleItem;
            }
        }

        return $arItems;
    }//public function getItems(..)



    /**
    * Returns the root container which contains everything.
    *
    * @return Services_MediaTomb_Container Root container object
    */
    public function getRootContainer()
    {
        $container = new Services_MediaTomb_Container();
        $container->setTomb($this);

        $container->id    = 0;
        $container->title = '/';

        return $container;
    }//public function getRootContainer()



    /**
    * Returns an array of Services_MediaTomb_Task objects if
    * any tasks are running.
    *
    * @return Services_MediaTomb_Task[] Array of task objects
    */
    public function getRunningTasks()
    {
        $jsonTasks = $this->sendRequest(array(
            'req_type' => 'void',
            'updates'  => 'check'
        ));

        $arTasks = array();
        if (isset($jsonTasks->task)) {
            //FIXME: multiple tasks?
//            foreach ($jsonTasks->task as $jsonTask) {
                $task = new Services_MediaTomb_Task($jsonTasks->task);
                $task->setTomb($this);
                $arTasks[$task->id] = $task;
//            }
        }

        return $arTasks;
    }//public function getRunningTasks()



    /**
    * Creates an array of parameters to send to the mediatomb server
    * with all values of the item that can be saved.
    *
    * @param Services_MediaTomb_ItemBase $item  Item to save
    * @param boolean                     $bSave If we save (true) or create (false)
    *
    * @return array Array of key-value pairs (not urlencoded)
    *
    * @throws Services_MediaTomb_Exception When $item contains no properties
    *                                      that shall be saved, the ID or a
    *                                      value to save is missing
    */
    protected function getSaveProperties(
        Services_MediaTomb_ItemBase $item, $bSave = true
    ) {
        $strPropsVar = $bSave ? 'arSaveProps' : 'arCreateProps';

        if (!isset($item->$strPropsVar)) {
            throw new Services_MediaTomb_Exception(
                'Item of class ' . get_class($item)
                . ' defines no properties to be saved.',
                Services_MediaTomb_Exception::NO_SAVE_PROPS
            );
        }

        if ($bSave && $item->id == 0) {
            throw new Services_MediaTomb_Exception(
                'Object has no ID.',
                Services_MediaTomb_Exception::OBJECT_HAS_NO_ID
            );
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
                    'Value ' . $strPropname . ' has not been set.',
                    Services_MediaTomb_Exception::PROP_NOT_SET
                );
            }
            $arParams[$strParamKey] = $item->$strPropname;
        }

        return $arParams;
    }//protected function getSaveProperties(..)



    /**
    * Returns a single container item that has the given title
    * and has the given parent item/id.
    *
    * @param mixed  $parent   Parent object or ID
    * @param string $strTitle Title of container that shall be returned
    *
    * @return Services_MediaTomb_Container or null if not found
    */
    public function getSingleContainer($parent, $strTitle)
    {
        $jsonContainers = $this->sendRequest(array(
            'req_type'  => 'containers',
            'parent_id' => $this->extractId($parent),
            'select_it' => 0
        ));
        foreach ($jsonContainers->containers->container as $jsonContainer) {
            if ($jsonContainer->title == $strTitle) {
                $container = new Services_MediaTomb_Container($jsonContainer);
                $container->setTomb($this);
                return $container;
            }
        }

        return null;
    }//public function getSingleContainer(..)



    /**
    * Returns a single item item that has the given title
    * and the given parent item/id.
    *
    * @param mixed   $parent     Parent object or ID
    * @param string  $strTitle   Title of item that shall be returned
    * @param boolean $bDetailed  If the detailed item shall be returned
    *
    * @return Services_MediaTomb_ItemBase or null if not found
    */
    public function getSingleItem($parent, $strTitle, $bDetailed = true)
    {
        $bHaveMore = true;
        $nStart    = 0;
        $nCount    = 25;
        $nParentId = $this->extractId($parent);

        while ($bHaveMore) {
            $jsonItems = $this->sendRequest(array(
                'req_type'  => 'items',
                'parent_id' => $nParentId,
                'start'     => $nStart,
                'count'     => $nCount
            ));

            $arItems = array();
            foreach ($jsonItems->items->item as $jsonItem) {
                if ((string)$jsonItem->title == $strTitle) {
                    $simpleItem = new Services_MediaTomb_SimpleItem($jsonItem);
                    $simpleItem->setTomb($this);
                    if ($bDetailed) {
                        return $simpleItem->getDetailedItem();
                    } else {
                        return $simpleItem;
                    }
                }
            }

            $nStart   += $nCount;
            $nTotal    = (int)$jsonItems->items->total_matches;
            $bHaveMore = $nTotal >= $nStart;
        }

        return null;
    }//public function getSingleItem(..)



    /**
    * Saves the given item. Does only work if the item exists already.
    *
    * @param Services_MediaTomb_ItemBase $item Item to save.
    *
    * @return boolean True if all went well
    *
    * @throws Services_MediaTomb_Exception When something goes wrong
    * @see create()
    */
    public function saveItem(Services_MediaTomb_ItemBase $item)
    {
        if ($item->id === null) {
            throw new Services_MediaTomb_Exception(
                'Only existing items can be saved.',
                Services_MediaTomb_Exception::OBJECT_HAS_NO_ID
            );
        }

        $arParams = array_merge(
            array(
                'req_type'  => 'edit_save',
                'object_id' => $item->id
            ),
            $this->getSaveProperties($item, true)
        );

        $this->sendRequest($arParams);

        return true;
    }//public function saveItem(..)

}//class Services_MediaTomb

?>