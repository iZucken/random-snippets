<?php

function traverse ($x,$y,$xd,$yd,$n,$o) {
    if ($xd == 0 && $yd == 0) {
        return 0;
    }
    $t = 0;
    $x += $xd;
    $y += $yd;
    while ($x >= 1 && $x <= $n && $y >= 1 && $y <= $n) {
        if (isset($o[$x + $y * $n])) {
            return $t;
        }
        $x += $xd;
        $y += $yd;
        $t++;
    }
    return $t;
}

function traverseSolution($n, $k, $r, $c, $obstacles) {
    $o = [];
    foreach ($obstacles as $ob) {
        $o[$obstacle[0] + $obstacle[1] * $n] = true;
    }
    for ($i = -1; $i <= 1; $i++) {
        for ($j = -1; $j <= 1; $j++) {
            $total += traverse($r_q,$c_q,$i,$j,$n,$o);
        }
    }
    return $total;
}

function normalSlope ($dx, $dy) {
    return [
        max(-1,min(1,$dx)) + 1,
        max(-1,min(1,$dy)) + 1,
    ];
}

function slopeDistance($x0,$y0,$x,$y) {
    $xdr = $x0 - $x;
    $ydr = $y0 - $y;
    $xd = abs($xdr);
    $yd = abs($ydr);
    if ($xd === $yd
        || ($xd == 0 || $yd == 0)) {
        $n = normalSlope($xdr,$ydr);
        return [$n[0], $n[1], max($xd,$yd) - 1];
    }
    return [1, 1, 0];
}

function defaultSlopes ($n, $r, $c) {
    $rd = $n - $r;
    $cd = $n - $c;
    $r0 = $r - 1;
    $c0 = $c - 1;
    return [
        min($cd,$rd), $rd, min($c0,$rd),
        $cd,            0,          $c0,
        min($r0,$cd), $r0, min($r0,$c0),
    ];
}

function queensAttack($n, $k, $r, $c, $obstacles) {
    $slopes = defaultSlopes($n, $r, $c);
    foreach ($obstacles as $ob) {
        $s = slopeDistance($r,$c,$ob[0],$ob[1]);
        $slopes[$s[0] * 3 + $s[1]] = min(
            $slopes[$s[0] * 3 + $s[1]],
            $s[2]
        );
    }
    return array_sum($total);
}
