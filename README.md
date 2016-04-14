# Intro
lightweight and powerful task load balancing for php

> like the `nginx` load balancing :)

# Features

- Support multiple drives for every task.
- Automatically choose a driver to execute task by drivers` weight value.
- Support multiple backup drivers.
- Task lifecycle and hooks system.

# Install

```php
    composer require 'toplan/task-balancer:~0.4.2'
```

# Usage

```php
//define a task
Balancer::task('task1', function($task){

    //define a driver for current task like this:
    $task->driver('driver_1 100 backup', function($driver, $data){
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
    $task->driver('driver_2', 90, function($driver, $data){
                //...same as above..
         })->data(['this is data 2']);

    //or like this:
    $task->driver('driver_3')
         ->weight(0)->backUp()
         ->data(['this is data 3'])
         ->work(function($driver, $data){
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

### Balancer::task($name [, $data], $work);

create a task instance, return task instance.

```php
Balancer::task('taskName', $data, function($task){
    //task`s ready work, like create drivers
});
```

> `$data` will store in task instance.

### Balancer::run($taskName [, array $opts])

run the task, and return a result array.

The $opts value:
* $opts['data']
* $opts['driver']

### $task->data($data)

set the data value of task instance, will override origin data.

### $task->driver($optionString [, $weight] [, 'backup'], $work);

create a driver instance for `$task`, return driver instance.

> `$weight` must be a integer, default value is '1'

```php
$task->driver('driverName 80 backup', function($driver, $data){
    //driver`s work
    //$driver is driver instance
});
```

### $driver->weight($weight)

set driver`s weight, return current driver,
supported chain operation.

> `$weight` must be a integer

### $driver->backup($is)

set driver is backup, return current driver,
supported chain operation.

> `$is` must be true of false

### $driver->data($data);

set the data value of driver instance,
support chain operation.

> `$data` will store in driver instance.

### $driver->work(function($driver, $data){});

set driver work, give two arguments: `$driver` and `$data`,
support chain operation.

> `$data` is a value try to get from driver instance,
if null will continue try to get from task instance.
>
> `$data` equals to `$driver->getData()`

### $driver->failure()

set current driver run failed,
support chain operation.

### $driver->success()

set current driver run successful.
support chain operation.

### $driver->getDriverData()

get data value of driver instance.

### $driver->getTaskData()

get data value of task instance.


## 2. Task Lifecycle

> support multiple handlers for every hook!

###Hooks Table

| Hook name | handler arguments | influence of the last handler`s return value |
| --------- | :----------------: | :-----: |
| beforeCreateDriver | $task, $preReturn, $index, $handlers | no effect |
| afterCreateDriver | $task, $preReturn, $index, $handlers | no effect |
| beforeRun | $task, $preReturn, $index, $handlers | if `false` will stop run task and return `false` |
| beforeDriverRun | $task, $driver, $preReturn, $index, $handlers | if `false` will stop to use current driver and try to use next backup driver |
| afterDriverRun | $task, $driverResult, $preReturn, $index, $handlers | no effect |
| afterRun | $task, $taskResult, $preReturn, $index, $handlers | if not boolean will override result value |

###Use Hooks

> `$override` default value is `false`, if `true` will override hook handlers.

* $task->hook($hookName, $handler, $override)

* $task->beforeCreateDriver($handler, $override)

* $task->afterCreateDriver($handler, $override)

* $task->beforeRun($handler, $override)

* $task->beforeDriverRun($handler, $override)

* $task->afterDriverRun($handler, $override)

* $task->afterRun($handler, $override)


```php
//example
$task->beforeRun(function($task, $preReturn, $index, $handlers){
    //what is $preReturn?
    $preReturn == null; //true
    //what is $index?
    $index == 0; //true
    //what is $handlers?
    echo count($handlers); //2
    //do something..
    return 'beforeRun_1';
}, false);

$task->beforeRun(function($task, $preReturn, $index, $handlers){
    //what is $preReturn?
    $preReturn == 'beforeRun_1'; //true
    //what is $index?
    $index == 1; //true
    //what is $handlers?
    echo count($handlers); //2
    //do other something..
}, false);
```

# Todo

- [x] remember every task`s start time and end time.
- [x] remember every driver`s start time and end time.
- [x] smart parse arguments of method `driver()`.
- [x] task lifecycle and hooks
- [ ] hot remove/add a driver.
- [ ] pause/resume task

# Dependents

- [phpsms](https://github.com/toplan/phpsms)

# License

MIT
