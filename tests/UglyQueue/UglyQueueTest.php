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
     * @covers \DCarbone\UglyQueue::addToQueue
     * @uses \DCarbone\UglyQueue
     * @depends testCanConstructUglyQueueWithValidParameter
     * @expectedException \RuntimeException
     * @param \DCarbone\UglyQueue $uglyQueue
     */
    public function testExceptionThrownWhenTryingToAddItemsToQueueWithoutLock(\DCarbone\UglyQueue $uglyQueue)
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
}