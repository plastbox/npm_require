# npm_require
Allows the use of npm modules in php.

Usage
-----
Download and include **npm_require.php**, and use the function *npm_require()* to include an npm module. The returned object should contain all the methods/properties you'd expect if you were using it in node.js

Example
-------
Using [reminyborg/aggsy](https://github.com/reminyborg/aggsy) and its usage example.

```php
<?php
include('npm_require.php');

$aggsy = npm_require('aggsy');

$cars = array(
	array('model'=> 'volvo', 'km'=> 100),
	array('model'=> 'tesla', 'make'=> 's', 'km'=> 200),
	array('model'=> 'tesla', 'make'=> 's', 'km'=> 120)
);

print_r($aggsy('model(_sum(km),_count())', $cars));

/* Outputs:
stdClass Object
(
    [volvo] => stdClass Object
        (
            [_sum(km)] => 100
            [_count()] => 1
        )

    [tesla] => stdClass Object
        (
            [_sum(km)] => 320
            [_count()] => 2
        )

)
*/
```
