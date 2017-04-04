<?php
/*
 * Copyright (C) 2014 - 2017 Takeshi SAKURAI <sakurai@pnop.co.jp>
 *      http://www.pnop.co.jp/
 *
 * Licensed under the Apache License, Version 2.0 (the &quot;License&quot;);
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an &quot;AS IS&quot; BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Microsoft Azure Document DB Library for PHP
 *
 * Wrapper class of Document DB REST API
 *
 * @link http://msdn.microsoft.com/en-us/library/azure/dn781481.aspx
 * @version 2.1
 * @author Takeshi SAKURAI <sakurai@pnop.co.jp>
 * @since PHP 5.3
 */
require_once 'HTTP/Request2.php';

class DocumentDBDatabase
{
  private $document_db;
  private $rid_db;

  public function __construct($document_db, $rid_db)
  {
    $this->document_db = $document_db;
    $this->rid_db      = $rid_db;
  }

  /**
   * selectCollection
   *
   * @access public
   * @param string $col_name Collection name
   */
  public function selectCollection($col_name)
  {
    $rid_col = false;
    $object = json_decode($this->document_db->listCollections($this->rid_db));
    $col_list = $object->DocumentCollections;
    for ($i=0; $i<count($col_list); $i++) {
      if ($col_list[$i]->id === $col_name) {
        $rid_col = $col_list[$i]->_rid;
      }
    }
    if (!$rid_col) {
      $object = json_decode($this->document_db->createCollection($this->rid_db, '{"id":"' . $col_name . '"}'));
      $rid_col = $object->_rid;
    }
    if ($rid_col) {
      return new DocumentDBCollection($this->document_db, $this->rid_db, $rid_col);
    } else {
      return false;
    }
  }

}

class DocumentDBCollection
{
  private $document_db;
  private $rid_db;
  private $rid_col;

  /**
   * __construct
   *
   * @access public
   * @param DocumentDB $document_db DocumentDB object
   * @param string $rid_db Database ID
   * @param string $rid_col Collection ID
   */
  public function __construct($document_db, $rid_db, $rid_col)
  {
    $this->document_db = $document_db;
    $this->rid_db      = $rid_db;
    $this->rid_col     = $rid_col;
  }

  /**
   * query
   * @access public
   * @param string $query Query
   * @return string JSON strings
   */
  public function query($query)
  {
    return $this->document_db->query($this->rid_db, $this->rid_col, $query);
  }

  /**
   * createDocument
   *
   * @access public
   * @param string $json JSON formatted document
   * @return string JSON strings
   */
  public function createDocument($json)
  {
    return $this->document_db->createDocument($this->rid_db, $this->rid_col, $json);
  }

  /**
   * deleteDocument
   *
   * @access public
   * @param  string $rid document ResourceID (_rid)
   * @return string JSON strings
   */
  public function deleteDocument($rid)
  {
    return $this->document_db->deleteDocument($this->rid_db, $this->rid_col, $rid);
  }

  /**
   * replaceDocument
   * 
   * @access public
   * @param  string $rid_doc Resource Doc ID
   * @param  string JSON strings
   * @return string JSON strings
   */
  public function replaceDocument($rid_doc, $json)
  {
    return $this->document_db->replaceDocument($this->rid_db, $this->rid_col, $rid_doc, $json);
  }

}

class DocumentDB
{
  private $host;
  private $master_key;
  private $debug;

  /**
   * __construct
   *
   * @access public
   * @param string $host       URI of Key
   * @param string $master_key Primary or Secondary key
   * @param bool   $debug      true: return Response Headers and JSON(if you need), false(default): return JSON only
   */
  public function __construct($host, $master_key, $debug = false)
  {
    $this->host       = $host;
    $this->master_key = $master_key;
    $this->debug      = $debug;
  }

  /**
   * getAuthHeaders
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn783368.aspx
   * @access private
   * @param string $verb          Request Method (GET, POST, PUT, DELETE)
   * @param string $resource_type Resource Type
   * @param string $resource_id   Resource ID
   * @return string Array of Request Headers
   */
  private function getAuthHeaders($verb, $resource_type, $resource_id)
  {
    $x_ms_date = gmdate('D, d M Y H:i:s T', strtotime('+2 minutes'));
    $master = 'master';
    $token = '1.0';
    $x_ms_version = '2016-07-11';

    $key = base64_decode($this->master_key);
    $string_to_sign = $verb . "\n" .
                      $resource_type . "\n" .
                      $resource_id . "\n" .
                      $x_ms_date . "\n" .
                      "\n";

    $sig = base64_encode(hash_hmac('sha256', strtolower($string_to_sign), $key, true));

    return Array(
             'Accept: application/json',
             'User-Agent: documentdb.php.sdk/1.0.0',
             'Cache-Control: no-cache',
             'x-ms-date: ' . $x_ms_date,
             'x-ms-version: ' . $x_ms_version,
             'authorization: ' . urlencode("type=$master&ver=$token&sig=$sig")
           );
  }

