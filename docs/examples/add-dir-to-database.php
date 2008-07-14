<?php
/**
* Adds a directory to mediatomb's database.
* MediaTomb will automatically start scanning the directory for new
* files, and all the import scripts will be called.
*/
$pathToAdd = '/path/to/my/new/music/files';

require_once dirname(__FILE__) . '/config.php';
require_once 'Services/MediaTomb.php';

$mt = new Services_MediaTomb($user, $pass, $host, $port);
$mt->add($pathToAdd);
//yes, that's all
?>