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
     * driver worker
     * @var null
     */
    protected $worker = null;

    /**
     * data for run worker
     * @var null
     */
    protected $data = null;

    /**
     * constructor
     * @param            $name
     * @param int        $weight
     * @param \Closure   $worker
     * @param bool|false $isBackUp
     */
    public function __constructor($name, $weight = 1, $isBackUp = false, \Closure $worker = null)
    {
        $this->name = $name;
        $this->weight = intval($weight);
        $this->isBackUp = boolval($isBackUp);
        $this->worker = $worker;
    }

    /**
     * create a driver instance
     * @param            $name
     * @param int        $weight
     * @param \Closure   $worker
     * @param bool|false $isBackUp
     *
     * @return static
     */
    public static function create($name, $weight = 1, $isBackUp = false, \Closure $worker = null)
    {
        $driver = new static($name, $weight, $isBackUp, $worker);
        return $driver;
    }

    /**
     * run driver`s worker
     * @return mixed|null
     */
    public function run()
    {
        $result = null;
        if ($this->worker) {
            $result = call_user_func_array($this->worker, [$this, $this->data]);
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
        return $this;
    }

    /**
     * @param \Closure $worker
     *
     * @return $this
     */
    public function worker(\Closure $worker)
    {
        $this->worker = $worker;
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