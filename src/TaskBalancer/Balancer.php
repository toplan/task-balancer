<?php

namespace Toplan\TaskBalance;

/**
 * Class Balancer.
 */
class Balancer
{
    /**
     * task instances.
     *
     * @var array
     */
    protected static $tasks = [];

    /**
     * create a task instance.
     *
     * @param string        $name
     * @param mixed         $data
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
     * run a task instance.
     *
     * @param string $name
     * @param array  $opts
     *
     * @throws TaskBalancerException
     *
     * @return mixed
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
        $driverName = isset($opts['driver']) ?
                      $opts['driver'] : (isset($opts['agent']) ?
                      $opts['agent'] : '');
        $results = $task->run((string) $driverName);
        $task->reset();

        return $results;
    }

    /**
     * whether has task.
     *
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
     * get a task instance by name.
     *
     * @param $name
     *
     * @return null|object
     */
    public static function getTask($name)
    {
        if (self::hasTask($name)) {
            return self::$tasks[$name];
        }
    }

    /**
     * destroy a task.
     *
     * @param $name
     */
    public static function destroy($name)
    {
        if (is_array($name)) {
            foreach ($name as $v) {
                self::destroy($v);
            }
        } elseif (is_string($name) && self::hasTask($name)) {
            self::$tasks[$name] = null;
            unset(self::$tasks[$name]);
        }
    }
}
