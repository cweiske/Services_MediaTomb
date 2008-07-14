<?php
/**
* List the first three levels of containers on the mediatomb server
*/
require_once dirname(__FILE__) . '/config.php';
require_once 'Services/MediaTomb.php';

try {
    $mt = new Services_MediaTomb($user, $pass, $host, $port);
} catch (Services_MediaTomb_Exception $e) {
    echo 'Exception: ';
    echo $e->getMessage() . "\r\n";
    exit(1);
}


function listContents($parent, $nCurrentLevel, $nDown)
{
    echo str_repeat('  ', $nCurrentLevel) . $parent->title . "\r\n";
    if ($nDown <= 0) {
        return;
    }

    $arContainers = $parent->getContainers();
    foreach ($arContainers as $container) {
        listContents($container, $nCurrentLevel + 1, $nDown - 1);
    }
}

$root = $mt->getRootContainer();
listContents($root, 0, 2);
?>