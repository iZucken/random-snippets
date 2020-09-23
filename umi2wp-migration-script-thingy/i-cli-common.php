<?php
ini_set( "display_errors", 'on' );
ini_set( "log_errors", 'off' );
error_reporting( -1 );
if ( php_sapi_name() != "cli" ) die;

class PDODatabase
{
    private $db = null;
    /**
     * @var PDOStatement[]
     */
    private $prepared = [];

    public function __construct ( PDO $pdo ) {
        $this->db = $pdo;
    }

    /**
     * @param string $query
     * @param mixed  ...$values
     *
     * @return array|null
     * @throws Exception
     */
    public function query ( $query, ...$values ) {
        $this->prepareQuery( $query );
        $this->executeArray( $query, $values );
        $fetch = $this->fetchAllSafe( $query );
        return empty( $fetch ) ? null : $fetch;
    }

    /**
     * @param string $query
     * @param mixed  ...$values
     *
     * @return null
     * @throws Exception
     */
    public function execute ( $query, ...$values ) {
        $this->prepareQuery( $query );
        $this->executeArray( $query, $values );
        return null;
    }

    /**
     * @param string $query
     * @param mixed  ...$values
     *
     * @return array|null
     * @throws Exception
     */
    public function queryOne ( $query, ...$values ) {
        $rows = $this->query( $query, ...$values );
        return empty( $rows[0] ) ? null : $rows[0];
    }

    /**
     * @param string $query
     * @param mixed  ...$values
     *
     * @return bool
     * @throws Exception
     */
    public function queryHasResults ( $query, ...$values ) {
        $rows = $this->query( $query, ...$values );
        return !empty( $rows );
    }

    /**
     * @param string $query
     * @param array  $namesAndValues
     *
     * @return array|null
     * @throws Exception
     */
    public function namedQuery ( $query, $namesAndValues ) {
        $this->prepareQuery( $query );
        $this->executeArray( $query, $namesAndValues );
        return $this->fetchAllSafe( $query );
    }

    private function makeException ( $queryContext = null, $message = "" ) {
        $errorInfo = $this->db->errorInfo();
        if ( !empty( $errorInfo[1] ) && !empty( $errorInfo[2] ) ) {
            $message .= "\nDriver error #$errorInfo[1]: $errorInfo[2]";
        }
        if ( isset( $queryContext ) ) {
            $message .= "\nWith query:\n" . $queryContext;
        }
        return new Exception( $message );
    }

    private function makeExceptionFromPDOException ( PDOException $exception, $queryContext = null ) {
        $message = $exception->getMessage();
        if ( isset( $queryContext ) ) {
            $message .= "\nWith query:\n" . $queryContext;
        }
        return new Exception( $message );
    }

    private function makeStatementException ( PDOStatement $context, $message ) {
        $errorInfo = $context->errorInfo();
        if ( !empty( $errorInfo[1] ) && !empty( $errorInfo[2] ) ) {
            $message .= "\nDriver error #$errorInfo[1]: $errorInfo[2]";
        }
        $message .= "\nWith query:\n" . $context->queryString;
        return new Exception( $message );
    }

    private function getQuery ( $name ) {
        return empty( $this->prepared[$name] ) ? null : $this->prepared[$name];
    }

    /**
     * @param string $query
     *
     * @throws Exception
     */
    private function prepareQuery ( $query ) {
        if ( empty( $this->getQuery( $query ) ) ) {
            try {
                $prepared = $this->db->prepare( $query );
            } catch ( PDOException $exception ) {
                throw $this->makeExceptionFromPDOException( $exception, $query );
            }
            if ( false === $prepared ) {
                throw $this->makeException( $query, "Preparation failed" );
            } else {
                $this->prepared[$query] = $prepared;
            }
        }
    }

    /**
     * @param string $query
     * @param array  $values
     *
     * @return int
     * @throws Exception
     */
    private function executeArray ( $query, $values ) {
        $statement = $this->getQuery( $query );
        try {
            $executed = $statement->execute( $values );
            if ( false === $executed ) {
                throw $this->makeStatementException( $statement, "Execution failed" );
            }
        } catch ( PDOException $exception ) {
            throw $this->makeExceptionFromPDOException( $exception, $query );
        }
        return $statement->rowCount();
    }

