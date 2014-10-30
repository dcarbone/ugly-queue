<?php namespace DCarbone;

/**
 * Class UglyQueueEnum
 * @package DCarbone
 *
 * Pseudo-enum thing.
 */
abstract class UglyQueueEnum
{
    // Typically used by UglyQueueManager
    const MANAGER_INITIALIZED = 1;
    const QUEUE_ADDED = 2;
    const QUEUE_REMOVED = 3;

    // Typically used by UglyQueues
    const QUEUE_INITIALIZED              = 100;
    const QUEUE_LOCKED                   = 101;
    const QUEUE_FAILED_TO_LOCK           = 102;
    const QUEUE_LOCKED_BY_OTHER_PROCESS  = 103;
    const QUEUE_UNLOCKED                 = 104;
    const QUEUE_PROCESSING               = 105;
    const QUEUE_REACHED_END              = 106;
}