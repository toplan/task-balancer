<?php

namespace Toplan\TaskBalance;

/**
 * Class Task.
 */
class Task
{
    /**
     * task status.
     */
    const RUNNING = 'running';

    const PAUSED = 'paused';

    const FINISHED = 'finished';

    /**
     * task instance cycle life hooks.
     *
     * @var array
     */
    protected static $hooks = [
        'beforeCreateDriver',
        'afterCreateDriver',
        'beforeRun',
        'beforeDriverRun',
        'afterDriverRun',
        'afterRun',
    ];

    /**
     * task name.
     *
     * @var
     */
    protected $name;

    /**
     * task`s driver instances.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * task`s back up drivers name.
     *
     * @var array
     */
    protected $backupDrivers = [];

    /**
     * task status.
     *
     * @var string|null
     */
    protected $status = null;

    /**
     * current use driver.
     *
     * @var null
     */
    protected $currentDriver = null;

    /**
     * task work.
     *
     * @var null
     */
    protected $work = null;

    /**
     * task run time.
     *
     * @var array
     */
    protected $time = [
        'started_at'  => 0,
        'finished_at' => 0,
    ];

    /**
     * data for driver.
     *
     * @var null
     */
    protected $data = null;

    /**
     * drivers` results.
     *
     * @var array
     */
    protected $results = [];

    /**
     * handlers for hooks.
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * constructor.
     *
     * @param               $name
     * @param               $data
     * @param \Closure|null $work
     */
    public function __construct($name, $data = null, \Closure $work = null)
    {
        $this->name = $name;
        $this->data = $data;
        $this->work = $work;
    }

    /**
     * create a new task.
     *
     * @param               $name
     * @param               $data
     * @param \Closure|null $work
     *
     * @return Task
     */
    public static function create($name, $data = null, \Closure $work = null)
    {
        $task = new self($name, $data, $work);
        $task->runWork();

        return $task;
    }

    /**
     * run work.
     */
    public function runWork()
    {
        if (is_callable($this->work)) {
            call_user_func($this->work, $this);
        }
    }

    /**
     * run task.
     *
     * @param string $driverName
     *
     * @throws \Exception
     *
     * @return bool
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
     * before run task.
     *
     * @return bool
     */
    protected function beforeRun()
    {
        $pass = $this->callHookHandler('beforeRun');
        if ($pass) {
            $this->status = static::RUNNING;
            $this->time['started_at'] = microtime();
        }

        return $pass;
    }

    /**
     * after run task.
     *
     * @param $success
     *
     * @return mixed
     */
    protected function afterRun($success)
    {
        $this->status = static::FINISHED;
        $this->time['finished_at'] = microtime();
        $return = [];
        $return['success'] = $success;
        $return['time'] = $this->time;
        $return['logs'] = $this->results;
        $data = $this->callHookHandler('afterRun', $return);

        return is_bool($data) ? $return : $data;
    }

    /**
     * run driver by name.
     *
     * @param $name
     *
     * @return bool
     */
    public function runDriver($name)
    {
        // if not find driver by the name,
        // will stop and return false
        $driver = $this->getDriver($name);
        if (!$driver) {
            return false;
        }
        $this->currentDriver = $driver;

        // before run a driver, call 'beforeDriverRun' hooks,
        // but current driver value is already change to this driver.
        // If 'beforeDriverRun' hook return false,
        // will stop to use current driver and try to use next driver
        $currentDriverEnable = $this->callHookHandler('beforeDriverRun', $driver);
        if (!$currentDriverEnable) {
            return $this->tryNextDriver();
        }

        // start run current driver,
        // and store result
        $result = $driver->run();
        $success = $driver->success;
        $data = [
            'driver'  => $driver->name,
            'time'    => $driver->time,
            'success' => $success,
            'result'  => $result,
        ];
        $this->storeDriverResult($data);

        // call 'afterDriverRun' hooks
        $this->callHookHandler('afterDriverRun', $data);

        // weather to use backup driver,
        // if failed will try to use next backup driver
        if (!$success) {
            return $this->tryNextDriver();
        }

        return true;
    }

    /**
     * store driver run result data.
     *
     * @param $data
     */
    public function storeDriverResult($data)
    {
        if (!is_array($this->results) || !$this->results) {
            $this->results = [];
        }
        if ($data) {
            array_push($this->results, $data);
        }
    }

    /**
     * try to use next backup driver.
     *
     * @return bool
     */
    public function tryNextDriver()
    {
        $backUpDriverName = $this->getNextBackupDriverName();
        if ($backUpDriverName) {
            // try to run a backup driver
           return $this->runDriver($backUpDriverName);
        }
        // not find a backup driver, current driver must be run false.
        return false;
    }

    /**
     * generator a back up driver`s name.
     *
     * @return null
     */
    public function getNextBackupDriverName()
    {
        $drivers = $this->backupDrivers;
        $currentDriverName = $this->currentDriver->name;
        if (!count($drivers)) {
            return;
        }
        if (!in_array($currentDriverName, $drivers)) {
            return $drivers[0];
        }
        if (in_array($currentDriverName, $drivers) && count($drivers) == 1) {
            return;
        }
        $currentKey = array_search($currentDriverName, $drivers);
        if (($currentKey + 1) < count($drivers)) {
            return $drivers[$currentKey + 1];
        }
    }