    /**
     * @param string $query
     *
     * @return array|null
     * @throws Exception
     */
    private function fetchAllSafe ( $query ) {
        $statement = $this->getQuery( $query );
        $set = $statement->fetchAll( PDO::FETCH_ASSOC );
        if ( false === $set ) {
            throw $this->makeStatementException( $statement, "Fetching failed" );
        }
        $statement->closeCursor();
        return $set;
    }

    private $transaction = false;

    /**
     * @throws PDOException
     * @throws Exception
     */
    public function transaction () {
        if ( $this->transaction ) {
            throw new Exception( "Nested transactions are not supported" );
        } else {
            $success = $this->db->beginTransaction();
            if ( false === $success ) {
                throw $this->makeException( null, "Transaction failed" );
            }
            $this->transaction = $success;
        }
    }

    /**
     * @throws PDOException
     * @throws Exception
     */
    public function commit () {
        if ( $this->transaction ) {
            $success = $this->db->commit();
            if ( false === $success ) {
                throw $this->makeException( null, "Commit failed" );
            }
            $this->transaction = !$success;
        } else {
            throw new Exception( "Can only commit in transaction" );
        }
    }

    /**
     * @throws PDOException
     * @throws Exception
     */
    public function rollback () {
        if ( $this->transaction ) {
            $success = $this->db->rollBack();
            if ( false === $success ) {
                throw $this->makeException( null, "Rollback failed" );
            }
            $this->transaction = !$success;
        } else {
            throw new Exception( "Can only rollback in transaction" );
        }
    }

    public function lastInsert () {
        return $this->db->lastInsertId();
    }

    /**
     * @return false|string
     * @throws Exception
     */
    public function timestamp () {
        $stamp = date( "Y-m-d H:i:s" );
        if ( false === $stamp ) {
            throw new Exception( "Failed to create a timestamp - check logs" );
        }
        return $stamp;
    }
}

class UmiEntity
{
    private $db;
    private $path;

    function __construct ( PDODatabase $db, $path ) {
        $this->db = $db;
        $this->path = $path;
        @mkdir( $path, 755 );
    }

    public $propConversionMap = [
        "varchar_val" => "string",
        "text_val" => "string",
        "int_val" => "int",
        "float_val" => "float",
        "rel_val" => "int",
        "tree_val" => "int"
    ];

    function convertProperty ( $value, $type ) {
        settype( $value, $this->propConversionMap[$type] );
        return $value;
    }

    function remap ( $data, $map ) {
        foreach ( $map as $from => $to ) {
            if ( !empty( $data[$from] ) ) {
                $data[$to] = $data[$from];
                unset( $data[$from] );
            }
        }
        return $data;
    }

