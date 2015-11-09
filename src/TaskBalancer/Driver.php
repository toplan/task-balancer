<?php
namespace Toplan\TaskBalance;

/**
 * Class Driver
 * @package Toplan\TaskBalance
 */
class Driver
{
    /**
     * driver name
     * @var
     */
    protected $name;

    /**
     * task instance
     * @var
     */
    protected $task;

    /**
     * driver run successful
     * @var bool
     */
    protected $success = false;

    /**
     * weight
     * @var int
     */
    protected $weight = 1;

    /**
     * is back up driver
     * @var bool
     */
    protected $isBackUp = false;

    /**
     * driver`s work
     * @var null
     */
    protected $work = null;

    /**
     * data for run work
     * @var null
     */
    protected $data = null;

    /**
     * constructor
     * @param            $task
     * @param            $name
     * @param int        $weight
     * @param \Closure   $work
     * @param bool|false $isBackUp
     */
    public function __construct(Task $task, $name, $weight = 1, $isBackUp = false, \Closure $work = null)
    {
        $this->task = $task;
        $this->name = $name;
        $this->weight = intval($weight);
        $this->isBackUp = boolval($isBackUp);
        $this->work = $work;
    }

    /**
     * create a driver instance
     * @param            $task
     * @param            $name
     * @param int        $weight
     * @param \Closure   $work
     * @param bool|false $isBackUp
     *
     * @return static
     */
    public static function create(Task $task, $name, $weight = 1, $isBackUp = false, \Closure $work = null)
    {
        $driver = new static($task, $name, $weight, $isBackUp, $work);
        return $driver;
    }

    /**
     * run driver`s work
     * @return mixed|null
     */
    public function run()
    {
        $result = null;
        if ($this->work) {
            $result = call_user_func_array($this->work, [$this, $this->data]);
        }
        return $result;
    }

    /**
     * set driver run success
     */
    public function success()
    {
        $this->success = true;
        return $this;
    }

    /**
     * set driver run failed
     */
    public function failed()
    {
        $this->success = false;
        return $this;
    }

    /**
     * @param $data
     *
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param $weight
     *
     * @return $this
     */
    public function weight($weight)
    {
        $this->weight = intval($weight);
        return $this;
    }

    /**
     * @param bool|true $is
     *
     * @return $this
     */
    public function backUp($is = true)
    {
        $this->isBackUp = (Boolean) $is;
        if ($is) {
            $this->task->addToBackupDrivers($this);
        }
        if (!$is) {
            $this->task->removeFromBackupDrivers($this);
        }
        return $this;
    }

    /**
     * @param \Closure $work
     *
     * @return $this
     */
    public function work(\Closure $work)
    {
        $this->work = $work;
        return $this;
    }

    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        return null;
    }
}