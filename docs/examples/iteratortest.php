<?php
/**
* Here we iterate over all items in a container and display their title.
*/
require_once dirname(__FILE__) . '/config.php';
require_once 'Services/MediaTomb.php';

$mt = new Services_MediaTomb($user, $pass, $host, $port);

$cont = $mt->getContainerByPath('/Audio/Albums/10th Anniversary/');
if ($cont == null) {
    die("path not found\n");
}

//simple variant:
$it = $cont->getItemIterator();

//complicated one:
//false - load simple items only (http url and title)
//3 - iterator page size. ignore it, only relevant for special optimization
$it = $cont->getItemIterator(false, 3);

foreach ($it as $key => $item) {
    echo "item #" . $key . ":\n";
    echo '  class: ' . get_class($item) . "\n";
    echo '  title: ' . $item . "\n";
    if (isset($item->url)) {
        echo '  url:   ' . $item->url . "\n";
    } else {
        echo '  loc:   ' . $item->location . "\n";
    }
}
?>