<?php
/**
* This example tells you how to see what mediatomb is doing by retrieving
* its running tasks
*/
require_once dirname(__FILE__) . '/config.php';
require_once 'Services/MediaTomb.php';

$mt = new Services_MediaTomb($user, $pass, $host, $port);

$arTasks = $mt->getRunningTasks();
if (count($arTasks) == 0) {
    echo "MediaTomb is idling\n";
    exit();
}

foreach ($arTasks as $task) {
    echo 'Task #' . $task->id . ': ' . $task->title . "\n";
    if ($task->cancellable) {
        echo " can be cancelled\n";
        //if you wanted to, you could call
        //$task->cancel();
    } else {
        echo " canNOT be cancelled\n";
    }

}
?>