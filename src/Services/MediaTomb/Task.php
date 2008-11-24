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
require_once 'Services/MediaTomb/ObjectBase.php';

/**
* Task of something mediatomb is currently doing.
*
* Tasks can be retrieved using Services_MediaTomb::getRunningTasks()
* and cancelled with cancel()
*
* @see Services_MediaTomb::getRunningTasks()
* @see cancel()
*
* @category Services
* @package  Services_MediaTomb
* @author   Christian Weiske <cweiske@php.net>
* @license  LGPL http://www.gnu.org/copyleft/lesser.html
* @link     http://pear.php.net/package/Services_MediaTomb
*/
class Services_MediaTomb_Task extends Services_MediaTomb_ObjectBase
{
    /**
    * Task ID
    *
    * @var integer
    */
    public $id = null;

    /**
    * Task title
    *
    * @var string
    */
    public $title = null;

    /**
    * If the task can be cancelled
    *
    * @var boolean
    */
    public $cancellable = false;



    /**
    * Creates a new task object
    *
    * @param stdClass $item Task json item
    */
    public function __construct(stdClass $item = null)
    {
        if ($item !== null) {
            $this->id          = (int)$item->id;
            $this->title       = (string)$item->text;
            $this->cancellable = ((string)$item->cancellable) == 1;
        }
    }//public function __construct(..)



    /**
    * Cancels the task.
    *
    * @return void
    *
    * @throws Services_MediaTomb_Exception When the task is not cancellable
    *
    * @see Services_MediaTomb::cancelTask()
    */
    public function cancel()
    {
        if (!$this->cancellable) {
            throw new Services_MediaTomb_Exception(
                'Task "' . $this->title . '" cannot be cancelled.',
                Services_MediaTomb_Exception::TASK_UNCANCELLABLE
            );
        }

        $this->tomb->cancelTask($this->id);
    }//public function cancel()

}//class Services_MediaTomb_Task extends Services_MediaTomb_ObjectBase

?>