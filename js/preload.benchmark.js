/*!
 * Author: Blake Regalia - blake.regalia@gmail.com
 *
 * Copyright 2012 Blake Regalia
 * Released under the MIT license
 * http://opensource.org/licenses/mit-license.php
 *
 */

// Benchmark
(function() {
	var date = Date;
	var now = function() {
		return (new date()).getTime();
	};
	
	var event = {};
	var order = [];
	
	var fancy = false;
	var online = false;
	var localhostOnline = false;
	
	var time_form = 'ms';
	var format = function(time) {
		if(isNaN(time)) return time;
		var str = false;
		switch(time_form) {
			case 'ms':
				str = time+'ms';
				break
			case 's':
				str = (time/1000)+'s';
		}
		if(str === false) return str;
		if(fancy) {
			var p = function(){};
			p.toString = function(){return str;};
			return p;
		}
		return str;
	};
	
	var log_file = [];
	var save_log = function() {
		if(console && console.info) console.info.apply(console, arguments);
		if(online) {
			var tmp = '';
			for(var i=0; i!==arguments.length; i++) {
				tmp += arguments[i];
			}
			log_file.push(tmp);
		}
	}
	
	var global = window['Benchmark'] = {
		start: function(key) {
			if(event[key]) {
				delete event[key];
			}
			event[key] = {
				start: now(),
				marks: [],
			};
		},
		stop: function(key, action) {
			if(arguments.length < 2) action = 'complete';
			var bench = event[key];
			if(bench) {
				bench.stop = {
					time: now(),
					action: action,
				};
				var ms = bench.stop.time-bench.start;
				save_log(key,' took ',format(ms),' to ',action);
				return ms;
			}
		},
		mark: function(what, since) {
			var bench = event[since];
			if(bench) {
				var mark = now()-bench.start;
				bench.marks.push({
					after: mark,
					what: what,
					
				});
				save_log(what,' took ',format(mark),' since ',since,' started');
			}
		},
		save: function(as) {
			if(!online) return;
			
			var framework_xhr_method = false;
			if(typeof dojo !== 'undefined' && dojo) {
				framework_xhr_get_method = function(o) {
					dojo['xhrGet'](o);
				};
			}
			
			if(framework_xhr_get_method) {
				var log = (new Date()).toString()+'\n'+log_file.join('\n');
				var urlstr = 'benchmark.php?save='+as+'&log='+encodeURIComponent(log);
				framework_xhr_get_method({
					url: urlstr,
				});
			}
			log_file.length = 0;
		},
		toString: function() {
			return 'Benchmark()';
		},
		setFormat: function(time_format) {
			switch(time_format.toLowerCase()) {
				case 's': time_form = 's'; break;
				case 'ms': time_form = 'ms'; break;
			}
		},
		setFancy: function(bool) {
			fancy = !!bool;
		},
		highlight: function(text) {
			var p = function(){};
			p.toString = function(){return text;};
			return p;
		},
	};
	
	Benchmark.setFancy(true);
	Benchmark.setFormat('ms');
	Benchmark.start('all scripts');
	
	if(window.location.host !== '') {
		if(localhostOnline || window.location.host !== 'localhost') {
			online = true;
		}
	}
})();