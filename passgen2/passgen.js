
//(function(){
	var view = {
		container: document.getElementById('passgen-container'),
		text: document.getElementById('passgen-text'),
		count: document.getElementById('passgen-count'),
		lowercase: document.getElementById('passgen-lowercase'),
		uppercase: document.getElementById('passgen-uppercase'),
		numbers: document.getElementById('passgen-numbers'),
		special: document.getElementById('passgen-special'),
		generate: document.getElementById('passgen-generate'),
		//toclip: document.getElementById('passgen-toclip'),
		options: document.getElementById('passgen-options'),
		countGhost: document.getElementById('passgen-count-ghost'),
	};
	var dictionary = ''; // used to generate a password
	/*
	var checks = {
		lowercase: view.lowercase.checked,
		uppercase: view.uppercase.checked,
		numbers: view.numbers.checked,
		special: view.special.checked
	};
	*/
	var dictionaries = [ // concated to dictionary based on checks
		'abcdefghijklmnopqrstuvwxyz',
		'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		'0123456789',
		'!@#$%^&()+-*/_=|~`\\'
	];
	var folded = true;
	var count = 0;
	var countMin = 4;
	var countMax = 20;
	var checksIndexes = [ // maps inputs to indexes
		view.lowercase,
		view.uppercase,
		view.numbers,
		view.special
	];
	var checks = [ // binary map
		1,
		0,
		0,
		0
	];
	var bakeDictionary = function () {
		dictionary = '';
		for ( var index in checks ) {
			if ( checks[index] == 1 ) {
				dictionary += dictionaries[index];
			}
		}
		generate();
	};
	var recount = function ( event ) {
		if ( count != view.count.value ) {
			count = view.count.value;
			bakeDictionary();
			view.countGhost.innerText = count;
			view.countGhost.style.left = ((count-countMin)/(countMax-countMin))*100 + "%";
		}
	};
	var check = function ( event ) {
		var target = event.target;
		var index = checksIndexes.indexOf(target);
		var checked = !checks[index];
		checks[index] = checked;
		var totalChecks = checks.reduce(function( sum, val ){
			return sum + val;
		});
		target.classList.toggle("on");
		if ( totalChecks <= 0 ) {
			target.checked = true;
			checks[ checksIndexes.indexOf ( target ) ] = true;
			target.classList.toggle("on");
		}
		bakeDictionary();
	};
	var generate = function ( event ) {
		var newPassword = Array
			.apply(null, Array( +count )) // + here to convert input's value from text to number
			.map(function(){return dictionary.charAt(Math.floor (Math.random () * dictionary.length ));})
			.join('');
		view.text.value = newPassword;
	};
	recount();
	var toclip = function ( event ) {
		view.text.select();
		document.execCommand('copy');
		view.text.select(false);
	};
	var options = function ( event ) {
		folded = !folded;
		view.container.classList.toggle("folded");
	}
	//view.count.addEventListener( 'change', recount );
	view.count.addEventListener( 'mousemove', recount );
	view.count.addEventListener( 'touchmove', recount );
	view.lowercase.addEventListener( 'click', check );
	view.uppercase.addEventListener( 'click', check );
	view.numbers.addEventListener( 'click', check );
	view.special.addEventListener( 'click', check );
	view.generate.addEventListener( 'click', generate );
	//view.toclip.addEventListener( 'click', toclip );
	view.options.addEventListener( 'click', options );
//})();
