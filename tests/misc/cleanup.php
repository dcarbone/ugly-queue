<?php

if (is_dir(__DIR__.'/queues/'))
{
    foreach(glob(__DIR__.'/queues/*', GLOB_ONLYDIR) as $queueDir)
    {
        foreach(glob($queueDir.'/*') as $file)
        {
            $split = preg_split('#[/\\\]+#', $file);
            if (strpos(end($split), '.') === 0)
                continue;

            unlink($file);
        }

        rmdir($queueDir);
    }
}
else
{
    mkdir(__DIR__.'/queues');
}