<?php

header("Content-type: text/plain; charset=cp1251");
header( "Cache-Control: no-cache, must-revalidate" );
header( 'X-Accel-Buffering: no' );

ob_implicit_flush();

session_write_close();

ob_end_flush();
flush();

ini_set( 'display_errors', 1 );
ini_set( 'log_errors', 0 );
error_reporting( -1 );

fms("Opening tcp connection...\n\n" );
$fp = fsockopen( "tcp://176.58.61.77", 2101, $errno, $errstr, 5 );

if ( !$fp ) {
    fms("$errstr ($errno)\n" );
    die;
}

function fms ( string $msg ) {
    echo $msg;
    //ob_end_flush();
    flush();
}

$key = base64_encode( $_REQUEST['key'] );
fms("key is $key\n" );

$out = "GET /NSRTCM23 HTTP/1.0\r\n" .
    "Host: 176.58.61.77\r\n" .
    "Ntrip-Version: Ntrip/1.0\r\n" .
    "User-Agent: NTRIP Client/1.0\r\n" .
    "Authorization: Basic $key\r\n" .
//    "Connection: close" .
    "\r\n";

fms("sending:\n" );
fms("---\n" );
fms($out );
fms("---\n\n" );

fwrite( $fp, $out );

$nmea = "\$GPGGA,233732.01,3946.2000000,N,08413.2000000,W,1,00,1.0,34.073,M,-34.073,M,0.0,*4F";
$receiving = false;
while ( !feof( $fp ) ) {
    $buffer = fgets( $fp, 128 );
    //fms( "Received ".strlen($buffer)." at ".date(DATE_W3C).":\n" );
    if ( $receiving ) {
        $buffer = fgets( $fp, 16 );
        //fms( $buffer . "\n" );
        //$buffer = decbin(ord($buffer));
        //$buffer = bin2hex($buffer);
        $buffer = (unpack('H*', $buffer))[1];
        //fms( $buffer . "\n" );
        $chunks =  str_split( $buffer,5 );
        foreach ( $chunks as &$chunk ) {
            $chunk = str_pad( base_convert($chunk, 16, 2),20, "0", STR_PAD_LEFT );
        }
        $buffer = implode("", $chunks);
        //fms( strlen($buffer) . "\n" );
        //fms( $buffer . "\n" );
        $chunks = str_split( $buffer,30 );
        foreach ( $chunks as $chunk ) {
            $sub = str_split( $chunk,24 );
            fms( "message: {$sub[0]} | {$sub[1]}\n" );
        }
    } else {
        fms( $buffer );
    }
    if ( $buffer == "" || $buffer == "\r\n" ) {
        $receiving = true;
        fms("\n---\nsending sample message\n---\n");
        fwrite( $fp, $nmea );
    }
}
fms("\n---\nReceived end of file or error\n\n" );

fclose( $fp );

die;