    function loadDataWhitelist ( $object, $whitelist, $remap ) {
        $rows = $this->db->query( "
SELECT of.name, of.title, oc.*
FROM cms3_object_content as oc
LEFT JOIN cms3_object_fields as `of` on oc.field_id = of.id
WHERE oc.obj_id = ?", $object["oid"] );
        $object["_fields"] = [];
        foreach ( $rows as $row ) {
            if ( empty( $whitelist[$row["name"]] ) ) {
                continue;
            }
            $typeValue = $this->firstSet( $row,
                "varchar_val",
                "text_val",
                "int_val",
                "float_val",
                "rel_val",
                "tree_val" );
            if ( !empty( $typeValue ) ) {
                $object["_fields"][$row["name"]] = $this->convertProperty( $typeValue[1], $typeValue[0] );
            }
        }
        $object["_fields"] = $this->remap( $object["_fields"], $remap );
        return $object;
    }

    function firstSet ( $value, ...$fields ) {
        foreach ( $fields as $field ) {
            if ( !empty( $value[$field] ) ) {
                return [$field, $value[$field]];
            }
        }
        return null;
    }

    function loadObjectsData ( &$objects, $whitelist, $remap ) {
        foreach ( $objects as $id => $object ) {
            $object = $this->loadDataWhitelist( $object, $whitelist, $remap );
            unset( $object["hid"] );
            unset( $object["oid"] );
            $objects[$id] = $object;
        }
        return $objects;
    }

    public $objectsByHierarchy = "
SELECT ch.id as hid, co.id as oid, ch.alt_name AS uri, co.name, ch.updatetime as `time` FROM cms3_hierarchy AS ch
LEFT JOIN cms3_objects AS co ON ch.obj_id = co.id
WHERE rel = ? AND is_active = 1 AND is_deleted = 0";
    public $rootObjectQuery = "
SELECT ch.id as hid, co.id as oid, ch.alt_name AS uri, co.name, ch.updatetime as `time` FROM cms3_hierarchy AS ch
LEFT JOIN cms3_objects AS co ON ch.obj_id = co.id
WHERE rel = 0 AND is_active = 1 AND is_deleted = 0 AND ch.alt_name = ?";

    function loadObjectsDataRecursive ( &$objects, $whitelist, $remap ) {
        foreach ( $objects as $id => $object ) {
            $object = $this->loadDataWhitelist( $object, $whitelist, $remap );
            $subObjects = $this->db->query( $this->objectsByHierarchy, $object["hid"] );
            if ( !empty( $subObjects ) ) {
                $this->loadObjectsDataRecursive( $subObjects, $whitelist, $remap );
                $object["_objects"] = $subObjects;
            }
            unset( $object["hid"] );
            unset( $object["oid"] );
            $objects[$id] = $object;
        }
        return $objects;
    }

    function loadRootObjectByAltName ( $altName, $whitelist, $remap ) {
        $object = $this->db->queryOne( $this->rootObjectQuery, $altName );
        $object = $this->loadDataWhitelist( $object, $whitelist, $remap );
        $subObjects = $this->db->query( $this->objectsByHierarchy, $object["hid"] );
        if ( !empty( $subObjects ) ) {
            $this->loadObjectsDataRecursive( $subObjects, $whitelist, $remap );
            $object["_objects"] = $subObjects;
        }
        unset( $object["hid"] );
        unset( $object["oid"] );
        return $object;
    }

    function loadObjectsByHierarchyRoot ( $rootId, $whitelist, $remap ) {
        $objects = $this->db->query( $this->objectsByHierarchy, $rootId );
        $this->loadObjectsData( $objects, $whitelist, $remap );
        return $objects;
    }

    function loadObjectsByHierarchyRootRecursive ( $rootId, $whitelist, $remap ) {
        $objects = $this->db->query( $this->objectsByHierarchy, $rootId );
        if ( !empty( $objects ) ) {
            $this->loadObjectsDataRecursive( $objects, $whitelist, $remap );
        }
        return $objects;
    }

    function j ( $to, $data ) {
        file_put_contents( "{$this->path}/$to.json", json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    }
}

class WpObject
{
    /**
     * @var PDODatabase
     */
    private $db;

    function __construct ( $db ) {
        $this->db = $db;
    }

    function setMeta ( $id, $key, $value = null ) {
        $this->db->execute( "DELETE FROM wp_postmeta WHERE post_id = ? AND meta_key = ?", $id, $key );
        if ( isset( $value ) ) {
            $this->db->execute( "INSERT INTO wp_postmeta ( post_id, meta_key, meta_value ) VALUES ( ?, ?, ? ) ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value)",
                $id, $key, $value );
        }
    }

    function updatePostSpecific ( $post, $attributes, $meta ) {
        if (empty($post['ID'])) {
            throw new \Exception("cant");
        }
        foreach ( $attributes as $key => $value ) {
            $this->db->execute( " UPDATE wp_posts SET `$key` = ? WHERE ID = ?", $value, $post['ID'] );
        }
        foreach ( $meta as $key => $value ) {
            $this->setMeta( $post["ID"], $key, $value );
        }
        return true;
    }

    function savePost ( $post, $meta ) {
        $date = date( "Y-m-d H:i:s", $post['time'] );
        $type = isset( $post['type'] ) ? $post['type'] : "post";
        $exists = $this->db->queryOne( "SELECT * FROM wp_posts WHERE post_type = ? AND post_name = ?", $type, $post['name'] );
        if ( empty( $exists ) ) {
            $this->db->execute( "
INSERT INTO wp_posts (post_author,post_title,post_content,post_name,guid,post_date,post_modified,post_date_gmt,post_modified_gmt,post_type,post_mime_type,post_parent)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                1,
                isset( $post['title'] ) ? $post['title'] : "",
                isset( $post['content'] ) ? $post['content'] : "",
                $post['name'],
                isset( $post['guid'] ) ? $post['guid'] : ( "imported-" . $post['name'] ),
                $date,
                $date,
                $date,
                $date,
                $type,
                isset( $post['mime'] ) ? $post['mime'] : "",
                isset( $post['parent'] ) ? $post['parent'] : 0
            );
            $exists = $this->db->queryOne( "SELECT * FROM wp_posts WHERE post_name = ?", $post['name'] );
        } else {
            $this->db->execute( "
UPDATE wp_posts SET post_title=?, post_content=?, guid=?, post_mime_type=?, post_parent=? WHERE post_type = ? AND post_name = ?",
                isset( $post['title'] ) ? $post['title'] : "",
                isset( $post['content'] ) ? $post['content'] : "",
                isset( $post['guid'] ) ? $post['guid'] : ( "imported-" . $post['name'] ),
                isset( $post['mime'] ) ? $post['mime'] : "",
                isset( $post['parent'] ) ? $post['parent'] : 0,
                isset( $post['type'] ) ? $post['type'] : "post",
                $post['name']
            );
        }
        if ( empty( $post['term'] ) ) {
            $this->db->execute( "DELETE FROM wp_term_relationships WHERE object_id = ?", $exists['ID'] );
        } else {
            $this->db->execute( "INSERT INTO wp_term_relationships ( object_id, term_taxonomy_id ) VALUES ( ?, ? ) ON DUPLICATE KEY UPDATE term_order=term_order",
                $exists['ID'], $post['term'] );
        }
//        "SELECT * FROM  `wp_postmeta` WHERE meta_key IN ('_wp_attachment_backup_sizes',  '_wp_attachment_metadata')";
        foreach ( $meta as $key => $value ) {
            $this->setMeta( $exists["ID"], $key, $value );
        }
        return $exists;
    }

    static $mimeMap = [
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "png" => "image/png",
        "gif" => "image/gif",
    ];

    function derivePreview ( $content, $title, $name, $time ) {
        $imageValues = [];
        $m = preg_match( "#src=[\"']\/wp-content\/uploads\/([^\"']+?)\.(jpe?g|png|bmp|gif)[\"']#", $content, $imageValues );
        if ( $m ) {
            $path = $imageValues[1];
            $mime = strtolower( $imageValues[2] );
            $mimeType = self::$mimeMap[$mime];
            if ( empty( $mimeType ) ) {
                return [];
            }
            return $this->saveImage(
                $time,
                "$path.$mime",
                $title,
                "thumb-" . $name,
                $mimeType
            );
        }
        return [];
    }

    function saveArticle ( $time, $title, $content, $name, $term, $metaTitle = null, $thumbImage = null ) {
        $content = $this->clearContent( $content );
        $derivedPreview = $this->derivePreview(
            $content,
            $title,
            $name,
            $time
        );
        return $this->savePost( [
            "time" => $time,
            "title" => $title,
            "content" => $content,
            "name" => $name,
            "term" => $term,
            "parent" => 0,
        ], [
            "_aioseop_title" => $metaTitle ? $metaTitle : $title,
            "_aioseop_description" => mb_substr( strip_tags( $content ), 0, 150 ) . "...",
            "_thumbnail_id" => isset( $derivedPreview["ID"] ) ? $derivedPreview["ID"] : null,
        ] );
    }

    function savePage ( $time, $title, $content, $name, $parent = null, $metaTitle = null, $thumbImage = null ) {
        $content = $this->clearContent( $content );
        $derivedPreview = $this->derivePreview(
            $content,
            $title,
            $name,
            $time
        );
        return $this->savePost( [
            "time" => $time,
            "title" => $title,
            "content" => $content,
            "name" => $name,
            "type" => "page",
            "parent" => $parent,
            "term" => null,
        ], [
            "_aioseop_title" => isset( $metaTitle ) ? $metaTitle : $title,
            "_aioseop_description" => mb_substr( strip_tags( $content ), 0, 150 ) . "...",
            "_thumbnail_id" => isset( $derivedPreview["ID"] ) ? $derivedPreview["ID"] : null,
        ] );
    }

    // post_mime_type=image/jpeg|png etc
    function saveImage ( $time, $path, $title, $name, $mime ) {
        return $this->savePost( [
            "time" => $time,
            "title" => $title,
            "name" => $name,
            "type" => "attachment",
            "mime" => $mime,
        ], [
            "_wp_attached_file" => $path
        ] );
    }

    function findPost ( $attributes ) {
        $values = [];
        foreach ( $attributes as $attribute => $value ) {
            if ( is_array( $value ) ) {
                foreach ( $value as $valueItem ) {
                    $values [] = $valueItem;
                }
                $attributes[$attribute] = "$attribute IN (" . join( ",", array_fill( 0, count( $value ), '?' ) ) . ")";
            } else {
                $values [] = $value;
                $attributes[$attribute] = "$attribute = ?";
            }
        }
        return $this->db->queryOne( "SELECT * FROM wp_posts WHERE " . join( " AND ", $attributes ), ...$values );
    }

    function postMeta ( $post ) {
        $rows = $this->db->query( "SELECT * FROM wp_postmeta WHERE post_id = ?", $post['ID'] );
        if (empty($rows)) {
            return [];
        }
        $result = [];
        foreach ( $rows as $row ) {
            $result[$row['meta_key']] = $row['meta_value'];
        }
        return $result;
    }

    function inConditionFromArray ( $field, $array ) {
        return "$field IN (" . join( ",", array_fill( 0, count( $array ), '?' ) ) . ")";
    }

    function findPostByMeta ( $attributes, $meta = [] ) {
        $values = [];
        foreach ( $attributes as $attribute => $value ) {
            if ( is_array( $value ) ) {
                foreach ( $value as $valueItem ) {
                    $values[] = $valueItem;
                }
                $attributes[$attribute] = $this->inConditionFromArray( $attribute, $value );
            } else {
                $values[] = $value;
                $attributes[$attribute] = "$attribute = ?";
            }
        }
        $conditions = [];
        if ( !empty( $attributes ) ) {
            $conditions[] = "(" . join( " AND ", $attributes ) . ")";
        }
        $metaCombos = [];
        foreach ( $meta as $key => $value ) {
            $values[] = $key;
            if ( is_array( $value ) ) {
                $metaCombos[] = "(meta_key = ? AND " . $this->inConditionFromArray( 'meta_value', $value ) . ")";
                foreach ( $value as $valueItem ) {
                    $values[] = $valueItem;
                }
            } else {
                $metaCombos[] = "(meta_key = ? AND meta_value = ?)";
                $values[] = $value;
            }
        }
        $metaJoin = "";
        if ( !empty( $metaCombos ) ) {
            $conditions[] = "(" . join( " OR ", $metaCombos ) . ")";
            $metaJoin = "LEFT JOIN wp_postmeta ON wp_posts.ID = wp_postmeta.post_id";
        }
        $query = "SELECT * FROM wp_posts $metaJoin WHERE " . join( " AND ", $conditions );
        try {
            $post = $this->db->queryOne( $query, ...$values );
        } catch ( \Exception $e ) {
            if ( strpos( $e->getMessage(), "Invalid parameter number" ) ) {
                print_r( $query );
                print_r( $values );
                die;
            }
        }
        return $post;
    }

    function bestGuessPost ( $object, $parentPath, $parentId = null ) {
        $guess = null;
        $post = null;
        if ( isset( $parentId ) ) {
            $post = $this->findPost( ['post_name' => $object['uri'], 'post_parent' => $parentId] );
        } else {
            $post = $this->findPost( ['post_name' => $object['uri']] );
        }
        $guessingTitle = [];
        if ( isset( $object['name'] ) ) {
            $guessingTitle[] = $object['name'];
        }
        if ( isset( $object['_fields']['h1'] ) ) {
            $guessingTitle[] = $object['_fields']['h1'];
        }
        if ( isset( $object['_fields']['title'] ) ) {
            $guessingTitle[] = $object['_fields']['title'];
        }
        if ( empty( $post ) && !empty( $guessingTitle ) ) {
            if ( isset( $parentId ) ) {
                $post = $this->findPost( ['post_title' => $guessingTitle, 'post_parent' => $parentId] );
            } else {
                $post = $this->findPost( ['post_title' => $guessingTitle] );
            }
            if ( !empty( $post ) ) {
                $guess = $parentPath . "/" . $post['post_name'];
            }
            if ( empty( $post ) ) {
                if ( isset( $parentId ) ) {
                    $post = $this->findPostByMeta( ['post_parent' => $parentId], ['h1' => $guessingTitle, 'title' => $guessingTitle] );
                } else {
                    $post = $this->findPostByMeta( [], ['h1' => $guessingTitle, 'title' => $guessingTitle] );
                }
                if ( !empty( $post ) ) {
                    $guess = $parentPath . "/" . $post['post_name'];
                }
            }
        }
        return [$post, $guess];
    }

    function traverseCheck ( &$result, $object, $parentPath, $parentId = null ) {
        list( $post, $guess ) = $this->bestGuessPost( $object, $parentPath, $parentId );
        $path = $parentPath . "/" . $object['uri'];
        if ( empty( $post ) ) {
            $result['missing'][] = $path;
        } else {
            if ( $guess ) {
                if ( isset( $result['guessed'][$post['ID']] ) ) {
                    $result['guess-collision'][$post['ID']] = "$guess => $path";
                } else {
                    $result['guessed'][$post['ID']] = "$guess => $path";
                }
            } else {
                $result['found'][] = $path;
            }
        }
        if ( isset( $object['_objects'] ) ) {
            foreach ( $object['_objects'] as $sub ) {
                $this->traverseCheck( $result, $sub, $path, isset( $post['ID'] ) ? $post['ID'] : null );
            }
        }
    }

    function firstSet ( $array, $lookup ) {
        foreach ( $lookup as $item ) {
            if ( !empty( $array[$item] ) ) {
                return $array[$item];
            }
        }
        return null;
    }

    function convergeFirstSet ( &$into, $key, $from, $lookup ) {
        $value = $this->firstSet( $from, $lookup );
        if ( !empty( $value ) ) {
            $into[$key] = $value;
        }
    }

    function convergeFirstSets ( &$into, $from, $map ) {
        foreach ( $map as $key => $lookup ) {
            $this->convergeFirstSet( $into, $key, $from, $lookup );
        }
    }

    function sanitizeMeta ( $value ) {
        $value = html_entity_decode( strip_tags( $value ) );
        $value = preg_replace( "#\s+#", " ", $value );
        return trim( $value );
    }

    function sanitizeMetas ( $metas ) {
        foreach ( $metas as $index => $meta ) {
            $metas[$index] = $this->sanitizeMeta( $meta );
        }
        return $metas;
    }

    function traverseUpdate ( &$result, $object, $parentPath, $parentId = null ) {
        list( $post, $guess ) = $this->bestGuessPost( $object, $parentPath, $parentId );
        $path = $parentPath . "/" . $object['uri'];
        $updating = false;
        if ( empty( $post ) ) {
            $result['missing'][] = $path;
        } else {
            if ( $guess ) {
                if ( isset( $result['guessed'][$post['ID']] ) ) {
                    $result['guess-collision'][$post['ID']] = "$guess => $path";
                } else {
                    $result['guessed'][$post['ID']] = "$guess => $path";
                    $updating = true;
                }
            } else {
                $result['found'][] = $path;
                $updating = true;
            }
        }
        if ( $updating ) {
            $metas = [];
            $attributes = [];
            $this->convergeFirstSets( $metas, $object['_fields'], [
                'h1' => ['h1', 'title'],
                '_aioseop_title' => ['title', 'h1'],
                '_aioseop_description' => ['meta_descriptions'],
            ] );
            $update = ['post' => $post['ID']];
            if ( $post['post_name'] != $object['uri'] ) {
                $attributes['post_name'] = $object['uri'];
                $update['from'] = $post['post_name'];
                $update['to'] = $object['uri'];
            }
            $update['meta-from'] = $this->filterSet( $this->postMeta( $post ), ['h1', '_aioseop_title', '_aioseop_description'] );
            $update['meta-to'] = $this->sanitizeMetas( $metas );
            $result['updated'][] = $update;
            $this->updatePostSpecific( $post, $attributes, $metas );
        }
        if ( isset( $object['_objects'] ) ) {
            foreach ( $object['_objects'] as $sub ) {
                $this->traverseUpdate( $result, $sub, $path, isset( $post['ID'] ) ? $post['ID'] : null );
            }
        }
    }

    function filterSet ( $set, $lookups ) {
        $filtered = [];
        foreach ( $lookups as $lookup ) {
            if ( !empty( $set[$lookup] ) ) {
                $filtered[$lookup] = $set[$lookup];
            }
        }
        return $filtered;
    }

    function clearContent ( $content ) {
        $content = preg_replace( "#href=([\"'])(https?:[\\\/]+)?(www)?\.chipmaster\.ru#", "href=$1", $content );
        $content = preg_replace( "#src=([\"'])\/#", "src=$1/wp-content/uploads/", $content );
        $content = preg_replace( "#src=([\"'])(https?:[\\\/]+)?(www\.)?chipmaster\.ru#", "src=$1/wp-content/uploads", $content );
        return $content;
    }
}

function j ( $to, $data ) {
    file_put_contents( __DIR__ . "/xport/$to.json", json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

function l ( $name ) {
    return json_decode( file_get_contents( __DIR__ . "/xport/$name.json" ), 1 );
}

function ca ( $a ) {
    global $ca;
    return in_array( $a, $ca );
}

function ci ( $a ) {
    global $ca;
    $ca = $a;
}