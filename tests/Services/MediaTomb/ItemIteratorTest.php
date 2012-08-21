<?php
require_once __DIR__ . '/../MediaTombTestBase.php';

/**
 * Test class for Services_MediaTomb.
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class Services_MediaTomb_ItemIteratorTest extends Services_MediaTombTestBase
{
    protected function makeSome($nCount = 3)
    {
        $containerPath = 'unittest/iterator/';
        $container = $this->smt->createContainerByPath($containerPath);
        $this->assertInstanceOf('Services_MediaTomb_Container', $container);

        for ($n = 0; $n < $nCount; $n++) {
            $item = new Services_MediaTomb_Item();
            $item->title       = 'item #' . $n;
            $item->location    = __FILE__;
            $item->description = 'Just a test';
            $item->mimetype    = 'text/plain';
            
            $item = $this->smt->create($container, $item);
            $this->assertInstanceOf(
                'Services_MediaTomb_Item',
                $item
            );
        }

        return $container;
    }

    public function testIterator()
    {
        $container = $this->makeSome();
        $iterator  = $container->getItemIterator();
        $count = 0;
        foreach ($iterator as $item) {
            $this->assertInstanceOf('Services_MediaTomb_Item', $item);
            $this->assertEquals('item #' . $count, $item->title);
            ++$count;
        }
        $this->assertEquals(3, $count);
    }

    public function testIteratorPaging()
    {
        $container = $this->makeSome(5);
        $iterator  = $container->getItemIterator(false, 2);
        $count = 0;
        foreach ($iterator as $item) {
            $this->assertInstanceOf('Services_MediaTomb_SimpleItem', $item);
            $this->assertEquals('item #' . $count, $item->title);
            ++$count;
        }
        $this->assertEquals(5, $count);
    }

    public function testIteratorCount()
    {
        $container = $this->makeSome();
        $this->assertEquals(3, iterator_count($container->getItemIterator()));
    }

    public function testIteratorReuse()
    {
        $container = $this->makeSome();
        $iterator = $container->getItemIterator();

        //first use: count
        $this->assertEquals(3, iterator_count($iterator));

        //second use: iterate over items
        $count = 0;
        foreach ($iterator as $item) {
            $this->assertInstanceOf('Services_MediaTomb_Item', $item);
            ++$count;
        }
        $this->assertEquals(3, $count, 'should be 10 on second try');
    }

}

?>
