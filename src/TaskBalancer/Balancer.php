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
     * create a task instance
     * @param string $name
     * @param mixed  $data
     * @param \Closure|null $callback
     *
     * @return null|Task
     */
    public static function task($name, $data = null, \Closure $callback = null)
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
     * @param array  $opts
     *
     * @return mixed
     * @throws TaskBalancerException
     */
    public static function run($name = '', array $opts = [])
    {
        $task = self::getTask($name);
        if (!$task) {
            throw new TaskBalancerException("run task $name failed, not find this task");
        }
        if (isset($opts['data'])) {
            $task->data($opts['data']);
        }
        $agentName = isset($opts['agent']) ? $opts['agent'] : '';
        $results = $task->run((String) $agentName);
        $task->reset();
        return $results;
    }

    /**
     * whether has task
     * @param $name
     *
     * @return bool
     */
    public static function hasTask($name)
    {
        if (!self::$tasks) {
            return false;
        }
        if (isset(self::$tasks[$name])) {
            return true;
        }
        return false;
    }

    /**
     * get a task instance by name
     * @param $name
     *
     * @return null|object
     */
    public static function getTask($name)
    {
        if (self::hasTask($name)) {
            return self::$tasks[$name];
        }
        return null;
    }
}
