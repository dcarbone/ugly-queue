ugly-queue
==========

A simple file-based FIFO queue system for PHP 5.3.3+

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
$queueBaseDir = 'path to where you would like queue files and directories to be stored';

$manager = new UglyQueueManager($queueBaseDir);
```

Once initialized, you can start adding queues!

```php
$sandwichQueue = $manager->createQueue('sandwiches');

$sandwichQueue->lock();

$sandwichQueue->addItems(array(
    'bread',
    'meat',
    'cheese',
    'lettuce',
    'bread'
));

$sandwichQueue->unlock();
```

Once you have items added to the queue, you can either pull items out ad-hoc or set up some sort of cron
or schedule task to process items regularly.

If the base directory for all of your queues remains the same, each initialization
of `UglyQueueManager` will automatically find and initialize instances of pre-existing
UglyQueues.

In a subsequent request, simply do the following:

```php
$queueBaseDir = 'path to where you would like queue files and directories to be stored';

$manager = new UglyQueueManager($queueBaseDir);

// 'tasty-sandwich' queue will exist now

$tastySandwich = $manager->getQueue('sandwiches');

$tastySandwich->lock();

// Specify the number of items you wish to retrieve from the queue

$items = $tastySandwich->retrieveItems(4);

// $items is now an array...

var_export($items);

/*
array (
  4 => 'bread',
  3 => 'lettuce',
  2 => 'cheese',
  1 => 'meat',
)
*/

```

The queue will then retain a single item, `0 => 'bread'` as the 5th item left in the queue.

At any time you can determine how many items remain in a queue by executing `count($queueObj);`

There are a few limitations currently:

1. This lib is designed for small values without much in the way of formatting or line breaks
2. It is designed to be atomic, meaning that only one process can be adding / retrieving items from
a queue at a time.  Reading actions (count, searching, etc) are NOT atomic, however.
