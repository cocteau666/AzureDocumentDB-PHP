AzureDocumentDB-PHP
===================

Azure Cosmos DB (Document DB) REST API Wrapper for PHP.  
Pear HTTP_Request2 module needed.


New
===================
- Add "Trigger" support (2.7)
- Add "User Defined Function" support (2.6)
- Add "Stored Procedure" support (2.5)
- Add "Permission" support (2.4)
- Add "Offer" support (2.3)
- Update API versipon "2017-02-22" (2.2)
- Add "DocDB" Command Line Tool to check SQL directly.
- Add "deleteDocument" method (2.1).
- HTTP_Request2 needed (2.0).


Info
===================
For using Azure Cosmos DB, select API "SQL (DocumentDB)".
Now these APIs are supported.

- Database
- User
- Collection
- Document
- Attachment
- Offer
- Permission
- Stored Procedure
- User Defined Function
- Trigger


Sample
===================


    <?php
    require_once 'phpdocumentdb.php';
      
    $host = 'https://example.documents.azure.com';
    $master_key = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
    
    // connect DocumentDB
    $documentdb = new DocumentDB($host, $master_key, function($request)
    {
        echo 'Database Error('.$request['status'].'): '.$request['body']."\n";
        exit;
    });
    
    // select Database or create
    $db = Util::db_selectDB("db_test");
    
    // select Collection or create
    $col = $db->selectCollection("col_test");

    echo "\n+++ Store Document: \n";
    
    // store JSON document ("id" needed)
    $data = '{"id":"1234567890", "FirstName": "Paul","LastName": "Smith"}';
    $result = $col->createDocument($data);
    
    // run query
    $result = $col->query("SELECT * FROM col_test");
    
    // Debug
    var_dump($result['data']->Documents);

    echo "\n+++ Delete Document (simple): \n";
    
    // get document ResourceID
    $result = $col->query("SELECT col_test._rid FROM col_test");
    var_dump($result['data']->Documents);

    // delete document (specify document _rid when created)
    $rid = $result['data']->Documents[0]->_rid;
    echo "Delete (Empty Response): > ".$col->deleteDocument($rid)['body']."<\n";
    
    echo "\n+++ Delete Document (advanced): \n";
    
    $data = '{"id":"1234567890", "FirstName": "Paul","LastName": "Smith"}';
    $result = $col->createDocument($data);
    $result = $col->query("SELECT * FROM col_test");
    
    // Get ressource element tag, ressource id
    $etag1 = $result['data']->Documents[0]->_etag;
    $rid1  = $result['data']->Documents[0]->_rid;
    
    echo "Empty Response (unchanged): >".$col->getDocument($rid1, $etag1)['body']."<\n";
    
    // create document (override existing)
    $result = $col->createDocument($data, true);
                
    // Get ressource element tag
    $etag2 = $result['data']->_etag;
    $rid2  = $result['data']->_rid;
    
    // Same Ressource:
    echo "rid1: $rid1\n";
    echo "rid2: $rid2\n";
    
    // Different etags:
    echo "etag1: $etag1\n";
    echo "etag2: $etag2\n";

    // Delete ressource if etag mataches (failes)
    echo "Delete (PreconditionFailed): > ".$col->deleteDocument($rid1, $etag1)['body']."<\n";
    
    // Check if ressource changed

    echo "New Ressource    (changed): >".$col->getDocument($rid1, $etag1)['body']."<\n";
    echo "Empty Response (unchanged): >".$col->getDocument($rid1, $etag2)['body']."<\n";
    
    // Delete ressource if etag mataches (succedes)
    echo "Delete (Empty Response): > ".$col->deleteDocument($rid1, $etag2)['body']."<\n";
    
    echo "\n+++ Replace Document: \n";
    
    $data = '{"id":"1234567890", "FirstName": "Paul","LastName": "Smith"}';
    $result = $col->createDocument($data);
    $result = $col->query("SELECT * FROM col_test");
    $etag1 = $result['data']->Documents[0]->_etag;
    $rid1  = $result['data']->Documents[0]->_rid;
    
    // Assume someone else modified our record:
    $data = '{"id":"1234567890", "FirstName": "Peter","LastName": "Smith"}';
    $result = $col->createDocument($data, true);
    $etag2 = $result['data']->_etag;
    
    // Different etags:
    echo "etag1: $etag1\n";
    echo "etag2: $etag2\n";
    
    // Replace Record:
    $data = '{"id":"1234567890", "FirstName": "Paul","LastName": "Parker"}';            
    echo "Replace (PreconditionFailed): > ".$col->replaceDocument($rid1, $data, $etag1)['body']."<\n";
    
    // getDocument, merge changes if possible, retry with new etag...
    
    // clean up
    echo "Delete (Empty Response): > ".$col->deleteDocument($rid1)['body']."<\n";


DocDB (Command line tool for executing SQL on Azure DocumentDB)
===================
Before execute, you don't forget to install "HTTP_Request2" !!

usage: php DocDB -h URI -k PRIMARY/SECONDARY_KEY -d Database -c Collection -q SQL


DocAdd (Command line tool for creating documents on Azure DocumentDB)
===================
Before execute, you don't forget to install "HTTP_Request2" !!

usage: php DocAdd -h URI -k PRIMARY/SECONDARY_KEY -d Database -c Collection -f JSON_file

