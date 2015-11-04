<?php
namespace Toplan\TaskBalance;

/**
 * Class Balancer
 * @package Toplan\TaskBalance
 */
class Balancer {

    /**
     * task instances
     * @var array
     */
    protected $tasks = [];

    /**
     * running task instances
     * @var array
     */
    protected $runningTasks = [];

    public function task($name, \Closure $fn = null)
    {
        return Task::create($name, $fn);
    }

    public function run($name = '')
    {
        $task = $this->tasks[$name];
        $result = $task->run();
        return $result;
    }

}
