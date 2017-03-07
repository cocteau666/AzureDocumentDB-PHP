AzureDocumentDB-PHP
===================

Azure DocumentDB REST API Wrapper for PHP.  
Pear HTTP_Request2 module needed.

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

    // store JSON document ("id" needed)
    $data = '{"id":1234567890, "FirstName": "Paul","LastName": "Smith"}';
    $result = $col->createDocument($data);
    
    // run query
    $json = $col->query("SELECT * FROM col_test");
    
    // Debug
    $object = json_decode($json);
    var_dump($object->Documents);

    // delete document (specify document id when created)
    $id = "1234567890";
    echo $col->deleteDocument($id);

