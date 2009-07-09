<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */
 
/**
 * Base controller to handle CRUD 
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
abstract class BaseController extends SecurityController
{
    /**
     * Default paginate parameters
     */
    protected $_paging = array(
        'startIndex' => 0,
        'count' => 20
    );
    
    /**
     * The main name of the model.
     * 
     * This model is the main subject which the controller operates on.
     */
    protected $_modelName = null;

    /**
     * Make sure the model has been properly set
     */
    public function init() 
    {
        parent::init();
        if (is_null($this->_modelName)) {
            //@todo english
            //Actually user should not be able to see this error message
            throw new Fisma_Exception('The subject model has not been specified');
        }
    }

    public function preDispatch()
    {
        /* Setting the first index of the page/table */
        $this->_paging['startIndex'] = $this->_request->getParam('startIndex', 0);
        parent::preDispatch();
    }

    /**
     * Get the specific form of the subject model
     *
     * @param string $formName
     */
    public function getForm(string $formName=null)
    {
        static $form = null;
        if (is_null($form)) {
            if (is_null($formName)) {
                $formName = (string)$this->_modelName;
            }
            $form = Fisma_Form_Manager::loadForm($formName);
            $form = Fisma_Form_Manager::prepareForm($form);
        }
        return $form;
    }

    /** 
     * Hooks for manipulating the values before setting to a form
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     * @return Zend_Form
     */
    protected function setForm($subject, $form)
    {
        $form->setDefaults($subject->toArray());
        return $form;
    }

    /** 
     * Hooks for manipulating and saveing the values retrieved by Forms
     *
     * @param Zend_Form $form
     * @param Doctrine_Record|null $subject
     */
    protected function saveValue($form, $subject=null)
    {
        if (is_null($subject)) {
            $subject = new $this->_modelName();
        } elseif (!$subject instanceof Doctrine_Record) {
            /** @todo english */
            throw new Fisma_Exception('Invalid parameter expecting a Record model');
        }
        $subject->merge($form->getValues());
        $subject->save();
    }

    /**
     * View detail information of the subject model
     */
    public function viewAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'read');
        $id     = $this->_request->getParam('id');
        $subject = Doctrine::getTable($this->_modelName)->find($id);
        if (!$subject) {
            /**
             * @todo english
             */
            throw new Fisma_Exception("Invalid {$this->_modelName}");
        }
        $form   = $this->getForm();
        
        $this->view->assign('editLink', "/panel/{$this->_modelName}/sub/edit/id/$id");
        $form->setReadOnly(true);            
        $this->view->assign('deleteLink', "/panel/{$this->_modelName}/sub/delete/id/$id");
        $this->setForm($subject, $form);
        $this->view->form = $form;
        $this->view->id   = $id;
        $this->render();
    }

    /**
     * Create a subject model/record
     */
    public function createAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'create');
        // Get the subject form
        $form   = $this->getForm();
        $form->setAction("/panel/{$this->_modelName}/sub/create");
        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                try {
                    Doctrine_Manager::connection()->beginTransaction();
                    $this->saveValue($form);
                    Doctrine_Manager::connection()->commit();
                    $msg   = "The {$this->_modelName} is created";
                    $model = self::M_NOTICE;
                } catch (Doctrine_Exception $e) {
                    Doctrine_Manager::connection()->rollback();
                    /** @todo english please notice following 3 sentences*/
                    $msg   = "Failure in creation. ";
                    if (Fisma::debug()) {
                        $msg .= $e->getMessage();
                    }
                    $model = self::M_WARNING;
                }
                $this->message($msg, $model);
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                // Error message
                $this->message("Unable to create the {$this->_modelName}:<br>$errorString", self::M_WARNING);
            }
        }
        $this->view->form = $form;
        $this->render();
    }

    /**
     * Edit a subject model
     */
    public function editAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'update');
        $id     = $this->_request->getParam('id');
        $subject = Doctrine::getTable($this->_modelName)->find($id);
        if (!$subject) {
            /**
             * @todo english
             */
            throw new Fisma_Exception("Invalid {$this->_modelName}");
        }
        $form   = $this->getForm();
        
        $this->view->assign('viewLink', "/panel/{$this->_modelName}/sub/view/id/$id");
        $form->setAction("/panel/{$this->_modelName}/sub/edit/id/$id");
        $this->view->assign('deleteLink', "/panel/{$this->_modelName}/sub/delete/id/$id");
        // Update the model
        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();
            if ($form->isValid($post)) {
                try {
                    $result = $this->saveValue($form, $subject);
                    /** @todo english. This notice span following segments */
                    $msg   = "The {$this->_modelName} is updated";
                    $model = self::M_NOTICE;
                } catch (Doctrine_Exception $e) {
                    //Doctrine_Manager::connection()->rollback();
                    $msg  = "Failure in saving ";
                    if (Fisma::debug()) {
                        $msg .= $e->getMessage();
                    }
                    $type = self::M_WARNING;
                }
                $this->message($msg, $model);
            } else {
                $errorString = Fisma_Form_Manager::getErrors($form);
                $this->message("Unable to update the {$this->_modelName}:<br>$errorString", self::M_WARNING);
            }
        }
        $form = $this->setForm($subject, $form);
        $this->view->form = $form;
        $this->view->id   = $id;
        $this->render();
    }

    /**
     * Delete a subject model
     */
    public function deleteAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'delete');
        $id = $this->_request->getParam('id');
        $subject = Doctrine::getTable($this->_modelName)->find($id);
        if (!$subject) {
            /** @todo english */
            $msg   = "Invalid {$this->_modelName}";
            $type = self::M_WARNING;
        } else {
            try {
                Doctrine_Manager::connection()->beginTransaction();
                $subject->delete();
                Doctrine_Manager::connection()->commit();
                 // @todo english
                $msg   = "{$this->_modelName} is deleted successfully";
                $type = self::M_NOTICE;
            } catch (Doctrine_Exception $e) {
                Doctrine_Manager::connection()->rollback();
                /** @todo english */
                if (Fisma::debug()) {
                    $msg .= $e->getMessage();
                }
                $type = self::M_WARNING;
            } 
        }
        $this->message($msg, $type);
        $this->_forward('list');
    }

    /**
     * List the subjects
     */
    public function listAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'read');
        $keywords = trim($this->_request->getParam('keywords'));
        $link = empty($keywords) ? '' :'/keywords/'.$keywords;
        $this->view->link     = $link;
        $this->view->pageInfo = $this->_paging;
        $this->view->keywords = $keywords;
        $this->render('list');
    }

    /** 
     * Search the subject 
     *
     * This outputs a json object. Allowing fulltext search from each record enpowered by lucene
     */
    public function searchAction()
    {
        Fisma_Acl::requirePrivilege($this->_modelName, 'read');
        $sortBy = $this->_request->getParam('sortby', 'id');
        $order  = $this->_request->getParam('order');
        $keywords  = $this->_request->getParam('keywords'); 

        //filter the sortby to prevent sqlinjection
        $subjectTable = Doctrine::getTable($this->_modelName);
        if (!in_array(strtolower($sortBy), $subjectTable->getColumnNames())) {
            /** @todo english */
            return $this->_helper->json('invalid parameters');
        }

        $order = strtoupper($order);
        if ($order != 'DESC') {
            $order = 'ASC'; //ignore other values
        }
        
        $query  = Doctrine_Query::create()
                    ->select('*')->from($this->_modelName)
                    ->orderBy("$sortBy $order")
                    ->limit($this->_paging['count'])
                    ->offset($this->_paging['startIndex']);

        //initialize the data rows
        $tableData    = array('table' => array(
                            'recordsReturned' => 0,
                            'totalRecords'    => 0,
                            'startIndex'      => $this->_paging['startIndex'],
                            'sort'            => $sortBy,
                            'dir'             => $order,
                            'pageSize'        => $this->_paging['count'],
                            'records'         => array()
                        ));
        if (!empty($keywords)) {
            // lucene search 
            $ids = $this->_helper->searchQuery($keywords, strtolower($this->_modelName));
            if (!empty($ids)) {
                $ids = implode(',', $ids);
                $query->where('id IN (' . $ids . ')');
            } else {
                //no data
                return $this->_helper->json($tableData);
            }
        }
        
        $totalRecords = $query->count();
        $rows         = $query->execute();
        $rows         = $this->handleCollection($rows);
        $tableData['table']['recordsReturned'] = count($rows);
        $tableData['table']['totalRecords'] = $totalRecords;
        $tableData['table']['records'] = $rows;
        return $this->_helper->json($tableData);
    }

    /**
     * Return array of the collection.
     * If an collection need to change its keys to some other value, please override it
     *    in the controller which is inherited from this Controller
     *
     * @param Doctrine_Collections $rows
     * @return array()
     */
    public function handleCollection($rows)
    {
        return $rows->toArray();
    }

}