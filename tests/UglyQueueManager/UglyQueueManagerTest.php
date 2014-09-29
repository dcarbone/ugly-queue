<?php

/**
 * Class UglyQueueManagerTest
 */
class UglyQueueManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers \DCarbone\UglyQueueManager::__construct
     * @covers \DCarbone\UglyQueueManager::init
     * @covers \DCarbone\UglyQueue::unserialize
     * @covers \DCarbone\UglyQueue::__get
     * @covers \DCarbone\UglyQueueManager::addQueue
     * @covers \DCarbone\UglyQueueManager::containsQueueWithName
     * @uses \DCarbone\UglyQueueManager
     * @uses \DCarbone\UglyQueue
     * @return \DCarbone\UglyQueueManager
     */
    public function testCanInitializeManagerWithConfigAndNoObservers()
    {
        $config = array(
            'queue-base-dir' => __DIR__.'/../misc/'
        );

        $manager = \DCarbone\UglyQueueManager::init($config);

        $this->assertInstanceOf('\\DCarbone\\UglyQueueManager', $manager);

        return $manager;
    }

    /**
     * @covers \DCarbone\UglyQueueManager::init
     * @covers \DCarbone\UglyQueueManager::__construct
     * @uses \DCarbone\UglyQueueManager
     * @expectedException \RuntimeException
     */
    public function testExceptionThrownDuringConstructionWithInvalidBasePathValue()
    {
        $config = array(
            'queue-base-dir' => 'i do not exist!'
        );

        $manager = \DCarbone\UglyQueueManager::init($config);
    }

    /**
     * @covers \DCarbone\UglyQueueManager::init
     * @covers \DCarbone\UglyQueueManager::__construct
     * @uses \DCarbone\UglyQueueManager
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionThrownDuringConstructionWithInvalidConfArray()
    {
        $config = array(
            'wrong-key' => 'wrong value'
        );

        $manager = \DCarbone\UglyQueueManager::init($config);
    }

    /**
     * @covers \DCarbone\UglyQueueManager::containsQueueWithName
     * @uses \DCarbone\UglyQueueManager
     * @depends testCanInitializeManagerWithConfigAndNoObservers
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testCanDetermineIfValidQueueExistsInManager(\DCarbone\UglyQueueManager $manager)
    {
        $shouldBeTrue = $manager->containsQueueWithName('tasty-sandwich');

        $this->assertTrue($shouldBeTrue);
    }

    /**
     * @covers \DCarbone\UglyQueueManager::containsQueueWithName
     * @uses \DCarbone\UglyQueueManager
     * @depends testCanInitializeManagerWithConfigAndNoObservers
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testCanDetermineQueueDoesNotExistInManager(\DCarbone\UglyQueueManager $manager)
    {
        $shouldBeFalse = $manager->containsQueueWithName('i should not exist');

        $this->assertFalse($shouldBeFalse);
    }

//    /**
//     * @covers \DCarbone\UglyQueue::queueExists
//     * @uses \DCarbone\UglyQueue
//     * @depends testCanInitializeNewUglyQueue
//     * @param \DCarbone\UglyQueue $uglyQueue
//     */
//    public function testCanDetermineExistenceOfExistingQueue(\DCarbone\UglyQueue $uglyQueue)
//    {
//        $exists = $uglyQueue->queueExists('tasty-sandwich');
//
//        $this->assertTrue($exists);
//    }
//
//    /**
//     * @covers \DCarbone\UglyQueue::queueExists
//     * @uses \DCarbone\UglyQueue
//     * @depends testCanInitializeNewUglyQueue
//     * @param \DCarbone\UglyQueue $uglyQueue
//     */
//    public function testCanDetermineExistenceOfNonExistingQueue(\DCarbone\UglyQueue $uglyQueue)
//    {
//        $exists = $uglyQueue->queueExists('nasty-sandwich');
//
//        $this->assertFalse($exists);
//    }
//
//    /**
//     * @covers \DCarbone\UglyQueue::getInitializedQueueList
//     * @uses \DCarbone\UglyQueue
//     * @depends testCanInitializeNewUglyQueue
//     * @param \DCarbone\UglyQueue $uglyQueue
//     */
//    public function testCanGetListOfInitializedQueues(\DCarbone\UglyQueue $uglyQueue)
//    {
//        $queueList = $uglyQueue->getInitializedQueueList();
//
//        $this->assertEquals(1, count($queueList));
//        $this->assertContains('tasty-sandwich', $queueList);
//    }
}
