<?php namespace DCarbone;

use DCarbone\Helpers\FileHelper;

/**
 * Class UglyQueue
 * @package DCarbone
 *
 * @property string name
 * @property string path
 * @property bool locked
 */
class UglyQueue implements \Serializable, \SplSubject, \Countable
{
    /** @var int */
    public $notifyStatus;

    const QUEUE_READONLY = 0;
    const QUEUE_READWRITE = 1;

    /** @var array */
    private $observers = array();

    /** @var int */
    protected $mode = null;

    /** @var string */
    protected $_name;

    /** @var string */
    protected $_path;

    /** @var bool */
    protected $_locked = false;

    /** @var resource */
    protected $_tmpHandle;

    /**
     * @param string $directoryPath
     * @param array $observers
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return UglyQueue
     */
    public static function queueWithDirectoryPathAndObservers($directoryPath, array $observers = array())
    {
        if (!is_string($directoryPath))
            throw new \InvalidArgumentException('Argument 1 expected to be string, '.gettype($directoryPath).' seen');

        if (($directoryPath = trim($directoryPath)) === '')
            throw new \InvalidArgumentException('Empty string passed for argument 1');

        if (file_exists($directoryPath))
        {
            if (!is_dir($directoryPath))
                throw new \RuntimeException('Argument 1 expected to be path to directory, path to non-directory seen');
        }
        else if (!@mkdir($directoryPath))
        {
            throw new \RuntimeException('Unable to create queue directory at path: "'.$directoryPath.'".');
        }

        $uglyQueue = new UglyQueue();
        $uglyQueue->observers = $observers;

        $split = preg_split('#[/\\\]+#', $directoryPath);

        $uglyQueue->_name = end($split);
        $uglyQueue->_path = rtrim(realpath(implode(DIRECTORY_SEPARATOR, $split)), "/\\").DIRECTORY_SEPARATOR;

        if (is_writable($uglyQueue->_path))
            $uglyQueue->mode = self::QUEUE_READWRITE;
        else if (is_readable($uglyQueue->_path))
            $uglyQueue->mode = self::QUEUE_READONLY;

        // Insert "don't look here" index.html file
        if (!file_exists($uglyQueue->_path.'index.html'))
        {
            if ($uglyQueue->mode === self::QUEUE_READONLY)
                throw new \RuntimeException('Cannot initialize queue with name "'.$uglyQueue->_name.'", the user lacks permission to create files.');

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
            file_put_contents($uglyQueue->_path.'index.html', $html);
        }

        if (!file_exists($uglyQueue->_path.'queue.txt'))
        {
            if ($uglyQueue->mode === self::QUEUE_READONLY)
                throw new \RuntimeException('Cannot initialize queue with name "'.$uglyQueue->_name.'", the user lacks permission to create files.');

            file_put_contents($uglyQueue->_path.'queue.txt', '');
        }

        $uglyQueue->notifyStatus = UglyQueueEnum::QUEUE_INITIALIZED;
        $uglyQueue->notify();

        return $uglyQueue;
    }

