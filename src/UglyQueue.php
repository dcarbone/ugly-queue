<?php namespace DCarbone;

use DCarbone\Helpers\FileHelper;

/**
 * Class UglyQueue
 * @package DCarbone
 */
class UglyQueue
{
    /** @var array */
    protected $config;

    /** @var string */
    protected $queueBaseDir;

    /** @var string */
    protected $queueGroup = null;

    /** @var string */
    protected $queueGroupDirPath = null;

    /** @var bool */
    protected $haveLock = false;

    /** @var bool */
    protected $init = false;

    /** @var resource */
    protected $_tmpHandle;

    /**
     * @param array $config
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config)
    {
        if (!isset($config['queue-base-dir']))
            throw new \InvalidArgumentException('UglyQueue::__construct - "$config" parameter "queue-base-dir" not seen.');

        if (!is_dir($config['queue-base-dir']) || !is_writable($config['queue-base-dir']))
            throw new \RuntimeException('UglyQueue::__construct - "$config[\'queue-base-dir\']" points to a directory that either doesn\'t exist or is not writable');

        $this->config = $config;

        $this->queueBaseDir = $this->config['queue-base-dir'];
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->unlock();
        $this->_populateQueue();
    }

    /**
     * @param int $ttl Time to live in seconds
     * @throws \InvalidArgumentException
     * @return bool
     */
    public function lock($ttl = 250)
    {
        if (!is_int($ttl))
            throw new \InvalidArgumentException('UglyQueue::lock - Argument 1 expected to be positive integer, "'.gettype($ttl).'" seen');

        if ($ttl < 0)
            throw new \InvalidArgumentException('UglyQueue::lock - Argument 1 expected to be positive integer, "'.$ttl.'" seen');

        $already_locked = $this->isLocked();

        // If there is no lock, currently
        if ($already_locked === false)
            return $this->haveLock = $this->createQueueLock($ttl);

        // If we make it this far, there is already a lock in place.
        return $this->haveLock = false;
    }

    /**
     * @param int $ttl seconds to live
     * @return bool
     */
    protected function createQueueLock($ttl)
    {
        $ok = (bool)@file_put_contents(
            $this->queueGroupDirPath.'queue.lock',
            json_encode(array('ttl' => $ttl, 'born' => time())));

        if ($ok !== true)
            return false;

        $this->haveLock = true;
        return true;
    }

    /**
     * Close this ugly queue, writing out contents to file.
     */
    public function unlock()
    {
        if ($this->haveLock === true)
        {
            unlink($this->queueGroupDirPath.'queue.lock');
            $this->haveLock = false;
        }
    }

    /**
     * @throws \RuntimeException
     * @return bool
     */
    public function isLocked()
    {
        if ($this->init === false)
            throw new \RuntimeException('UglyQueue::isLocked - Must first initialize queue');

        // First check for lock file
        if (is_file($this->queueGroupDirPath.'queue.lock'))
        {
            $lock = json_decode(file_get_contents($this->queueGroupDirPath.'queue.lock'), true);

            // If we have an invalid lock structure, THIS IS BAD.
            if (!isset($lock['ttl']) || !isset($lock['born']))
                throw new \RuntimeException('UglyQueue::isLocked - Invalid "queue.lock" file structure seen at "'.$this->queueGroupDirPath.'queue.lock'.'"');

            // Otherwise...
            $lock_ttl = ((int)$lock['born'] + (int)$lock['ttl']);

            // If we're within the TTL of the lock, assume another thread is already processing.
            // We'll pick it up on the next go around.
            if ($lock_ttl > time())
                return true;

            // Else, remove lock file and assume we're good to go!
            unlink($this->queueGroupDirPath.'queue.lock');
            return false;
        }

        // If no file, assume not locked.
        return false;
    }

    /**
     * @param string $queueGroup
     */
    public function initialize($queueGroup)
    {
        $this->queueGroup = $queueGroup;
        $this->queueGroupDirPath = $this->queueBaseDir.$queueGroup.DIRECTORY_SEPARATOR;

        // Create directory for this queue group
        if (!is_dir($this->queueGroupDirPath))
            mkdir($this->queueGroupDirPath);

        // Insert "don't look here" index.html file
        if (!file_exists($this->queueGroupDirPath.'index.html'))
        {
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
            file_put_contents($this->queueGroupDirPath.'index.html', $html);
        }

        if (!file_exists($this->queueGroupDirPath.'queue.txt'))
            file_put_contents($this->queueGroupDirPath.'queue.txt', '');

        $this->init = true;
    }

