<?php

class Statistics
{
    function readIntArray ( $input, $delimeter = " " ) {
        return array_map( 'intval', explode( $delimeter, fgets( $input ) ) );
    }

    function stdOutFloatLine ( $value, $precision = 3 ) {
        echo sprintf( "%.{$precision}f\n", $value );
    }

    function mean ( $numbers ) {
        return array_sum( $numbers ) / count( $numbers );
    }

    function median ( $numbers ) {
        sort( $numbers );
        $count = count( $numbers );
        $middle = floor( $count / 2 );
        return $count % 2 ?
            $numbers[ $middle ] :
            round( ( $numbers[ $middle ] + $numbers[ $middle - 1 ] ) / 2, 1 );
    }

    function quartiles ( $numbers ) {
        sort( $numbers );
        $count = count( $numbers );
        $middle = floor( $count / 2 );
        return [
            $this->median( array_slice( $numbers, 0, $middle ) ),
            $this->median( $numbers ),
            $this->median( array_slice( $numbers, $count % 2 ? $middle + 1 : $middle ) )
        ];
    }

    function quartileRange ( $numbers ) {
        [ $low, $median, $high ] = $this->quartiles( $numbers );
        return $high - $low;
    }

    function mode ( $numbers ) {
        sort( $numbers );
        $modes = [];
        foreach ( $numbers as $number ) {
            $modes[ $number ] = 1 + ( $modes[ $number ] ?? 0 );
        }
        $bestMode = -1;
        $bestTimes = 0;
        foreach ( $modes as $mode => $times ) {
            if ( $times > $bestTimes ) {
                $bestMode = $mode;
                $bestTimes = $times;
            }
            if ( $times == $bestTimes && $bestMode > $mode ) {
                $bestMode = $mode;
            }
        }
        return $bestMode;
    }

    function variance ( $numbers ) {
        $variance = 0;
        $mean = $this->mean( $numbers );
        foreach ( $numbers as $number ) {
            $variance += pow( $number - $mean, 2 );
        }
        $variance /= count( $numbers );
        return $variance;
    }

    function deviation ( $numbers ) {
        return sqrt( $this->variance( $numbers ) );
    }

    function defrequent ( $numbers, $frequencies ) {
        $chunks = [];
        asort( $numbers );
        foreach ( $numbers as $index => $number ) {
            $chunks [] = array_fill( 0, $frequencies[ $index ], $number );
        }
        return array_merge( ...$chunks );
    }

    function weightedMean ( $numbers, $weights ) {
        $sum = 0;
        $wsum = 0;
        foreach ( $numbers as $index => $number ) {
            $weight = $weights[ $index ];
            $sum += $number * $weight;
            $wsum += $weight;
        }
        return $sum / $wsum;
    }

    function factorial ( $n ) {
        if ( $n == 0 ) {
            return 1;
        }
        $c = $n;
        while ( $n > 1 ) {
            $n--;
            $c *= $n;
        }
        return $c;
    }

    function permutations ( $n, $r ) {
        $d = $n - $r;
        $c = 1;
        while ( $n > $d ) {
            $c *= $n;
            $n--;
        }
        return $c;
    }

    function combinations ( $n, $r ) {
        return $this->permutations( $n, $r ) / $this->factorial( $r );
    }

    function binomialPower ( $x, $n, $p ) {
        return pow( $p, $x ) * pow( ( 1 - $p ), ( $n - $x ) );
    }

    function binomial ( $x, $n, $p ) {
        return $this->combinations( $n, $x ) * $this->binomialPower( $x, $n, $p );
    }

    function negativeBinomial ( $x, $n, $p ) {
        return $this->combinations( $n - 1, $x - 1 )
            * $this->binomialPower( $x, $n, $p );
    }

    function geometricDistribution ( $n, $p ) {
        return $this->binomialPower( 1, $n, $p );
    }

    function totalGeometricDistribution ( $n, $p ) {
        return 1 - pow( ( 1 - $p ), $n );
    }

    function cumulative ( $from, $to, $n, $p ) {
        $c = 0;
        while ( $from <= $to ) {
            $c += $this->binomial( $from, $n, $p );
            $from++;
        }
        return $c;
    }

    function poisson ( $average, $actual ) {
        return pow( $average, $actual ) * pow( M_E, -$average ) / $this->factorial( $actual );
    }

    function poissonExpectation ( $average ) {
        return $average + pow( $average, 2 );
    }

    function trapezoidIntegrationSteps ( $from, $to, $function, $steps ) {
        $dx = ( $to - $from ) / $steps;
        $i = 0;
        $sum = 0;
        while ( $i < $steps ) {
            $x = $from + $i * $dx;
            $sum += ( $function( $x + $dx ) + $function( $x ) ) / 2 * $dx;
            $i++;
        }
        return $sum;
    }

    function trapezoidIntegration ( $from, $to, $function, $errorMargin = 0.005 ) {
        $steps = 2;
        $value = $this->trapezoidIntegrationSteps( $from, $to, $function, 1 );
        $error = $value;
        while (
            abs( $error - (
                $value = $this->trapezoidIntegrationSteps( $from, $to, $function, $steps ) )
            ) > $errorMargin
            && $steps < 1024
        ) {
            $errorDelta = abs( $error - $value );
            echo "$errorDelta\t$steps\n";
            $error = $value;
            $steps *= 2;
        }
        return $value;
    }

    function normalErrorFunction ( $z ) {
        $integral = $this->trapezoidIntegration( 0, $z, function ( $x ) {
            return pow( M_E, -( $x * $x ) );
        } );
        return M_2_SQRTPI * $integral;
    }

    function normalDistributionCumulative ( $mean, $deviation, $x ) {
        return ( 1 + $this->normalErrorFunction( ( $x - $mean ) / M_SQRT2 / $deviation ) ) / 2;
    }
}

$statistics = new Statistics();

$mean = 20;
$deviation = 2;

$cumulative = $statistics->normalDistributionCumulative( $mean, $deviation, 19.5 );
echo $cumulative, "\n";
$cumulative = $statistics->normalDistributionCumulative( $mean, $deviation, 22 );
$cumulative = $cumulative - $statistics->normalDistributionCumulative( $mean, $deviation, 20 );
echo $cumulative, "\n";