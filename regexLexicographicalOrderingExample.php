<?php
$samples = [
    "ru/album.*/(1242x2688|1125x2436|full-hd-iphone|750x1334|2048x1536)",
    "ru/album/.*/(1242x2688|1125x2436|full-hd-iphone|750x1334|2048x1536)",
    "ru/album/goroda",
    "ru/album/goroda$",
];
function simpler($regex)
{
    $regex = preg_replace("#[\w\d\s\-\/]+#", "1", $regex);
    $regex = preg_replace("#\(1(\|1)*\)#", "2", $regex);
    $regex = preg_replace("#\[[^\]]+\]#", "2", $regex);
    $regex = preg_replace("#\.#", "3", $regex);
    $regex = preg_replace("#2(\*|\+)#", "4", $regex);
    $regex = preg_replace("#3(\*|\+)#", "5", $regex);
    $regex = preg_replace("#(?<!\\$)$#", "5", $regex);
    $regex = preg_replace("#\\$$#", "", $regex);
    return $regex;
}

function pad($items)
{
    $l = 0;
    foreach ($items as $sample => $item) {
        $l = max($l, strlen($item));
    }
    $result = [];
    foreach ($items as $sample => $item) {
        $item = str_pad($item, $l, substr($item,strlen($item) - 1,1));
        $result[$sample] = $item;
    }
    return $result;
}

function sortShit (&$rexes) {
    uksort($rexes, function ($a,$b) use (&$rexes) {
        return ($rexes[$a] <=> $rexes[$b]) ?: (strlen($b) <=> strlen($a));
    });
    return $rexes;
}

$order = [];
foreach ($samples as $sample) {
    $order[$sample] = simpler($sample);
}
$order = pad($order);
$order = sortShit($order);
print_r($order);