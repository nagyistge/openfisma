<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * Search engine backend based on the PECL solr extension
 *
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
class Fisma_Search_Backend_Solr extends Fisma_Search_Backend_Abstract
{
    /**
     * Client object is used for communicating with Solr server
     *
     * @var SolrClient
     */
    private $_client;

    /**
     * Constructor
     *
     * @param string $hostname Hostname or IP address where Solr's servlet container is running
     * @param int $port The port that Solr's servlet container is listening on
     * @param string $path The path within the servlet container that Solr is running on
     */
    public function __construct($hostname, $port, $path)
    {
        $clientConfig = array(
            'hostname' => $hostname,
            'port' => $port,
            'path' => $path
        );

        $this->_client = new SolrClient($clientConfig);
    }

    /**
     * Delete all documents in the index
     */
    public function deleteAll()
    {
        $this->_client->deleteByQuery('*:*');

        $this->_client->commit();
    }

    /**
     * Delete all documents of the specified type in the index
     *
     * "Type" refers to a model, such as Asset, Finding, Incident, etc.
     *
     * @param string $type
     */
    public function deleteByType($type)
    {
        $this->_client->deleteByQuery('luceneDocumentType:' . $type);

        $this->_client->commit();
    }

    /**
     * Delete the specified object from the index
     *
     * The $object needs to belong to a table which implements Fisma_Search_Searchable
     *
     * @param Fisma_Doctrine_Record $object
     */
    public function deleteObject($object)
    {
        $luceneDocumentId = get_class($object) . $object->id;

        $this->_client->deleteById($luceneDocumentId);

        $this->_client->commit();

    }

    /**
     * Index an array of objects
     *
     * @param string $type The class of the object
     * @param array $collection
     */
    public function indexCollection($type, $collection)
    {
        $documents = $this->_convertCollectionToDocumentArray($type, $collection);

        $this->_client->addDocuments($documents);

        $this->_client->commit();
    }

    /**
     * Add the specified object (in array format) to the search engine index
     *
     * This will overwrite any existing object with the same luceneDocumentId
     *
     * @param string $type The class of the object
     * @param array $object
     */
    public function indexObject($type, $object)
    {
        $document = $this->_convertObjectToDocument($type, $object);

        $this->_client->addDocument($document);

        $this->_client->commit();
    }

    /**
     * Returns true if the specified column is sortable
     *
     * This is defined in the search abstraction layer since ultimately the sorting capability is determined by the
     * search engine implementation.
     *
     * In Solr, sorting is only available for stored, un-analyzed, single-valued fields.
     *
     * @param string $type The class containing the column
     * @param string $columnName
     * @return bool
     */
    public function isColumnSortable($type, $columnName)
    {
        $table = Doctrine::getTable($type);

        if (!($table instanceof Fisma_Search_Searchable)) {
            throw new Fisma_Search_Exception("This table is not searchable: $type");
        }

        $searchableFields = $table->getSearchableFields();

        return isset($searchableFields[$columnName]['sortable']) && $searchableFields[$columnName]['sortable'];
    }

    /**
     * Optimize the index (degfragments the index)
     */
    public function optimizeIndex()
    {
        $this->_client->optimize();
    }

