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
    protected static $tasks = [];

    /**
     * running task instances
     * @var array
     */
    protected static $runningTasks = [];

    /**
     * create a task instance
     * @param               $name
     * @param \Closure|null $fn
     *
     * @return Task
     */
    public static function task($name, \Closure $fn = null)
    {
        $task = self::getTask($name);
        if (!$task) {
            $task = Task::create($name, $fn);
            self::$tasks[$name] = $task;
        }
        return $task;
    }

    /**
     * run a task instance
     * @param string $name
     *
     * @return mixed
     * @throws \Exception
     */
    public static function run($name = '')
    {
        $task = self::getTask($name);
        if (!$task) {
            throw new \Exception("run task $name failed, not find this task");
        }
        $task->run();
        return $task->results;
    }

    /**
     * get a task instance by name
     * @param $name
     *
     * @return null
     */
    public static function getTask($name)
    {
        if (!self::$tasks) {
            return null;
        }
        if (isset(self::$tasks[$name])) {
            return self::$tasks[$name];
        }
        return null;
    }
}
