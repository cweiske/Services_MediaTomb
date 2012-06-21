<?php
/**
 * Test class for Services_MediaTomb.
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class Services_MediaTombTest extends PHPUnit_Framework_TestCase
{
    protected $configExists = null;

    /**
     * @var Services_MediaTomb
     */
    protected $smt;


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
        $this->smt = new Services_MediaTomb(
            $GLOBALS['Services_MediaTomb_UnittestConfig']['username'],
            $GLOBALS['Services_MediaTomb_UnittestConfig']['password'],
            $GLOBALS['Services_MediaTomb_UnittestConfig']['host'],
            $GLOBALS['Services_MediaTomb_UnittestConfig']['port']
        );
        if (isset($GLOBALS['Services_MediaTomb_UnittestConfig']['bWorkaroundTimingBug'])) {
            $this->smt->bWorkaroundTimingBug
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
        $cont = $this->smt->getContainerByPath('unittest');
        if ($cont instanceof Services_MediaTomb_Container) {
            $cont->delete();
        }
    }

    public function testAddFile()
    {
        //TODO: try with real file and check if it works
        $this->assertTrue(
            $this->smt->add(__FILE__)
        );
    }

    public function testAddDirectory()
    {
        $this->assertTrue(
            $this->smt->add(dirname(__FILE__))
        );

        //doesn't return false as of mediatomb 0.11
        /*
        $this->assertFalse(
            $this->smt->add('/this/path/should/really/not/exist/on/your/box')
        );
        */
    }

    /**
    * Test item creation
    */
    public function testCreateItem()
    {
        $containerPath = 'unittest/createItem/';
        $itemTitle = 'test item';

        $container = $this->smt->createContainerByPath($containerPath);
        $this->assertInstanceOf('Services_MediaTomb_Container', $container);

        $item = new Services_MediaTomb_Item();
        $item->title       = $itemTitle;
        $item->location    = '/etc/passwd';//should exist on a unix server
        $item->description = 'Just a test';
        $item->mimetype    = 'text/plain';

        $item = $this->smt->create($container, $item);
        $this->assertInstanceOf(
            'Services_MediaTomb_Item',
            $item
        );

        //see if we really added it and it can be accessed
        $item2 = $this->smt->getItemByPath($containerPath . $itemTitle);
        $this->assertInstanceOf('Services_MediaTomb_Item', $item2);
        $this->assertEquals($item->id, $item2->id);
    }

    /**
    * Test item creation
    */
    public function testCreateItemInvalidPath()
    {
        $containerPath = 'unittest/testCreateItemInvalidPath/';
        $itemTitle = 'test item';

        $container = $this->smt->createContainerByPath($containerPath);
        $this->assertInstanceOf('Services_MediaTomb_Container', $container);

        $item = new Services_MediaTomb_Item();
        $item->title       = $itemTitle;
        //should not exist anywhere
        $item->location    = '/this/stRaNgE/PatH/ShoulD/nOt/eXXisT/on/YOur/enihcam';
        $item->description = 'Just a test to fail';
        $item->mimetype    = 'text/plain';

        $bException = false;
        try {
            $item = $this->smt->create($container, $item);
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
        $utcon = $this->smt->createContainer(0, 'unittest');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        $cont = new Services_MediaTomb_Container();
        $cont->title = 'testCreate-container';
        $cont2 = $this->smt->create($utcon->id, $cont);
        $this->assertInstanceOf('Services_MediaTomb_Container', $cont2);
        $this->assertEquals($cont->title, $cont2->title);
    }

    /**
     * use create() to create an external link
     */
    public function testCreateAnExternalLink()
    {
        $utcon = $this->smt->createContainer(0, 'unittest');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        $link = new Services_MediaTomb_ExternalLink();
        $link->title = 'testCreate-externallink';
        try {
            $this->smt->create($utcon->id, $link);
            $this->assertTrue(false, 'Expected exception not thrown');
        } catch (Services_MediaTomb_Exception $e) {
        }

        $link->description = 'Descriptiontext';
        $link->url         = 'http://pear.php.net';
        $link->mimetype    = 'text/html';
        $link->protocol    = Services_MediaTomb::PROTOCOL_HTTP_GET;

        $cont2 = $this->smt->create($utcon->id, $link);
        $this->assertInstanceOf('Services_MediaTomb_ExternalLink', $cont2);
        $this->assertEquals($link->title, $cont2->title);
    }

    /**
     *
     */
    public function testCreateContainer()
    {
        //create container with mediatomb object
        $utcon = $this->smt->createContainer(0, 'unittest');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        //create subcontainer
        $utcon2 = $this->smt->createContainer($utcon->id, 'firstsub');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon2);

        //create container from container object
        $utcon3 = $utcon2->createContainer('secondsub');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon3);

        $utcon4 = $this->smt->getContainerByPath('unittest/firstsub/secondsub');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon4);
        $this->assertEquals($utcon3->id, $utcon4->id);
    }

    /**
     *
     */
    public function testCreateContainerByPath()
    {
        $utcon = $this->smt->createContainerByPath('unittest/odins');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        $utcon = $this->smt->createContainerByPath('unittest/odins/dwa/tri');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        $utcon2 = $this->smt->getContainerByPath('unittest/odins/dwa/tri');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon2);
        $this->assertEquals($utcon->id, $utcon2->id);

        //test with slash at beginning
        $utcon3 = $this->smt->createContainerByPath('/unittest/odins/dwa/tchetirje');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        $utcon4 = $this->smt->getContainerByPath('/unittest/odins/dwa/tchetirje');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon4);
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
        $utcon = $this->smt->createContainer(0, 'unittest');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        $link = $this->smt->createExternalLink(
            $utcon->id,
            'testCreateExternalLink', 'http://example.org/testCreateExternalLink',
            'descript', 'text/html'
        );
        $this->assertInstanceOf('Services_MediaTomb_ExternalLink', $link);
        $this->assertEquals('testCreateExternalLink', $link->title);
    }

    /**
     *
     */
    public function testGetContainerByPath()
    {
        //any server should have an audio dir, except perhaps a video-only server
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->getContainerByPath('Audio'),
            'Maybe you have no "Audio" container'
        );

        //test own
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->createContainerByPath('unittest/one/two/three')
        );
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->getContainerByPath('unittest/one/two/three')
        );

        //test with slash at beginning
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->createContainerByPath('/unittest/one/two/four')
        );
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->getContainerByPath('/unittest/one/two/four')
        );

        //test with slash at end
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->getContainerByPath('/unittest/one/two/four/')
        );

        //test root
        $rootcontainer = $this->smt->getContainerByPath('');
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $rootcontainer
        );
        $this->assertEquals(0, $rootcontainer->id);

        $rootcontainer = $this->smt->getContainerByPath('/');
        $this->assertInstanceOf(
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
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->createContainerByPath('unittest/one/two/three')
        );

        $arContainers = $this->smt->getContainersByPath('unittest/one/two/three');
        $this->assertInternalType('array', $arContainers);
        $this->assertEquals(0, array_shift($arContainers)->id);
        $this->assertEquals(
            $this->smt->getContainerByPath('unittest')->id,
            array_shift($arContainers)->id
        );
        $this->assertEquals(
            $this->smt->getContainerByPath('unittest/one')->id,
            array_shift($arContainers)->id
        );
        $this->assertEquals(
            $this->smt->getContainerByPath('unittest/one/two')->id,
            array_shift($arContainers)->id
        );
        $this->assertEquals(
            $this->smt->getContainerByPath('unittest/one/two/three')->id,
            array_shift($arContainers)->id
        );
    }

    public function testGetContainersByPathSlashAtBeginning()
    {
        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->createContainerByPath('unittest/one/two/three')
        );

        $arContainers = $this->smt->getContainersByPath('/unittest/one/two/three');
        $this->assertInternalType('array', $arContainers);
        $this->assertEquals(0, array_shift($arContainers)->id);
    }

    /**
     *
     */
    public function testGetContainers()
    {
        $arContainers = $this->smt->getContainers(0);
        $this->assertNotEquals(0, count($arContainers));

        $bFoundAudio = false;
        foreach ($arContainers as $container) {
            $this->assertInstanceOf(
                'Services_MediaTomb_Container',
                $container
            );
            if ($container->title == 'Audio') {
                $bFoundAudio = true;
            }
        }
        $this->assertTrue($bFoundAudio, '"Audio" container not found');
    }

    public function testGetDetailedItem()
    {
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
            $this->smt->getItemClass('container')
        );
    }

    /**
     *
     */
    public function testGetItemByPath()
    {
        //prepare
        $utcon = $this->smt->createContainer(0, 'unittest');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        $link = $this->smt->createExternalLink(
            $utcon->id,
            'testGetItemByPath', 'http://example.org/testGetItemByPath',
            'descript', 'text/html'
        );
        $this->assertInstanceOf('Services_MediaTomb_ExternalLink', $link);

        //test
        $link2 = $this->smt->getItemByPath('unittest/testGetItemByPath');
        $this->assertInstanceOf('Services_MediaTomb_ExternalLink', $link2);
        $this->assertEquals($link->title, $link2->title);
        $this->assertEquals($link->url, $link2->url);
    }

    /**
     *
     */
    public function testGetItems()
    {
        $utcon = $this->smt->createContainerByPath('unittest/testGetItems');
        $this->smt->createExternalLink(
            $utcon->id, 'testGetItems1', 'http://example.org/testGetItems1',
            'desc', 'text/plain'
        );
        $this->smt->createExternalLink(
            $utcon->id, 'testGetItems2', 'http://example.org/testGetItems1',
            'desc', 'text/plain'
        );
        $this->smt->createExternalLink(
            $utcon, 'testGetItems3', 'http://example.org/testGetItems1',
            'desc', 'text/plain'
        );

        //id
        $arItems = $this->smt->getItems($utcon->id, 0, 10);
        $this->assertEquals(3, count($arItems));
        $arFound = array();
        foreach ($arItems as $item) {
            $this->assertInstanceOf('Services_MediaTomb_ExternalLink', $item);
            $arFound[$item->title] = true;
        }

        $this->assertTrue(isset($arFound['testGetItems1']));
        $this->assertTrue(isset($arFound['testGetItems2']));
        $this->assertTrue(isset($arFound['testGetItems3']));

        //object
        $arItems = $this->smt->getItems($utcon, 0, 10);
        $this->assertEquals(3, count($arItems));
        $arFound = array();
        foreach ($arItems as $item) {
            $this->assertInstanceOf('Services_MediaTomb_ExternalLink', $item);
            $arFound[$item->title] = true;
        }

        $this->assertTrue(isset($arFound['testGetItems1']));
        $this->assertTrue(isset($arFound['testGetItems2']));
        $this->assertTrue(isset($arFound['testGetItems3']));
    }



    public function testGetRootContainer()
    {
        $cont = $this->smt->getRootContainer();
        $this->assertNotNull($cont);
        $this->assertEquals(0, $cont->id);

        $containers = $cont->getContainers();
        $this->assertNotNull($containers);
        $this->assertInternalType('array', $containers);
        $this->assertTrue(count($containers) > 0);
    }



    /**
    * Test getRunningTasks() without any running tasks
    */
    public function testGetRunningTasksNone()
    {
        $tasks = $this->smt->getRunningTasks();
        $this->assertInternalType('array', $tasks);
        $this->assertEquals(0, count($tasks));
    }



    /**
    * Test getRunningTasks() when one is running
    */
    public function testGetRunningTasksOne()
    {
        $path = realpath(dirname(__FILE__) . '/../../');

        //clean up
        $item = $this->smt->getContainerByPath('PC Directory/' . $path);
        if ($item) {
            $this->smt->deleteItem($item);
        }

        //add so we get a task
        $this->smt->add($path);

        $tasks = $this->smt->getRunningTasks();
        $this->assertInternalType('array', $tasks);
        $this->assertEquals(1, count($tasks));

        //cancel it
        $this->smt->cancelTask(reset($tasks));
        usleep(1000);

        $tasks = $this->smt->getRunningTasks();
        $this->assertInternalType('array', $tasks);
        $this->assertEquals(0, count($tasks));
    }



    /**
     *
     */
    public function testGetSingleContainer()
    {
        $utcon = $this->smt->createContainerByPath('unittest/testGetSingleContainer');

        $utcon2 = $this->smt->getSingleContainer(
            0, 'unittest'
        );
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon2);

        //id
        $utcon3 = $this->smt->getSingleContainer(
            $utcon2->id, 'testGetSingleContainer'
        );
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon3);
        $this->assertEquals($utcon->id, $utcon3->id);

        //object
        $utcon4 = $this->smt->getSingleContainer(
            $utcon2, 'testGetSingleContainer'
        );
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon4);
        $this->assertEquals($utcon->id, $utcon4->id);

        //non-existing item
        $this->assertNull(
            $this->smt->getSingleContainer(
                $utcon2->id, 'testGetSingleContainerShouldNotExist'
            )
        );

        //no partial matches
        $this->assertNull(
            $this->smt->getSingleContainer(
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
        $utcon = $this->smt->createContainer(0, 'unittest');
        $this->assertInstanceOf('Services_MediaTomb_Container', $utcon);

        $link = $this->smt->createExternalLink(
            $utcon->id,
            'testGetSingleItem', 'http://example.org/testGetSingleItem',
            'descript', 'text/html'
        );
        $this->assertInstanceOf('Services_MediaTomb_ExternalLink', $link);

        //test
        $link2 = $this->smt->getSingleItem($utcon->id, 'testGetSingleItem');
        $this->assertInstanceOf('Services_MediaTomb_ExternalLink', $link2);
        $this->assertEquals($link->title, $link2->title);
        $this->assertEquals($link->url, $link2->url);

        //non-existing item
        $this->assertNull(
            $this->smt->getSingleItem(
                $utcon->id, 'testGetSingleItemShouldNotExist'
            )
        );

        //no partial matches
        $this->assertNull(
            $this->smt->getSingleItem(
                $utcon->id, 'testGetSingleI'
            )
        );
    }

    /**
     * use saveItem() to change a container
     */
    public function testSaveItemAContainer()
    {
        $utcon = $this->smt->createContainerByPath('unittest/testSaveItem');
        $utcon->title = 'testSaveItem2';
        $utcon->save();

        $this->assertInstanceOf(
            'Services_MediaTomb_Container',
            $this->smt->getContainerByPath('unittest/testSaveItem2')
        );
        $this->assertNull(
            $this->smt->getContainerByPath('unittest/testSaveItem')
        );
    }

    /**
    * Test item renaming
    */
    public function testSaveItemRenaming()
    {
        $containerPath = 'unittest/testSaveItemRenaming/';
        $itemTitle  = 'test item';
        $itemTitle2 = 'renamed item';

        $container = $this->smt->createContainerByPath($containerPath);
        $this->assertInstanceOf('Services_MediaTomb_Container', $container);

        $item = new Services_MediaTomb_Item();
        $item->title       = $itemTitle;
        $item->location    = '/etc/passwd';//should exist on a unix server
        $item->description = 'Just a test';
        $item->mimetype    = 'text/plain';

        $item = $this->smt->create($container, $item);
        $this->assertInstanceOf('Services_MediaTomb_Item', $item);

        $item->title = $itemTitle2;
        $this->assertTrue($item->save());

        //see if we really added it and it can be accessed
        $item2 = $this->smt->getItemByPath($containerPath . $itemTitle2);
        $this->assertInstanceOf('Services_MediaTomb_Item', $item2);
        $this->assertEquals($item->id, $item2->id);

        //old one should not exist anymore
        $this->assertNull(
            $this->smt->getItemByPath($containerPath . $itemTitle)
        );
    }

}
?>
