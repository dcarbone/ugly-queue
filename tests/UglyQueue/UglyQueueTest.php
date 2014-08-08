<?php

/**
 * Class UglyQueueTest
 */
class UglyQueueTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $tastySandwich = array(
        '0' => 'unsalted butter',
        '1' => 'all-purpose flour',
        '2' => 'hot milk',
        '3' => 'kosher salt',
        '4' => 'freshly ground black pepper',
        '5' => 'nutmeg',
        '6' => 'grated Gruyere',
        '7' => 'freshly grated Parmesan',
        '8' => 'white sandwich bread, crust removed',
        '9' => 'Dijon mustard',
        '10' => 'Virginia baked ham, sliced',
    );

    /**
     * @covers \DCarbone\UglyQueue::__construct
     * @uses \DCarbone\UglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanConstructUglyQueueWithValidParameter()
    {
        $conf = array(
            'queue-base-dir' => dirname(__DIR__).'/misc/',
        );

        $uglyQueue = new \DCarbone\UglyQueue($conf);

        $this->assertInstanceOf('\\DCarbone\\UglyQueue', $uglyQueue);

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::__construct
     * @uses \DCarbone\UglyQueue
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionThrownWhenConstructingUglyQueueWithEmptyOrInvalidConf()
    {
        $conf = array();
        $uglyQueue = new \DCarbone\UglyQueue($conf);
    }

    /**
     * @covers \DCarbone\UglyQueue::__construct
     * @uses \DCarbone\UglyQueue
     * @expectedException \RuntimeException
     */
    public function testExceptionThrownWhenConstructingUglyQueueWithInvalidQueueBaseDirPath()
    {
        $conf = array(
            'queue-base-dir' => 'sandwiches',
        );

        $uglyQueue = new \DCarbone\UglyQueue($conf);
    }

    /**
     * @covers \DCarbone\UglyQueue::initialize
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCanInitializeExistingQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        $uglyQueue->initialize('tasty-sandwich');

        $this->assertTrue(
            is_dir($uglyQueue->getQueueBaseDir().$uglyQueue->getQueueGroup().'/'),
            'Could not verify existence of queue group dir');

        $this->assertTrue(
            file_exists($uglyQueue->getQueueBaseDir().$uglyQueue->getQueueGroup().'/'.'index.html'),
            'Could not verify existence of index.html in queue group dir');

        $this->assertTrue(
            file_exists($uglyQueue->getQueueBaseDir().$uglyQueue->getQueueGroup().'/'.'queue.txt'),
            'Could not verify existence of queue.txt in queue group dir');
    }
}