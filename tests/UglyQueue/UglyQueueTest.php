<?php

date_default_timezone_set('UTC');

require_once __DIR__.'/../misc/cleanup.php';

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
     * @covers \DCarbone\UglyQueue::getQueueBaseDir
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCanGetQueueBaseDir(\DCarbone\UglyQueue $uglyQueue)
    {
        $queueBaseDir = $uglyQueue->getQueueBaseDir();

        $this->assertFileExists(
            $queueBaseDir,
            'Could not verify that Queue Base Dir exists');
    }

    /**
     * @covers \DCarbone\UglyQueue::getQueueGroup
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testGetQueueGroupReturnsNullBeforeInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $queueGroup = $uglyQueue->getQueueGroup();

        $this->assertNull($queueGroup);
    }

    /**
     * @covers \DCarbone\UglyQueue::getInit
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testGetInitReturnsFalseBeforeInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $init = $uglyQueue->getInit();
        $this->assertFalse($init);
    }

    /**
     * @covers \DCarbone\UglyQueue::getQueueGroupDirPath
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testGetQueueGroupDirPathReturnsNullBeforeInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $queueGroupDir = $uglyQueue->getQueueGroupDirPath();

        $this->assertNull($queueGroupDir);
    }

    /**
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenCallingIsLockedOnUninitializedQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        $isLocked = $uglyQueue->isLocked();
    }

    /**
     * @covers \DCarbone\UglyQueue::addToQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToAddItemsToUninitializedQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        $addToQueue = $uglyQueue->addToQueue('test', 'value');
    }

    /**
     * @covers \DCarbone\UglyQueue::getQueueItemCount
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToGetCountOfItemsInQueueBeforeInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $itemCount = $uglyQueue->getQueueItemCount();
    }

    /**
     * @covers \DCarbone\UglyQueue::keyExistsInQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToFindKeyBeforeInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $keyExists = $uglyQueue->keyExistsInQueue(0);
    }

    /**
     * @covers \DCarbone\UglyQueue::processQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToProcessQueueBeforeInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $process = $uglyQueue->processQueue();
    }

    /**
     * @covers \DCarbone\UglyQueue::initialize
     * @covers \DCarbone\UglyQueue::getInit
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanInitializeNewUglyQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        $uglyQueue->initialize('tasty-sandwich');

        $this->assertTrue($uglyQueue->getInit());

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::processQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToProcessQueueAfterInitializationBeforeLock(\DCarbone\UglyQueue $uglyQueue)
    {
        $process = $uglyQueue->processQueue();
    }

    /**
     * @covers \DCarbone\UglyQueue::keyExistsInQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testKeyExistsInQueueReturnsFalseWithEmptyQueueAfterInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $exists = $uglyQueue->keyExistsInQueue(0);

        $this->assertFalse($exists);
    }

    /**
     * @covers \DCarbone\UglyQueue::addToQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToAddItemsToQueueWithoutLockAfterInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $addToQueue = $uglyQueue->addToQueue('test', 'value');
    }

    /**
     * @covers \DCarbone\UglyQueue::getInit
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testGetInitReturnsTrueAfterInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $init = $uglyQueue->getInit();
        $this->assertTrue($init);
    }

    /**
     * @covers \DCarbone\UglyQueue::getQueueGroupDirPath
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCanGetQueueGroupDirectoryAfterInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $queueGroupDir = $uglyQueue->getQueueGroupDirPath();

        $this->assertFileExists($queueGroupDir);
    }

    /**
     * @covers \DCarbone\UglyQueue::getQueueGroup
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testCanGetQueueGroupAfterInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $queueGroup = $uglyQueue->getQueueGroup();

        $this->assertEquals('tasty-sandwich', $queueGroup);
    }

    /**
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testIsLockedReturnsFalseBeforeLockingAfterInitialization(\DCarbone\UglyQueue $uglyQueue)
    {
        $isLocked = $uglyQueue->isLocked();

        $this->assertFalse($isLocked);
    }

    /**
     * @covers \DCarbone\UglyQueue::getQueueItemCount
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testGetQueueItemCountReturnsZeroAfterInitializingEmptyQueue(\DCarbone\UglyQueue $uglyQueue)
    {
        $itemCount = $uglyQueue->getQueueItemCount();
        $this->assertEquals(0, $itemCount);
    }

    /**
     * @covers \DCarbone\UglyQueue::initialize
     * @covers \DCarbone\UglyQueue::__construct
     * @covers \DCarbone\UglyQueue::getInit
     * @uses \DCarbone\UglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanInitializeExistingQueue()
    {
        $conf = array(
            'queue-base-dir' => dirname(__DIR__).'/misc/',
        );

        $uglyQueue = new \DCarbone\UglyQueue($conf);

        $this->assertInstanceOf('\\DCarbone\\UglyQueue', $uglyQueue);

        $this->assertFalse($uglyQueue->getInit());

        $uglyQueue->initialize('tasty-sandwich');

        $this->assertTrue($uglyQueue->getInit());

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @expectedException \InvalidArgumentException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenPassingNonIntegerValueToLock(\DCarbone\UglyQueue $uglyQueue)
    {
        $uglyQueue->lock('7 billion');
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @expectedException \InvalidArgumentException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenPassingNegativeIntegerValueToLock(\DCarbone\UglyQueue $uglyQueue)
    {
        $uglyQueue->lock(-73);
    }

    /**
     * @covers \DCarbone\UglyQueue::lock
     * @covers \DCarbone\UglyQueue::isLocked
     * @covers \DCarbone\UglyQueue::createQueueLock
     * @uses \DCarbone\UglyQueue
     * @depends testCanInitializeNewUglyQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanLockUglyQueueWithDefaultTTL(\DCarbone\UglyQueue $uglyQueue)
    {
        $locked = $uglyQueue->lock();

        $this->assertTrue($locked);

        $queueDir = $uglyQueue->getQueueBaseDir().$uglyQueue->getQueueGroup().'/';

        $this->assertFileExists($queueDir.'queue.lock');

        $decode = @json_decode(file_get_contents($queueDir.'queue.lock'));

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
    public function testCannotLockInitializedQueueThatIsAlreadyLocked(\DCarbone\UglyQueue $uglyQueue)
    {
        $lock = $uglyQueue->lock();

        $this->assertFalse($lock);
    }

    /**
     * @covers \DCarbone\UglyQueue::isLocked
     * @uses \DCarbone\UglyQueue
     * @depends testCanLockUglyQueueWithDefaultTTL
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testIsLockedReturnsTrueAfterLockingInitializedQueue(\DCarbone\UglyQueue $uglyQueue)
    {
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
        $uglyQueue->unlock();

        $queueGroupDir = $uglyQueue->getQueueGroupDirPath();

        $this->assertFileNotExists($queueGroupDir.'queue.lock');

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
     * @covers \DCarbone\UglyQueue::getQueueGroupDirPath
     * @uses \DCarbone\UglyQueue
     * @depends testCanUnlockLockedQueue
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanLockQueueWithValidIntegerValue(\DCarbone\UglyQueue $uglyQueue)
    {
        $locked = $uglyQueue->lock(200);

        $this->assertTrue($locked);

        $queueDir = $uglyQueue->getQueueGroupDirPath();

        $this->assertFileExists($queueDir.'queue.lock');

        $decode = @json_decode(file_get_contents($queueDir.'queue.lock'));

        $this->assertTrue((json_last_error() === JSON_ERROR_NONE));
        $this->assertObjectHasAttribute('ttl', $decode);
        $this->assertObjectHasAttribute('born', $decode);
        $this->assertEquals(200, $decode->ttl);

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::addToQueue
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanLockQueueWithValidIntegerValue
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock(\DCarbone\UglyQueue $uglyQueue)
    {
        foreach(array_reverse($this->tastySandwich, true) as $k=>$v)
        {
            $added = $uglyQueue->addToQueue($k, $v);
            $this->assertTrue($added);
        }

        $groupDir = $uglyQueue->getQueueGroupDirPath();

        $this->assertFileExists(
            $groupDir.'queue.tmp',
            'queue.tmp file was not created!');

        $lineCount = \DCarbone\Helpers\FileHelper::getLineCount($groupDir.'queue.tmp');

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
        $uglyQueue->_populateQueue();

        $groupDir = $uglyQueue->getQueueGroupDirPath();

        $this->assertFileNotExists($groupDir.'queue.tmp');

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
        $itemCount = $uglyQueue->getQueueItemCount();

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
        $exists = $uglyQueue->keyExistsInQueue(5);

        $this->assertTrue($exists);
    }

    /**
     * @covers \DCarbone\UglyQueue::processQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @expectedException \InvalidArgumentException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToProcessLockedQueueWithNonInteger(\DCarbone\UglyQueue $uglyQueue)
    {
        $process = $uglyQueue->processQueue('Eleventy Billion');
    }

    /**
     * @covers \DCarbone\UglyQueue::processQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @expectedException \InvalidArgumentException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToProcessLockedQueueWithIntegerLessThan1(\DCarbone\UglyQueue $uglyQueue)
    {
        $process = $uglyQueue->processQueue(0);
    }

    /**
     * @covers \DCarbone\UglyQueue::processQueue
     * @covers \DCarbone\UglyQueue::getQueueItemCount
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanPopulateQueueTempFileAfterInitializationAndAcquiringLock
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanGetPartialQueueContents(\DCarbone\UglyQueue $uglyQueue)
    {
        $process = $uglyQueue->processQueue(5);

        $this->assertEquals(5, count($process));

        $this->assertArrayHasKey('0', $process);
        $this->assertArrayHasKey('4', $process);

        $this->assertEquals(6, $uglyQueue->getQueueItemCount());

        return $uglyQueue;
    }

    /**
     * @covers \DCarbone\UglyQueue::processQueue
     * @covers \DCarbone\UglyQueue::getQueueItemCount
     * @uses \DCarbone\UglyQueue
     * @uses \DCarbone\Helpers\FileHelper
     * @depends testCanGetPartialQueueContents
     * @param \DCarbone\UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueue
     */
    public function testCanGetFullQueueContents(\DCarbone\UglyQueue $uglyQueue)
    {
        $process = $uglyQueue->processQueue(6);

        $this->assertEquals(6, count($process));

        $this->assertArrayHasKey('10', $process);
        $this->assertArrayHasKey('5', $process);

        $this->assertEquals(0, $uglyQueue->getQueueItemCount());

        return $uglyQueue;
    }
}