    /**
     * Simple search: search all fields for the specified keyword
     *
     * If keyword is null, then this is just a listing of all documents of a specific type
     *
     * @param string $type Name of model index to search
     * @param string $keyword
     * @param string $sortColumn Name of column to sort on
     * @param boolean $sortDirection True for ascending sort, false for descending
     * @param int $start The offset within the result set to begin returning documents from
     * @param int $rows The number of documents to return
     * @return Fisma_Search_Result
     */
    public function searchByKeyword($type, $keyword, $sortColumn, $sortDirection, $start, $rows)
    {
        $query = new SolrQuery;

        $table = Doctrine::getTable($type);
        $searchableFields = $table->getSearchableFields();

        if (!isset($searchableFields[$sortColumn]) || !$searchableFields[$sortColumn]['sortable']) {
            throw new Fisma_Search_Exception("Not a sortable column: $sortColumn");
        }

        $sortColumnDefinition = $searchableFields[$sortColumn];

        // Text columns have different sorting rules (see design document)
        if ('text' == $sortColumnDefinition['type']) {
            $sortColumnParam = $this->escape($sortColumn) . '_textsort';
        } else {
            $sortColumnParam = $this->escape($sortColumn) . '_' . $sortColumnDefinition['type'];
        }

        $sortDirectionParam = $sortDirection ? SolrQuery::ORDER_ASC : SolrQuery::ORDER_DESC;

        // Add required fields to query. The rest of the fields are added below.
        $query->addField('id')
              ->addField('luceneDocumentId')
              ->addSortField($sortColumnParam, $sortDirectionParam)
              ->setStart($start)
              ->setRows($rows);

        $trimmedKeyword = trim($keyword);

        $filterQuery = 'luceneDocumentType:' . $this->escape($type);

        // Add ACL constraints to filter query
        $aclQueryFilter = $this->_getAclQueryFilter($table, $searchableFields);

        if (!empty($aclQueryFilter)) {
            $filterQuery .= ' AND (' 
                          . $aclQueryFilter
                          . ')';
        }

        if (empty($trimmedKeyword)) {
            // Without keywords, this is just a listing of all documents of a specific type
            $query->setQuery($filterQuery);
        } else {
            // For keyword searches, use the filter query (for efficient caching) and enable highlighting
            $query->setHighlight(true)
                  ->setHighlightSimplePre('***')
                  ->setHighlightSimplePost('***')
                  ->addFilterQuery($filterQuery);

            // Tokenize keyword on spaces and escape all tokens
            $keywordTokens = split(' ', $trimmedKeyword);
            $keywordTokens = array_filter($keywordTokens);
            $keywordTokens = array_map(array($this, 'escape'), $keywordTokens);
        }

        // Enumerate all fields so they can be included in search results
        $searchTerms = array();

        foreach ($searchableFields as $fieldName => $fieldDefinition) {

            $documentFieldName = $fieldName . '_' . $fieldDefinition['type'];

            $query->addField($documentFieldName);

            // Add keyword terms and highlighting to all non-date fields
            if (!empty($trimmedKeyword) &&
                'date' != $fieldDefinition['type'] &&
                'datetime' != $fieldDefinition['type']) {

                // Solr can't highlight sortable integer fields
                if ('integer' != $fieldDefinition['type']) {
                    $query->addHighlightField($documentFieldName);
                }

                foreach ($keywordTokens as $keywordToken) {

                    // Don't search for strings in integer fields (Solr emits an error)
                    if ( !('integer' == $fieldDefinition['type'] && !is_numeric($keywordToken)) ) {
                        $searchTerms[] = $documentFieldName . ':' . $keywordToken;
                    }
                }
            }
        }

        if (!empty($trimmedKeyword) > 0) {
            // If there are search terms, then combine them with the logical OR operator
            $query->setQuery(implode(' OR ', $searchTerms));
        }

        $response = $this->_client->query($query)->getResponse();

        return $this->_convertSolrResultToStandardResult($type, $response);
    }

