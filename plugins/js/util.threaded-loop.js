/**
* public class ThreadedLoop

* @description  Runs a user-defined loop-like function in multiple iterations to avoid hanging the 
* 		main thread. This essentially accomplishes threading.
* @author		Blake Regalia
* @email		blake.regalia@gmail.com
*
**/
(function() {
	var __func__ = 'ThreadedLoop';
	var construct = function(program, opt) {
		var cycle_time = opt.cycleTime || 10;
		var breathe_time = opt.breatheTime || 0;
		var is_running;
		var timer;
		var inter_cycle = 0;
		
		var initial_loop_data = opt.data || {};
		var exec_on_start = opt.beforeStart || function(){};
		
		var methods = {};
		
		// begin a cycle of the loop
		var start = function() {
			inter_cycle = 0;
			timer = new Timer(cycle_time);
			program.apply(self, arguments);
		};
		
		var self = {
			data: {},
			
			// returns true while the loop should continue
			runs: function() {
				// if the loop was cancelled, return false
				if(!is_running) {
					return false;
				}
				
				// while the loop is running...
				else {
					// update the timer
					timer();
					
					// and return the status of this cycle
					return !timer.expired;
				}
			},
			
			isRunning: function() {
				return is_running;
			},
			
			
			// continue the loop in a new thread
			cycle: function() {
				
				// if this loop is still alive
				if(is_running) {
					
					// start the next thread
					inter_cycle = setTimeout(start, breathe_time);
				}
			},
			
			// terminate the loop
			die: function() {
				is_running = false;
			},
		};
		
		var operator = function() {
			// set the flag that this loop is in progress
			is_running = true;
			
			// initialize the loop data
			delete self.data;
			self.data = $.extend(true, {}, initial_loop_data);
			exec_on_start.apply(self);
			self.data.start_time = (new Date()).getTime();
			
			// start the loop in a thread
			setTimeout(start, 0);
		};
		$.extend(operator, {
			
			// sets the cycle duration
			// longer cycles execute faster, shorter cycles allow for better chance of interuption
			setCycleTime: function(ms) {
				cycle_time = ms;
			},
			
			setSleepTime: function(ms) {
				sleep_time = ms;
			},
			
			setLoopData: function(data) {
				initial_loop_data = data;
			},
			
			onStart: function(exec) {
				exec_on_start = exec;
			},
			
			// interupt the loop
			interupt: function() {
				is_running = false;
				clearTimeout(inter_cycle);
			},
			
			resume: function() {
				// set the flag that this loop is in progress
				is_running = true;
				
				// start the loop in a thread
				setTimeout(start, 0);
			},
			
			// allow change to data
			data: function(obj) {
				$.extend(true, self.data, obj);
			},
			
			lastResults: function() {
				return this.data.results || false;
			},
			
			// defines a function that can be runs as "self" to give context of loop
			define: function(name, fn) {
				methods[name] = fn;
			},
			
			// run a custom function
			execute: function(name) {
				methods[name].apply(self, []);
			},
		});
		return operator;
	};
	var global = window[__func__] = function() {
		if(this !== window) {
			var instance = construct.apply(this, arguments);
			return instance;
		}
		else {
			
		}
	};
	$.extend(global, {
		
	});
})();
