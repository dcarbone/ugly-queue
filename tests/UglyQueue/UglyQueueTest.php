<?php

/**
 * Class UglyQueueTest
 */
class UglyQueueTest extends PHPUnit_Framework_TestCase
{
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
}