    /**
     * get a driver name by driver weight.
     *
     * @return mixed
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
                    'min'    => $base,
                    'max'    => $max,
                    'driver' => $driver->name,
                ];
                $base = $max;
            }
        }
        if ($count <= 0) {
            return;
        }
        $number = mt_rand(0, $count - 1);
        foreach ($map as $data) {
            if ($number >= $data['min'] && $number < $data['max']) {
                return $data['driver'];
            }
        }

        return array_rand(array_keys($this->drivers));
    }

    /**
     * create a new driver instance for current task.
     *
     * @throws TaskBalancerException
     *
     * @return null|static
     */
    public function driver()
    {
        $args = func_get_args();
        if (!count($args)) {
            throw new TaskBalancerException('Please give task`s method `driver` some args');
        }
        extract($this->parseDriverArgs($args));
        if (!$name) {
            throw new TaskBalancerException('Please give the new driver a unique name!');
        }
        $driver = $this->getDriver($name);
        if (!$driver) {
            $this->callHookHandler('beforeCreateDriver');
            $driver = Driver::create($this, $name, $weight, $isBackup, $work);
            $this->drivers[$name] = $driver;
            if ($isBackup) {
                $this->backupDrivers[] = $name;
            }
            $this->callHookHandler('afterCreateDriver');
        }

        return $driver;
    }

    /**
     * parse arguments for method `driver()`.
     *
     * @param $args
     *
     * @return array
     */
    protected function parseDriverArgs($args)
    {
        $result = [
            'name'     => '',
            'work'     => null,
            'weight'   => 1,
            'isBackup' => false,
        ];
        foreach ($args as $arg) {
            //find work
            if (is_callable($arg)) {
                $result['work'] = $arg;
            }
            //find weight, backup, name
            if (is_string($arg) || is_numeric($arg)) {
                $arg = preg_replace('/\s+/', ' ', "$arg");
                $subArgs = explode(' ', trim($arg));
                foreach ($subArgs as $subArg) {
                    if (preg_match('/^[0-9]+$/', $subArg)) {
                        $result['weight'] = $subArg;
                    } elseif (preg_match('/(backup)/', strtolower($subArg))) {
                        $result['isBackup'] = true;
                    } else {
                        $result['name'] = $subArg;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * current task has character driver?
     *
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
     * get a driver from current task drives pool.
     *
     * @param $name
     *
     * @return mixed
     */
    public function getDriver($name)
    {
        if ($this->hasDriver($name)) {
            return $this->drivers[$name];
        }
    }

    /**
     * init back up drivers.
     *
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
     *
     * @return bool
     */
    public function isRunning()
    {
        return $this->status == static::RUNNING;
    }

    /**
     * reset status.
     *
     * @return $this
     */
    public function reset()
    {
        $this->status = null;
        $this->results = null;
        $this->currentDriver = null;
        $this->time['started_at'] = 0;
        $this->time['finished_at'] = 0;

        return $this;
    }

    /**
     * add a driver to backup drivers.
     *
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
     * remove character driver from backup drivers.
     *
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
     * set data.
     *
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
     * set hook handler.
     *
     * @param      $hookName
     * @param null $handler
     * @param bool $override
     *
     * @throws TaskBalancerException
     */
    public function hook($hookName, $handler = null, $override = false)
    {
        if ($handler && is_callable($handler) && is_string($hookName)) {
            if (in_array($hookName, self::$hooks)) {
                if (!isset($this->handlers[$hookName])) {
                    $this->handlers[$hookName] = [];
                }
                if ($override) {
                    $this->handlers[$hookName] = [$handler];
                } else {
                    array_push($this->handlers[$hookName], $handler);
                }
            } else {
                throw new TaskBalancerException("Don`t support the hook [$hookName]");
            }
        } elseif (is_array($hookName)) {
            foreach ($hookName as $k => $h) {
                $this->hook($k, $h, false);
            }
        }
    }

    /**
     * call hook handler.
     *
     * @param $hookName
     * @param $data
     *
     * @return mixed|null
     */
    protected function callHookHandler($hookName, $data = null)
    {
        if (array_key_exists($hookName, $this->handlers)) {
            $handlers = $this->handlers[$hookName] ?: [];
            $result = null;
            foreach ($handlers as $index => $handler) {
                $handlerArgs = $data === null ?
                               [$this, $result, $index, $handlers] :
                               [$this, $data, $result, $index, $handlers];
                $result = call_user_func_array($handler, $handlerArgs);
            }
            if ($result === null) {
                return true;
            }

            return $result;
        }

        return true;
    }

    /**
     * properties overload.
     *
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
    }

    /**
     * method overload.
     *
     * @param $name
     * @param $args
     *
     * @throws TaskBalancerException
     */
    public function __call($name, $args)
    {
        if (in_array($name, self::$hooks)) {
            if (isset($args[0]) && is_callable($args[0])) {
                $override = isset($args[1]) ? (bool) $args[1] : false;
                $this->hook($name, $args[0], $override);
            } else {
                throw new TaskBalancerException("Please give the method [$name()] a callable argument");
            }
        } else {
            throw new TaskBalancerException("Don`t find the method [$name()]");
        }
    }
}
