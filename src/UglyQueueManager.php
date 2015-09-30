<?php namespace DCarbone;

/**
 * Class UglyQueueManager
 * @package DCarbone
 */
class UglyQueueManager implements \SplObserver, \Countable
{
    /** @var UglyQueue[] */
    protected $queues = array();

    /** @var string */
    protected $baseDir;

    /**
     * Constructor
     *
     * @param string $baseDir
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct($baseDir)
    {
        if (false === is_string($baseDir))
            throw new \InvalidArgumentException('Argument 1 expected to be string, "'.gettype($baseDir).'" seen.');

        if (false === is_dir($baseDir))
            throw new \RuntimeException('"'.$baseDir.'" points to a directory that does not exist.');

        if (false === is_readable($baseDir))
            throw new \RuntimeException('"'.$baseDir.'" is not readable and/or writable .');

        $this->baseDir =  rtrim($baseDir, "/\\");

        foreach(glob(sprintf('%s/*', $this->baseDir), GLOB_ONLYDIR) as $queueDir)
        {
            $this->addQueueAtPath($queueDir);
        }
    }

    /**
     * @param string $name
     * @return UglyQueue|UglyQueueManager
     */
    public function getQueue($name)
    {
        if (isset($this->queues[$name]))
            return $this->queues[$name];

        $path = sprintf('%s/%s', $this->baseDir, $name);
        if (file_exists($path))
            return $this->addQueueAtPath($path);

        return $this->createQueue($name);
    }

    /**
     * @param UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueueManager
     * @throws \RuntimeException
     */
    public function addQueue(UglyQueue $uglyQueue)
    {
        $name = $uglyQueue->getName();

        if ($this->containsQueueWithName($name))
            throw new \RuntimeException('Queue named "'.$name.'" already exists in this manager.');

        $this->queues[$name] = $uglyQueue;

        return $this;
    }

    /**
     * @param string $name
     * @return UglyQueue
     */
    public function createQueue($name)
    {
        $queue = new UglyQueue($this->baseDir, $name, array($this));
        $this->addQueue($queue);
        return end($this->queues);
    }

    /**
     * @param string $path
     * @return \DCarbone\UglyQueueManager
     */
    public function addQueueAtPath($path)
    {
        // Try to avoid looking at hidden directories or magic dirs such as '.' and '..'
        $split = preg_split('#[/\\\]+#', $path);

        $queueName = end($split);

        if (0 === strpos($queueName, '.'))
            return null;

        $serializedFile = sprintf('%s/%s/ugly-queue.obj', $this->baseDir, $queueName);
        /** @var \DCarbone\UglyQueue $uglyQueue */
        if (file_exists($serializedFile))
            $uglyQueue = unserialize(file_get_contents($serializedFile));

        if (!isset($uglyQueue) || $uglyQueue->_valid() === false)
            $uglyQueue = new UglyQueue($this->baseDir, $queueName, array($this));

        $uglyQueue->attach($this);

        return $this->addQueue($uglyQueue);
    }

    /**
     * @param UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueueManager
     */
    public function removeQueue(UglyQueue $uglyQueue)
    {
        $name = $uglyQueue->getName();
        if ($this->containsQueueWithName($name))
            unset($this->queues[$name]);

        return $this;
    }

    /**
     * @param string $name
     * @return \DCarbone\UglyQueueManager
     */
    public function removeQueueByName($name)
    {
        if ($this->containsQueueWithName($name))
            unset($this->queues[$name]);

        return $this;
    }

    /**
     * @param string $name
     * @return \DCarbone\UglyQueue
     * @throws \InvalidArgumentException
     */
    public function getQueueWithName($name)
    {
        if (isset($this->queues[$name]))
            return $this->queues[$name];

        throw new \InvalidArgumentException('Argument 1 expected to be valid queue name.');
    }

    /**
     * @param string $name
     * @return bool
     */
    public function containsQueueWithName($name)
    {
        return isset($this->queues[$name]);
    }

    /**
     * @return array
     */
    public function getQueueList()
    {
        return array_keys($this->queues);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer. The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->queues);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Receive update from subject
     * @link http://php.net/manual/en/splobserver.update.php
     *
     * @param \SplSubject $subject The SplSubject notifying the observer of an update.
     * @return void
     */
    public function update(\SplSubject $subject)
    {
        // Nothing for now...
    }
}