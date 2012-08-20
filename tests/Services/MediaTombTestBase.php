<?php
/**
 * Base test class for Services_MediaTomb that is shared across all tests.
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class Services_MediaTombTestBase extends PHPUnit_Framework_TestCase
{
    protected $configExists = null;

    /**
     * @var Services_MediaTomb
     */
    protected $smt;


    public function __construct()
    {
        parent::__construct();
        $configFile = dirname(__FILE__) . '/../config.php';
        $this->configExists = file_exists($configFile);
        if ($this->configExists) {
            include_once $configFile;
        }

        require_once 'Services/MediaTomb.php';
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
        if ($this->smt) {
            $cont = $this->smt->getContainerByPath('unittest');
            if ($cont instanceof Services_MediaTomb_Container) {
                $cont->delete();
            }
        }
    }
}
?>
