<?php
require_once('../src/TaskBalancer/Balancer.php');
require_once('../src/TaskBalancer/Task.php');
require_once('../src/TaskBalancer/Driver.php');

use Toplan\TaskBalance\Balancer;
use Toplan\TaskBalance\Task;
use Toplan\TaskBalance\Driver;

//define task:
$t = Balancer::task('test1', function($task){
    $task->driver('driver1')
         ->weight(100)->backUp()
         ->data(['this is data 1'])
         ->work(function($driver, $data){
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver1 working', $data];
                });
    $task->driver('driver2')
         ->weight(80)
         ->data(['this is data 2'])
         ->work(function($driver, $data){
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver2 working', $data];
                });
    $task->driver('driver3')
         ->weight(0)->backUp()
         ->data(['this is data 3'])
         ->work(function($driver, $data){
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver3 working', $data];
                });
});

//run task:
$result = Balancer::run('test1');

print_r('<br>resuts data:<br>');
var_dump($result);