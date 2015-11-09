# task-balancer
task load balancer for php (like the nginx load balancing)

# Usage

```php
//define tas
Balancer::task('task1', function($task){
    //define drive
    $task->driver('driver1')
         ->weight(3)->backUp()
         ->data(['this is data 1'])
         ->worker(function($driver, $data){
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver1 working', $data];
                });

    //define drive
    $task->driver('driver2')
         ->weight(3)->backUp(false)
         ->data(['this is data 2'])
         ->worker(function($driver, $data){
                    $driver->success();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver2 working', $data];
                });

    //define drive
    $task->driver('driver3')
         ->weight(0)->backUp()
         ->data(['this is data 3'])
         ->worker(function($driver, $data){
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver3 working', $data];
                });
});

//run task
$result = Balancer::run('task1');
```
# Todo

- [ ] remember every drivers` start time and end time.
- [ ] driver`s create arguments in task class (smart parse arguments)
- [ ] define task`s lifecycle
