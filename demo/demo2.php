<?php

//test multiple handlers

//require('../vendor/autoload.php');
require '../src/TaskBalancer/Balancer.php';
require '../src/TaskBalancer/Driver.php';
require '../src/TaskBalancer/Task.php';
require '../src/TaskBalancer/TaskBalancerException.php';

use Toplan\TaskBalance\Balancer;

//define task:
Balancer::task('task1', $data, function ($task) {
    $task->driver('driver1 10 backup', function ($driver, $data) {
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                });

    $task->beforeRun(function ($task, $preReturn, $index, $handlers) {
        print_r("before run ---$preReturn-----$index<br>");

        return 11;
    });

    $task->beforeRun(function ($task, $preReturn, $index, $handlers) {
        print_r("before run ---$preReturn-----$index<br>");

        return 22;
    }, false);

    $task->beforeRun(function ($task, $preReturn, $index, $handlers) {
        print_r("before run ---$preReturn-----$index<br>");
    });

    $task->hook('beforeDriverRun', function ($task, $preReturn, $index, $handlers) {
        print_r("before driver run ---$preReturn-----$index<br>");

        return [1];
    });

    $task->hook('beforeDriverRun', function ($task, $preReturn, $index, $handlers) {
        print_r('before driver run ---'.implode(',', $preReturn)."-----$index<br>");

        return [1, 2];
    }, true);

    $task->hook('beforeDriverRun', function ($task, $preReturn, $index, $handlers) {
        print_r('before driver run ---'.implode(',', $preReturn)."-----$index<br>");

        return [1, 2, 3];
    });

    $task->afterRun(function ($task, $results, $preReturn, $index, $handlers) {
        print_r('after run --------!<br>');
    });
});

//run task:
$result = Balancer::run('task1', $data);

print_r('<br>');
print_r($result);