    /**
     * Advanced search: search based on a list of specific field criteria
     *
     * @param string $type Name of model index to search
     * @param Fisma_Search_Criteria $criteria
     * @param string $sortColumn Name of column to sort on
     * @param boolean $sortDirection True for ascending sort, false for descending
     * @param int $start The offset within the result set to begin returning documents from
     * @param int $rows The number of documents to return
     * @return Fisma_Search_Result Rectangular array of search results
     */
    public function searchByCriteria($type, Fisma_Search_Criteria $criteria, $sortColumn, $sortDirection, $start, $rows)
    {
        $query = new SolrQuery;

        $table = Doctrine::getTable($type);
        $searchableFields = $table->getSearchableFields();

        if (!isset($searchableFields[$sortColumn]) || !$searchableFields[$sortColumn]['sortable']) {
            throw new Fisma_Search_Exception("Not a sortable column: $sortColumn");
        }

        $sortColumnDefinition = $searchableFields[$sortColumn];

        // Text columns have different sorting rules (see design document)
        if ('text' == $sortColumnDefinition['type']) {
            $sortColumnParam = $this->escape($sortColumn) . '_textsort';
        } else {
            $sortColumnParam = $this->escape($sortColumn) . '_' . $sortColumnDefinition['type'];
        }

        $sortDirectionParam = $sortDirection ? SolrQuery::ORDER_ASC : SolrQuery::ORDER_DESC;

        // Add required fields to query. The rest of the fields are added below.
        $query->addField('id')
              ->addField('luceneDocumentId')
              ->addSortField($sortColumnParam, $sortDirectionParam)
              ->setStart($start)
              ->setRows($rows);

        $filterQuery = 'luceneDocumentType:' . $this->escape($type);

        // Add ACL constraints to filter query
        $aclQueryFilter = $this->_getAclQueryFilter($table, $searchableFields);

        if (!empty($aclQueryFilter)) {
            $filterQuery .= ' AND (' 
                          . $aclQueryFilter
                          . ')';
        }

        // Use the filter query (for efficient caching) and enable highlighting
        $query->setHighlight(true)
              ->setHighlightSimplePre('***')
              ->setHighlightSimplePost('***')
              ->setHighlightRequireFieldMatch(true)
              ->addFilterQuery($filterQuery);

        // Add the fields which should be returned in the result set and indicate which should be highlighted
        foreach ($searchableFields as $fieldName => $fieldDefinition) {

            // Some twiddling to convert Doctrine's field names to Solr's field names
            $documentFieldName = $fieldName . '_' . $fieldDefinition['type'];

            $query->addField($documentFieldName);

            // Highlighting doesn't work for date or integerfields in Solr 4.1
            if ('date' != $fieldDefinition['type'] &&
                'datetime' != $fieldDefinition['type'] &&
                'integer' != $fieldDefinition['type']) {

                $query->addHighlightField($documentFieldName);
            }
        }

        // Add specific query terms based on the user's request
        $searchTerms = array();

        foreach ($criteria as $criterion) {

            $doctrineFieldName = $this->escape($criterion->getField());

            if (!isset($searchableFields[$doctrineFieldName])) {
                throw new Fisma_Search_Exception("Invalid field name: " . $doctrineFieldName);
            }

            $fieldName = $doctrineFieldName . '_' . $searchableFields[$doctrineFieldName]['type'];

            $operands = array_map('addslashes', $criterion->getOperands());

            $operator = $criterion->getOperator();

            switch ($operator) {
                // These cases intentionally fall through
                case 'dateAfter':
                    $afterDate = $this->_convertToSolrDate($operands[0]);
                    $searchTerms[] = "$fieldName:[$afterDate TO *]";
                    break;

                case 'dateBefore':
                    $beforeDate = $this->_convertToSolrDate($operands[0]);
                    $searchTerms[] = "$fieldName:[* TO $beforeDate]";
                    break;

                case 'dateBetween':
                    $afterDate = $this->_convertToSolrDate($operands[0]);
                    $beforeDate = $this->_convertToSolrDate($operands[1]);
                    $searchTerms[] = "$fieldName:[$afterDate TO $beforeDate]";
                    break;

                case 'dateDay':
                    $date = $this->_convertToSolrDate($operands[0]);
                    $searchTerms[] = "$fieldName:[$date/DAY TO $date/DAY+1DAY]";
                    break;

                case 'dateThisMonth':
                    $searchTerms[] = "$fieldName:[NOW/MONTH TO NOW/MONTH+1MONTH]";
                    break;

                case 'dateThisYear':
                    $searchTerms[] = "$fieldName:[NOW/YEAR TO NOW/YEAR+1YEAR]";
                    break;

                case 'dateToday':
                    $searchTerms[] = "$fieldName:[NOW/DAY TO NOW/DAY+1DAY]";
                    break;

                case 'integerBetween':
                    $lowEndIntValue = intval($operands[0]);
                    $highEndIntValue = intval($operands[1]);
                    $searchTerms[] = "$fieldName:[$lowEndIntValue TO $highEndIntValue]";
                    break;

                case 'integerDoesNotEqual':
                    $intValue = intval($operands[0]);
                    $searchTerms[] = "-$fieldName:$intValue";
                    break;

                case 'integerEquals':
                    $intValue = intval($operands[0]);
                    $searchTerms[] = "$fieldName:$intValue";
                    break;

                case 'integerGreaterThan':
                    $intValue = intval($operands[0]);
                    $searchTerms[] = "$fieldName:[$intValue TO *]";
                    break;

                case 'integerLessThan':
                    $intValue = intval($operands[0]);
                    $searchTerms[] = "$fieldName:[* TO $intValue]";
                    break;

                // The following cases intentionally fall through
                case 'textContains':
                case 'enumIs':
                    $searchTerms[] = "$fieldName:\"{$operands[0]}\"";
                    break;

                // The following cases intentionally fall through
                case 'textDoesNotContain':
                case 'enumIsNot':
                    $searchTerms[] = "-$fieldName:\"{$operands[0]}\"";
                    break;

                // Exact text match is a little different. It uses a separate field and it only works for sortable
                // fields. Because the sort field is unanalyzed, this is a case sensitive operator.
                case 'textExactMatch':
                    $searchTerms[] = "{$doctrineFieldName}_textsort:\"{$operands[0]}\"";
                    break;

                default:
                    // Fields can define custom criteria (that wouldn't match any of the above cases)
                    if (isset($searchableFields[$doctrineFieldName]['extraCriteria'][$operator])) {
                        $callback = $searchableFields[$doctrineFieldName]['extraCriteria'][$operator]['callback'];

                        $customTerms = call_user_func_array($callback, $operands);

                        if ($customTerms === false) {
                            throw new Fisma_Zend_Exception("Not able to call callback ($callback)");
                        }

                        foreach ($customTerms as $termName => $termExpression) {
                            $fieldType = $searchableFields[$termName]['type'];

                            $searchTerms[] = "{$termName}_{$fieldType}:$termExpression";
                        }
                    } else {
                        throw new Fisma_Search_Exception("Undefined search operator: " . $criterion->getOperator());
                    }
            }
        }

        $queryString = implode($searchTerms, ' AND ');

        $query->setQuery($queryString);

        $response = $this->_client->query($query)->getResponse();

        return $this->_convertSolrResultToStandardResult($type, $response);
    }

