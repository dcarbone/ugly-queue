<?php

/**
 * Class UglyQueueManagerTest
 */
class UglyQueueManagerTest extends PHPUnit_Framework_TestCase
{
    protected $baseDir;

    protected $reallyTastySandwich = array(
        '0' => 'beef broth',
        '1' => 'barbeque sauce',
        '2' => 'boneless pork ribs',
    );

    protected function setUp()
    {
        $this->baseDir = realpath(__DIR__.'/../misc/queues');
    }

    /**
     * @covers \DCarbone\UglyQueueManager::__construct
     * @covers \DCarbone\UglyQueue::unserialize
     * @covers \DCarbone\UglyQueueManager::addQueue
     * @covers \DCarbone\UglyQueueManager::containsQueueWithName
     * @uses \DCarbone\UglyQueueManager
     * @uses \DCarbone\UglyQueue
     * @return \DCarbone\UglyQueueManager
     */
    public function testCanInitializeObjectWithValidPath()
    {
        $manager = new \DCarbone\UglyQueueManager($this->baseDir);

        $this->assertInstanceOf('\\DCarbone\\UglyQueueManager', $manager);

        return $manager;
    }

    /**
     * @covers \DCarbone\UglyQueueManager::__construct
     * @uses \DCarbone\UglyQueueManager
     * @expectedException \RuntimeException
     */
    public function testExceptionThrownDuringConstructionWithInvalidBasePathValue()
    {
        new \DCarbone\UglyQueueManager('i do not exist!');
    }


    /**
     * @covers \DCarbone\UglyQueueManager::containsQueueWithName
     * @uses \DCarbone\UglyQueueManager
     * @depends testCanInitializeObjectWithValidPath
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
     * @depends testCanInitializeObjectWithValidPath
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testCanDetermineQueueDoesNotExistInManager(\DCarbone\UglyQueueManager $manager)
    {
        $shouldBeFalse = $manager->containsQueueWithName('i should not exist');

        $this->assertFalse($shouldBeFalse);
    }

    /**
     * @covers \DCarbone\UglyQueueManager::getQueueWithName
     * @uses \DCarbone\UglyQueueManager
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidPath
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testCanGetUglyQueueObjectFromManager(\DCarbone\UglyQueueManager $manager)
    {
        $uglyQueue = $manager->getQueueWithName('tasty-sandwich');

        $this->assertInstanceOf('\\DCarbone\\UglyQueue', $uglyQueue);
        $this->assertEquals('tasty-sandwich', $uglyQueue->getName());
    }

    /**
     * @covers \DCarbone\UglyQueueManager::getQueueWithName
     * @uses \DCarbone\UglyQueueManager
     * @expectedException \InvalidArgumentException
     * @depends testCanInitializeObjectWithValidPath
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testExceptionThrownWhenTryingToGetNonExistentQueueFromManager(\DCarbone\UglyQueueManager $manager)
    {
        $manager->getQueueWithName('sandwiches');
    }

    /**
     * @covers \DCarbone\UglyQueueManager::getQueueList
     * @uses \DCarbone\UglyQueueManager
     * @depends testCanInitializeObjectWithValidPath
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testCanGetListOfQueuesInManager(\DCarbone\UglyQueueManager $manager)
    {
        $queueList = $manager->getQueueList();

        $this->assertInternalType('array', $queueList);
        $this->assertCount(1, $queueList);
        $this->assertContains('tasty-sandwich', $queueList);
    }

    /**
     * @covers \DCarbone\UglyQueueManager::getQueueWithName
     * @covers \DCarbone\UglyQueueManager::addQueue
     * @uses \DCarbone\UglyQueueManager
     * @uses \DCarbone\UglyQueue
     * @expectedException \RuntimeException
     * @depends testCanInitializeObjectWithValidPath
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testExceptionThrownWhenReAddingQueueToManager(\DCarbone\UglyQueueManager $manager)
    {
        $uglyQueue = $manager->getQueueWithName('tasty-sandwich');

        $manager->addQueue($uglyQueue);
    }

    /**
     * @covers \DCarbone\UglyQueueManager::addQueueAtPath
     * @covers \DCarbone\UglyQueueManager::addQueue
     * @covers \DCarbone\UglyQueueManager::getQueueWithName
     * @uses \DCarbone\UglyQueueManager
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidPath
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testCanInitializeNewQueueAndAddToManager(\DCarbone\UglyQueueManager $manager)
    {
        $manager->addQueueAtPath($this->baseDir.'/really-tasty-sandwich');

        $uglyQueue = $manager->getQueueWithName('really-tasty-sandwich');

        $this->assertInstanceOf('\\DCarbone\\UglyQueue', $uglyQueue);
        $this->assertEquals('really-tasty-sandwich', $uglyQueue->getName());

        $queueList = $manager->getQueueList();

        $this->assertInternalType('array', $queueList);
        $this->assertCount(2, $queueList);
        $this->assertContains('really-tasty-sandwich', $queueList);
    }

    /**
     * @covers \DCarbone\UglyQueueManager::removeQueueByName
     * @uses \DCarbone\UglyQueueManager
     * @depends testCanInitializeObjectWithValidPath
     * @param \DCarbone\UglyQueueManager $manager
     */
    public function testCanRemoveQueueFromManagerByName(\DCarbone\UglyQueueManager $manager)
    {
        $manager->removeQueueByName('really-tasty-sandwich');

        $queueList = $manager->getQueueList();

        $this->assertCount(1, $queueList);
        $this->assertNotContains('really-tasty-sandwich', $queueList);
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
