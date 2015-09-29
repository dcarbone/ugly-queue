ugly-queue
==========

A simple file-based queue system for PHP 5.3.3+

Build statuses:
- master: [![Build Status](https://travis-ci.org/dcarbone/ugly-queue.svg?branch=master)](https://travis-ci.org/dcarbone/ugly-queue)
- 0.3.1: [![Build Status](https://travis-ci.org/dcarbone/ugly-queue.svg?branch=0.3.1)](https://travis-ci.org/dcarbone/ugly-queue)

## Installation
This library is designed to be installed into your app using [https://getcomposer.org/](Composer).
Simply copy-paste the following line into your `"requires:"` hash:

```json
"dcarbone/ugly-queue": "0.3.*"
```

## Basic Usage
Once installed, you must first initialize an instance of [src/UglyQueueManager.php](UglyQueueManager).
This is done as such:

```php
$config = array(
    'queue-base-dir' => 'path to where you would like queue files and directories to be stored'
);

$manager = new UglyQueueManager($config);
```

Once initialized, you can start adding queues!

```php
$manager
```