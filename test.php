<?php
include('npm_require.php');

$aggsy = npm_require('aggsy');

$cars = array(
	array('model'=> 'volvo', 'km'=> 100),
	array('model'=> 'tesla', 'make'=> 's', 'km'=> 200),
	array('model'=> 'tesla', 'make'=> 's', 'km'=> 120)
);

$aggsy('model(_sum(km),_count())', $cars);

echo 'Test: '.$aggsy->test(4, 5).PHP_EOL.$aggsy->foo.PHP_EOL.$aggsy->bar.PHP_EOL;
?>