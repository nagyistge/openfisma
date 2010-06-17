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
 * The report context switch allows a controller to produce a YUI/HTML, Excel, and/or PDF reports in a single action 
 * without needing to write three separate view scripts.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Zend_Controller
 * @version    $Id $
 */
class Fisma_Zend_Controller_Action_Helper_ReportContextSwitch extends Zend_Controller_Action_Helper_ContextSwitch
{
    /**
     * A Fisma_Report instance to be displayed by this helper
     * 
     * @var Fisma_Report
     */
    private $_report;
    
    /**
     * An array of actions and controllers invoked for an HTML response if an action stack is specified
     * 
     * @var array
     */
    private $_htmlActionStack = array();

    /**
     * A boolean which indicates if this has been rendered already or not
     * 
     * This is necessary because the rendering of these various report formats is hooked into the postDispatch
     * event of the action helper. This event will be called for ALL actions which get executed in the current request,
     * including auxilary actions like the panel header and footer.
     * 
     * If we rendered the view each time, then content would appear on the page multiple times. This variable lets us
     * track and avoid that condition.
     */
    private $_isRendered = false;
    
    /**
     * Set the report object
     * 
     * @param Fisma_Report $report
     */
    public function setReport(Fisma_Report $report)
    {
        $this->_report = $report;
    }
    
    /**
     * Add extra initialization steps when this helper is used instead of the Zend version.
     *
     * @return void
     */
    public function init()
    {
        // Session cache limiter fixes bugs in IE6 and IE7 when sending files over SSL connection
        session_cache_limiter(false);
        
        parent::init();

        $this->clearContexts();
        
        $this->setAutoDisableLayout(false);

        $this->addContext(
            'html',
            array(
                'callbacks' => array(
                    'init' => 'actionStack',
                    'post' => 'renderHtml'
                )
            )
        );
        
        $this->addContext(
            'pdf',
            array(
                'headers' => array(
                    'Content-Disposition' => 'inline; filename=Report.pdf',
                    'Content-Type' => 'application/pdf'
                ),
                'callbacks' => array(
                    'init' => 'disableLayout',
                    'post' => 'renderPdf'
                )
            )
        );

        $this->addContext(
            'xls',
            array(
                'headers' => array(
                    'Content-Disposition' => 'attachment; filename=Report.xls',
                    'Content-Type' => 'application/vnd.ms-excel'
                )    ,
                'callbacks' => array(
                    'init' => 'disableLayout',
                    'post' => 'renderXls'
                )
            )
        );
    }
    
    /**
     * The HTML context will call this as part of the pre-init hook
     */
    public function actionStack()
    {
        // Set up any action stacks that were requested
        if (count($this->_htmlActionStack) > 0) {
            $controller = $this->getActionController();

            foreach ($this->_htmlActionStack as $stack) {
                $controller->getHelper('actionStack')->direct($stack['action'], $stack['controller']);
            }
        }
    }

    /**
     * Disable layout
     */
    public function disableLayout()
    {    
        Zend_Layout::getMvcInstance()->disableLayout();
    }
    
    /**
     * Render the report as an HTML document
     */
    public function renderHtml()
    {
        if (!$this->_isRendered) {
            // Ensure that a report object has been provided
            if (is_null($this->_report)) {
                throw new Fisma_Zend_Exception('Report context switch has no report object');
            }
                
            // Create a view and render it to the response body
            $view = Zend_Layout::getMvcInstance()->getView();

            /*
             * Create "Export to Excel" and "Export to PDF" buttons conditionally on whether the action has those 
             * contexts.
             */
            if ($this->hasActionContext($this->getRequest()->getActionName(), 'xls')) {
                $view->exportXlsButton = new Fisma_Yui_Form_Button_Link(
                    'exportXls',
                    array(
                        'value' => 'Export Excel',
                        'href' => $this->_getFormatUrl('xls'),
                        'imageSrc' => '/images/xls.gif'
                    )
                );                
            }

            if ($this->hasActionContext($this->getRequest()->getActionName(), 'pdf')) {              
                $view->exportPdfButton = new Fisma_Yui_Form_Button_Link(
                    'exportPdf',
                    array(
                        'value' => 'Export PDF',
                        'href' => $this->_getFormatUrl('pdf'),
                        'imageSrc' => '/images/pdf.gif'
                    )
                );
            }
        
            $view->title = $this->_report->getTitle();

            $dataTable = new Fisma_Yui_DataTable_Local();

            $dataTable->setData($this->_report->getData());

            foreach ($this->_report->getColumns() as $reportColumn) {
                $yuiColumn = new Fisma_Yui_DataTable_Column(
                    $reportColumn->getName(), 
                    $reportColumn->isSortable(), 
                    $reportColumn->getFormatter()
                );
                
                $dataTable->addColumn($yuiColumn);
            }

            $view->dataTable = $dataTable;

            $this->_getViewRenderer()->renderScript('/report/report.phtml');
            
            // Prevent this from being rendered multiple times if there are multiple dispatches (e.g. action stacks)
            $this->_isRendered = true;
        }
    }

    /**
     * Render the report as a PDF document
     */
    public function renderPdf()
    {
        // Ensure that a report object has been provided
        if (is_null($this->_report)) {
            throw new Fisma_Zend_Exception('Report context switch has no report object');
        }

        $view = Zend_Layout::getMvcInstance()->getView();

        $view->title = $this->_report->getTitle();
        $view->columns = $this->_report->getColumnNames();
        $view->timestamp = Zend_Date::now()->toString('Y-m-d h:i:s A T');
        $view->systemName = Fisma::configuration()->getConfig('system_name');

        /*
         * For some reazon, EZPdf needs its data array numerically indexed, so convert the string indices to numeric 
         * indices.
         */
        $data = $this->_report->getData();
        
        foreach ($data as &$row) {
            $row = array_values($row);
        }
        
        $view->data = $data;
        
        $this->_getViewRenderer()->renderScript('/report/report.pdf.phtml');
    }
    
    /**
     * Render the report as an Excel document
     */
    public function renderXls()
    {
        // Ensure that a report object has been provided
        if (is_null($this->_report)) {
            throw new Fisma_Zend_Exception('Report context switch has no report object');
        }

        $view = Zend_Layout::getMvcInstance()->getView();

        $view->title = $this->_report->getTitle();
        $view->columns = $this->_report->getColumnNames();
        $view->timestamp = Zend_Date::now()->toString('Y-m-d h:i:s A T');
        $view->systemName = Fisma::configuration()->getConfig('system_name');
        $view->data = $this->_report->getData();
        
        $this->_getViewRenderer()->renderScript('/report/report.xls.phtml');
    }
    
    /**
     * Stack an action for an HTML response
     * 
     * These actions will be stacked for HTML formats, but not for other formats
     * 
     * @param string $action
     * @param string $controller
     * @return Fluent
     */
    public function htmlActionStack($action, $controller = null)
    {
        $action = array('action' => $action, 'controller' => $controller);

        $this->_htmlActionStack[] = $action;
        
        return $this;
    }

    /**
     * Return a URL to this same controller action but with the format parameter modified set to the specified value
     * 
     * Notice that this works by overwriting the "format" parameter in the URL. Because the report context switch
     * requires a format parameter, we don't consider the case of a URL which doesn't have such a parameter.
     * 
     * @param string $format
     * @return string
     */
    private function _getFormatUrl($format)
    {
        // Look for a format parameter and overwrite it with the request format.
        return preg_replace('/([^\/]\/format\/)\w+/', "\$1$format", Fisma_Url::currentUrl());        
    }
}