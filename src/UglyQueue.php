<?php namespace DCarbone;

use DCarbone\Helpers\FileHelper;

/**
 * Class UglyQueue
 * @package DCarbone
 */
class UglyQueue implements \Serializable, \SplSubject, \Countable
{
    const QUEUE_READONLY = 0;
    const QUEUE_READWRITE = 1;

    /** @var int */
    private $_notifyStatus;

    /** @var \SplObserver[] */
    private $_observers = array();

    /** @var int */
    protected $mode = null;

    /** @var string */
    protected $baseDir;

    /** @var string */
    protected $name;

    /** @var string */
    protected $path;

    /** @var bool */
    protected $locked = false;

    /** @var resource */
    protected $tmpHandle;

    /** @var string */
    protected $queueFile;

    /** @var string */
    protected $queueTmpFile;

    /** @var string */
    protected $lockFile;

    /** @var string */
    protected $serializeFile;

    /**
     * @param string $baseDir
     * @param string $name
     * @param \SplObserver[] $observers
     */
    public function __construct($baseDir, $name, array $observers = array())
    {
        $this->baseDir = realpath($baseDir);
        $this->name = $name;
        $this->_observers = $observers;

        $path = sprintf('%s%s%s', $baseDir, DIRECTORY_SEPARATOR, $name);
        if (!file_exists($path) && !@mkdir($path))
            throw new \RuntimeException('Unable to initialize queue directory "'.$path.'".  Please check permissions.');

        $this->path = $path;
        $this->lockFile = sprintf('%s%squeue.lock', $path, DIRECTORY_SEPARATOR);
        $this->queueFile = sprintf('%s%squeue.txt', $path, DIRECTORY_SEPARATOR);
        $this->queueTmpFile = sprintf('%s%squeue.tmp', $path, DIRECTORY_SEPARATOR);
        $this->serializeFile = sprintf('%s%sugly-queue.obj', $path, DIRECTORY_SEPARATOR);

        $this->initialize();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->_populateQueue();
        $this->unlock();
        file_put_contents($this->path.'/ugly-queue.obj', serialize($this));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * @return string
     */
    public function getQueueFile()
    {
        return $this->queueFile;
    }

    /**
     * @return string
     */
    public function getQueueTmpFile()
    {
        return $this->queueTmpFile;
    }

    /**
     * @return string
     */
    public function getLockFile()
    {
        return $this->lockFile;
    }

    /**
     * @return string
     */
    public function getSerializeFile()
    {
        return $this->serializeFile;
    }

    /**
     * @return boolean
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * @param int $ttl Time to live in seconds
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function lock($ttl = 250)
    {
        if (!is_int($ttl))
            throw new \InvalidArgumentException('Argument 1 expected to be integer, "'.gettype($ttl).'" seen');

        if ($ttl < 1)
            throw new \InvalidArgumentException('Argument 1 expected to be greater than 0 "'.$ttl.'" seen');

        // If there is currently no lock
        if ($this->isAlreadyLocked() === false)
            return $this->createLockFile($ttl);

        // If we make it this far, there is already a lock in place.
        $this->locked = false;
        $this->_notifyStatus = UglyQueueEnum::QUEUE_LOCKED_BY_OTHER_PROCESS;
        $this->notify();

        return false;
    }

    /**
     * Close this ugly queue, writing out contents to file.
     */
    public function unlock()
    {
        if ($this->isLocked() === true)
        {
            unlink($this->lockFile);
            $this->locked = false;

            $this->_notifyStatus = UglyQueueEnum::QUEUE_UNLOCKED;
            $this->notify();
        }
    }

    /**
     * @throws \RuntimeException
     * @return bool
     */
    public function isAlreadyLocked()
    {
        // First check for lock file
        if (is_file($this->lockFile))
        {
            $lock = json_decode(file_get_contents($this->lockFile), true);

            // If the decoded lock file contains a ttl and born value...
            if (isset($lock['ttl']) && isset($lock['born']))
            {
                $lock_ttl = ((int)$lock['born'] + (int)$lock['ttl']);

                // If we're within the TTL of the lock, assume another thread is already processing.
                // We'll pick it up on the next go around.
                if ($lock_ttl > time())
                    return true;
            }

            // Else, remove lock file and assume we're good to go!
            unlink($this->lockFile);
            return false;
        }

        // If no file, assume not locked.
        return false;
    }

    /**
     * @param int $count
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return bool|array
     */
    public function retrieveItems($count = 1)
    {
        if ($this->mode === self::QUEUE_READONLY)
            throw new \RuntimeException('Queue "'.$this->name.'" cannot be processed. It was started in Read-Only mode (the user running this process does not have permission to write to the queue directory).');

        // If we don't have a lock, assume issue and move on.
        if ($this->isLocked() === false)
            throw new \RuntimeException('Cannot process queue named "'.$this->name.'".  It is locked by another process.');

        // If non-int valid is passed
        if (!is_int($count))
            throw new \InvalidArgumentException('Argument 1 expected to be integer greater than 0, "'.gettype($count).'" seen');

        // If negative integer passed
        if ($count <= 0)
            throw new \InvalidArgumentException('Argument 1 expected to be integer greater than 0, "'.$count.'" seen');

        if ($this->_notifyStatus !== UglyQueueEnum::QUEUE_PROCESSING)
        {
            $this->_notifyStatus = UglyQueueEnum::QUEUE_PROCESSING;
            $this->notify();
        }

        // Find number of lines in the queue file
        $lineCount  = count($this);

        // If queue line count is 0, assume empty
        if ($lineCount === 0)
            return false;

        // Try to open the file for reading / writing.
        $queueFileHandle = fopen($this->queueFile, 'r+');
        if ($queueFileHandle === false)
            $this->unlock();

        // Get an array of the oldest $count data in the queue
        $data = array();
        $start_line = $lineCount - $count;
        $i = 0;
        while (($line = fscanf($queueFileHandle, "%s\t%s\n")) !== false && $i < $lineCount)
        {
            if ($i++ >= $start_line)
            {
                list ($key, $value) = $line;
                $data = array($key => $value) + $data;
            }
        }

        // If we have consumed the rest of the file
        if ($count >= $lineCount)
        {
            rewind($queueFileHandle);
            ftruncate($queueFileHandle, 0);
            fclose($queueFileHandle);

            $this->_notifyStatus = UglyQueueEnum::QUEUE_REACHED_END;
            $this->notify();
        }
        // Otherwise, create new queue file minus the processed lines.
        else
        {
            $tmp = fopen($this->queueTmpFile, 'w+');
            rewind($queueFileHandle);
            $i = 0;
            while (($line = fgets($queueFileHandle)) !== false && $i < $start_line)
            {
                if ($line !== "\n" || $line !== "")
                    fwrite($tmp, $line);

                $i++;
            }

            fclose($queueFileHandle);
            fclose($tmp);
            unlink($this->queueFile);
            rename($this->queueTmpFile, $this->queueFile);
        }

        return $data;
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return bool
     * @throws \RuntimeException
     */
    public function addItem($key, $value)
    {
        if ($this->mode === self::QUEUE_READONLY)
            throw new \RuntimeException('Cannot add item to queue "'.$this->name.'" as it is in read-only mode');

        // If we don't have a lock, assume issue and move on.
        if ($this->isLocked() === false)
            throw new \RuntimeException('Cannot add item to queue "'.$this->name.'". Queue is already locked by another process');

        if (!is_resource($this->tmpHandle))
        {
            $this->tmpHandle = fopen($this->queueTmpFile, 'w+');
            if ($this->tmpHandle === false)
                throw new \RuntimeException('Unable to create "queue.tmp" file.');
        }

        if (is_array($value) || $value instanceof \stdClass)
            $value = json_encode($value);

        return (bool)fwrite(
            $this->tmpHandle,
            $key."\t".str_replace(array("\r\n", "\n"), ' ', $value)
            ."\n");
    }

    /**
     * @param array $items
     */
    public function addItems(array $items)
    {
        foreach($items as $k=>$v)
        {
            $this->addItem($k, $v);
        }
    }

    /**
     * If there is a tmp queue file, add it's contents to the beginning of a new queue file
     *
     * @return void
     */
    public function _populateQueue()
    {
        if (is_resource($this->tmpHandle))
        {
            if (file_exists($this->queueFile))
            {
                $queueFileHandle = fopen($this->queueFile, 'r+');
                while (($line = fgets($queueFileHandle)) !== false)
                {
                    if ($line !== "\n" && $line !== "")
                        fwrite($this->tmpHandle, $line);
                }

                fclose($queueFileHandle);
                unlink($this->queueFile);
            }

            fclose($this->tmpHandle);
            rename($this->queueTmpFile, $this->queueFile);
        }
    }

    /**
     * @param string $key
     * @return bool
     * @throws \RuntimeException
     */
    public function keyExistsInQueue($key)
    {
        $key = (string)$key;

        // Try to open the file for reading / writing.
        $queueFileHandle = fopen($this->queueFile, 'r');

        while(($line = fscanf($queueFileHandle, "%s\t%s\n")) !== false)
        {
            if ($key === $line[0])
            {
                fclose($queueFileHandle);
                return true;
            }
        }

        fclose($queueFileHandle);
        return false;
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     */
    public function count()
    {
        return (int)FileHelper::getLineCount($this->queueFile);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     *
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->baseDir,
                $this->name,
                $this->path,
                $this->queueFile,
                $this->queueTmpFile,
                $this->lockFile,
                $this->serializeFile,
            )
        );
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized The string representation of the object.
     * @return void
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->baseDir = $data[0];
        $this->name = $data[1];
        $this->path = $data[2];
        $this->queueFile = $data[3];
        $this->queueTmpFile = $data[4];
        $this->lockFile = $data[5];
        $this->serializeFile = $data[6];
        $this->initialize();
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
        if (!in_array($observer, $this->_observers))
            $this->_observers[] = $observer;
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
        $idx = array_search($observer, $this->_observers, true);
        if ($idx !== false)
            unset($this->_observers[$idx]);
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
        foreach($this->_observers as $observer)
        {
            $observer->update($this);
        }
    }

    /**
     * This method is mostly intended to check the "validity" of a re-initialized queue
     *
     * Could probably stand to be improved.
     *
     * @return bool
     */
    public function _valid()
    {
        return (
            $this->baseDir !== null &&
            $this->name !== null &&
            $this->path !== null &&
            $this->queueFile !== null &&
            $this->queueTmpFile !== null &&
            $this->lockFile !== null &&
            $this->serializeFile !== null
        );
    }

    // --------

    /**
     * @param int $ttl seconds to live
     * @return bool
     */
    protected function createLockFile($ttl)
    {
        $ok = (bool)@file_put_contents(
            $this->lockFile,
            json_encode(array('ttl' => $ttl, 'born' => time())));

        if ($ok !== true)
        {
            $this->_notifyStatus = UglyQueueEnum::QUEUE_FAILED_TO_LOCK;
            $this->notify();
            return $this->locked = false;
        }

        $this->locked = true;
        $this->_notifyStatus = UglyQueueEnum::QUEUE_LOCKED;
        $this->notify();

        return true;
    }

    /**
     * Post-construct initialization method.
     *
     * Also used post-un-serialization
     */
    protected function initialize()
    {
        if (is_readable($this->path) && is_writable($this->path))
            $this->mode = self::QUEUE_READWRITE;
        else if (is_readable($this->path))
            $this->mode = self::QUEUE_READONLY;

        if (!file_exists($this->path.'/index.html'))
        {
            if ($this->mode === self::QUEUE_READONLY)
                throw new \RuntimeException('Cannot initialize queue with name "'.$this->name.'", the user lacks permission to create files.');

            $html = <<<HTML
<html>
<head>
	<title>403 Forbidden</title>
</head>
<body>
    <p>Directory access is forbidden.</p>
</body>
</html>
HTML;
            file_put_contents($this->path.'/index.html', $html);
        }

        if (!file_exists($this->queueFile))
        {
            if ($this->mode === self::QUEUE_READONLY)
                throw new \RuntimeException('Cannot initialize queue with name "'.$this->name.'", the user lacks permission to create files.');

            file_put_contents($this->queueFile, '');
        }

        $this->_notifyStatus = UglyQueueEnum::QUEUE_INITIALIZED;
        $this->notify();
    }
}