<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
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
 * A PHP wrapper for a YUI DataTable which has a remote data source (i.e. a URL which is fetched by XHR)
 * 
 * The purpose of this class is to provide a consistent way of using the YUI table, so this class automatically
 * provides functionality such as paging, dynamic data requests, row highlighting, and column sorting. (These
 * functions are mandatory and cannot be turned off. For simpler tables, there is a "local" version of this class.)
 * 
 * Optional functionality includes: row click events
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 * @version    $Id$
 */
class Fisma_Yui_DataTable_Remote extends Fisma_Yui_DataTable_Abstract
{
    /**
     * The base URL from which this table fetches its data
     * 
     * @var string
     */
    private $_dataUrl;
    
    /**
     * The name of the JSON variable that contains the table data sent in the response
     * 
     * @var string
     */
    private $_resultVariable;

    /**
     * The maximum number of rows to display at a single time.
     * 
     * @var int
     */
    private $_rowCount;

    /**
     * The column which is initially sorted in this table
     * 
     * This column is determined by the initial query's sort order, i.e. the ORDER BY clause. The column name specified
     * must match the column name return in the hydrated result set.
     * 
     * @var string
     */
    private $_initialSortColumn;
    
    /**
     * Set to true if this column is sorted ascending, false if sorted descending, or null if no sort applied
     * 
     * @var bool
     */
    private $_sortAscending;
    
    /**
     * This URL is the base URL when the user clicks a row and is redirected to another URL.
     * 
     * @var string
     */
    private $_clickEventBaseUrl;

    /**
     * The value of this variable in the clicked row is appended to the URL which the user is redirected to.
     * 
     * @var string
     */
    private $_clickEventVariableName;
    
    /**
     * Render the datatable with HTML and/or Javascript
     * 
     * @return string
     */
    public function render()
    {
        $this->_validate();
        
        $view = Zend_Layout::getMvcInstance()->getView();

        $uniqueId = uniqid();
               
        $data = array(
            'clickEventBaseUrl' => $this->_clickEventBaseUrl,
            'clickEventVariableName' => $this->_clickEventVariableName,
            'columns' => $this->getColumns(),
            'columnDefinitions' => $this->_getYuiColumnDefinitions(),
            'containerId' => $uniqueId . "_container",
            'dataUrl' => $this->_dataUrl,
            'initialSortColumn' => $this->_initialSortColumn,
            'resultVariable' => $this->_resultVariable,
            'rowCount' => $this->_rowCount,
            'sortDirection' => ($this->_sortAscending ? 'asc' : 'desc')
        );
        
        return $view->partial('yui/data-table-remote.phtml', 'default', $data);
    }

    /**
     * Validate that all of the required parameters for the table object have been set.
     * 
     * Because this is called in render, it can't throw an exception, so it triggers a user error instead.
     */
    private function _validate()
    {
        $requiredFields = array('_dataUrl', '_resultVariable', '_rowCount', '_initialSortColumn', '_sortAscending');
        
        foreach ($requiredFields as $requiredField) {
            if (is_null($this->$requiredField)) {
                trigger_error("$requiredField cannot be null when rendering a remote table.", E_USER_ERROR);
            }
        }
        
        if (count($this->getColumns()) == 0) {
            trigger_error("Table must contain at least one column.", E_USER_ERROR);
        }
    }
    
    /**
     * Mutator for $_dataUrl
     * 
     * Fluent interface
     * 
     * @param string $dataUrl
     */
    public function setDataUrl($dataUrl)
    {
        $this->_dataUrl = $dataUrl;
        
        return $this;
    }

    /**
     * Mutator for $_resultVariable
     * 
     * Fluent interface
     * 
     * @param string $resultVariable
     */
    public function setResultVariable($resultVariable)
    {
        $this->_resultVariable = $resultVariable;
        
        return $this;
    }

    /**
     * Mutator for $_rowCount
     * 
     * Fluent interface
     * 
     * @param int $rowCount
     */
    public function setRowCount($rowCount)
    {
        $this->_rowCount = $rowCount;
        
        return $this;
    }
    
    /**
     * Mutator for $_initialSortColumn
     * 
     * Fluent interface
     * 
     * @param string $initialSortColumn
     */
    public function setInitialSortColumn($initialSortColumn)
    {
        $this->_initialSortColumn = $initialSortColumn;
        
        return $this;
    }

    /**
     * Mutator for $_sortAscending
     * 
     * Fluent interface
     * 
     * @param bool $sortAscending
     */
    public function setSortAscending($sortAscending)
    {
        $this->_sortAscending = $sortAscending;
        
        return $this;
    }    

    /**
     * Mutator for $_clickEventBaseUrl
     * 
     * Fluent interface
     * 
     * @param string $clickEventBaseUrl
     */
    public function setClickEventBaseUrl($clickEventBaseUrl)
    {
        $this->_clickEventBaseUrl = $clickEventBaseUrl;
        
        return $this;
    }

    /**
     * Mutator for $_clickEventVariableName
     * 
     * Fluent interface
     * 
     * @param string $clickEventVariableName
     */
    public function setClickEventVariableName($clickEventVariableName)
    {
        $this->_clickEventVariableName = $clickEventVariableName;
        
        return $this;
    }
}
