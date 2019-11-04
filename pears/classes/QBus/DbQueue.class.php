<?php

/**
 * db队列
 */

class QBus_DbQueue implements QBus_QueueConstraint
{
    protected $queueTable = 'public.img_queue';

    protected $job;

    /**
     * 生产消息队列
     * @param $className
     * @param $parameters
     * @param $planConsumeTime
     * @param $tries
     * @return bool
     */
    public function production($className, $parameters, $planConsumeTime, $tries) : bool
    {
        return !! DB::Insert($this->queueTable, [
            "job_name" => $className,
            "parameters" => json_encode($parameters, JSON_UNESCAPED_UNICODE),
            "plan_consume_time" => $planConsumeTime,
            "tries" => $tries,
        ], "id");
    }

    /**
     * 获取消息队列
     * @return QBus_DbQueue
     */
    public function get()
    {
        $nowDate = date("Y-m-d H:i:s");
        $sql = <<<SQL
 select id, job_name, parameters, plan_consume_time, consume_time, tries , failures, create_time, update_time
 from {$this->queueTable} 
 where consume_time is null and plan_consume_time < '{$nowDate}' and  tries > failures
 order by plan_consume_time asc, id asc
SQL;

        $job = DB::GetQueryResult($sql);

        if(! $job) {
            return null;
        }
        $job["parameters"] = json_decode($job["parameters"], true);
        $this->job = (object) $job;
        return $this;
    }

    /**
     * 获取队列信息
     * @return mixed
     */
    public function getJobInfo()
    {
        return $this->job;
    }

    /**
     * 消费队列
     * @return bool
     * @throws QBus_QueueException
     */
    public function consume() : bool
    {
        $this->checkJob();
        $nowDate = date("Y-m-d H:i:s");
        $updateResult = !! DB::Update($this->queueTable, $this->job->id, [
            "consume_time" => $nowDate,
            "update_time" => $nowDate,
        ]);
        if ($updateResult) {
            $this->job->consume_time = $this->job->update_time = $nowDate;
        }

        return $updateResult;
    }

    /**
     * 消费失败
     * @return bool
     * @throws QBus_QueueException
     */
    public function failed() : bool
    {
        $this->checkJob();
        $planConsumeTime = date("Y-m-d H:i:s", time() + 5);
        $updateTime = date("Y-m-d H:i:s");
        $sql = <<<SQL
    update {$this->queueTable} 
    set failures = failures + 1, plan_consume_time = '{$planConsumeTime}', update_time = '{$updateTime}' 
    where id = {$this->job->id}
SQL;
        $updateResult = !! DB::Query($sql);

        if ($updateResult) {
            $this->job->failures++;
            $this->job->plan_consume_time = $planConsumeTime;
            $this->job->update_time = $updateTime;
        }

        return $updateResult;
    }

    /**
     * 验证队列信息是否异常
     * @throws QBus_QueueException
     */
    private function checkJob()
    {
        if (is_null($this->job)) {
            throw new \QBus_QueueException("job任务为空");
        }
    }
}