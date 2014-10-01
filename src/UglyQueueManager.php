<?php namespace DCarbone;

/**
 * Class UglyQueueManager
 * @package DCarbone
 */
class UglyQueueManager implements \SplObserver, \SplSubject
{
    const NOTIFY_MANAGER_INITIALIZED = 0;
    const NOTIFY_QUEUE_ADDED = 1;
    const NOTIFY_QUEUE_REMOVED = 2;

    /** @var int */
    public $notifyStatus;

    const UGLY_QUEUE_SERIALIZED_NAME = 'ugly-queue.obj';

    /** @var array */
    private $observers = array();

    /** @var array */
    protected $queues = array();

    /** @var array */
    protected $config = array();

    /** @var string */
    protected $queueBaseDir;

    /**
     * Constructor
     *
     * @param array $config
     * @param array $observers
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function __construct(array $config, array $observers = array())
    {
        if (!isset($config['queue-base-dir']))
            throw new \InvalidArgumentException('"$config" parameter "queue-base-dir" not seen.');

        if (!is_dir($config['queue-base-dir']))
            throw new \RuntimeException('"queue-base-dir" points to a directory that does not exist.');

        $this->config = $config;
        $this->queueBaseDir =  rtrim(realpath($this->config['queue-base-dir']), "/\\").DIRECTORY_SEPARATOR;
        $this->observers = $observers;
    }

    /**
     * @param array $config
     * @param array $observers
     * @return UglyQueueManager
     */
    public static function init(array $config, array $observers = array())
    {
        /** @var \DCarbone\UglyQueueManager $manager */
        $manager = new static($config, $observers);

        /** @var \DCarbone\UglyQueue $uglyQueue */

        foreach(glob($manager->queueBaseDir.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) as $queueDir)
        {
            // Try to avoid looking at hidden directories or magic dirs such as '.' and '..'
            $split = preg_split('#[/\\\]+#', $queueDir);
            if (strpos(end($split), '.') === 0)
                continue;

            if (file_exists($queueDir.DIRECTORY_SEPARATOR.self::UGLY_QUEUE_SERIALIZED_NAME))
                $uglyQueue = unserialize(file_get_contents($queueDir.DIRECTORY_SEPARATOR.self::UGLY_QUEUE_SERIALIZED_NAME));
            else
                $uglyQueue = UglyQueue::queueWithDirectoryPathAndObservers($queueDir, $manager->observers);

            $manager->addQueue($uglyQueue);
        }

        $manager->notifyStatus = self::NOTIFY_MANAGER_INITIALIZED;
        $manager->notify();

        return $manager;
    }

    /**
     * @param UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueueManager
     * @throws \RuntimeException
     */
    public function addQueue(UglyQueue $uglyQueue)
    {
        if ($this->containsQueueWithName($uglyQueue->name))
            throw new \RuntimeException('Queue named "'.$uglyQueue->name.'" already exists in this manager.');

        $this->queues[$uglyQueue->name] = $uglyQueue;

        $this->notifyStatus = self::NOTIFY_QUEUE_ADDED;
        $this->notify();

        return $this;
    }

    /**
     * @param $path
     * @return \DCarbone\UglyQueueManager
     */
    public function addQueueAtPath($path)
    {
        $uglyQueue = UglyQueue::queueWithDirectoryPathAndObservers($path, $this->observers);

        return $this->addQueue($uglyQueue);
    }

    /**
     * @param UglyQueue $uglyQueue
     * @return \DCarbone\UglyQueueManager
     */
    public function removeQueue(UglyQueue $uglyQueue)
    {
        if ($this->containsQueueWithName($uglyQueue->name))
            unset($this->queues[$uglyQueue->name]);

        return $this;
    }

    /**
     * @param string $name
     * @return \DCarbone\UglyQueueManager
     */
    public function removeQueueByName($name)
    {
        if ($this->containsQueueWithName($name))
        {
            unset($this->queues[$name]);
            $this->notifyStatus = self::NOTIFY_QUEUE_REMOVED;
            $this->notify();
        }

        return $this;
    }

    /**
     * @param string $name
     * @return \DCarbone\UglyQueue
     * @throws \InvalidArgumentException
     */
    public function &getQueueWithName($name)
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
     * Receive update from subject
     * @link http://php.net/manual/en/splobserver.update.php
     *
     * @param \SplSubject $subject The SplSubject notifying the observer of an update.
     * @return void
     */
    public function update(\SplSubject $subject)
    {
        for ($i = 0, $count = count($this->observers); $i < $count; $i++)
        {
            $this->observers[$i]->notify($subject);
        }
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Attach an SplObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     *
     * @param \SplObserver $observer The SplObserver to attach.
     * @return void
     */
    public function attach(\SplObserver $observer)
    {
        if (!in_array($observer, $this->observers, true))
            $this->observers[] = $observer;
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Detach an observer
     * @link http://php.net/manual/en/splsubject.detach.php
     *
     * @param \SplObserver $observer The SplObserver to detach.
     * @return void
     */
    public function detach(\SplObserver $observer)
    {
        $idx = array_search($observer, $this->observers, true);
        if ($idx !== false)
            unset($this->observers[$idx]);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Notify an observer
     * @link http://php.net/manual/en/splsubject.notify.php
     *
     * @return void
     */
    public function notify()
    {
        for ($i = 0, $count = count($this->observers); $i < $count; $i++)
        {
            $this->observers[$i]->notify($this);
        }
    }
}