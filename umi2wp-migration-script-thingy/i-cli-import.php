<?php
#region init
require __DIR__ . "/i-cli-common.php";
$db = new \PDODatabase( new PDO( "mysql:dbname=dbname;host=localhost", "user", "pass", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
] ) );
$wo = new \WpObject( $db );
#endregion init
ci( $argv );
if ( ca( "reviews" ) ) {
    $db->execute("DELETE FROM wp_gwolle_gb_entries");
    $reviews = l( "reviews" );
    foreach ( $reviews as $review ) {
        $data = [
            empty( $review['_fields']['name'] ) ? "Аноним" : $review['_fields']['name'],
            empty( $review['_fields']['email'] ) ? "<без адреса>" : $review['_fields']['email'],
            $review['_fields']['publish_time'],
            $review['_fields']['content']
        ];
        $stmt = $pdo->prepare( "
			INSERT INTO wp_gwolle_gb_entries (author_id,author_ip,author_host,ischecked,checkedby,author_name,author_email,datetime,content)
			VALUES (1,'127.0.0.1','127.0.0.1',1,1,?,?,?,?);" );
        try {
            $stmt->execute( $data );
        } catch ( \Throwable $t ) {
            print_r( $t );
            die;
        }
    }
}
if ( ca( 'articles' ) ) {
$tree = l( "articles" );
$defaultRootTerm = 4;
$rootMap = [
    "chip-tyuning" => 17,
    "udalenie-sazhevogo-filtra" => 17,
    "sistema-upravleniya-dvigatelem" => 17,
];
$mimeMap = [
    "jpg" => "image/jpeg",
    "jpeg" => "image/jpeg",
    "png" => "image/png",
    "gif" => "image/gif",
];
foreach ( $tree as $category ) {
    if ( empty( $category['_objects'] ) ) {
        // logic for case when upper level tax is actually an article
        continue;
    }
    $newRoot = $rootMap[$category['uri']] ?? $defaultRootTerm;
    $categoryTerm = $db->queryOne( "
		SELECT t.term_id, t.slug, t.name, tt.taxonomy, tt.term_taxonomy_id, tt.parent FROM wp_terms AS t
		LEFT JOIN wp_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.slug = ?", $category['uri'] );
    if ( empty( $categoryTerm ) ) {
        echo "TERM {$category['uri']}\n";
        $db->execute( "INSERT INTO wp_terms ( slug, `name` ) VALUES ( ?, ? )", $category['uri'], $category['name'] );
        $termId = $db->lastInsert();
        $db->execute( "INSERT INTO wp_term_taxonomy ( term_id, parent, taxonomy ) VALUES ( ?, ?, 'category' )", $termId, $newRoot );
        $categoryTerm = $db->query( "
			SELECT t.slug, t.name, tt.taxonomy, tt.term_taxonomy_id, tt.parent FROM wp_terms AS t
			LEFT JOIN wp_term_taxonomy AS tt ON t.term_id = tt.term_id WHERE t.slug = ?", $category['uri'] );
    } else {
        $db->execute( "INSERT INTO wp_term_taxonomy ( term_id, parent, taxonomy ) VALUES ( ?, ?, 'category' ) ON DUPLICATE KEY UPDATE parent = VALUES(parent)", $categoryTerm['term_id'], $newRoot );
        echo "TERM EXISTS {$categoryTerm['slug']} as {$categoryTerm['taxonomy']}\n";
    }
    foreach ( $category['_objects'] as $article ) {
        $wo->saveArticle(
            $article['time'],
            $article['_fields']['h1'] ?? $article['_fields']['title'],
            $article['_fields']['content'],
            $article['uri'],
            $categoryTerm['term_taxonomy_id'],
            $article['_fields']['title'] ?? $article['_fields']['h1']
        );
    }
}
}
if ( ca( "goods" ) ) {
$tree = l( "goods" );
$rootPost = $db->queryOne( "SELECT * FROM wp_posts WHERE post_name = ?", "osnawenie-masterskoj" );
foreach ( $tree as $article ) {
    $page = $wo->savePage(
        $article['time'],
        $article['_fields']['h1'] ?? $article['_fields']['title'],
        $article['_fields']['content'] ?? "",
        $article['uri'],
        $rootPost['ID'],
        $article['_fields']['title'] ?? $article['_fields']['h1']
    );
    foreach ( $article['_objects'] ?? [] as $subArticle ) {
        $wo->savePage(
            $subArticle['time'],
            $subArticle['_fields']['h1'] ?? $subArticle['_fields']['title'],
            $subArticle['_fields']['content'] ?? "",
            $subArticle['uri'],
            $page["ID"],
            $subArticle['_fields']['title'] ?? $subArticle['_fields']['h1']
        );
    }
}
}
if ( ca( "test-services" ) ) {
    $services = l( "services" );
    $checks = [
        'missing' => [],
        'guess-collision' => [],
        'guessed' => [],
        'found' => [],
    ];
    foreach ( $services as $serviceRootUri => $service ) {
        $wo->traverseCheck( $checks, $service, "" );
    }
    j( 'checks', $checks );
}
if ( ca( "services-apply" ) ) {
    $services = l( "services" );
    $checks = [
        'missing' => [],
        'guess-collision' => [],
        'guessed' => [],
        'found' => [],
        'updated' => [],
    ];
    foreach ( $services as $serviceRootUri => $service ) {
        $wo->traverseUpdate( $checks, $service, "" );
    }
    j( 'checks-apply', $checks );
}