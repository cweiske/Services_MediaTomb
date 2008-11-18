<?php
// Call Services_MediaTombTest::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Services_MediaTombTest::main');
}

require_once 'PHPUnit/Framework.php';

/**
 * Test class for Services_MediaTomb.
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class Services_MediaTombTest extends PHPUnit_Framework_TestCase
{
    protected $configExists = null;

    /**
     * @var    Services_MediaTomb
     * @access protected
     */
    protected $object;


    public function __construct()
    {
        $configFile = dirname(__FILE__) . '/../config.php';
        $this->configExists = file_exists($configFile);
        if ($this->configExists) {
            include_once $configFile;
        }

        $strSrcDir = dirname(__FILE__) . '/../../src';
        if (file_exists($strSrcDir)) {
            chdir($strSrcDir);
        }

        require_once 'Services/MediaTomb.php';

        $strMockPath = dirname(__FILE__) . '/MediaTombTest/MediaTombMock.php';
        if (file_exists($strMockPath)) {
            require_once $strMockPath;
        } else {
            require_once 'Services/MediaTombTest/MediaTombMock.php';
        }
    }

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('Services_MediaTombTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        if (!$this->configExists) {
            $this->markTestSkipped('Unit test configuration is missing.');
        }
        $this->object = new Services_MediaTomb(
            $GLOBALS['Services_MediaTomb_UnittestConfig']['username'],
            $GLOBALS['Services_MediaTomb_UnittestConfig']['password'],
            $GLOBALS['Services_MediaTomb_UnittestConfig']['host'],
            $GLOBALS['Services_MediaTomb_UnittestConfig']['port']
        );
        if (isset($GLOBALS['Services_MediaTomb_UnittestConfig']['bWorkaroundTimingBug'])) {
            $this->object->bWorkaroundTimingBug
                = $GLOBALS['Services_MediaTomb_UnittestConfig']['bWorkaroundTimingBug'];
        }
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        //remove unittest container
        $cont = $this->object->getContainerByPath('unittest');
        if ($cont instanceof Services_MediaTomb_Container) {
            $cont->delete();
        }
    }

    public function testAddFile()
    {
        //TODO: try with real file and check if it works
        $this->assertTrue(
            $this->object->add(__FILE__)
        );
    }

    public function testAddDirectory()
    {
        $this->assertTrue(
            $this->object->add(dirname(__FILE__))
        );

        //doesn't return false as of mediatomb 0.11
        /*
        $this->assertFalse(
            $this->object->add('/this/path/should/really/not/exist/on/your/box')
        );
        */
    }

    /**
    * Test item creation
    */
    public function testCreateItem()
    {
$this->markTestSkipped('mediatomb crashes with this test');
        $containerPath = 'unittest/createItem/';
        $itemTitle = 'test item';

        $container = $this->object->createContainerByPath($containerPath);
        $this->assertType('Services_MediaTomb_Container', $container);

        $item = new Services_MediaTomb_Item();
        $item->title       = $itemTitle;
        $item->location    = '/etc/passwd';//should exist on a unix server
        $item->description = 'Just a test';
        $item->mimetype    = 'text/plain';

        $item = $this->object->create($container, $item);
        $this->assertType(
            'Services_MediaTomb_Item',
            $item
        );

        //see if we really added it and it can be accessed
        $item2 = $this->object->getItemByPath($containerPath . $itemTitle);
        $this->assertType('Services_MediaTomb_Item', $item2);
        $this->assertEquals($item->id, $item2->id);
    }

    /**
    * Test item creation
    */
    public function testCreateItemInvalidPath()
    {
$this->markTestSkipped('mediatomb crashes with this');
        $containerPath = 'unittest/testCreateItemInvalidPath/';
        $itemTitle = 'test item';

        $container = $this->object->createContainerByPath($containerPath);
        $this->assertType('Services_MediaTomb_Container', $container);

        $item = new Services_MediaTomb_Item();
        $item->title       = $itemTitle;
        //should not exist anywhere
        $item->location    = '/this/stRaNgE/PatH/ShoulD/nOt/eXXisT/on/YOur/enihcam';
        $item->description = 'Just a test to fail';
        $item->mimetype    = 'text/plain';

        $bException = false;
        try {
            $item = $this->object->create($container, $item);
        } catch (Services_MediaTomb_Exception $e) {
            $this->assertEquals(
                Services_MediaTomb_Exception::FILE_NOT_FOUND,
                $e->getCode()
            );
            $bException = true;
        }
        $this->assertTrue($bException);
    }

    /**
     * use create() to create a container
     */
    public function testCreateAContainer()
    {
        $utcon = $this->object->createContainer(0, 'unittest');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        $cont = new Services_MediaTomb_Container();
        $cont->title = 'testCreate-container';
        $cont2 = $this->object->create($utcon->id, $cont);
        $this->assertType('Services_MediaTomb_Container', $cont2);
        $this->assertEquals($cont->title, $cont2->title);
    }

    /**
     * use create() to create an external link
     */
    public function testCreateAnExternalLink()
    {
        $utcon = $this->object->createContainer(0, 'unittest');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        $link = new Services_MediaTomb_ExternalLink();
        $link->title = 'testCreate-externallink';
        try {
            $this->object->create($utcon->id, $link);
            $this->assertTrue(false, 'Expected exception not thrown');
        } catch (Services_MediaTomb_Exception $e) {
        }

        $link->description = 'Descriptiontext';
        $link->url         = 'http://pear.php.net';
        $link->mimetype    = 'text/html';
        $link->protocol    = Services_MediaTomb::PROTOCOL_HTTP_GET;

        $cont2 = $this->object->create($utcon->id, $link);
        $this->assertType('Services_MediaTomb_ExternalLink', $cont2);
        $this->assertEquals($link->title, $cont2->title);
    }

    /**
     *
     */
    public function testCreateContainer()
    {
        //create container with mediatomb object
        $utcon = $this->object->createContainer(0, 'unittest');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        //create subcontainer
        $utcon2 = $this->object->createContainer($utcon->id, 'firstsub');
        $this->assertType('Services_MediaTomb_Container', $utcon2);

        //create container from container object
        $utcon3 = $utcon2->createContainer('secondsub');
        $this->assertType('Services_MediaTomb_Container', $utcon3);

        $utcon4 = $this->object->getContainerByPath('unittest/firstsub/secondsub');
        $this->assertType('Services_MediaTomb_Container', $utcon4);
        $this->assertEquals($utcon3->id, $utcon4->id);
    }

    /**
     *
     */
    public function testCreateContainerByPath()
    {
        $utcon = $this->object->createContainerByPath('unittest/odins');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        $utcon = $this->object->createContainerByPath('unittest/odins/dwa/tri');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        $utcon2 = $this->object->getContainerByPath('unittest/odins/dwa/tri');
        $this->assertType('Services_MediaTomb_Container', $utcon2);
        $this->assertEquals($utcon->id, $utcon2->id);

        //test with slash at beginning
        $utcon3 = $this->object->createContainerByPath('/unittest/odins/dwa/tchetirje');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        $utcon4 = $this->object->getContainerByPath('/unittest/odins/dwa/tchetirje');
        $this->assertType('Services_MediaTomb_Container', $utcon4);
        $this->assertEquals($utcon3->id, $utcon4->id);
    }

    public function testDecodePath()
    {
        $this->assertEquals(
            '/home/cweiske/Music/CDs/2raumwohnung/36 Grad',
            Services_MediaTombTest_MediaTombMock::decodePath(
                '2f686f6d652f63776569736b652f4d7'
                . '57369632f4344732f327261756d776f'
                . '686e756e672f33362047726164'
            )
        );
    }

    public function testEncodePath()
    {
        $this->assertEquals(
              '2f686f6d652f63776569736b652f4d7'
            . '57369632f4344732f327261756d776f'
            . '686e756e672f33362047726164',
            Services_MediaTombTest_MediaTombMock::encodePath(
                '/home/cweiske/Music/CDs/2raumwohnung/36 Grad'
            )
        );
    }

    public function testEnDecodePath()
    {
        $str = '/path/to/my/files';
        $this->assertEquals(
            $str,
            Services_MediaTombTest_MediaTombMock::decodePath(
                Services_MediaTombTest_MediaTombMock::encodePath(
                    $str
                )
            )
        );
    }

    /**
     *
     */
    public function testCreateExternalLink()
    {
        $utcon = $this->object->createContainer(0, 'unittest');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        $link = $this->object->createExternalLink(
            $utcon->id,
            'testCreateExternalLink', 'http://example.org/testCreateExternalLink',
            'descript', 'text/html'
        );
        $this->assertType('Services_MediaTomb_ExternalLink', $link);
        $this->assertEquals('testCreateExternalLink', $link->title);
    }

    /**
     *
     */
    public function testGetContainerByPath()
    {
        //any server should have an audio dir, except perhaps a video-only server
        $this->assertType(
            'Services_MediaTomb_Container',
            $this->object->getContainerByPath('Audio'),
            'Maybe you have no "Audio" container'
        );

        //test own
        $this->assertType(
            'Services_MediaTomb_Container',
            $this->object->createContainerByPath('unittest/one/two/three')
        );
        $this->assertType(
            'Services_MediaTomb_Container',
            $this->object->getContainerByPath('unittest/one/two/three')
        );

        //test with slash at beginning
        $this->assertType(
            'Services_MediaTomb_Container',
            $this->object->createContainerByPath('/unittest/one/two/four')
        );
        $this->assertType(
            'Services_MediaTomb_Container',
            $this->object->getContainerByPath('/unittest/one/two/four')
        );

        //test with slash at end
        $this->assertType(
            'Services_MediaTomb_Container',
            $this->object->getContainerByPath('/unittest/one/two/four/')
        );

        //test root
        $rootcontainer = $this->object->getContainerByPath('');
        $this->assertType(
            'Services_MediaTomb_Container',
            $rootcontainer
        );
        $this->assertEquals(0, $rootcontainer->id);

        $rootcontainer = $this->object->getContainerByPath('/');
        $this->assertType(
            'Services_MediaTomb_Container',
            $rootcontainer
        );
        $this->assertEquals(0, $rootcontainer->id);
    }

    /**
     *
     */
    public function testGetContainersByPath()
    {
        $this->assertType(
            'Services_MediaTomb_Container',
            $this->object->createContainerByPath('unittest/one/two/three')
        );

        $arContainers = $this->object->getContainersByPath('unittest/one/two/three');
        $this->assertType('array', $arContainers);
        $this->assertEquals(0, array_shift($arContainers)->id);
        $this->assertEquals(
            $this->object->getContainerByPath('unittest')->id,
            array_shift($arContainers)->id
        );
        $this->assertEquals(
            $this->object->getContainerByPath('unittest/one')->id,
            array_shift($arContainers)->id
        );
        $this->assertEquals(
            $this->object->getContainerByPath('unittest/one/two')->id,
            array_shift($arContainers)->id
        );
        $this->assertEquals(
            $this->object->getContainerByPath('unittest/one/two/three')->id,
            array_shift($arContainers)->id
        );
    }

    /**
     *
     */
    public function testGetContainers()
    {
        $arContainers = $this->object->getContainers(0);
        $this->assertNotEquals(0, count($arContainers));

        $bFoundAudio = false;
        foreach ($arContainers as $container) {
            $this->assertType(
                'Services_MediaTomb_Container',
                $container
            );
            if ($container->title == 'Audio') {
                $bFoundAudio = true;
            }
        }
        $this->assertTrue($bFoundAudio, '"Audio" container not found');
    }

    /**
     * @todo Implement testGetDetailedItem().
     */
    public function testGetDetailedItem() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     *
     */
    public function testGetItemClass()
    {
        $this->assertEquals(
            'Services_MediaTomb_Container',
            $this->object->getItemClass('container')
        );
    }

    /**
     *
     */
    public function testGetItemByPath()
    {
        //prepare
        $utcon = $this->object->createContainer(0, 'unittest');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        $link = $this->object->createExternalLink(
            $utcon->id,
            'testGetItemByPath', 'http://example.org/testGetItemByPath',
            'descript', 'text/html'
        );
        $this->assertType('Services_MediaTomb_ExternalLink', $link);

        //test
        $link2 = $this->object->getItemByPath('unittest/testGetItemByPath');
        $this->assertType('Services_MediaTomb_ExternalLink', $link2);
        $this->assertEquals($link->title, $link2->title);
        $this->assertEquals($link->url, $link2->url);
    }

    /**
     *
     */
    public function testGetItems()
    {
        $utcon = $this->object->createContainerByPath('unittest/testGetItems');
        $this->object->createExternalLink(
            $utcon->id, 'testGetItems1', 'http://example.org/testGetItems1',
            'desc', 'text/plain'
        );
        $this->object->createExternalLink(
            $utcon->id, 'testGetItems2', 'http://example.org/testGetItems1',
            'desc', 'text/plain'
        );
        $this->object->createExternalLink(
            $utcon, 'testGetItems3', 'http://example.org/testGetItems1',
            'desc', 'text/plain'
        );

        //id
        $arItems = $this->object->getItems($utcon->id, 0, 10);
        $this->assertEquals(3, count($arItems));
        $arFound = array();
        foreach ($arItems as $item) {
            $this->assertType('Services_MediaTomb_ExternalLink', $item);
            $arFound[$item->title] = true;
        }

        $this->assertTrue(isset($arFound['testGetItems1']));
        $this->assertTrue(isset($arFound['testGetItems2']));
        $this->assertTrue(isset($arFound['testGetItems3']));

        //object
        $arItems = $this->object->getItems($utcon, 0, 10);
        $this->assertEquals(3, count($arItems));
        $arFound = array();
        foreach ($arItems as $item) {
            $this->assertType('Services_MediaTomb_ExternalLink', $item);
            $arFound[$item->title] = true;
        }

        $this->assertTrue(isset($arFound['testGetItems1']));
        $this->assertTrue(isset($arFound['testGetItems2']));
        $this->assertTrue(isset($arFound['testGetItems3']));
    }



    public function testGetRootContainer()
    {
        $cont = $this->object->getRootContainer();
        $this->assertNotNull($cont);
        $this->assertEquals(0, $cont->id);

        $containers = $cont->getContainers();
        $this->assertNotNull($containers);
        $this->assertType('array', $containers);
        $this->assertTrue(count($containers) > 0);
    }



    /**
    * Test getRunningTasks() without any running tasks
    */
    public function testGetRunningTasksNone()
    {
        $tasks = $this->object->getRunningTasks();
        $this->assertType('array', $tasks);
        $this->assertEquals(0, count($tasks));
    }



    /**
    * Test getRunningTasks() when one is running
    */
    public function testGetRunningTasksOne()
    {
        $path = realpath(dirname(__FILE__) . '/../../');

        //clean up
        $item = $this->object->getContainerByPath('PC Directory/' . $path);
        if ($item) {
            $this->object->deleteItem($item);
        }

        //add so we get a task
        $this->object->add($path);

        $tasks = $this->object->getRunningTasks();
        $this->assertType('array', $tasks);
        $this->assertEquals(1, count($tasks));

        //cancel it
        $this->object->cancelTask(reset($tasks));
        usleep(500);

        $tasks = $this->object->getRunningTasks();
        $this->assertType('array', $tasks);
        $this->assertEquals(0, count($tasks));
    }



    /**
     *
     */
    public function testGetSingleContainer()
    {
        $utcon = $this->object->createContainerByPath('unittest/testGetSingleContainer');

        $utcon2 = $this->object->getSingleContainer(
            0, 'unittest'
        );
        $this->assertType('Services_MediaTomb_Container', $utcon2);

        //id
        $utcon3 = $this->object->getSingleContainer(
            $utcon2->id, 'testGetSingleContainer'
        );
        $this->assertType('Services_MediaTomb_Container', $utcon3);
        $this->assertEquals($utcon->id, $utcon3->id);

        //object
        $utcon4 = $this->object->getSingleContainer(
            $utcon2, 'testGetSingleContainer'
        );
        $this->assertType('Services_MediaTomb_Container', $utcon4);
        $this->assertEquals($utcon->id, $utcon4->id);

        //non-existing item
        $this->assertNull(
            $this->object->getSingleContainer(
                $utcon2->id, 'testGetSingleContainerShouldNotExist'
            )
        );

        //no partial matches
        $this->assertNull(
            $this->object->getSingleContainer(
                $utcon2->id, 'testGetSingleContai'
            )
        );
    }

    /**
     *
     */
    public function testGetSingleItem()
    {
        //prepare
        $utcon = $this->object->createContainer(0, 'unittest');
        $this->assertType('Services_MediaTomb_Container', $utcon);

        $link = $this->object->createExternalLink(
            $utcon->id,
            'testGetSingleItem', 'http://example.org/testGetSingleItem',
            'descript', 'text/html'
        );
        $this->assertType('Services_MediaTomb_ExternalLink', $link);

        //test
        $link2 = $this->object->getSingleItem($utcon->id, 'testGetSingleItem');
        $this->assertType('Services_MediaTomb_ExternalLink', $link2);
        $this->assertEquals($link->title, $link2->title);
        $this->assertEquals($link->url, $link2->url);

        //non-existing item
        $this->assertNull(
            $this->object->getSingleItem(
                $utcon->id, 'testGetSingleItemShouldNotExist'
            )
        );

        //no partial matches
        $this->assertNull(
            $this->object->getSingleItem(
                $utcon->id, 'testGetSingleI'
            )
        );
    }

    /**
     * use saveItem() to change a container
     */
    public function testSaveItemAContainer()
    {
        $utcon = $this->object->createContainerByPath('unittest/testSaveItem');
        $utcon->title = 'testSaveItem2';
        $utcon->save();

        $this->assertType(
            'Services_MediaTomb_Container',
            $this->object->getContainerByPath('unittest/testSaveItem2')
        );
        $this->assertNull(
            $this->object->getContainerByPath('unittest/testSaveItem')
        );
    }

    /**
    * Test item renaming
    */
    public function testSaveItemRenaming()
    {
$this->markTestSkipped('mediatomb crashes with this test');
        $containerPath = 'unittest/testSaveItemRenaming/';
        $itemTitle  = 'test item';
        $itemTitle2 = 'renamed item';

        $container = $this->object->createContainerByPath($containerPath);
        $this->assertType('Services_MediaTomb_Container', $container);

        $item = new Services_MediaTomb_Item();
        $item->title       = $itemTitle;
        $item->location    = '/etc/passwd';//should exist on a unix server
        $item->description = 'Just a test';
        $item->mimetype    = 'text/plain';

        $item = $this->object->create($container, $item);
        $this->assertType('Services_MediaTomb_Item', $item);

        $item->title = $itemTitle2;
        $this->assertTrue($item->save());

        //see if we really added it and it can be accessed
        $item2 = $this->object->getItemByPath($containerPath . $itemTitle2);
        $this->assertType('Services_MediaTomb_Item', $item2);
        $this->assertEquals($item->id, $item2->id);

        //old one should not exist anymore
        $this->assertNull(
            $this->object->getItemByPath($containerPath . $itemTitle)
        );
    }

}

// Call Services_MediaTombTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'Services_MediaTombTest::main') {
    Services_MediaTombTest::main();
}
?>
