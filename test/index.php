<?php
require_once('../src/TaskBalancer/Balancer.php');
require_once('../src/TaskBalancer/Task.php');
require_once('../src/TaskBalancer/Driver.php');

use Toplan\TaskBalance\Balancer;
use Toplan\TaskBalance\Task;
use Toplan\TaskBalance\Driver;

//define task:
$data = [
    'name' => 'top$',
    'age'  => '25!'
];
$t = Balancer::task('test1', $data, function($task){
    $task->driver('driver1')
         ->weight(100)->backUp()
         ->work(function($driver, $data){
                    $person = new Person($data['name'], $data['age']);
                    $driver->failed();
                    print_r('run work! by '.$driver->name.'<br>');
                    return ['test.driver1 working', $person->toString()];
                });

    $task->driver('driver2')
         ->weight(100)
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
$data['age'] = '25###';
$result = Balancer::run('test1', $data);

print_r('<br>resuts data:<br>');
var_dump($result);
print_r('<hr>task data:<br>');
var_dump($t);

class Person {

    protected  $name;

    protected  $age;

    public function __construct($name, $age)
    {
        $this->name = $name;
        $this->age = $age;
    }

    public function toString()
    {
        return "hi, I am $this->name, and $this->age year old";
    }
}