<?php
// Call Services_MediaTombTest::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Services_MediaTombTest::main');
}

require_once 'PHPUnit/Framework.php';

$strSrcDir = dirname(__FILE__) . '/../../src';
if (file_exists($strSrcDir)) {
    chdir($strSrcDir);
}

require_once 'Services/MediaTomb.php';

/**
 * Test class for Services_MediaTomb.
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
            $this->object->delete($cont);
        }
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
            $this->object->getContainerByPath('Audio')
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
        $this->assertTrue($bFoundAudio);
    }

    /**
     * @todo Implement testGetDetailledItem().
     */
    public function testGetDetailledItem() {
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
            $this->object->getItemClass(1)
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
     * @todo Implement testGetItems().
     */
    public function testGetItems() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
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

        $utcon3 = $this->object->getSingleContainer(
            $utcon2->id, 'testGetSingleContainer'
        );
        $this->assertType('Services_MediaTomb_Container', $utcon3);
        $this->assertEquals($utcon->id, $utcon3->id);

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
     * @todo Implement testSaveItem().
     */
    public function testSaveItem() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}

// Call Services_MediaTombTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'Services_MediaTombTest::main') {
    Services_MediaTombTest::main();
}
?>