    /**
     * Validate that PECL extension is installed and SOLR server responds to a Solr ping request (not an ICMP)
     *
     * @return mixed Return TRUE if configuration is valid, or a string error message otherwise
     */
    public function validateConfiguration()
    {
        if (!function_exists('solr_get_version')) {
            return "PECL Solr extension is not installed";
        }

        try {
            $this->_client->ping();
        } catch (SolrClientException $e) {
            return 'Not able to reach Solr server: ' . $e->getMessage();
        }

        return true;
    }

    /**
     * Convert an array of objects into an array of indexable Solr documents
     *
     * @param array $collection
     * @return array Array of SolrInputDocument
     */
    private function _convertCollectionToDocumentArray($type, $collection)
    {
        $documents = array();

        foreach ($collection as $object) {
            $documents[] = $this->_convertObjectToDocument($type, $object);
        }

        return $documents;
    }

    /**
     * Convert an object (in array format) into an indexable Solr document
     *
     * The object's table must also implement Fisma_Search_Searchable so that this method can get its search metadata.
     *
     * @param string $type The class of the object
     * @param array $object
     * @return SolrInputDocument
     */
    private function _convertObjectToDocument($type, $object)
    {
        $table = Doctrine::getTable($type);

        if (!($table instanceof Fisma_Search_Searchable)) {
            $message = 'Objects which are to be indexed must have a table that implements'
                     . ' the Fisma_Search_Searchable interface';

            throw new Fisma_Zend_Exception($message);
        }

        $document = new SolrInputDocument;

        // All documents have the following three fields
        if (isset($object['id'])) {
            $document->addField('luceneDocumentId', $type . $object['id']);
            $document->addField('luceneDocumentType', $type);
            $document->addField('id', $object['id']);
        } else {
            throw new Fisma_Search_Exception("Cannot index object type ($type) because it does not have an id field.");
        }

        // Iterate over the model's columns and see which ones need to be indexed
        $searchableFields = $table->getSearchableFields();

        foreach ($searchableFields as $doctrineFieldName => $searchFieldDefinition) {

            if ('luceneDocumentId' == $doctrineFieldName) {
                throw new Fisma_Search_Exception("Model columns cannot be named luceneDocumentId");
            }

            $documentFieldName = $doctrineFieldName . '_' . $searchFieldDefinition['type'];

            // @todo move this block to abstract class
            // Values can come from the main model as well as related model
            if (!isset($searchFieldDefinition['join'])) {
                $rawValue = $object[$table->getFieldName($doctrineFieldName)];
            } else {
                // Handle nested relations
                $relationParts = explode('.', $searchFieldDefinition['join']['relation']);

                $relatedObject = $object;

                foreach ($relationParts as $relationPart) {
                    $relatedObject = $relatedObject[$relationPart];
                }

                $rawValue = $relatedObject[$searchFieldDefinition['join']['field']];
            }

            $doctrineDefinition = $table->getColumnDefinition($table->getColumnName($doctrineFieldName));

            $containsHtml = isset($doctrineDefinition['extra']['purify']['html']) &&
                                  $doctrineDefinition['extra']['purify']['html'];

            $documentFieldValue = $this->_getValueForColumn($rawValue, $searchFieldDefinition['type'], $containsHtml);

            $document->addField($documentFieldName, $documentFieldValue);

            // For sortable text columns, add a separate 'textsort' column (see design document)
            if ('text' == $searchFieldDefinition['type'] && $searchFieldDefinition['sortable']) {
                $document->addField($doctrineFieldName . '_textsort', $documentFieldValue);
            }
        }

        return $document;
    }

