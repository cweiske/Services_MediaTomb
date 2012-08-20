<?php
require_once __DIR__ . '/../MediaTombTestBase.php';

/**
 * Test class for Services_MediaTomb.
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class Services_MediaTomb_ItemIteratorTest extends Services_MediaTombTestBase
{
    protected function makeSome()
    {
        $containerPath = 'unittest/iterator/';
        $container = $this->smt->createContainerByPath($containerPath);
        $this->assertInstanceOf('Services_MediaTomb_Container', $container);

        for ($n = 0; $n < 10; $n++) {
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
        $iterator = $container->getItemIterator();
        $count = 0;
        foreach ($iterator as $item) {
            $this->assertInstanceOf('Services_MediaTomb_Item', $item);
            ++$count;
        }
        $this->assertEquals(10, $count);
    }

    public function testIteratorCount()
    {
        $container = $this->makeSome();
        $this->assertEquals(10, iterator_count($container->getItemIterator()));
    }

}

?>
