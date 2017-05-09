# Intro

Lightweight and powerful task load balancing.

> like the `nginx` load balancing :smile:

# Features

- Support multiple drives for every task.
- Automatically choose a driver to execute task by drivers` weight value.
- Support multiple backup drivers.
- Task lifecycle and hooks system.

# Install

```php
composer require 'toplan/task-balancer:~0.5'
```

# Usage

```php
//define a task
Balancer::task('task1', function($task){
    //define a driver for current task like this:
    $task->driver('driver_1 100 backup', function ($driver, $data) {
            //do something here
            ...
            //set whether run success/failure at last
            if ($success) {
                $driver->success();
            } else {
                $driver->failure();
            }
            //return some data you need
            return 'some data here';
        });

    //or like this:
    $task->driver('driver_2', 90, function ($driver, $data) {
            //...same as above..
        })->data(['this is data 2']);

    //or like this:
    $task->driver('driver_3')
        ->weight(0)->backUp()
        ->data(['this is data 3'])
        ->work(function ($driver, $data) {
            //...same as above..
        });
});

//run the task
$result = Balancer::run('task1');
```

The `$result` structure:
```php
[
    'success' => true,
    'time' => [
        'started_at' => timestamp,
        'finished_at' => timestamp
    ],
    'logs' => [
        '0' => [
            'driver' => 'driver_1',
            'success' => false,
            'time' => [
                'started_at' => timestamp,
                'finished_at' => timestamp
            ],
            'result' => 'some data here'
        ],
        ...
    ]
]
```

# API

## 1. Create & Run

### Balancer::task($name[, $data], Closure $ready);

Create a task instance, and return it.

```php
Balancer::task('taskName', $data, function($task){
    //task`s ready work, such as create drivers.
});
```

> `$data` will store in the task instance.

### Balancer::run($name[, array $options])

Run the task by name, and return the result data.

The keys of `$options`:
- `data`
- `driver`

### $task->data($data)

Set the data of task.

### $task->driver($config[, $weight][, 'backup'], Closure $work)

Create a driver for the task.

> Expected `$weight` to be a integer, default `1`.

```php
$task->driver('driverName 80 backup', function($driver, $data){
    //driver`s work
});
```

### $driver->weight($weight)

Set the weight value of driver.

### $driver->backup($is)

Whether the backup driver.

> Expected `$is` to be boolean.

### $driver->data($data);

Set the data of driver.

> `$data` will store in driver instance.

### $driver->work(Closure $work function($driver, $data){});

Set the work of driver, which will been called with two arguments: `$driver`, `$data`.

> `$data` equals to `$driver->getData()`

### $driver->failure()

Set current driver run failed.

### $driver->success()

Set current driver run succeed.

### $driver->getDriverData()

Get the data of driver.

### $driver->getTaskData()

Get the data of task.


## 2. Lifecycle & Hooks

> Support multiple handlers for every hooks!

### Hooks

| Hook name | handler arguments | influence of the last handler's return value |
| --------- | :----------------: | :-----: |
| beforeCreateDriver | $task, $props, $index, &$handlers, $prevReturn | if an array will be merged to original props |
| afterCreateDriver | $task, $driver, $index, &$handlers, $prevReturn | - |
| beforeRun | $task, $index, &$handlers, $prevReturn | if `false` will stop run task and return `false` |
| beforeDriverRun | $task, $driver, $index, &$handlers, $prevReturn | if `false` will stop to use current driver and try to use next backup driver |
| afterDriverRun | $task, $driverResult, $index, &$handlers, $prevReturn | - |
| afterRun | $task, $taskResult, $index, &$handlers, $prevReturn | if not boolean will override result value |

### Usage

* $task->hook($hookName, $handler, $override)

* $task->beforeCreateDriver($handler, $override)

* $task->afterCreateDriver($handler, $override)

* $task->beforeRun($handler, $override)

* $task->beforeDriverRun($handler, $override)

* $task->afterDriverRun($handler, $override)

* $task->afterRun($handler, $override)

> `$override` default `false`.

```php
//example
$task->beforeRun(function($task, $index, $handlers, $prevReturn){
    //what is $prevReturn?
    echo $prevReturn == null; //true
    //what is $index?
    echo $index == 0; //true
    //what is $handlers?
    echo count($handlers); //2
    //do something..
    return 'beforeRun_1';
}, false);

$task->beforeRun(function($task, $index, $handlers, $prevReturn){
    //what is $prevReturn?
    echo $prevReturn == 'beforeRun_1'; //true
    //what is $index?
    echo $index == 1; //true
    //what is $handlers?
    echo count($handlers); //2
    //do other something..
}, false);
```

# Dependents

- [phpsms](https://github.com/toplan/phpsms)

# License

MIT