  /**
   * request
   *
   * use cURL functions
   *
   * @access private
   * @param string $path    request path
   * @param string $method  request method
   * @param array  $headers request headers
   * @param string $body    request body (JSON or QUERY)
   * @return string JSON response
   */
  private function request($path, $method, $headers, $body = NULL)
  {
    $request = new Http_Request2($this->host . $path);
    $request->setHeader($headers);
    if ($method === "GET") {
      $request->setMethod(HTTP_Request2::METHOD_GET);
    } else if ($method === "POST") {
      $request->setMethod(HTTP_Request2::METHOD_POST);
    } else if ($method === "PUT") {
      $request->setMethod(HTTP_Request2::METHOD_PUT);
    } else if ($method === "DELETE") {
      $request->setMethod(HTTP_Request2::METHOD_DELETE);
    }
    if ($body) {
      $request->setBody($body);
    }
    try
    {
      $response = $request->send();
      return $response->getBody();
    }
    catch (HttpException $ex)
    {
      return $ex;
    }

  }

  /**
   * selectDB
   *
   * @access public
   * @param string $db_name Database name
   * @return DocumentDBDatabase class
   */
  public function selectDB($db_name)
  {
    $rid_db = false;
    $object = json_decode($this->listDatabases());
    $db_list = $object->Databases;
    for ($i=0; $i<count($db_list); $i++) {
      if ($db_list[$i]->id === $db_name) {
        $rid_db = $db_list[$i]->_rid;
      }
    }
    if (!$rid_db) {
      $object = json_decode($this->createDatabase('{"id":"' . $db_name . '"}'));
      $rid_db = $object->_rid;
    }
    if ($rid_db) {
      return new DocumentDBDatabase($this, $rid_db);
    } else {
      return false;
    }
  }

  /**
   * getInfo
   *
   * @access public
   * @return string JSON response
   */
  public function getInfo()
  {
    $headers = $this->getAuthHeaders('GET', '', '');
    $headers[] = 'Content-Length:0';
    return $this->request("", "GET", $headers);
  }

  /**
   * query
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn783363.aspx
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @param string $query   Query
   * @return string JSON response
   */
  public function query($rid_id, $rid_col, $query)
  {
    $headers = $this->getAuthHeaders('POST', 'docs', $rid_col);
    $headers[] = 'Content-Length:' . strlen($query);
    $headers[] = 'Content-Type:application/sql';
    $headers[] = 'x-ms-max-item-count:-1';
    $headers[] = 'x-ms-documentdb-isquery:True';
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs", "POST", $headers, $query);
  }

  /**
   * listDatabases
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803945.aspx
   * @access public
   * @return string JSON response
   */
  public function listDatabases()
  {
    $headers = $this->getAuthHeaders('GET', 'dbs', '');
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs", "GET", $headers);
  }

