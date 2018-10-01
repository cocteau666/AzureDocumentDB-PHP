<?php

namespace Jupitern\CosmosDb;

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
 * @version 2.8
 * @author Takeshi SAKURAI <sakurai@pnop.co.jp>
 * @since PHP 5.3
 */

class CosmosDbDatabase
{
    private $document_db;
    private $rid_db;

    public function __construct($document_db, $rid_db)
    {
        $this->document_db = $document_db;
        $this->rid_db = $rid_db;
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
        for ($i = 0; $i < count($col_list); $i++) {
            if ($col_list[$i]->id === $col_name) {
                $rid_col = $col_list[$i]->_rid;
            }
        }
        if (!$rid_col) {
            $object = json_decode($this->document_db->createCollection($this->rid_db, '{"id":"' . $col_name . '"}'));
            $rid_col = $object->_rid;
        }
        if ($rid_col) {
            return new CosmosDbCollection($this->document_db, $this->rid_db, $rid_col);
        } else {
            return false;
        }
    }

}
