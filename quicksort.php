<?php

$mc = microtime( true ) * 1000;

function array_add_column ( $a, $c, $name ) {
    foreach ( $a as $i => $e ) {
        $a[ $i ][ $name ] = array_shift( $c );
    }
    return $a;
}

function array_remove_column ( $a, $name ) {
    foreach ( $a as $i => $e ) {
        unset( $a[ $i ][ $name ] );
    }
    return $a;
}

function msortByColumn ( &$array, $column ) {
    if ( count( $array ) <= 1 ) {
        return $array;
    } else {
        $pivot = $array[ 0 ];
        $left = [];
        $right = [];
        for ( $i = 1; $i < count( $array ); $i++ ) {
            if ( $array[ $i ][ $column ] < $pivot[ $column ] ) {
                $left[] = $array[ $i ];
            } else {
                $right[] = $array[ $i ];
            }
        }
        return array_merge( msortByColumn( $left, $column ), [ $pivot ], msortByColumn( $right, $column ) );
    }
}

function visualise ( $arr, $left, $right, $from = -1, $to = -1 ) {
    $s = [];
    foreach ( $arr as $key => $item ) {
        $p = str_pad( $item, 3, " ", STR_PAD_LEFT );
        if ( $key == $from || $key == $to ) {
            $p = "\e[4;31m$p\e[0m";
        }
        if ( $key >= $left && $key <= $right ) {
            $p = "$p";
        } else {
            $p = "\e[0;90m$p\e[0m";
        }
        $s[] = $p;
    }
    return implode( " : ", $s );
}

function qsortByColumnPartition ( &$arr, $left, $right, $col ) {
    $first = $right;
    for ( $i = $left; $i < $right; $i++ ) {
        if ( $arr[ $i ][ $col ] <= $arr[ $right ][ $col ] ) {
            if ( $first < $i ) {
                $tmp = $arr[ $i ];
                $arr[ $i ] = $arr[ $first ];
                $arr[ $first ] = $tmp;
                $first++;
            }
        } else {
            if ( $first > $i ) {
                $first = $i;
            }
        }
    }
    if ( $first != $right ) {
        $tmp = $arr[ $right ];
        $arr[ $right ] = $arr[ $first ];
        $arr[ $first ] = $tmp;
    }
    return $first - 1;
}

function qsortByColumnPartitionVisual ( &$arr, $left, $right, $col ) {
    $first = $right;
    echo "comp to {$arr[$right][$col]}\n";
    for ( $i = $left; $i < $right; $i++ ) {
        echo "$i\t{$arr[$i][$col]}\t";
        if ( $arr[ $i ][ $col ] <= $arr[ $right ][ $col ] ) {
            echo "less\t";
            if ( $first < $i ) {
                echo "swp";
                echo "\t" . visualise( array_column( $arr, $col ), $left, $right, $i, $first ) . "\n";
                $tmp = $arr[ $i ];
                $arr[ $i ] = $arr[ $first ];
                $arr[ $first ] = $tmp;
                $first++;
            } else {
                echo "\t" . visualise( array_column( $arr, $col ), $left, $right ) . "\n";
            }
        } else {
            echo "more\t";
            if ( $first > $i ) {
                echo "piv";
                echo "\t" . visualise( array_column( $arr, $col ), $left, $right, $i, $first ) . "\n";
                $first = $i;
            } else {
                echo "\t" . visualise( array_column( $arr, $col ), $left, $right ) . "\n";
            }
        }
    }
    if ( $first != $right ) {
        $tmp = $arr[ $right ];
        $arr[ $right ] = $arr[ $first ];
        $arr[ $first ] = $tmp;
        echo "slice\tat\t$first\tfin\t" . visualise( array_column( $arr, $col ), $left, $right, $first, $right ) . "\n\n";
    } else {
        echo "slice\tat\t$first\tfin\t" . visualise( array_column( $arr, $col ), $left, $right ) . "\n\n";
    }
    return $first - 1;
}

function qsortByColumnRecursion ( &$array, $col, $left, $right ) {
    if ( $right - $left <= 1 ) {
        return;
    } else {
        $pivot = qsortByColumnPartition( $array, $left, $right, $col );
        qsortByColumnRecursion( $array, $col, $left, $pivot );
        qsortByColumnRecursion( $array, $col, $pivot + 1, $right );
    }
}

