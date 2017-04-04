AzureDocumentDB-PHP
===================

Azure DocumentDB REST API Wrapper for PHP.  
Pear HTTP_Request2 module needed.


New
===================
Add "deleteDocument" method (2.1)
HTTP_Request2 needed (2.0)


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

    // replace Document
    $paulSmith = $object[0];
    $paulSmith->age = '1946-07-05';
    $col->replaceDocument($paulSmith->_rid, $paulSmith);
    
    // get document ResourceID
    $json = $col->query("SELECT col_test._rid FROM col_test");
    $object = json_decode($json);
    var_dump($object->Documents);

    // delete document (specify document _rid when created)
    $rid = "In4LANe-bbAAAAAAAAAAAA==";
    echo $col->deleteDocument($rid);

