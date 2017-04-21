<?php
declare(ticks = 1); 

$callbacks = array();

pcntl_signal(SIGPIPE, function ($signal) {
	global $process, $pipes, $callbacks;

//	echo 'HANDLE SIGNAL '.$signal.PHP_EOL;
	$output = stream_get_contents($pipes[1]);
	if(!empty($output)) {
		echo 'Output:'.PHP_EOL.$output.PHP_EOL.'----------'.PHP_EOL;
		$output = json_decode('['.str_replace(PHP_EOL, ',', trim($output)).']', true);
		foreach($output as $outp) {
			if(!empty($outp["func"]) && !empty($outp["args"])) {
				call_user_func_array($callbacks[$outp["func"]], $outp["args"]);
			}
		}
	}
});

$descriptorspec = array(
   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
   2 => array("pipe", "w")   // stdout is a pipe that the child will write to
);
$module = 'rejigger';

$process = proc_open('node mod_wrapper.js --module '.$module.' --pid '.getmypid(), $descriptorspec, $pipes, getcwd(), array());
stream_set_blocking($pipes[0], false);
stream_set_blocking($pipes[1], false);
stream_set_blocking($pipes[2], false);

/*for($i = 0; $i < 15; $i++) {
	echo $i.PHP_EOL;
	usleep(500000);
}*/
if (is_resource($process)) {

	$output = stream_get_contents($pipes[1]);
	print_r($output);
	

	function asyncprint($str, $time, $callback) {
		global $pipes, $callbacks;
		$callbacks[] = $callback;
		fwrite($pipes[0], json_encode(array(
			"func" => "asyncprint",
			"args" => array($str, $time),
			"callback"=> array_keys($callbacks)[count($callbacks) -1 ])).PHP_EOL);
	}

	asyncprint('foobar', 500, function($data) {
		print_r($data);
		echo PHP_EOL;
	});
	asyncprint('foobaz', 1000, function($data) {
		print_r($data);
		echo PHP_EOL;
	});
	asyncprint('fozbarz', 2000, function($data) {
		print_r($data);
		echo PHP_EOL;
	});
    //fwrite($pipes[0], '{"func":"log","args":[1,2,3,"foo","bar"]}');
    //echo stream_get_contents($pipes[1]);
}

while(1) {
	usleep(10000);
}
/*
fclose($pipes[0]);
fclose($pipes[1]);
echo "command returned $return_value\n";
$return_value = proc_close($process);*/

?>