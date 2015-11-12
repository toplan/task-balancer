# task-balancer
task load balancer for php (like the nginx load balancing)

# Usage

```php
//define a task
Balancer::task('task1', function($task){

    //define a driver for `task1` like this
    $task->driver('driver1') //create a driver instance named 'driver1'
         ->weight(100) //driver weight
         ->backUp() //this driver is a back up driver
         ->data(['this is data 1']) //data for driver work
         ->work(function($driver, $data){ //define driver`s work
                    $driver->failed();
                    $msg = 'working! by '.$driver->name.'<br>';
                    print_r($msg);
                    return [$msg, $data];
                });

    //or
    $task->driver('driver2')
         ->weight(3)->backUp(false)
         ->data(['this is data 2'])
         ->work(function($driver, $data){
                    $driver->success();
                    $msg = 'working! by '.$driver->name.'<br>';
                    print_r($msg);
                    return [$msg, $data];
                });

    //or
    $task->driver('driver3')
         ->weight(0)->backUp()
         ->data(['this is data 3'])
         ->work(function($driver, $data){
                    $driver->failed();
                    $msg = 'working! by '.$driver->name.'<br>';
                    print_r($msg);
                    return [$msg, $data];
                });
});

//run task
$result = Balancer::run('task1');
```

#


# Todo

- [x] remember every tasks` start time and end time.
- [x] remember every drivers` start time and end time.
- [ ] smart parse driver`s create arguments in task class
- [ ] define task`s lifecycle
