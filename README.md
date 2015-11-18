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
    composer require 'toplan/task-balancer:~0.1.1'
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

###1. Balancer::task($name [, $data], $work);

create a task instance, return task instance.

```php
Balancer::task('taskName', $data, function($task){
    //task`s ready work, like create drivers
});
```

**note:** `$data` will store in task instance.

###2. Balancer::run($taskName [, $data])

run the task, and return a results array.
**note:** `$data` will override data which in task instance.

###3. $task->driver($optionString [, $weight] [, 'backup'], $work);

create a driver instance for `$task`, return driver instance.

**note:** `$weight` must be a integer, default value is '1'

```php
$task->driver('driverName 80 backup', function($driver, $data){
    //driver`s work
    //$driver is driver instance
});
```

###4. $driver->weight($weight)

set driver`s weight, return current driver,
supported chain operation.

**note:** `$weight` must be a integer

###5. $driver->backup($is)

set driver is backup, return current driver,
supported chain operation.

**note:** `$is` must be true of false

###6. $driver->data($data);

set data for driver`s work use,
support chain operation.

**note:** `$data` will store in driver instance.

###7. $driver->work(function($driver, $data){});

set driver work, give two arguments: `$driver` and `$data`,
support chain operation.

**note:** `$data` is try to get from driver instance,
if null will continue try to get from task instance.

###8. $driver->failed()

set current driver run failed,
support chain operation.

###9. $driver->success()

set current driver run successful.
support chain operation.

###10. $driver->getDriverData()

get data from driver instance.

###11. $driver->getTaskData()

get data from task instance.

###12. $task->setData()

set data to task instance, and override old data!

## Task Lifecycle

| Hook name | handler arguments | handler return value |
| --------- | :----------------: | :-----: |
| beforeCreateDriver | $task | on effect |
| afterCreateDriver | $task | on effect |
| beforeRun | $task | if `false` will stop run task and return `false` |
| beforeDriverRun | $task | no effect |
| afterDriverRun | $task | no effect |
| afterRun | $task, $results | override run task`s results data |

###1. $task->hook($hookName, $handler);

###2. $task->beforeCreateDriver($handler);

###3. $task->afterCreateDriver($handler);

###4. $task->beforeRun($handler);

###5 $task->beforeDriverRun($handler)

###6 $task->afterDriverRun($handler)

###7. $task->afterRun($handler);


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
