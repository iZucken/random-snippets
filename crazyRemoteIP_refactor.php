<?php


function seekGlobalValue ( $key ) {
    global $$key;
    return $$key ?? $_SERVER[ $key ] ?? $_ENV[ $key ] ?? @getenv( $key ) ?? null;
}

function remoteIP_respectProxy () {
    static $lookFor = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'HTTP_VIA', 'HTTP_X_COMING_FROM', 'HTTP_COMING_FROM' ];
    $proxy = null;
    foreach ( $lookFor as $key ) {
        $value = seekGlobalValue( $key );
        if ( !empty( $proxy = $value ) ) break;
    }
    $proxy = filter_var( $proxy, FILTER_VALIDATE_IP );
    if ( empty( $proxy ) ) {
        return seekGlobalValue( 'REMOTE_ADDR' );
    } else {
        return $proxy;
    }
}

function remoteIP_lovecraftian () {
    global $REMOTE_ADDR;
    global $HTTP_X_FORWARDED_FOR, $HTTP_X_FORWARDED, $HTTP_FORWARDED_FOR, $HTTP_FORWARDED;
    global $HTTP_VIA, $HTTP_X_COMING_FROM, $HTTP_COMING_FROM;
    global $HTTP_SERVER_VARS, $HTTP_ENV_VARS;
    if ( empty( $REMOTE_ADDR ) ) {
        if ( !empty( $_SERVER ) && isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
            $REMOTE_ADDR = $_SERVER[ 'REMOTE_ADDR' ];
        } else if ( !empty( $_ENV ) && isset( $_ENV[ 'REMOTE_ADDR' ] ) ) {
            $REMOTE_ADDR = $_ENV[ 'REMOTE_ADDR' ];
        } else if ( !empty( $HTTP_SERVER_VARS ) && isset( $HTTP_SERVER_VARS[ 'REMOTE_ADDR' ] ) ) {
            $REMOTE_ADDR = $HTTP_SERVER_VARS[ 'REMOTE_ADDR' ];
        } else if ( !empty( $HTTP_ENV_VARS ) && isset( $HTTP_ENV_VARS[ 'REMOTE_ADDR' ] ) ) {
            $REMOTE_ADDR = $HTTP_ENV_VARS[ 'REMOTE_ADDR' ];
        } else if ( @getenv( 'REMOTE_ADDR' ) ) {
            $REMOTE_ADDR = getenv( 'REMOTE_ADDR' );
        }
    }
    if ( empty( $HTTP_X_FORWARDED_FOR ) ) {
        if ( !empty( $_SERVER ) && isset( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
            $HTTP_X_FORWARDED_FOR = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
        } else if ( !empty( $_ENV ) && isset( $_ENV[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
            $HTTP_X_FORWARDED_FOR = $_ENV[ 'HTTP_X_FORWARDED_FOR' ];
        } else if ( !empty( $HTTP_SERVER_VARS ) && isset( $HTTP_SERVER_VARS[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
            $HTTP_X_FORWARDED_FOR = $HTTP_SERVER_VARS[ 'HTTP_X_FORWARDED_FOR' ];
        } else if ( !empty( $HTTP_ENV_VARS ) && isset( $HTTP_ENV_VARS[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
            $HTTP_X_FORWARDED_FOR = $HTTP_ENV_VARS[ 'HTTP_X_FORWARDED_FOR' ];
        } else if ( @getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
            $HTTP_X_FORWARDED_FOR = getenv( 'HTTP_X_FORWARDED_FOR' );
        }
    }
    if ( empty( $HTTP_X_FORWARDED ) ) {
        if ( !empty( $_SERVER ) && isset( $_SERVER[ 'HTTP_X_FORWARDED' ] ) ) {
            $HTTP_X_FORWARDED = $_SERVER[ 'HTTP_X_FORWARDED' ];
        } else if ( !empty( $_ENV ) && isset( $_ENV[ 'HTTP_X_FORWARDED' ] ) ) {
            $HTTP_X_FORWARDED = $_ENV[ 'HTTP_X_FORWARDED' ];
        } else if ( !empty( $HTTP_SERVER_VARS ) && isset( $HTTP_SERVER_VARS[ 'HTTP_X_FORWARDED' ] ) ) {
            $HTTP_X_FORWARDED = $HTTP_SERVER_VARS[ 'HTTP_X_FORWARDED' ];
        } else if ( !empty( $HTTP_ENV_VARS ) && isset( $HTTP_ENV_VARS[ 'HTTP_X_FORWARDED' ] ) ) {
            $HTTP_X_FORWARDED = $HTTP_ENV_VARS[ 'HTTP_X_FORWARDED' ];
        } else if ( @getenv( 'HTTP_X_FORWARDED' ) ) {
            $HTTP_X_FORWARDED = getenv( 'HTTP_X_FORWARDED' );
        }
    }
    if ( empty( $HTTP_FORWARDED_FOR ) ) {
        if ( !empty( $_SERVER ) && isset( $_SERVER[ 'HTTP_FORWARDED_FOR' ] ) ) {
            $HTTP_FORWARDED_FOR = $_SERVER[ 'HTTP_FORWARDED_FOR' ];
        } else if ( !empty( $_ENV ) && isset( $_ENV[ 'HTTP_FORWARDED_FOR' ] ) ) {
            $HTTP_FORWARDED_FOR = $_ENV[ 'HTTP_FORWARDED_FOR' ];
        } else if ( !empty( $HTTP_SERVER_VARS ) && isset( $HTTP_SERVER_VARS[ 'HTTP_FORWARDED_FOR' ] ) ) {
            $HTTP_FORWARDED_FOR = $HTTP_SERVER_VARS[ 'HTTP_FORWARDED_FOR' ];
        } else if ( !empty( $HTTP_ENV_VARS ) && isset( $HTTP_ENV_VARS[ 'HTTP_FORWARDED_FOR' ] ) ) {
            $HTTP_FORWARDED_FOR = $HTTP_ENV_VARS[ 'HTTP_FORWARDED_FOR' ];
        } else if ( @getenv( 'HTTP_FORWARDED_FOR' ) ) {
            $HTTP_FORWARDED_FOR = getenv( 'HTTP_FORWARDED_FOR' );
        }
    }
    if ( empty( $HTTP_FORWARDED ) ) {
        if ( !empty( $_SERVER ) && isset( $_SERVER[ 'HTTP_FORWARDED' ] ) ) {
            $HTTP_FORWARDED = $_SERVER[ 'HTTP_FORWARDED' ];
        } else if ( !empty( $_ENV ) && isset( $_ENV[ 'HTTP_FORWARDED' ] ) ) {
            $HTTP_FORWARDED = $_ENV[ 'HTTP_FORWARDED' ];
        } else if ( !empty( $HTTP_SERVER_VARS ) && isset( $HTTP_SERVER_VARS[ 'HTTP_FORWARDED' ] ) ) {
            $HTTP_FORWARDED = $HTTP_SERVER_VARS[ 'HTTP_FORWARDED' ];
        } else if ( !empty( $HTTP_ENV_VARS ) && isset( $HTTP_ENV_VARS[ 'HTTP_FORWARDED' ] ) ) {
            $HTTP_FORWARDED = $HTTP_ENV_VARS[ 'HTTP_FORWARDED' ];
        } else if ( @getenv( 'HTTP_FORWARDED' ) ) {
            $HTTP_FORWARDED = getenv( 'HTTP_FORWARDED' );
        }
    }
    if ( empty( $HTTP_VIA ) ) {
        if ( !empty( $_SERVER ) && isset( $_SERVER[ 'HTTP_VIA' ] ) ) {
            $HTTP_VIA = $_SERVER[ 'HTTP_VIA' ];
        } else if ( !empty( $_ENV ) && isset( $_ENV[ 'HTTP_VIA' ] ) ) {
            $HTTP_VIA = $_ENV[ 'HTTP_VIA' ];
        } else if ( !empty( $HTTP_SERVER_VARS ) && isset( $HTTP_SERVER_VARS[ 'HTTP_VIA' ] ) ) {
            $HTTP_VIA = $HTTP_SERVER_VARS[ 'HTTP_VIA' ];
        } else if ( !empty( $HTTP_ENV_VARS ) && isset( $HTTP_ENV_VARS[ 'HTTP_VIA' ] ) ) {
            $HTTP_VIA = $HTTP_ENV_VARS[ 'HTTP_VIA' ];
        } else if ( @getenv( 'HTTP_VIA' ) ) {
            $HTTP_VIA = getenv( 'HTTP_VIA' );
        }
    }
    if ( empty( $HTTP_X_COMING_FROM ) ) {
        if ( !empty( $_SERVER ) && isset( $_SERVER[ 'HTTP_X_COMING_FROM' ] ) ) {
            $HTTP_X_COMING_FROM = $_SERVER[ 'HTTP_X_COMING_FROM' ];
        } else if ( !empty( $_ENV ) && isset( $_ENV[ 'HTTP_X_COMING_FROM' ] ) ) {
            $HTTP_X_COMING_FROM = $_ENV[ 'HTTP_X_COMING_FROM' ];
        } else if ( !empty( $HTTP_SERVER_VARS ) && isset( $HTTP_SERVER_VARS[ 'HTTP_X_COMING_FROM' ] ) ) {
            $HTTP_X_COMING_FROM = $HTTP_SERVER_VARS[ 'HTTP_X_COMING_FROM' ];
        } else if ( !empty( $HTTP_ENV_VARS ) && isset( $HTTP_ENV_VARS[ 'HTTP_X_COMING_FROM' ] ) ) {
            $HTTP_X_COMING_FROM = $HTTP_ENV_VARS[ 'HTTP_X_COMING_FROM' ];
        } else if ( @getenv( 'HTTP_X_COMING_FROM' ) ) {
            $HTTP_X_COMING_FROM = getenv( 'HTTP_X_COMING_FROM' );
        }
    }
    if ( empty( $HTTP_COMING_FROM ) ) {
        if ( !empty( $_SERVER ) && isset( $_SERVER[ 'HTTP_COMING_FROM' ] ) ) {
            $HTTP_COMING_FROM = $_SERVER[ 'HTTP_COMING_FROM' ];
        } else if ( !empty( $_ENV ) && isset( $_ENV[ 'HTTP_COMING_FROM' ] ) ) {
            $HTTP_COMING_FROM = $_ENV[ 'HTTP_COMING_FROM' ];
        } else if ( !empty( $HTTP_COMING_FROM ) && isset( $HTTP_SERVER_VARS[ 'HTTP_COMING_FROM' ] ) ) {
            $HTTP_COMING_FROM = $HTTP_SERVER_VARS[ 'HTTP_COMING_FROM' ];
        } else if ( !empty( $HTTP_ENV_VARS ) && isset( $HTTP_ENV_VARS[ 'HTTP_COMING_FROM' ] ) ) {
            $HTTP_COMING_FROM = $HTTP_ENV_VARS[ 'HTTP_COMING_FROM' ];
        } else if ( @getenv( 'HTTP_COMING_FROM' ) ) {
            $HTTP_COMING_FROM = getenv( 'HTTP_COMING_FROM' );
        }
    }

    $direct_ip = null;
    if ( !empty( $REMOTE_ADDR ) ) {
        $direct_ip = $REMOTE_ADDR;
    }

    $proxy_ip = '';
    if ( !empty( $HTTP_X_FORWARDED_FOR ) ) {
        $proxy_ip = $HTTP_X_FORWARDED_FOR;
    } else if ( !empty( $HTTP_X_FORWARDED ) ) {
        $proxy_ip = $HTTP_X_FORWARDED;
    } else if ( !empty( $HTTP_FORWARDED_FOR ) ) {
        $proxy_ip = $HTTP_FORWARDED_FOR;
    } else if ( !empty( $HTTP_FORWARDED ) ) {
        $proxy_ip = $HTTP_FORWARDED;
    } else if ( !empty( $HTTP_VIA ) ) {
        $proxy_ip = $HTTP_VIA;
    } else if ( !empty( $HTTP_X_COMING_FROM ) ) {
        $proxy_ip = $HTTP_X_COMING_FROM;
    } else if ( !empty( $HTTP_COMING_FROM ) ) {
        $proxy_ip = $HTTP_COMING_FROM;
    }

    if ( empty( $proxy_ip ) ) {
        return $direct_ip;
    } else {
        $is_ip = preg_match( "/^([0-9]{1,3}\.){3}[0-9]{1,3}/", $proxy_ip, $regs );
        if ( $is_ip == 1 && ( count( $regs ) > 0 ) ) {
            return $regs[ 0 ];
        } else {
            return FALSE;
        }
    }
}