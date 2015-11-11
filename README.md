# task-balancer
task load balancer for php (like the nginx load balancing)

# Usage

```php
//define task
Balancer::task('task1', function($task){
    //define a driver
    $task->driver('driver_1 100 backup', function($driver, $data){
                    $person = new Person($data['name'], $data['age']);
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver1 working', $person->toString()];
                });

    //define a driver
    $task->driver('driver_2', 100, function($driver, $data){
             $driver->failed();
             print_r('run work! by '.$driver->name.'<br>');
             return ['test.driver2 working', $data];
         })
         ->data(['this is data 2']);

    //define a driver
    $task->driver('driver_3')
         ->weight(0)->backUp()
         ->data(['this is data 3'])
         ->work(function($driver, $data){
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver3 working', $data];
                });
});

//run task
$result = Balancer::run('task1');
```
# Todo

- [x] remember every tasks` start time and end time.
- [x] remember every drivers` start time and end time.
- [x] smart parse driver`s create arguments in task class
- [ ] define task`s lifecycle and hook
- [ ] pause and continue task
