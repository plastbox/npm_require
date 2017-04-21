<?php
declare(ticks = 1); 

class npm_module {
	private $pipes;
	private $props = array();
	private $callbacks = array();

	function __construct($modulename) {

		pcntl_signal(SIGPIPE, function ($signal) use ($modulename) {
			//echo 'HANDLE SIGNAL '.$signal.PHP_EOL;
			$output = stream_get_contents($this->pipes[1]);
			if(!empty($output)) {
				//echo 'Output:'.PHP_EOL.$output.PHP_EOL.'----------'.PHP_EOL;
				$output = json_decode('['.str_replace(PHP_EOL, ',', trim($output)).']', true);
				foreach($output as $outp) {
					if(!empty($outp["func"]) && !empty($outp["args"])) {
						call_user_func_array($this->callbacks[$outp["func"]], $outp["args"]);
					}
				}
			}
		});

		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
			1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
			2 => array("pipe", "w")   // stdout is a pipe that the child will write to
		);

		$this->process = proc_open('node mod_wrapper.js --module '.$modulename.' --pid '.getmypid(), $descriptorspec, $this->pipes, getcwd(), array());
		stream_set_blocking($this->pipes[0], false);
		stream_set_blocking($this->pipes[1], false);
		stream_set_blocking($this->pipes[2], false);

		if (is_resource($this->process)) {
			//echo 'Child process started'.PHP_EOL;

			while(!($output = stream_get_contents($this->pipes[1]))) {
				usleep(100000);
			}
			//echo 'Output:'.PHP_EOL.$output.PHP_EOL.'----------'.PHP_EOL;
			$output = json_decode('['.str_replace(PHP_EOL, ',', trim($output)).']', true);
			foreach($output as $outp) {
				foreach($outp["properties"] as $prop) {
					list($name, $val) = explode(':', trim($prop));
					if($name === 'self') {
						$this->props['self'] = function() use ($modulename, $name) {
							$args = json_encode(func_get_args());
							$args = substr($args, 1, strlen($args) - 2);
							
							$ret = json_decode(trim(shell_exec('nodejs -e "var m=require(\"'.$modulename.'\");'.
								'console.log(JSON.stringify(m('.addslashes($args).')));"'.PHP_EOL)));
							return $ret;
						};
					}
					elseif($val === 'function') {
						//echo 'Alias function "'.$name.'" in module "'.$modulename.'"'.PHP_EOL;
						$this->props[$name] = function() use ($modulename, $name) {
							$args = func_get_args();
							if(is_object($args[count($args)-1]) && ($args[count($args)-1] instanceof Closure)) {
								$callback = array_pop($args);
								$this->callbacks[] = $callback;
								fwrite($this->pipes[0], json_encode(array(
										"func" => "asyncprint",
										"args" => $args,
										"callback"=> array_keys($this->callbacks)[count($this->callbacks) -1 ]
									)).PHP_EOL);
							}
						};
					}
					else {
						$this->props[$name] = $val;
					}
				}
			}
		}
	}

	function __call($name, $arguments) {
		if(isset($this->props[$name])) {
			return call_user_func_array($this->props[$name], $arguments);
		}
	}
	function __get($name) {
		if(isset($this->props[$name])) {
			return $this->props[$name];
		}
	}

	function __destruct() {
		fclose($this->pipes[0]);
		fclose($this->pipes[1]);
		$return_value = proc_close($this->process);
		echo "command returned $return_value\n";
	}
}
function npm_require($modulename) {
	return new npm_module($modulename);
}

$rejigger = npm_require('rejigger');

$rejigger->asyncprint('foobar', 500, function($data) {
	print_r($data);
	echo PHP_EOL;
});
$rejigger->asyncprint('foobaz', 1000, function($data) {
	print_r($data);
	echo PHP_EOL;
});
$rejigger->asyncprint('fozbarz', 2000, function($data) {
	print_r($data);
	echo PHP_EOL;
});

$i = 0;
while(1) {
	echo ($i++).PHP_EOL;
	usleep(100000);
	if($i === 30) die();
}