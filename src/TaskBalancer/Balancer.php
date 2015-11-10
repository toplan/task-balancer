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
     * @param      $name
     * @param      $data
     * @param \Closure|null $callback
     *
     * @return null|Task
     */
    public static function task($name, $data, \Closure $callback = null)
    {
        $task = self::getTask($name);
        if (!$task) {
            if (is_callable($data)) {
                $callback = $data;
                $data = null;
            }
            $task = Task::create($name, $data, $callback);
            self::$tasks[$name] = $task;
        }
        return $task;
    }

    /**
     * run a task instance
     * @param string $name
     * @param string $data
     *
     * @return mixed
     * @throws \Exception
     */
    public static function run($name = '', $data = null)
    {
        $task = self::getTask($name);
        if (!$task) {
            throw new \Exception("run task $name failed, not find this task");
        }
        if ($data) {
            $task->data($data);
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
