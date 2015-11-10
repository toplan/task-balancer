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
     * task work
     * @var null
     */
    protected $work = null;

    /**
     * task run time
     * @var array
     */
    protected $time = [
        'started_at' => 0,
        'finished_at' => 0
    ];

    /**
     * drivers` results
     * @var array
     */
    protected $results = [];

    /**
     * constructor
     * @param               $name
     * @param \Closure|null $work
     */
    public function __construct($name, \Closure $work = null)
    {
        $this->name = $name;
        $this->work = $work;
    }

    /**
     * create a new task
     * @param               $name
     * @param \Closure|null $work
     * @return Task
     */
    public static function create($name, \Closure $work = null)
    {
        $task = new static($name, $work);
        $task->runWork();
        return $task;
    }

    /**
     * run work
     */
    public function runWork()
    {
        if (is_callable($this->work)) {
            call_user_func($this->work, $this);
        }
    }

    /**
     * run task
     * @param string $driverName
     *
     * @return bool
     * @throws \Exception
     */
    public function run($driverName = '')
    {
        if ($this->isRunning()) {
            //stop run because current task is running
            return false;
        }
        if (!$this->beforeRun()) {
            return false;
        }
        if (!$driverName) {
            $driverName = $this->getDriverNameByWeight();
        }
        $this->resortBackupDrivers($driverName);
        $success = $this->runDriver($driverName);
        return $this->afterRun($success);
    }

    /**
     * before run task
     * @return bool
     */
    private function beforeRun()
    {
        $this->time['started_at'] = microtime();
        $this->status = static::RUNNING;
        return true;
    }

    /**
     * after run task
     * @param $result
     *
     * @return mixed
     */
    private function afterRun($result)
    {
        $this->status = static::FINISHED;
        $this->time['finished_at'] = microtime();
        return $result;
    }

    /**
     * run driver by name
     * @param $name
     *
     * @return bool
     * @throws \Exception
     */
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
            'time' => $driver->time,
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

    /**
     * get a backup driver and run it
     * @return bool
     * @throws \Exception
     */
    public function runBackupDriver()
    {
        $name = $this->getNextBackupDriverName();
        if ($name) {
            return $this->runDriver($name);
        }
        return true;
    }

    /**
     * generator a back up driver`s name
     * @return null
     */
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

    /**
     * get a driver`s name by drivers` weight
     * @return mixed
     * @throws \Exception
     */
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

    /**
     * get a driver name
     * @return mixed
     */
    public function driverNameRand()
    {
        return array_rand(array_keys($this->drivers));
    }

    /**
     * create a new driver instance for current task
     * @param               $name
     * @param int           $weight
     * @param bool|false    $isBackup
     * @param \Closure|null $work
     *
     * @return mixed
     */
    public function driver($name, $weight = 1, $isBackup = false, \Closure $work = null)
    {
        $driver = $this->getDriver($name);
        if (!$driver) {
            $driver = Driver::create($this, $name, $weight, $isBackup, $work);
            $this->drivers[$name] = $driver;
            if ($isBackup) {
                $this->backupDrivers[] = $name;
            }
        }
        return $driver;
    }

    /**
     * current task has driver?
     * @param $name
     *
     * @return bool
     */
    public function hasDriver($name)
    {
        if (!$this->drivers) {
            return false;
        }
        return isset($this->drivers[$name]);
    }

    /**
     * get a driver from current task drives pool
     * @param $name
     *
     * @return null
     */
    public function getDriver($name)
    {
        if ($this->hasDriver($name)) {
            return $this->drivers[$name];
        }
        return null;
    }

    /**
     * init back up drivers
     * @param $name
     */
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

    /**
     * task is running ?
     * @return bool
     */
    public function isRunning()
    {
        return $this->status == static::RUNNING;
    }

    /**
     * reset status
     * @return $this
     */
    public function reset()
    {
        $this->status = '';
        return $this;
    }

    /**
     * add a driver to backup drivers
     * @param $driverName
     */
    public function addToBackupDrivers($driverName)
    {
        if ($driverName instanceof Driver) {
            $driverName = $driverName->name;
        }
        if (!in_array($driverName, $this->backupDrivers)) {
            array_push($this->backupDrivers, $driverName);
        }
    }

    /**
     * remove character driver from backup drivers
     * @param $driverName
     */
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