  /**
   * getDatabase
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803937.aspx
   * @access public
   * @param string $rid_id Resource ID
   * @return string JSON response
   */
  public function getDatabase($rid_id)
  {
    $headers = $this->getAuthHeaders('GET', 'dbs', $rid_id);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id, "GET", $headers);
  }

  /**
   * createDatabase
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803954.aspx
   * @access public
   * @param string $json JSON request
   * @return string JSON response
   */
  public function createDatabase($json)
  {
    $headers = $this->getAuthHeaders('POST', 'dbs', '');
    $headers[] = 'Content-Length:' . strlen($json);
    return $this->request("/dbs", "POST", $headers, $json);
  }

  /**
   * replaceDatabase
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803943.aspx
   * @access public
   * @param string $rid_id Resource ID
   * @param string $json   JSON request
   * @return string JSON response
   */
  public function replaceDatabase($rid_id, $json)
  {
    $headers = $this->getAuthHeaders('PUT', 'dbs', $rid_id);
    $headers[] = 'Content-Length:' . strlen($json);
    return $this->request("/dbs/" . $rid_id, "PUT", $headers, $json);
  }

  /**
   * deleteDatabase
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803942.aspx
   * @access public
   * @param string $rid_id Resource ID
   * @return string JSON response
   */
  public function deleteDatabase($rid_id)
  {
    $headers = $this->getAuthHeaders('DELETE', 'dbs', $rid_id);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id, "DELETE", $headers);
  }

  /**
   * listUsers
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803958.aspx
   * @access public
   * @param string $rid_id Resource ID
   * @return string JSON response
   */
  public function listUsers($rid_id)
  {
    $headers = $this->getAuthHeaders('GET', 'users', $rid_id);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/users", "GET", $headers);
  }

  /**
   * getUser
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803949.aspx
   * @access public
   * @param string $rid_id   Resource ID
   * @param string $rid_user Resource User ID
   * @return string JSON response
   */
  public function getUser($rid_id, $rid_user)
  {
    $headers = $this->getAuthHeaders('GET', 'users', $rid_user);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/users/" . $rid_user, "GET", $headers);
  }

  /**
   * createUser
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803946.aspx
   * @access public
   * @param string $rid_id Resource ID
   * @param string $json   JSON request
   * @return string JSON response
   */
  public function createUser($rid_id, $json)
  {
    $headers = $this->getAuthHeaders('POST', 'users', $rid_id);
    $headers[] = 'Content-Length:' . strlen($json);
    return $this->request("/dbs/" . $rid_id . "/users", "POST", $headers, $json);
  }

  /**
   * replaceUser
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803941.aspx
   * @access public
   * @param string $rid_id   Resource ID
   * @param string $rid_user Resource User ID
   * @param string $json     JSON request
   * @return string JSON response
   */
  public function replaceUser($rid_id, $rid_user, $json)
  {
    $headers = $this->getAuthHeaders('PUT', 'users', $rid_user);
    $headers[] = 'Content-Length:' . strlen($json);
    return $this->request("/dbs/" . $rid_id . "/users/" . $rid_user, "PUT", $headers, $json);
  }

  /**
   * deleteUser
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803953.aspx
   * @access public
   * @param string $rid_id   Resource ID
   * @param string $rid_user Resource User ID
   * @return string JSON response
   */
  public function deleteUser($rid_id, $rid_user)
  {
    $headers = $this->getAuthHeaders('DELETE', 'users', $rid_user);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/users/" . $rid_user, "DELETE", $headers);
  }

  /**
   * listCollections
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803935.aspx
   * @access public
   * @param string $rid_id Resource ID
   * @return string JSON response
   */
  public function listCollections($rid_id)
  {
    $headers = $this->getAuthHeaders('GET', 'colls', $rid_id);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/colls", "GET", $headers);
  }

  /**
   * getCollection
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803951.aspx
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @return string JSON response
   */
  public function getCollection($rid_id, $rid_col)
  {
    $headers = $this->getAuthHeaders('GET', 'colls', $rid_col);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col, "GET", $headers);
  }

  /**
   * createCollection
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803934.aspx
   * @access public
   * @param string $rid_id Resource ID
   * @param string $json   JSON request
   * @return string JSON response
   */
  public function createCollection($rid_id, $json)
  {
    $headers = $this->getAuthHeaders('POST', 'colls', $rid_id);
    $headers[] = 'Content-Length:' . strlen($json);
    return $this->request("/dbs/" . $rid_id . "/colls", "POST", $headers, $json);
  }

  /**
   * deleteCollection
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803953.aspx
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @return string JSON response
   */
  public function deleteCollection($rid_id, $rid_col)
  {
    $headers = $this->getAuthHeaders('DELETE', 'colls', $rid_col);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col, "DELETE", $headers);
  }

  /**
   * listDocuments
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803955.aspx
   * @access public
   * @param string $rid_id Resource ID
   * @param string $rid_colResource Collection ID
   * @return string JSON response
   */
  public function listDocuments($rid_id, $rid_col)
  {
    $headers = $this->getAuthHeaders('GET', 'docs', $rid_col);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs", "GET", $headers);
  }

  /**
   * getDocument
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803957.aspx
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @param string $rid_doc Resource Doc ID
   * @return string JSON response
   */
  public function getDocument($rid_id, $rid_col, $rid_doc)
  {
    $headers = $this->getAuthHeaders('GET', 'docs', $rid_doc);
    $headers[] = 'Content-Length:0';
    $options = array(
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_HTTPGET => true,
    );
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs/" . $rid_doc, "GET", $headers);
  }

  /**
   * createDocument
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803948.aspx
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @param string $json    JSON request
   * @return string JSON response
   */
  public function createDocument($rid_id, $rid_col, $json)
  {
    $headers = $this->getAuthHeaders('POST', 'docs', $rid_col);
    $headers[] = 'Content-Length:' . strlen($json);
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs", "POST", $headers, $json);
  }

  /**
   * replaceDocument
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803947.aspx
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @param string $rid_doc Resource Doc ID
   * @param string $json    JSON request
   * @return string JSON response
   */
  public function replaceDocument($rid_id, $rid_col, $rid_doc, $json)
  {
    $headers = $this->getAuthHeaders('PUT', 'docs', $rid_doc);
    $headers[] = 'Content-Length:' . strlen($json);
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs/" . $rid_doc, "PUT", $headers, $json);
  }

  /**
   * deleteDocument
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803952.aspx
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @param string $rid_doc Resource Doc ID
   * @return string JSON response
   */
  public function deleteDocument($rid_id, $rid_col, $rid_doc)
  {
    $headers = $this->getAuthHeaders('DELETE', 'docs', $rid_doc);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs/" . $rid_doc, "DELETE", $headers);
  }

  /**
   * listAttachments
   *
   * @link http://
   * @access public
   * @param string $rid_id Resource ID
   * @param string $rid_colResource Collection ID
   * @param string $rid_doc Resource Doc ID
   * @return string JSON response
   */
  public function listAttachments($rid_id, $rid_col, $rid_doc)
  {
    $headers = $this->getAuthHeaders('GET', 'attachments', $rid_doc);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs/" . $rid_doc . "/attachments", "GET", $headers);
  }

  /**
   * getAttachment
   *
   * @link http://
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @param string $rid_doc Resource Doc ID
   * @param string $rid_at  Resource Attachment ID
   * @return string JSON response
   */
  public function getAttachment($rid_id, $rid_col, $rid_doc, $rid_at)
  {
    $headers = $this->getAuthHeaders('GET', 'attachments', $rid_at);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs/" . $rid_doc . "/attachments/" . $rid_at, "GET", $headers);
  }

  /**
   * createAttachment
   *
   * @link http://msdn.microsoft.com/en-us/library/azure/dn803933.aspx
   * @access public
   * @param string $rid_id       Resource ID
   * @param string $rid_col      Resource Collection ID
   * @param string $rid_doc      Resource Doc ID
   * @param string $content_type Content-Type of Media
   * @param string $filename     Attachement file name
   * @param string $file         URL encoded Attachement file (Raw Media)
   * @return string JSON response
   */
  public function createAttachment($rid_id, $rid_col, $rid_doc, $content_type, $filename, $file)
  {
    $headers = $this->getAuthHeaders('POST', 'attachments', $rid_doc);
    $headers[] = 'Content-Length:' . strlen($file);
    $headers[] = 'Content-Type:' . $content_type;
    $headers[] = 'Slug:' . urlencode($filename);
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs/" . $rid_doc . "/attachments", "POST", $headers, $file);
  }

  /**
   * replaceAttachment
   *
   * @link http://
   * @access public
   * @param string $rid_id       Resource ID
   * @param string $rid_col      Resource Collection ID
   * @param string $rid_doc      Resource Doc ID
   * @param string $rid_at       Resource Attachment ID
   * @param string $content_type Content-Type of Media
   * @param string $filename     Attachement file name
   * @param string $file         URL encoded Attachement file (Raw Media)
   * @return string JSON response
   */
  public function replaceAttachment($rid_id, $rid_col, $rid_doc, $rid_at, $content_type, $filename, $file)
  {
    $headers = $this->getAuthHeaders('PUT', 'attachments', $rid_at);
    $headers[] = 'Content-Length:' . strlen($file);
    $headers[] = 'Content-Type:' . $content_type;
    $headers[] = 'Slug:' . urlencode($filename);
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs/" . $rid_doc . "/attachments/" . $rid_at, "PUT", $headers, $file);
  }

  /**
   * deleteAttachment
   *
   * @link http://
   * @access public
   * @param string $rid_id  Resource ID
   * @param string $rid_col Resource Collection ID
   * @param string $rid_doc Resource Doc ID
   * @param string $rid_at  Resource Attachment ID
   * @return string JSON response
   */
  public function deleteAttachment($rid_id, $rid_col, $rid_doc, $rid_at)
  {
    $headers = $this->getAuthHeaders('DELETE', 'attachments', $rid_at);
    $headers[] = 'Content-Length:0';
    return $this->request("/dbs/" . $rid_id . "/colls/" . $rid_col . "/docs/" . $rid_doc . "/attachments/" . $rid_at, "DELETE", $headers);
  }

}

