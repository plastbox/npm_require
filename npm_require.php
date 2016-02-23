<?php
function npm_require($modulename) {
	return new npm_module($modulename);
}

class npm_module {
	private $props = array();
	function __construct($modulename) {
		$mod = shell_exec('nodejs -e "var m=require(\"'.$modulename.'\");'.
			'if(typeof m===\"function\"){console.log(\"self function\");}'.
			'Object.keys(m).forEach(function(k){console.log(k, m[k]);})"'.PHP_EOL);
		
		$props = explode("\n", trim($mod));
		
		foreach($props as $prop) {
			list($name, $val) = explode(' ', trim($prop));
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
				$this->props[$name] = function() use ($modulename, $name) {
					$args = json_encode(func_get_args());
					$args = substr($args, 1, strlen($args) - 2);
					$ret = json_decode(trim(shell_exec('nodejs -e "var m=require(\"'.$modulename.'\");'.
						'console.log(m[\''.$name.'\']('.addslashes($args).'));"'.PHP_EOL)));

					return $ret;
				};
			}
			else {
				$this->props[$name] = $val;
			}
		}
		
		return $mod;
	}
	
	function __invoke() {
		if(isset($this->props['self'])) {
			return call_user_func_array($this->props['self'], func_get_args());
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
}
?>