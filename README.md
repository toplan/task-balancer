# task-balancer
task load balancer for php (like the nginx load balancing)

# Usage

```php
//define tas
Balancer::task('task1', function($task){
    //define driver
    $task->driver('driver1')
         ->weight(3)->backUp()
         ->data(['this is data 1'])
         ->work(function($driver, $data){
                    $driver->failed();
                    $msg = 'working! by '.$driver->name.'<br>';
                    print_r($msg);
                    return [$msg, $data];
                });

    //define driver
    $task->driver('driver2')
         ->weight(3)->backUp(false)
         ->data(['this is data 2'])
         ->work(function($driver, $data){
                    $driver->success();
                    $msg = 'working! by '.$driver->name.'<br>';
                    print_r($msg);
                    return [$msg, $data];
                });

    //define driver
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
# Todo

- [ ] remember every tasks` start time and end time.
- [ ] remember every drivers` start time and end time.
- [ ] driver`s create arguments in task class (smart parse arguments)
- [ ] define task`s lifecycle