    /**
     * Converts a Solr query result into the system's standard result format
     *
     * Solr does some weird stuff with object storage, so this method is a little hard to understand. var_dump'ing
     * each variable will help to sort through the structure for debugging purposes.
     *
     * @param string $type
     * @param SolrResult $solrResult
     * @return Fisma_Search_Result
     */
    public function _convertSolrResultToStandardResult($type, SolrResult $solrResult)
    {
        // @todo set global timestamp options
        Zend_Date::setOptions(array('format_type' => 'iso'));

        $numberFound = count($solrResult->response->docs);
        $numberReturned = $solrResult->response->numFound;
        $highlighting = (array)$solrResult->highlighting;

        $tableData = array();

        $table = Doctrine::getTable($type);
        $searchableFields = $table->getSearchableFields();

        // Construct initial table data from documents part of the response
        foreach ($solrResult->response->docs as $document) {

            $row = array();

            foreach ($document as $columnName => $columnValue) {
                $newColumnName = $this->_removeSuffixFromColumnName($columnName);

                if (strlen($columnValue[0]) > self::MAX_ROW_LENGTH) {
                    $shortValue = substr($columnValue[0], 0, self::MAX_ROW_LENGTH);

                    // Trim after the last white space (so as not to break in the middle of a word)
                    $spacePosition = strrpos($shortValue, ' ');

                    if ($spacePosition) {
                        $shortValue = substr($shortValue, 0, $spacePosition);
                    }

                    // Solr has a weird format. Each field is an array with length 1, so we take index 0
                    $row[$newColumnName] = $shortValue . '...';
                } else {
                    $row[$newColumnName] = $columnValue[0];
                }
            }

            // Convert any dates or datetimes from Solr's UTC format back to native format
            foreach ($row as $fieldName => $fieldValue) {
                $fieldDefinition = $searchableFields[$fieldName];

                if ('date' == $fieldDefinition['type'] || 'datetime' == $fieldDefinition['type']) {
                    $date = new Zend_Date($fieldValue, 'YYYY-MM-ddTHH:mm:ssZ');

                    if ('date' == $fieldDefinition['type']) {
                        $row[$fieldName] = $date->toString('YYYY-MM-dd');
                    } else {
                        $row[$fieldName] = $date->toString('YYYY-MM-dd HH:mm:ss');
                    }
                }
            }

            $luceneDocumentId = $row['luceneDocumentId'];

            $tableData[$luceneDocumentId] = $row;
        }

        // Now merge any highlighted fields into the table data
        foreach ($highlighting as $luceneDocumentId => $row) {
            foreach ($row as $fieldName => $fieldValue) {
                $newFieldName = $this->_removeSuffixFromColumnName($fieldName);

                // Solr stores each field as an array with length 1, so we take index 0
                $tableData[$luceneDocumentId][$newFieldName] = $fieldValue[0];
            }
        }

        // Remove the luceneDocumentId from each field
        foreach ($tableData as &$document) {
            unset($document['luceneDocumentId']);
        }

        // Discard the row IDs
        $tableData = array_values($tableData);

        return new Fisma_Search_Result($numberReturned, $numberFound, $tableData);
    }

