<?php

if (is_dir(__DIR__.'/tasty-sandwich'))
{
    foreach(glob(__DIR__.'/tasty-sandwich/*') as $file)
    {
        if (substr($file, -1) === '.')
            continue;

        unlink($file);
    }
    rmdir(__DIR__.'/tasty-sandwich');
}