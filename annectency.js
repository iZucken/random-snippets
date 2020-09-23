

function vectorLinearDistance ( v1, v2 ) {
	let sum = 0;
	v1.forEach( function ( e, i ) {
		let d = e - v2[i];
		sum += d * d;
	} );
	return Math.sqrt( sum );
}


// function toroidDistance ( $v1, $v2 ) {
//     $sum = 0;
//     foreach ( $v1 as $i => $e ) {
//         $d = abs( ($e+1) - ($v2[ $i ]+1) );
//         if ( $d > 1 ) {
//             $d -= 2;
//         }
//         $sum += $d * $d;
//     }
//     return sqrt( $sum );
// }

function closestToVectorInMap ( vector, map ) {
	let leastIndex = null;
	let leastDistance = 99999999999999999999999;
	for ( let value in map ) {
		let d = vectorLinearDistance( map[value], vector );
		if ( d < leastDistance ) {
			leastDistance = d;
			leastIndex = value;
		}
	}
	return leastIndex;
}

function vectorDelta ( v1, v2 ) {
	let v3 = {};
	v1.forEach( function ( e, i ) {
		v3[ i ] = v1[ i ] - v2[ i ];
	} );
	return v3;
}

