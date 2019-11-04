<?php

/**
 * Trait QBus_Dispatchable
 */

trait QBus_Dispatchable
{
    /**
     * 任务及时执行
     * @param mixed ...$parameters job 参数
     * @throws QBus_QueueException
     */
    public static function dispatch(... $parameters)
    {
        $jobClass = static::class;
        (new QBus_Dispatcher)->dispatchToQueue(new $jobClass(), $parameters);
    }

    /**
     * 任务延迟执行
     * @param $planConsumeTime string 计划执行时间 Y-m-d H:i:s
     * @param mixed ...$parameters job 参数
     * @throws QBus_QueueException
     */
    public static function planDispatch($planConsumeTime, ... $parameters)
    {
        $jobClass = static::class;
        (new QBus_Dispatcher)->dispatchToQueue(new $jobClass(), $parameters, $planConsumeTime);
    }
}