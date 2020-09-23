<?php
require __DIR__ . "/i-cli-common.php";
$db = new \PDODatabase( new PDO( "mysql:dbname=dbname;host=localhost", "user", "pass", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] ) );
$ob = new UmiEntity( $db, __DIR__ . "/xport" );
if ( in_array( "redirects", $argv ) ) {
    $ob->j( "redirects", $db->query( "SELECT source AS `from`, target AS `to`, status AS `code` FROM cms3_redirects" ) );
}
if ( in_array( "reviews", $argv ) ) {
    $ob->j( "reviews", $ob->loadObjectsByHierarchyRoot( 942, [
        "content" => true,
        "imya" => true,
        "email" => true,
        "data" => true,
    ], [
        "data" => "publish_time",
        "imya" => "name",
    ] ) );
}
if ( in_array( "articles", $argv ) ) {
    $root = $db->queryOne( "SELECT id FROM cms3_hierarchy WHERE alt_name = ?", "stati" );
    $ob->j( "articles", $ob->loadObjectsByHierarchyRootRecursive( $root["id"], [
        "title" => true,
        "h1" => true,
        "meta_descriptions" => true,
        "tags" => true,
        "content" => true,
        "data" => true,
        "publish_time" => true,
    ], [
        "data" => "publish_time",
    ] ) );
}
if ( in_array( "goods", $argv ) ) {
    $root = $db->queryOne( "SELECT id FROM cms3_hierarchy WHERE alt_name = ?", "osnawenie-masterskoj" );
    $ob->j( "goods", $ob->loadObjectsByHierarchyRootRecursive( $root["id"], [
        "title" => true,
        "h1" => true,
        "meta_descriptions" => true,
        "tags" => true,
        "content" => true,
        "data" => true,
        "publish_time" => true,
    ], [
        "data" => "publish_time",
    ] ) );
}
if ( in_array( "services", $argv ) ) {
    $services = [];
    $roots = [
        'chip-tyuning-inomarok',
        'chip-tyuning-gruzovyh-avtomobilej',
        'chip-tyuning',
        'chip-tyuning-motociklov',
        'chip-tyuning-kvadrociklov',
        'chip-tyuning-yaht-katerov-gidrociklov',
        'chip-tyuning-akpp',
        'chip-tyuning-avtodomov',
        'otklyuchit-sazhevij-filtr-udalit-sazhevij-filtr',
        'otklyuchenie-egr-egr',
        'otklyuchit-datchik-kisloroda-udalit-virezat-katalizator',
        'diagnostika-dvigatelya-diagnostika-i-remont-inzhektora',
        'perevod-mili-v-km-farengejti-v-celsii-galloni-v-litri',
        'remont-airbag-i-srs',
        'diagnostika-otklyuchenie-i-remont-immobilajzera-udalit-otklyuchit-immobilajzer',
        'ustanovka-sportivnih-raspredvalov',
        'promivka-inzhektora-ultrazvukovaya-i-himicheskaya',
        'zamer-mownosti-dvigatelya-avtomobilya-na-stende',
        'korrekciya-spidometrov-odometrov',
        'snyatie-ogranichenie-skorosti'
    ];
    foreach ( $roots as $rootName ) {
        $services[$rootName] = $ob->loadRootObjectByAltName( $rootName, [
            "title" => true,
            "h1" => true,
            "meta_descriptions" => true,
            "tags" => true,
            "content" => true,
            "data" => true,
            "publish_time" => true,
        ], [
            "data" => "publish_time",
        ] );
    }
    $ob->j( "services", $services );
}
if ( in_array( "zip-images", $argv ) ) {
    $zip = new ZipArchive();
    $zipFile = __DIR__ . '/pack-export.zip';
    @unlink( $zipFile );
    $ret = $zip->open( $zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE );
    if ( $zip->open( $zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== TRUE ) die( 'Failed to open zip' );
    $iterator = new RecursiveDirectoryIterator( __DIR__ . "/images/" );
    $iterator = new RecursiveIteratorIterator( $iterator );
    $iterator = new RegexIterator( $iterator, '~^' . __DIR__ . '((\/[^\/\.]+)+\.(jpe?g|png|gif))$~i', RecursiveRegexIterator::REPLACE );
    $iterator->replacement = '$1';
    foreach ( $iterator as $index => $item ) {
        echo "$index:\t$item" . $zip->addFile( $index, $item ) . "\n";
    }
    $zip->close();
}