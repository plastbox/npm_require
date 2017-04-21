var argv = require('minimist')(process.argv.slice(2));
var mod = require(argv.module);

//console.log('argv:', argv);
//process.kill(argv.pid, 'SIGPIPE');

/*(function() {
	var i = 0,
		tmr = setInterval(() => {
		i++;
		console.log(JSON.stringify({func: 'callback_0', args:[i]}));
		process.kill(argv.pid, 'SIGPIPE');
		if(i === 3) {
			clearInterval(tmr);
		}
	}, 1000);
})();*/

if(typeof mod === 'function') {
	var tmp = {
		log: console.log,
		asyncprint: function(val, time, cb) {
			setTimeout(() => {
				cb('Returned: ' + val + ' after ' + time + ' ms');
			}, time);
		}
	};
	tmp[argv.module] = mod;
	mod = tmp;
	//console.log('self', 'function');
}
console.log(JSON.stringify({properties: Object.keys(mod).map((key) => {return key + ':' + typeof mod[key];})}));

/*Object.keys(mod).forEach((key) => {
	console.log(key, typeof mod[key]);
});*/

var rl = require('readline').createInterface({
	input: process.stdin,
	output: process.stdout,
	terminal: false
});

rl.on('line', function(line){
	try {
		data = JSON.parse(line);
	} catch(e) {
		console.error(e);
		return false;
	}
	//console.log('Data from STDIN:', data);
	//if(argv.pid) process.kill(argv.pid, 'SIGPIPE');

	if(typeof mod[data.func] === 'function') {

		if(!(data.args instanceof Array)) {
			data.args = [data.args];
		}
		data.args.push(function() {
			console.log(JSON.stringify({func: data.callback, args:arguments}));
			if(argv.pid) process.kill(argv.pid, 'SIGPIPE');
		});

		console.log(JSON.stringify({
			func: data.func,
			ret: mod[data.func].apply(this, data.args) || null
		}));
		if(argv.pid) process.kill(argv.pid, 'SIGPIPE');
	}
});

//console.log(JSON.stringify({"func":"log", "args":[1,2,3,'foo', 'bar']}));

//process.kill(process.pid, 'SIGINT');