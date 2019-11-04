<?php

/**
 * Interface QBus_QueueConstraint
 */

interface QBus_QueueConstraint
{
    /**
     * 生产消息队列
     * @param $className
     * @param $parameters
     * @param $planConsumeTime
     * @param $tries
     * @return bool
     */
    public function production($className, $parameters, $planConsumeTime, $tries) : bool;

    /**
     * 读取消息队列
     * @return mixed
     */
    public function get();

    /**
     * 消费队列
     * @return mixed
     */
    public function consume() : bool;

    /**
     * 消费失败
     * @return mixed
     */
    public function failed() : bool;

    /**
     * 获取队列信息
     * @return mixed
     */
    public function getJobInfo();

}