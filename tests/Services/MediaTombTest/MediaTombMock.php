<?php

/**
* Mock class that makes protected Services_MediaTomb methods public,
* so that we can test it.
*
* @author Christian Weiske <cweiske@cweiske.de>
*/
class Services_MediaTombTest_MediaTombMock extends Services_MediaTomb
{
    /**
    * @see Services_MediaTomb::encodePath();
    */
    public static function decodePath($encpath)
    {
        return parent::decodePath($encpath);
    }//public static function decodePath(..)



    /**
    * @see Services_MediaTomb::encodePath();
    */
    public static function encodePath($path)
    {
        return parent::encodePath($path);
    }//public static function encodePath(..)

}

?>