    /**
     * @param int $count
     * @throws \RuntimeException
     * @return bool|array
     */
    public function processQueue($count = 1)
    {
        if ($this->init === false)
            throw new \RuntimeException('UglyQueue::processQueue - Must first initialize queue!');

        // If we don't have a lock, assume issue and move on.
        if ($this->haveLock === false || !file_exists($this->queueGroupDirPath.'queue.txt'))
            return false;

        // Find number of lines in the queue file
        $lineCount  = FileHelper::getLineCount($this->queueGroupDirPath.'queue.txt');

        // If queue line count is 0, assume empty
        if ($lineCount === 0)
            return false;

        // Try to open the file for reading / writing.
        $queueFileHandle = fopen($this->queueGroupDirPath.'queue.txt', 'r+');
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
            $this->unlock();
        }
        // Otherwise, create new queue file minus the processed lines.
        else
        {
            $tmp = fopen($this->queueGroupDirPath.'queue.tmp', 'w+');
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
            unlink($this->queueGroupDirPath.'queue.txt');
            rename($this->queueGroupDirPath.'queue.tmp', $this->queueGroupDirPath.'queue.txt');
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
        if ($this->init === false)
            throw new \RuntimeException('UglyQueue::addToQueue - Must first initialize queue!');

        // If we don't have a lock, assume issue and move on.
        if ($this->haveLock === false)
            throw new \RuntimeException('UglyQueue::addToQueue - You do not have a lock on this queue');

        if (!is_resource($this->_tmpHandle))
        {
            $this->_tmpHandle = fopen($this->queueGroupDirPath.'queue.tmp', 'w+');
            if ($this->_tmpHandle === false)
                throw new \RuntimeException('UglyQueue::addToQueue - Unable to create "queue.tmp" file');
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
            if (file_exists($this->queueGroupDirPath.'queue.txt'))
            {
                $queueFileHandle = fopen($this->queueGroupDirPath.'queue.txt', 'r+');
                while (($line = fgets($queueFileHandle)) !== false)
                {
                    if ($line !== "\n" && $line !== "")
                        fwrite($this->_tmpHandle, $line);
                }

                fclose($queueFileHandle);
                unlink($this->queueGroupDirPath.'queue.txt');
            }

            fclose($this->_tmpHandle);
            rename($this->queueGroupDirPath.'queue.tmp', $this->queueGroupDirPath.'queue.txt');
        }
    }

    /**
     * @return int
     * @throws \RuntimeException
     */
    public function getQueueItemCount()
    {
        if ($this->init === false)
            throw new \RuntimeException('UglyQueue::getQueueItemCount - Must first initialize queue');

        $count = FileHelper::getLineCount($this->queueGroupDirPath.'queue.txt');

        if ($count > 0)
            return ($count - 1);

        return $count;
    }

    /**
     * @param string $key
     * @return bool
     * @throws \RuntimeException
     */
    public function keyExistsInQueue($key)
    {
        if ($this->init === false)
            throw new \RuntimeException('UglyQueue::keyExistsInQueue - Must first initialize queue');

        $key = (string)$key;

        // Try to open the file for reading / writing.
        $queueFileHandle = fopen($this->queueGroupDirPath.'queue.txt', 'r');

        while(($line = fscanf($queueFileHandle, "%s\t")) !== false)
        {
            list($queueKey) = $line;

            if ($key === $queueKey)
            {
                fclose($queueFileHandle);
                return true;
            }
        }

        fclose($queueFileHandle);
        return false;
    }

    /**
     * @return boolean
     */
    public function getInit()
    {
        return $this->init;
    }

    /**
     * @return string
     */
    public function getQueueBaseDir()
    {
        return $this->queueBaseDir;
    }

    /**
     * @return string
     */
    public function getQueueGroupDirPath()
    {
        return $this->queueGroupDirPath;
    }

    /**
     * @return string
     */
    public function getQueueGroup()
    {
        return $this->queueGroup;
    }
}