<?php

/**
 * Class QBus_Dispatcher
 */

class QBus_Dispatcher
{
    /**
     * @var QBus_QueueConstraint 消息队列供应者
     */
    protected $queueProvider;

    /**
     * @var QBus_ShouldQueue 当前job
     */
    protected $currentJob;

    /**
     * @var array 队列驱动列表
     */
    protected $drivers;

    /**
     * @var int 最大尝试次数
     */
    public $tries = 5;

    /**
     * QBus_Dispatcher constructor.
     * @param string $defaultDrivers 队列驱动
     * @throws QBus_QueueException
     */
    public function __construct($defaultDrivers = QBus_DbQueue::class)
    {
        $this->drivers = [
            QBus_DbQueue::class,
        ];

        if (! in_array($defaultDrivers, $this->drivers)) {
            throw new QBus_QueueException("无效队列驱动");
        }
        $this->queueProvider = new $defaultDrivers();
    }

    /**
     * 压进队列
     * @param QBus_ShouldQueue $job
     * @param $parameters
     * @param null $planConsumeTime 为null计划消费时间默认为当前时间
     */
    public function dispatchToQueue(\QBus_ShouldQueue $job, $parameters, $planConsumeTime = null)
    {
        $this->queueProvider->production(get_class($job), $parameters, is_null($planConsumeTime) ? date("Y-m-d H:i:s") : $planConsumeTime, $this->tries);
    }

    /**
     * 执行队列
     */
    public function run()
    {
        while (true) {
            DB::TransBegin();
            $job = $this->queueProvider->get();
            if (! $job instanceof QBus_QueueConstraint) {
                echo "no task, sleep 500ms".PHP_EOL;
                DB::TransRollback();
                usleep(500000);
                continue;
            }
            echo PHP_EOL."<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<".PHP_EOL;
            $jobInfo = $job->getJobInfo();
            echo "获取队列 id:{$jobInfo->id}".PHP_EOL;
            $this->currentJob = new $jobInfo->job_name;
            if ($this->currentJob->handle(... $jobInfo->parameters)) {
                echo "消费成功 id:{$jobInfo->id}".PHP_EOL;
                if ($job->consume()) {
                    echo "更新队列信息成功 id:{$jobInfo->id}".PHP_EOL;
                }else{
                    echo "更新队列信息失败 id:{$jobInfo->id}".PHP_EOL;
                    DB::TransRollback();
                    echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>".PHP_EOL.PHP_EOL;
                    continue;
                }
            }else{
                echo "消费失败 id:{$jobInfo->id}".PHP_EOL;
                if ($job->failed()) {
                    echo "更新队列信息成功 id:{$jobInfo->id}".PHP_EOL;
                }else{
                    echo "更新队列信息失败 id:{$jobInfo->id}".PHP_EOL;
                    DB::TransRollback();
                    echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>".PHP_EOL;
                    continue;
                }
            }
            DB::TransCommit();
            echo ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>".PHP_EOL.PHP_EOL;
        }
    }
}