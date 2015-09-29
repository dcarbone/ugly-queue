<?php

date_default_timezone_set('UTC');

require_once __DIR__.'/../misc/cleanup.php';

/**
 * Class UglyQueueTest
 */
class UglyQueueTest extends PHPUnit_Framework_TestCase
{
    protected $baseDir;

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

    protected function setUp()
    {
        $this->baseDir = realpath(__DIR__.'/../misc/queues');
    }

    /**
     * @covers \DCarbone\UglyQueue::__construct
     * @uses \DCarbone\UglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanInitializeObjectWithValidParameters()
    {
        echo __FUNCTION__."\n";
        $uglyQueue = new \DCarbone\UglyQueue($this->baseDir, 'tasty-sandwich');

        $this->assertInstanceOf('\\DCarbone\\UglyQueue', $uglyQueue);

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::retrieveItems
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToProcessQueueAfterInitializationBeforeLock(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $uglyQueue->retrieveItems();
    }

    /**
     * @covers \DCarbone\UglyQueue::keyExistsInQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testKeyExistsInQueueReturnsFalseWithEmptyQueueAfterInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $exists = $uglyQueue->keyExistsInQueue(0);

        $this->assertFalse($exists);
    }

    /**
     * @covers \DCarbone\UglyQueue::addItem
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToAddItemsToQueueWithoutLock(\DCarbone\UglyQueue $uglyQueue)
    {
        $uglyQueue->addItem('test', 'value');
    }

    /**
     * @covers \DCarbone\UglyQueue::getPath
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCanGetQueueDirectory(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $queuePath = $uglyQueue->getPath();

        $this->assertFileExists($queuePath);
    }

    /**
     * @covers \DCarbone\UglyQueue::getName
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCanGetQueueName(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $queueName = $uglyQueue->getName();

        $this->assertEquals('tasty-sandwich', $queueName);
    }

    /**
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCanGetQueueLockedStatus(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $locked = $uglyQueue->isLocked();

        $this->assertFalse($locked);
    }

    /**
     * @covers \DCarbone\UglyQueue::count
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testGetQueueItemCountReturnsZeroWithEmptyQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $itemCount = count($uglyQueue);
        $this->assertEquals(0, $itemCount);
    }

    /**
     * @covers \DCarbone\UglyQueue::__construct
     * @uses \DCarbone\UglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanInitializeExistingQueue()
    {
        echo __FUNCTION__."\n";
        $uglyQueue = new \DCarbone\UglyQueue($this->baseDir, 'tasty-sandwich');

        $this->assertInstanceOf('\\DCarbone\\UglyQueue', $uglyQueue);

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @expectedException \InvalidArgumentException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenPassingNonIntegerValueToLock(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $uglyQueue->lock('7 billion');
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @expectedException \InvalidArgumentException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenPassingNegativeIntegerValueToLock(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $uglyQueue->lock(-73);
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @covers \DCarbone\UglyQueue::isLocked
     * @covers \DCarbone\UglyQueue::createLockFile
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeObjectWithValidParameters
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanLockUglyQueueWithDefaultTTL(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $locked = $uglyQueue->lock();

        $this->assertTrue($locked);

        $this->assertFileExists($uglyQueue->getLockFile());

        $decode = @json_decode(file_get_contents($uglyQueue->getLockFile()));

        $this->assertTrue((json_last_error() === JSON_ERROR_NONE));
        $this->assertObjectHasAttribute('ttl', $decode);
        $this->assertObjectHasAttribute('born', $decode);
        $this->assertEquals(250, $decode->ttl);

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeExistingQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCannotLockQueueThatIsAlreadyLocked(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $lock = $uglyQueue->lock();

        $this->assertFalse($lock);
    }

    /**
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @depends testCanLockUglyQueueWithDefaultTTL
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testIsLockedReturnsTrueAfterLocking(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $isLocked = $uglyQueue->isLocked();
        $this->assertTrue($isLocked);
    }

    /**
     * @covers \DCarbone\UglyQueue::unlock
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanLockUglyQueueWithDefaultTTL
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanUnlockLockedQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $uglyQueue->unlock();

        $this->assertFileNotExists($uglyQueue->getLockFile());

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @depends testCanUnlockLockedQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testIsLockedReturnsFalseAfterUnlockingQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $isLocked = $uglyQueue->isLocked();

        $this->assertFalse($isLocked);
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanUnlockLockedQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testIsLockedReturnsFalseWithStaleQueueLockFile(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $uglyQueue->lock(2);
        $isLocked = $uglyQueue->isLocked();
        $this->assertTrue($isLocked);

        sleep(3);

        $isLocked = $uglyQueue->isLocked();
        $this->assertFalse($isLocked);
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @depends testCanUnlockLockedQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanLockQueueWithValidIntegerValue(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $locked = $uglyQueue->lock(200);

        $this->assertTrue($locked);

        $this->assertFileExists($uglyQueue->getLockFile());

        $decode = @json_decode(file_get_contents($uglyQueue->getLockFile()));

        $this->assertTrue((json_last_error() === JSON_ERROR_NONE));
        $this->assertObjectHasAttribute('ttl', $decode);
        $this->assertObjectHasAttribute('born', $decode);
        $this->assertEquals(200, $decode->ttl);

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::addItem
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanLockQueueWithValidIntegerValue
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        foreach(array_reverse($this->tastySandwich, true) as $k=>$v)
        {
            $added = $uglyQueue->addItem($k, $v);
            $this->assertTrue($added);
        }

        $this->assertFileExists(
            $uglyQueue->getQueueTmpFile(),
            'queue.tmp file was not created!');

        $lineCount = \DCarbone\Helpers\FileHelper::getLineCount($uglyQueue->getQueueTmpFile());

        $this->assertEquals(11, $lineCount);

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::_populateQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanForciblyUpdateQueueFileFromTempFile(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $uglyQueue->_populateQueue();

        $this->assertFileNotExists($uglyQueue->getQueueTmpFile());

        $uglyQueue->_populateQueue();

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::getQueueItemCount
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCanGetCountOfItemsInPopulatedQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        $itemCount = count($uglyQueue);

        $this->assertEquals(11, $itemCount);
    }

    /**
     * @covers \DCarbone\UglyQueue::keyExistsInQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testKeyExistsReturnsTrueWithPopulatedQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $exists = $uglyQueue->keyExistsInQueue(5);

        $this->assertTrue($exists);
    }

    /**
     * @covers \DCarbone\UglyQueue::retrieveItems
     * @uses \DCarbone\UglyQueue
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @expectedException \InvalidArgumentException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToProcessLockedQueueWithNonInteger(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $uglyQueue->retrieveItems('Eleventy Billion');
    }

    /**
     * @covers \DCarbone\UglyQueue::retrieveItems
     * @uses \DCarbone\UglyQueue
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @expectedException \InvalidArgumentException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToProcessLockedQueueWithIntegerLessThan1(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $uglyQueue->retrieveItems(0);
    }

    /**
     * @covers \DCarbone\UglyQueue::retrieveItems
     * @covers \DCarbone\UglyQueue::getQueueItemCount
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanGetPartialQueueContents(\DCarbone\UglyQueue $uglyQueue)
    {
        echo __FUNCTION__."\n";
        $process = $uglyQueue->retrieveItems(5);

        $this->assertEquals(5, count($process));

        $this->assertArrayHasKey('0', $process);
        $this->assertArrayHasKey('4', $process);

        $this->assertEquals(6, count($uglyQueue));

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::retrieveItems
     * @covers \DCarbone\UglyQueue::getQueueItemCount
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanGetPartialQueueContents
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanGetFullQueueContents(\DCarbone\UglyQueue $uglyQueue)
    {
        $process = $uglyQueue->retrieveItems(6);

        $this->assertEquals(6, count($process));

        $this->assertArrayHasKey('10', $process);
        $this->assertArrayHasKey('5', $process);

        $this->assertEquals(0, count($uglyQueue));

        return $uglyQueue;
    }
}