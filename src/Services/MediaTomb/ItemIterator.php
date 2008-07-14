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
require_once 'Services/MediaTomb.php';

class Services_MediaTomb_ItemIterator implements Iterator
{
    /**
    * Mediatomb instance
    *
    * @var Services_MediaTomb
    */
    protected $tomb = null;

    /**
    * Container$container
    *
    * @var Services_MediaTomb_Container
    */
    protected $nContainerId = null;

    /**
    * Number of items to fetch for a page.
    * Set via constructor
    *
    * @var int
    */
    protected $nPageSize = 30;

    /**
    * Current item position number
    *
    * @var int
    */
    protected $nPos = null;

    /**
    * Current key
    *
    * @var integer
    */
    protected $nIteratorPos = null;

    /**
    * If real full items or only simple items shall be loaded.
    * SimpleItems are cheap, while full detailled items cost on extra request
    * per item.
    *
    * @var boolean
    */
    protected $bDetailled = true;

    /**
    * If we have more items, but not loaded yet
    *
    * @var boolean
    */
    protected $bHaveMore = false;

    /**
    * Array of items
    *
    * @var array
    */
    protected $arItems = array();



    public function __construct(
        Services_MediaTomb $tomb, $nContainerId, $bDetailled = true,
        $nPageSize = 30
    ) {
        $this->tomb         = $tomb;
        $this->nContainerId = $nContainerId;
        $this->bDetailled   = $bDetailled;
        if ($nPageSize !== null) {
            $this->nPageSize = $nPageSize;
        }
    }//public function __construct(..)



    /**
    * Returns the current item.
    *
    * @return Services_MediaTomb_ItemBase
    */
    public function current()
    {
        return $this->arItems[$this->nIteratorPos];
    }//public function current()



    /**
    * Advances the internal iterator position to the next position.
    *
    * @return void
    */
    public function next()
    {
        ++$this->nIteratorPos;
    }//public function next()



    /**
    * Returns the current iterator position.
    *
    * @return integer
    */
    public function key()
    {
        return $this->nIteratorPos;
    }//public function key()



    /**
    * Returns if the current iterator position is valid.
    *
    * @return boolean True if it is valid and current() may be called.
    */
    public function valid()
    {
        $bValid = array_key_exists($this->nIteratorPos, $this->arItems);
        if (!$bValid) {
            $this->nPos += $this->nPageSize;
            $this->loadItems();
            $bValid = array_key_exists($this->nIteratorPos, $this->arItems);
        }
        return $bValid;
    }//public function valid()



    /**
    * Resets the internal iterator position to the first item.
    *
    * @return void
    */
    public function rewind()
    {
        $bLoadItems = $this->nPos === null || $this->nIteratorPos >= $this->nPageSize;

        $this->nIteratorPos = 0;
        $this->nPos = 0;

        if ($bLoadItems) {
            $this->loadItems();
        }
    }//public function rewind()



    /**
    * Loads the mediatomb items in $arItems based on the class variables
    * $nPos, $nPageSize and $bDetailled.
    *
    * @return void
    */
    protected function loadItems()
    {
        $arItems = $this->tomb->getItems(
            $this->nContainerId, $this->nPos, $this->nPageSize,
            $this->bDetailled
        );

        if (count($arItems) == 0) {
            $this->bHaveMore = false;
            $this->arItems = array();
            return;
        }

        //re-key items
        reset($arItems);
        if (key($arItems) != $this->nPos) {
            //re-key items
            $arItems = array_combine(
                range($this->nPos, $this->nPos + count($arItems) - 1, 1),
                $arItems
            );
        }

        $this->arItems = $arItems;

        if (count($this->arItems) < $this->nPageSize) {
            $this->bHaveMore = false;
        }
    }//protected function loadItems()

}//class Services_MediaTomb_ItemIterator implements Iterator

?>