    /**
     * Remove the type suffix (e.g. _text, _date, etc.) from a columnName
     *
     * @param string $columnName
     * @return string
     */
    private function _removeSuffixFromColumnName($columnName)
    {
        $suffixPosition = strpos($columnName, '_');

        if ($suffixPosition) {
            return substr($columnName, 0, strpos($columnName, '_'));
        } else {
            return $columnName;
        }
    }

    /**
     * Create the field value for an object based on its type and other metadata
     *
     * This includes transformations such as correctly formatting dates, times, and stripping HTML content
     *
     * @param mixed $value
     * @param string $type
     * @param bool $html True if the value contains HTML
     * @return mixed
     */
    private function _getValueForColumn($rawValue, $type, $html)
    {
        if ('text' == $type && $html) {
            $value = $this->_convertHtmlToIndexString($rawValue);
        } elseif ('integer' == $type) {
            $value = intval($rawValue);
        } elseif ('date' == $type || 'datetime' == $type) {
            $value = $this->_convertToSolrDate($rawValue);
        } else {
            // By default, just index the raw value
            $value = $rawValue;
        }

        return $value;
    }

    /**
     * Convert a database format date or date time (2010-01-01 12:00:00) to Solr's ISO-8601 UTC format
     *
     * @param string $date
     * @return string
     */
    private function _convertToSolrDate($date)
    {
        // @todo set global timestamp options
        Zend_Date::setOptions(array('format_type' => 'iso'));

        // Date fields need to be converted to UTC
        $tempDate = new Zend_Date($date, 'YYYY-MM-dd HH:mm:ss');

        return $tempDate->toString('YYYY-MM-ddTHH:mm:ss') . 'Z';
    }

    /**
     * Returns query terms to limit the search results on 
     * 
     * @param Doctrine_Table $table
     * @param array $searchableFields
     */
    private function _getAclQueryFilter($table, $searchableFields)
    {
        $aclFields = $table->getAclFields();

        $ids = array();

        if (count($aclFields) == 0) {
            // This model doesn't specify any ACL constraints
            return '';
        }
        
        foreach ($aclFields as $aclFieldName => $callback) {                
            $aclIds = call_user_func($callback);

            if ($aclIds === false) {
                $message = "Could not call ACL ID provider ($callback) for ACL field ($name).";

                throw new Fisma_Zend_Exception($message);
            }

            $aclFieldType = $searchableFields[$aclFieldName]['type'];

            foreach ($aclIds as &$aclId) {
                $aclId = "{$aclFieldName}_{$aclFieldType}:" . $this->escape($aclId);
            }

            $ids = array_merge($ids, $aclIds);
        }

        if (count($ids)) {
            return implode(' OR ', $ids);
        } else {
            // If no IDs match, then return an impossible condition
            return ('id:0');
        }
    }
}