function qsortByColumnRecursionVisual ( &$array, $col, $left, $right ) {
    if ( $right - $left <= 1 ) {
        return;
    } else {
        $pivot = qsortByColumnPartitionVisual( $array, $left, $right, $col );
        qsortByColumnRecursionVisual( $array, $col, $left, $pivot );
        qsortByColumnRecursionVisual( $array, $col, $pivot + 1, $right );
    }
}

function qsortByColumn ( &$array, $col, $preserve_keys = false ) {
    if ( $preserve_keys ) {
        $array = array_values( array_add_column( $array, array_keys( $array ), "name" ) );
    } else {
        $array = array_values( $array );
    }
    qsortByColumnRecursion( $array, $col, 0, count( $array ) - 1 );
    if ( $preserve_keys ) {
        $array = array_combine( array_column( $array, "name" ), array_remove_column( $array, "name" ) );
    }
}

function qsortByColumnVisual ( &$array, $col, $preserve_keys = false ) {
    if ( $preserve_keys ) {
        $array = array_values( array_add_column( $array, array_keys( $array ), "name" ) );
    } else {
        $array = array_values( $array );
    }
    qsortByColumnRecursionVisual( $array, $col, 0, count( $array ) - 1 );
    if ( $preserve_keys ) {
        $array = array_combine( array_column( $array, "name" ), array_remove_column( $array, "name" ) );
    }
}

function crude_avg_by_col_alt ( &$array, $col ) {
    $avg = 0;
    $avg_v = 0;
    $avg_i = 0;
    $avg_a = 9999;
    foreach ( $array as $key => $val ) {
        $avg += $val[ $col ];
        $avg_ = $avg / ( $key + 1 );
        $avg_m = abs( $avg_ - $val[ $col ] );
        echo "$key\t{$val[$col]}\t$avg\t$avg_\t$avg_m\t\t$avg_i\t$avg_v\t$avg_a\t";
        if ( $avg_m < $avg_a ) {
            $avg_a = $avg_m;
            $avg_v = $val[ $col ];
            $avg_i = $key;
        }
        echo "\n";
    }
    return $avg_i;
}

function bsortByColumn ( &$array, $column ) {
    $total = count( $array );
    $ttl = 0;
    do {
        $swap = false;
        for ( $i = 0; $i < $total - 1; $i++ ) {
            $ttl++;
            if ( $array[ $i ][ $column ] > $array[ $i + 1 ][ $column ] ) {
                $tmp = $array[ $i ];
                $array[ $i ] = $array[ $i + 1 ];
                $array[ $i + 1 ] = $tmp;
                $swap = true;
                $last = $i;
            }
        }
        $total = $last + 1;
    } while ( $swap );
//    echo "\nTotal bsort ops: $ttl\n";
}

function demo_array ( $size = 20, $bound = 99, $with_keys = false ) {
    $array = [];
    if ( $with_keys ) {
        for ( $i = 0; $i < $size; $i++ ) {
            $idx = substr( md5( rand() ), 0, 9 );
            $array[ $idx ] = [ 'something' => rand( 1, 10 ), 'stuff' => rand( 1, $bound ) ];
        }
    } else {
        for ( $i = 0; $i < $size; $i++ ) {
            $array[ $i ] = [ 'something' => rand( 1, 10 ), 'stuff' => rand( 1, $bound ) ];
        }
    }
    return $array;
}

function demo_stats ( &$last_time, $line = null, &$last_mem = null ) {
    echo "\n\e[1;34m--------------------------------\e[0m\n";
    if ( !empty( $line ) ) {
        echo "says: ", $line, "\n";
    }
    echo "time: ", round( microtime( true ) * 1000 - $last_time ), "\n";
    echo "mem: ", memory_get_peak_usage();
    $last_time = microtime( true ) * 1000;
    echo "\n\e[1;34m--------------------------------\e[0m\n";
}

$array = demo_array( 20, 99 );
qsortByColumnVisual( $array, 'stuff' );
demo_stats( $mc );

$array = demo_array( 10000, 99999 );
$array = array_column( $array,'stuff' );
sort( $array );
demo_stats( $mc, 'native sort' );

$array = demo_array( 10000, 99999 );
qsortByColumn( $array, 'stuff' );
demo_stats( $mc, 'qsortByColumn' );

$array = demo_array( 10000, 99999 );
$array = msortByColumn( $array, 'stuff' );
demo_stats( $mc, 'msortByColumn' );

$array = demo_array( 1000, 99999 );
bsortByColumn( $array, 'stuff' );
demo_stats( $mc, 'bsortByColumn' );
