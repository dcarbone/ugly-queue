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
     * @return bool
     */
    public function lock($ttl = 250)
    {
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
            @FileHelper::superUnlink($this->queueGroupDirPath.'queue.lock');
            $this->haveLock = false;
        }
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        // First check for lock file
        if (is_file($this->queueGroupDirPath.'queue.lock'))
        {
            $lock = json_decode(file_get_contents($this->queueGroupDirPath.'queue.lock'), true);

            // If we have an invalid lock structure, THIS IS BAD.
            if (!isset($lock['ttl']) || !isset($lock['born']))
                return true;

            $lock_ttl = ((int)$lock['born'] + (int)$lock['ttl']);

            // If we're within the TTL of the lock, assume another thread is already processing.
            // We'll pick it up on the next go around.
            if ($lock_ttl > time())
                return true;

            // Else, remove lock file and assume we're good to go!
            @FileHelper::superUnlink($this->queueGroupDirPath.'queue.lock');
            return false;
        }

        // If no file, assume not locked.
        return false;
    }

    /**
     * @param string $queue_group
     */
    public function initialize($queue_group)
    {
        $this->queueBaseDir = $this->config['queue-base-dir'];

        $this->queueGroup = $queue_group;
        $this->queueGroupDirPath = $this->queueBaseDir.$queue_group.DIRECTORY_SEPARATOR;

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
        $line_count  = FileHelper::getLineCount($this->queueGroupDirPath.'queue.txt');

        // If queue line count is 0, assume empty
        if ($line_count === 0)
            return false;

        // Try to open the file for reading / writing.
        $queue_file_handle = fopen($this->queueGroupDirPath.'queue.txt', 'r+');
        if ($queue_file_handle === false)
            $this->unlock();

        // Get an array of the oldest $count data in the queue
        $data = array();
        $start_line = $line_count - $count;
        $i = 0;
        while (($line = fscanf($queue_file_handle, "%s\t%s\n")) !== false && $i < $line_count)
        {
            if ($i++ >= $start_line)
            {
                list ($key, $value) = $line;
                $data[$key] = $value;
            }
        }

        // If we have consumed the rest of the file
        if ($count >= $line_count)
        {
            rewind($queue_file_handle);
            ftruncate($queue_file_handle, 0);
            fclose($queue_file_handle);
            $this->unlock();
        }
        // Otherwise, create new queue file minus the processed lines.
        else
        {
            $tmp = fopen($this->queueGroupDirPath.'queue.tmp', 'w+');
            rewind($queue_file_handle);
            $i = 0;
            while (($line = fgets($queue_file_handle)) !== false && $i < $start_line)
            {
                if ($line !== "\n" || $line !== "")
                    fwrite($tmp, $line);

                $i++;
            }

            fclose($queue_file_handle);
            fclose($tmp);
            FileHelper::superUnlink($this->queueGroupDirPath.'queue.txt');
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
            return false;

        if (!is_resource($this->_tmpHandle))
        {
            $this->_tmpHandle = fopen($this->queueGroupDirPath.'queue.tmp', 'w+');
            if ($this->_tmpHandle === false)
                return false;
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
    protected function _populateQueue()
    {
        if (is_resource($this->_tmpHandle))
        {
            if (file_exists($this->queueGroupDirPath.'queue.txt'))
            {
                $queue_file_handle = fopen($this->queueGroupDirPath.'queue.txt', 'r+');
                while (($line = fgets($queue_file_handle)) !== false)
                {
                    if ($line !== "\n" && $line !== "")
                        fwrite($this->_tmpHandle, $line);
                }

                fclose($queue_file_handle);
                FileHelper::superUnlink($this->queueGroupDirPath.'queue.txt');
            }

            fclose($this->_tmpHandle);
            rename($this->queueGroupDirPath.'queue.tmp', $this->queueGroupDirPath.'queue.txt');
        }
    }

    /**
     * @return string
     */
    public function getQueueGroup()
    {
        return $this->queueGroup;
    }

    /**
     * @return string
     */
    public function getQueueBaseDir()
    {
        return $this->queueBaseDir;
    }
}