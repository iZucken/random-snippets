<?php

function mc () {
    return floor( microtime( true ) * 1000 );
}

function variantsCount ( $l ) {
    $t = array_fill( 0, $l, 0 );
    $t[ 1 ] = 1;
    $t[ 2 ] = 1;
    for ( $i = 3; $i < $l; $i++ ) {
        $t[ $i ] = $t[ $i - 2 ] + $t[ $i - 3 ];
    }
    return $t[ $l - 1 ];
}

function dumpRelations ( $relations ) {
    foreach ( $relations as $parent => $relation ) {
        echo "$parent=>\t";
        foreach ( $relation as $child => $relative ) {
            echo "$child:$relative;\t";
        }
        echo "\n";
    }
}

function dumpRelationAmounts ( $relations ) {
    $amounts = [];
    foreach ( $relations as $parent => $relation ) {
        $amounts[ $parent ] = count( $relation );
    }
    $breaker = 1;
    foreach ( $amounts as $count => $index ) {
        $breaker = ($breaker + 1) % 20;
        echo "$count: $index;\t";
        if(!$breaker) echo "\n";
    }
    echo "\n";
}

function dumpRelationCountStats ( $relations ) {
    $amounts = [];
    foreach ( $relations as $parent => $relation ) {
        $amounts[ count( $relation ) ] = 1 + ( $amounts[ count( $relation ) ] ?? 0 );
    }
    $breaker = 1;
    ksort( $amounts );
    foreach ( $amounts as $count => $index ) {
        $breaker = ($breaker + 1) % 20;
        echo "$count: $index;\t";
        if(!$breaker) echo "\n";
    }
    echo "\n";
}

function sums ( $relations ) {
    $sums = [];
    foreach ( $relations as $key => $relation ) {
        if ( empty( $relation ) ) {
            $sums[ $key ] = 0;
        } else {
            $sums[ $key ] = 1;
        }
    }
    return $sums;
}

function unpackVariant ( $v, $l ) {
    $o = $l;
    $u = 0;
    while ( $l > 0 ) {
        $u += 1 << ( $o - $l );
        $l -= ( $v & 1 ) + 2;
        $v >>= 1;
    }
    return $u;
}

function variants ( $ml, $l, $c = 0, &$b = [] ) {
    if ( $l > 2 ) variants( $ml, $l - 3, $c << 1 | 1, $b );
    if ( $l > 1 ) variants( $ml, $l - 2,  $c << 1, $b );
    if ( $l == 0 ) $b[ $c ] = unpackVariant( $c, $ml ) >> 2;
    return $b;
}

function relations ( $width ) {
    $variants = variants( $width, $width );
    $keys = array_keys( $variants );
    $values = array_values( $variants );
    $relations = [];
    $c = count( $variants );
    $totalComparisons = 0;
    $succeedComparisons = 0;
    $i = 0;
    while ( $i < $c ) {
        $j = $i + 1;
        while ( $j < $c ) {
            $totalComparisons++;
            if ( !( $values[ $i ] & $values[ $j ] ) ) {
                $succeedComparisons++;
                $relations[ $keys[ $i ] ] [] = $keys[ $j ];
                $relations[ $keys[ $j ] ] [] = $keys[ $i ];
            }
            $j++;
        }
        $i++;
    }
    echo sprintf( "out of %s combinations %s are compared, succeed %s with ratio %s\n",
        $c*$c,$totalComparisons, $succeedComparisons, round($succeedComparisons/$totalComparisons,4 ) );
    return $relations;
}

function steps ( $relations, $steps, $mod ) {
    $sums = sums( $relations );
    while ( $steps > 1 ) {
        $sums2 = $sums;
        foreach ( $relations as $key => $related ) {
            $sum = 0;
            foreach ( $related as $index ) {
                $sum += $sums2[ $index ];
            }
            $sums[ $key ] = $sum % $mod;
        }
        $steps--;
    }
    return array_sum( $sums );
}

function combinations ( $width, $height, $mod ) {
    return steps( relations( $width ),
            $height,
            $mod ) % $mod;
}

$width = 35;
$height = 100;
$mod = 1000;

$timer = mc();

$relations = relations( $width );
echo "relations computed in ", mc() - $timer, "ms\n";
$timer = mc();

//dumpRelationAmounts( $relations );
dumpRelationCountStats( $relations );

$total = steps( $relations, $height, $mod );
echo "steps computed in ", mc() - $timer, "ms\n";
$timer = mc();

$total %= $mod;
echo "total: $total\n";


# new relations compute branching algo

# COMPUTE variant = nil USE mask. length, computed
# if can add 2 by mask, COMPUTE ( variant << 2 | 1 )
# if can add 3 by mask, COMPUTE ( variant << 3 | 1 )
# if variant length == length add to computed
