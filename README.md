# task-balancer
task load balancer for php (like the nginx load balancing)

# Usage

```php
//define a task
Balancer::task('task1', function($task){

    //define a driver for current task like this:
    $task->driver('driver_1 100 backup', function($driver, $data){
                    $person = new Person($data['name'], $data['age']);
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver1 working', $person->toString()];
                });

    //or like this:
    $task->driver('driver_2', 90, function($driver, $data){
             $driver->failed();
             print_r('run work! by '.$driver->name.'<br>');
             return ['test.driver2 working', $data];
         })
         ->data(['this is data 2']);

    //or like this:
    $task->driver('driver_3 ')
         ->weight(0)->backUp()
         ->data(['this is data 3'])
         ->work(function($driver, $data){
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver3 working', $data];
                });
});

//run the task
$result = Balancer::run('task1');
```

# Method

1. Balancer::task($taskName, [$data, ] $work);

create a task instance, return task instance.

```php
Balancer::task('taskName', $data, function($task){
    //task`s work
});
```

2. Balancer::run($taskName)

run the task, return a results array.

3. $task->driver($optionString, [$weight, 'backup', ] $work);

create a driver instance for `$task`, return driver instance.

**note:**$weight must be a integer

```php
$task->driver('driverName 80 backup', function($driver, $data){
    //driver`s work
    //$driver is driver instance
});
```

4. $driver->weight($weight)

set driver`s weight, return current driver,
supported chain operation.

**note:**$weight must be a integer

5. $driver->backup($is)

set driver is backup, return current driver,
supported chain operation.

**note:**$is must be true of false

6. $driver->data($data);

set data for driver`s work use,
support chain operation.

7. $driver->work(function($driver, $data){});

set driver`s work, give two arguments: $driver and $data,
support chain operation.

8. $driver->failed()

set current driver run failed,
support chain operation.

9. $driver->success()

set current driver run successful.
support chain operation.

10. $driver->getDriverData()

get data from driver.

11. $driver->getTaskData()

get data from task.

# Todo

- [x] remember every tasks` start time and end time.
- [x] remember every drivers` start time and end time.
- [x] smart parse driver`s create arguments in task class
- [ ] define task`s lifecycle and hook
- [ ] pause and continue task
