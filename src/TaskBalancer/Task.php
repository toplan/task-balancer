<?php
namespace Toplan\TaskBalance;

/**
 * Class Task
 * @package Toplan\TaskBalance
 */
class Task {

    /**
     * task status
     */
    const RUNNING = 'running';

    const PAUSED = 'paused';

    const FINISHED = 'finished';

    /**
     * task name
     * @var
     */
    protected $name;

    /**
     * task`s driver instances
     * @var array
     */
    protected $drivers = [];

    /**
     * task`s back up drivers name
     * @var array
     */
    protected $backupDrivers = [];

    /**
     * task status
     * @var string
     */
    protected $status = '';

    /**
     * current use driver
     * @var null
     */
    protected $currentDriver = null;

    /**
     * task worker
     * @var null
     */
    protected $worker = null;

    /**
     * drivers` results
     * @var array
     */
    protected $results = [];

    /**
     * constructor
     * @param               $name
     * @param \Closure|null $worker
     */
    public function __construct($name, \Closure $worker = null)
    {
        $this->name = $name;
        $this->worker = $worker;
    }

    /**
     * create a new task
     * @param               $name
     * @param \Closure|null $worker
     * @return Task
     */
    public static function create($name, \Closure $worker = null)
    {
        $task = new static($name, $worker);
        $task->runWorker();
        return $task;
    }

    /**
     * run worker
     */
    public function runWorker()
    {
        if ($this->worker) {
            call_user_func($this->worker, $this);
        }
    }

    public function run($driverName = '')
    {
        if ($this->isRunning()) {
            //stop run because current task is running
            return false;
        }
        $this->status = static::RUNNING;
        if (!$driverName) {
            $driverName = $this->getDriverNameByWeight();
        }
        $this->resortBackupDrivers($driverName);
        $success = $this->runDriver($driverName);
        $this->status = static::FINISHED;
        return $success;
    }

    public function runDriver($name)
    {
        $driver = $this->getDriver($name);
        if (!$driver) {
            throw new \Exception("not found driver [$name] in task [$this->name], please define it for current task");
        }
        $this->currentDriver = $driver;
        $result = $driver->run();
        $success = $driver->success;
        $data = [
            'driver' => $driver->name,
            'success' => $success,
            'result' => $result,
        ];
        array_push($this->results, $data);
        if (!$success) {
            $result = $this->runBackupDriver();
            if ($result) {
                return true;
            }
        }
        return $success;
    }

    public function runBackupDriver()
    {
        $name = $this->getNextBackupDriverName();
        if ($name) {
            return $this->runDriver($name);
        }
        return true;
    }

    public function getNextBackupDriverName()
    {
        $drivers = $this->backupDrivers;
        $currentDriverName = $this->currentDriver->name;
        if (!count($drivers)) {
            return null;
        }
        if (!in_array($currentDriverName, $drivers)) {
            return $drivers[0];
        }
        if (count($drivers) == 1 && array_pop($drivers) == $currentDriverName) {
            return null;
        }
        $currentKey = array_search($currentDriverName, $drivers);
        if (($currentKey + 1) < count($drivers)) {
            return $drivers[$currentKey + 1];
        }
        return null;
    }

    public function getDriverNameByWeight()
    {
        $count = $base = 0;
        $map = [];
        foreach ($this->drivers as $driver) {
            $count += $driver->weight;
            if ($driver->weight) {
                $max = $base + $driver->weight;
                $map[] = [
                    'min' => $base,
                    'max' => $max,
                    'driver' => $driver->name,
                ];
                $base = $max;
            }
        }
        if ($count < 1) {
            return $this->driverNameRand();
        }
        $number = mt_rand(0, $count - 1);
        foreach ($map as $data) {
            if ($number >= $data['min'] && $number < $data['max']) {
                return $data['driver'];
            }
        }
        throw new \Exception('get driver name by weight failed, something wrong');
    }

    public function driverNameRand()
    {
        return array_rand(array_keys($this->drivers));
    }

    public function driver($name, $weight = 1, $isBackup = false, \Closure $worker = null)
    {
        $driver = $this->getDriver($name);
        if (!$driver) {
            $driver = Driver::create($this, $name, $weight, $isBackup, $worker);
            $this->drivers[$name] = $driver;
            if ($isBackup) {
                $this->backupDrivers[] = $name;
            }
        }
        return $this->drivers[$name];
    }

    public function hasDriver($name)
    {
        if (!$this->drivers) {
            return false;
        }
        return isset($this->drivers[$name]);
    }

    public function getDriver($name)
    {
        if ($this->hasDriver($name)) {
            return $this->drivers[$name];
        }
        return null;
    }

    public function resortBackupDrivers($name)
    {
        if (count($this->backupDrivers) < 2) {
            return;
        }
        if (in_array($name, $this->backupDrivers)) {
            $key = array_search($name, $this->backupDrivers);
            unset($this->backupDrivers[$key]);
            array_unshift($this->backupDrivers, $name);
            $this->backupDrivers = array_values($this->backupDrivers);
        }
    }

    public function isRunning()
    {
        return $this->status == static::RUNNING;
    }

    public function reset()
    {
        $this->status = '';
        return $this;
    }

    public function addToBackupDrivers($driverName)
    {
        if ($driverName instanceof Driver) {
            $driverName = $driverName->name;
        }
        if (!in_array($driverName, $this->backupDrivers)) {
            array_push($this->backupDrivers, $driverName);
        }
    }

    public function removeFromBackupDrivers($driverName)
    {
        if ($driverName instanceof Driver) {
            $driverName = $driverName->name;
        }
        if (in_array($driverName, $this->backupDrivers)) {
            $key = array_search($driverName, $this->backupDrivers);
            unset($this->backupDrivers[$key]);
            $this->backupDrivers = array_values($this->backupDrivers);
        }
    }

    /**
     * override
     * @param $name
     *
     * @return null
     */
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }
        if (array_key_exists($name, $this->drivers)) {
            return $this->drivers[$name];
        }
        return null;
    }
}