    /**
     * @param string $param
     * @return string
     * @throws \OutOfBoundsException
     */
    public function __get($param)
    {
        switch($param)
        {
            case 'name' :
                return $this->_name;

            case 'path':
                return $this->_path;

            case 'locked':
                return $this->_locked;

            default:
                throw new \OutOfBoundsException(get_class($this).' does not have a property named "'.$param.'".');
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->_populateQueue();
        $this->unlock();
        file_put_contents($this->_path.UglyQueueManager::UGLY_QUEUE_SERIALIZED_NAME, serialize($this));
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

        if ($ttl < 0)
            throw new \InvalidArgumentException('Argument 1 expected to be positive integer, "'.$ttl.'" seen');

        $alreadyLocked = $this->isLocked();

        // If there is currently no lock
        if ($alreadyLocked === false)
            return $this->createLockFile($ttl);

        // If we make it this far, there is already a lock in place.
        $this->_locked = false;
        $this->notifyStatus = UglyQueueEnum::QUEUE_LOCKED_BY_OTHER_PROCESS;
        $this->notify();

        return false;
    }

    /**
     * @param int $ttl seconds to live
     * @return bool
     */
    protected function createLockFile($ttl)
    {
        $ok = (bool)@file_put_contents(
            $this->_path.'queue.lock',
            json_encode(array('ttl' => $ttl, 'born' => time())));

        if ($ok !== true)
        {
            $this->notifyStatus = UglyQueueEnum::QUEUE_FAILED_TO_LOCK;
            $this->notify();
            return $this->_locked = false;
        }

        $this->_locked = true;

        $this->notifyStatus = UglyQueueEnum::QUEUE_LOCKED;
        $this->notify();

        return true;
    }

    /**
     * Close this ugly queue, writing out contents to file.
     */
    public function unlock()
    {
        if ($this->_locked === true)
        {
            unlink($this->_path.'queue.lock');
            $this->_locked = false;

            $this->notifyStatus = UglyQueueEnum::QUEUE_UNLOCKED;
            $this->notify();
        }
    }

    /**
     * @throws \RuntimeException
     * @return bool
     */
    public function isLocked()
    {
        // First check for lock file
        if (is_file($this->_path.'queue.lock'))
        {
            $lock = json_decode(file_get_contents($this->_path.'queue.lock'), true);

            // If we have an invalid lock structure.
            if (!isset($lock['ttl']) || !isset($lock['born']))
                throw new \RuntimeException('Invalid "queue.lock" file structure seen at "'.$this->_path.'queue.lock".');

            // Otherwise...
            $lock_ttl = ((int)$lock['born'] + (int)$lock['ttl']);

            // If we're within the TTL of the lock, assume another thread is already processing.
            // We'll pick it up on the next go around.
            if ($lock_ttl > time())
                return true;

            // Else, remove lock file and assume we're good to go!
            unlink($this->_path.'queue.lock');
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
    public function processQueue($count = 1)
    {
        if ($this->mode === self::QUEUE_READONLY)
            throw new \RuntimeException('Queue "'.$this->_name.'" cannot be processed. It was started in Read-Only mode (the user running this process does not have permission to write to the queue directory).');

        // If we don't have a lock, assume issue and move on.
        if ($this->_locked === false)
            throw new \RuntimeException('Cannot process queue named "'.$this->_name.'".  It is locked by another process.');

        // If non-int valid is passed
        if (!is_int($count))
            throw new \InvalidArgumentException('Argument 1 expected to be integer greater than 0, "'.gettype($count).'" seen');

        // If negative integer passed
        if ($count <= 0)
            throw new \InvalidArgumentException('Argument 1 expected to be integer greater than 0, "'.$count.'" seen');

        if ($this->notifyStatus !== UglyQueueEnum::QUEUE_PROCESSING)
        {
            $this->notifyStatus = UglyQueueEnum::QUEUE_PROCESSING;
            $this->notify();
        }

        // Find number of lines in the queue file
        $lineCount  = FileHelper::getLineCount($this->_path.'queue.txt');

        // If queue line count is 0, assume empty
        if ($lineCount === 0)
            return false;

        // Try to open the file for reading / writing.
        $queueFileHandle = fopen($this->_path.'queue.txt', 'r+');
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
                $data[$key] = $value;
            }
        }

        // If we have consumed the rest of the file
        if ($count >= $lineCount)
        {
            rewind($queueFileHandle);
            ftruncate($queueFileHandle, 0);
            fclose($queueFileHandle);

            $this->notifyStatus = UglyQueueEnum::QUEUE_REACHED_END;
            $this->notify();
        }
        // Otherwise, create new queue file minus the processed lines.
        else
        {
            $tmp = fopen($this->_path.'queue.tmp', 'w+');
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
            unlink($this->_path.'queue.txt');
            rename($this->_path.'queue.tmp', $this->_path.'queue.txt');
        }

        return $data;
    }

    /**
     * @param string $key
     * @param string|array $value
     * @return bool
     * @throws \RuntimeException
     */
    public function addToQueue($key, $value)
    {
        if ($this->mode === self::QUEUE_READONLY)
            throw new \RuntimeException('Cannot add items to queue "'.$this->_name.'" as it is in read-only mode');

        // If we don't have a lock, assume issue and move on.
        if ($this->_locked === false)
            throw new \RuntimeException('Cannot add items to queue "'.$this->_name.'". Queue is already locked by another process');

        if (!is_resource($this->_tmpHandle))
        {
            $this->_tmpHandle = fopen($this->_path.'queue.tmp', 'w+');
            if ($this->_tmpHandle === false)
                throw new \RuntimeException('Unable to create "queue.tmp" file.');
        }

        if (is_array($value) || $value instanceof \stdClass)
            $value = json_encode($value);

        return (bool)fwrite(
            $this->_tmpHandle,
            $key."\t".str_replace(array("\r\n", "\n"), ' ', $value)
            ."\n");
    }

    /**
     * If there is a tmp queue file, add it's contents to the beginning of a new queue file
     *
     * @return void
     */
    public function _populateQueue()
    {
        if (is_resource($this->_tmpHandle))
        {
            if (file_exists($this->_path.'queue.txt'))
            {
                $queueFileHandle = fopen($this->_path.'queue.txt', 'r+');
                while (($line = fgets($queueFileHandle)) !== false)
                {
                    if ($line !== "\n" && $line !== "")
                        fwrite($this->_tmpHandle, $line);
                }

                fclose($queueFileHandle);
                unlink($this->_path.'queue.txt');
            }

            fclose($this->_tmpHandle);
            rename($this->_path.'queue.tmp', $this->_path.'queue.txt');
        }
    }

    /**
     * @return int
     * @throws \RuntimeException
     */
    public function getQueueItemCount()
    {
        return FileHelper::getLineCount($this->_path.'queue.txt');
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
        $queueFileHandle = fopen($this->_path.'queue.txt', 'r');

        while(($line = fscanf($queueFileHandle, "%s\t%s\n")) !== false)
        {
            list ($lineKey, $lineValue) = $line;

            if ($key === $lineKey)
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
        return $this->getQueueItemCount();
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
        return serialize(array($this->_name, $this->_path));
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
        /** @var \DCarbone\UglyQueue $uglyQueue */
        $data = unserialize($serialized);
        $this->_name = $data[0];
        $this->_path = $data[1];
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
        if (!in_array($observer, $this->observers))
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