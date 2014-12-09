AzureDocumentDB-PHP
===================

Azure DocumentDB REST API Wrapper for PHP.

Sample
===================


    <?php
    require_once 'phpdocumentdb.php';
      
    $host = 'https://example.documents.azure.com';
    $master_key = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
    
    // connect DocumentDB
    $documentdb = new DocumentDB($host, $master_key);
    
    // select Database or create
    $db = $documentdb->selectDB("db_test");
    
    // select Collection or create
    $col = $db->selectCollection("col_test");
    
    // run query
    $json = $col->query("SELECT * FROM col_test");
    
    // Debug
    $object = json_encode($json);
    var_dump($object);
