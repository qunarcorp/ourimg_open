<?php

/**
 * Interface QBus_ShouldQueue
 */

interface QBus_ShouldQueue
{
    /**
     * @return bool 队列默认执行方法
     */
    public function handle() : bool ;
}