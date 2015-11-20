# Intro
lightweight and powerful task load balancing for php

> like the nginx load balancing :)

# Features

- Support multiple drives for every task.
- Automatically choose a driver to execute task by drivers` weight value.
- Support multiple backup drivers.
- task lifecycle and hooks.

# Install

```php
    composer require 'toplan/task-balancer:~0.1.2'
```

# Usage

```php
//define a task
Balancer::task('task1', function($task){

    //define a driver for current task like this:
    $task->driver('driver_1 100 backup', function($driver, $data){
                    //do something here
                    ...
                    //set whether run success/failed at last
                    if ($success) {
                        $driver->success();
                    } else {
                        $driver->failed();
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

# API

## Create & Run

### Balancer::task($name [, $data], $work);

create a task instance, return task instance.

```php
Balancer::task('taskName', $data, function($task){
    //task`s ready work, like create drivers
});
```

> `$data` will store in task instance.

### Balancer::run($taskName [, $data])

run the task, and return a results array.
> `$data` will override data which in task instance.

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

### $driver->failed()

set current driver run failed,
support chain operation.

### $driver->success()

set current driver run successful.
support chain operation.

### $driver->getDriverData()

get data value of driver instance.

### $driver->getTaskData()

get data value of task instance.


## Task Lifecycle

| Hook name | handler arguments | handler return value |
| --------- | :----------------: | :-----: |
| beforeCreateDriver | $task | no effect |
| afterCreateDriver | $task | no effect |
| beforeRun | $task | if `false` will stop run task and return `false` |
| beforeDriverRun | $task | no effect |
| afterDriverRun | $task | no effect |
| afterRun | $task, $results | override run task`s results data |

### $task->hook($hookName, $handler);

### $task->beforeCreateDriver($handler);

### $task->afterCreateDriver($handler);

### $task->beforeRun($handler);

### $task->beforeDriverRun($handler)

### $task->afterDriverRun($handler)

### $task->afterRun($handler);


# Todo

- [x] remember every task`s start time and end time.
- [x] remember every driver`s start time and end time.
- [x] smart parse arguments of method `driver()`.
- [x] task lifecycle and hooks
- [ ] pause/resume task

# Dependents

- [phpsms](https://github.com/toplan/phpsms)

# License

MIT
