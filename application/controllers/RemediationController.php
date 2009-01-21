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
 */

/**
 * The remediation controller handles CRUD for findings in remediation.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 *
 * @todo As part of the ongoing refactoring, this class should probably be
 * merged with the FindingController.
 */
class RemediationController extends PoamBaseController
{
    //define the events of notification
    private $_notificationArray =
        array('action_suggested'=>Notification::UPDATE_FINDING_RECOMMENDATION,
              'type'=>Notification::UPDATE_COURSE_OF_ACTION,
              'action_planned'=>Notification::UPDATE_COURSE_OF_ACTION,
              'action_est_date'=>Notification::UPDATE_EST_COMPLETION_DATE,
              'threat_level'=>Notification::UPDATE_THREAT,
              'threat_source'=>Notification::UPDATE_THREAT,
              'threat_justification'=>Notification::UPDATE_THREAT,
              'cmeasure_effectiveness'=>Notification::UPDATE_COUNTERMEASURES,
              'cmeasure'=>Notification::UPDATE_COUNTERMEASURES,
              'cmeasure_justification'=>Notification::UPDATE_COUNTERMEASURES,
              'system_id'=>Notification::UPDATE_FINDING_ASSIGNMENT,
              'blscr_id'=>Notification::UPDATE_CONTROL_ASSIGNMENT,
              'action_status'=>Notification::MITIGATION_STRATEGY_APPROVED,
              'action_resources'=>Notification::UPDATE_FINDING_RESOURCES);
              
              
    /**
    * init() - Create the additional pdf and xls contexts for this class.
    *
    */
    public function init()
    {
        parent::init();
        $attach = $this->_helper->contextSwitch();
        if (!$attach->hasContext('pdf')) {
             $attach->addContext('pdf',
                array('suffix' => 'pdf',
                   'headers' => array(
                        'Content-Disposition' => "attachement;filename=export.pdf",
                        'Content-Type' => 'application/pdf'
                    )
                )
             )
             ->addActionContext('raf', array('pdf'))
             ->addActionContext('search', array('pdf'))
             ->setAutoDisableLayout(true);
        }
        if (!$attach->hasContext('xls')) {
             $attach->addContext('xls',
                array('suffix' => 'xls',
                   'headers' => array(
                        'Content-Disposition' => "attachement;filename=export.xls",
                        'Content-Type' => 'application/xls'
                    )
                )
             )
             ->addActionContext('search', array('xls'))
             ->setAutoDisableLayout(true);
        }
    }
    
    /**
     *  Default action.
     *
     *  It combines the searching and summary into one page.
     */
    
    public function indexAction()
    {
        $this->_acl->requirePrivilege('remediation', 'read');
        
        $this->_helper->actionStack('searchbox', 'Remediation');
        $this->_helper->actionStack('summary', 'Remediation');
    }

    /**
     *  Display the summary page of remediation, per systems.
     */
    public function summaryAction()
    {
        $this->_acl->requirePrivilege('remediation', 'read');
        $criteria['sourceId'] = $this->_request->getParam('source_id', 0);
        $criteria['type'] = $this->_request->getParam('type');
        $criteria['notStatus'] = 'PEND'; //exclude pending findings from the search criteria
        $criteria['aging'] = $this->_request->getParam('aging');
        $criteria['created_date_begin'] = $this->_request->getParam('created_date_begin');
        $criteria['created_date_end'] = $this->_request->getParam('created_date_end');

        if (!empty($criteria['created_date_begin'])) {
            $criteria['createdDateBegin'] = new Zend_Date($criteria['created_date_begin'], 'Y-m-d');
        }
        if (!empty($criteria['created_date_end'])) {
            $criteria['createdDateEnd'] = new Zend_Date($criteria['created_date_end'], 'Y-m-d');
        }
        
        $criteriaUrl = '';
        if (!empty($criteria['sourceId'])) {
            $criteriaUrl = '/source_id/'.$criteria['sourceId'];
        }
        if (!empty($criteria['type'])) {
            $criteriaUrl .='/type/'.$criteria['type'];
        }
        if (!empty($criteria['createdDateBegin'])) {
            $criteriaUrl .='/created_date_begin/'.$criteria['created_date_begin'];
        }
        if (!empty($criteria['created_date_end'])) {
            $criteriaUrl .='/created_date_end/'.$criteria['created_date_end'];
        }
        if (!empty($criteria['aging'])) {
            $endDate = self::$now;
            $endDate->sub($criteria['aging'], Zend_Date::DAY);
            $criteriaUrl .='/created_date_end/'.$endDate->toString('Ymd');
            $criteria['createdDateEnd'] = $endDate;
        }

        $eval = new Evaluation();
        $mpEvalList = $eval->getEvalList('ACTION');
        $epEvalList = $eval->getEvalList('EVIDENCE');
        foreach ($mpEvalList as $row) {
            $mpStatus[$row['nickname']] = $row['precedence_id'];
            $mpSummaryTmp[$row['nickname']] = 0;
        }
        $overduePeriod['EN'] = 0;
        foreach ($epEvalList as $row) {
            $epStatus[$row['nickname']] = $row['precedence_id'];
            $epSummaryTmp[$row['nickname']] = 0;
        }
    
        $summaryTmp = array_merge(array('NEW'=>0, 'DRAFT'=>0), $mpSummaryTmp);
        $summaryTmp = array_merge($summaryTmp, array('EN'=>0));
        $summaryTmp = array_merge($summaryTmp, $epSummaryTmp);
        $summaryTmp = array_merge($summaryTmp, array('CLOSED'=>0, 'TOTAL'=>0));
        // mock array_fill_key in 5.2.0
        $count = count($this->_me->systems);
        if ( 0 == $count ) {
            $summary = array();
        } else {
            $sum = array_fill(0, $count, $summaryTmp);
            $summary = array_combine($this->_me->systems, $sum);
        }
        $total = $summaryTmp;
        $ret = $this->_poam->search($this->_me->systems, array(
            'count' => array(
                'status',
                'system_id'
            ) ,
            'status',
            'type',
            'system_id'
        ), $criteria);
        $sum = array();
        foreach ($ret as $s) {
            $sum[$s['system_id']][$s['status']] = $s['count'];
        }
        foreach ($sum as $id => & $s) {
            $summary[$id] = $summaryTmp;
            $summary[$id]['NEW'] = isset($s['NEW'])?$s['NEW']: 0;
            $summary[$id]['DRAFT'] = isset($s['DRAFT'])?$s['DRAFT']: 0;
            $summary[$id]['EN'] = isset($s['EN'])?$s['EN']: 0;
            $summary[$id]['CLOSED'] = isset($s['CLOSED'])?$s['CLOSED']: 0;
            $summary[$id]['TOTAL'] = array_sum($s);
            $total['NEW']+= $summary[$id]['NEW'];
            $total['DRAFT']+= $summary[$id]['DRAFT'];
            $total['EN']+= $summary[$id]['EN'];
            $total['CLOSED']+= $summary[$id]['CLOSED'];
            $total['TOTAL']+= $summary[$id]['TOTAL'];
        }

        foreach ($mpEvalList as $row) {
            $mp = $this->_poam->search($this->_me->systems, array(
                'count' => 'system_id',
                'system_id'
            ), array_merge(array('mp' => $row['precedence_id']), $criteria));
            foreach ($mp as $v) {
                $summary[$v['system_id']][$row['nickname']] = $v['count'];
                $total[$row['nickname']]+= $v['count'];
            }
        }

        foreach ($epEvalList as $row) {
            $ep = $this->_poam->search($this->_me->systems, array(
                'count' => 'system_id',
                'system_id'
            ), array_merge(array('ep' => $row['precedence_id']), $criteria));
            foreach ($ep as $v) {
                $summary[$v['system_id']][$row['nickname']] = $v['count'];
                $total[$row['nickname']]+= $v['count'];
            }
        }

        // count the Overdue stauts
        $statusArray = array_keys($summaryTmp);
        $statusArray = array_slice($statusArray, 0, -2);
        foreach ($statusArray as $status) {
            $overdueCount = $this->_poam->search($this->_me->systems, array(
                'count' => 'system_id',
                'system_id'
            ), array_merge($criteria, array('ontime'=>'overdue', 'status'=>$status)));
            foreach ($overdueCount as $row) {
                $summary[$row['system_id']][$status.'overdue'] = $row['count'];
            }
        }

        $this->view->assign('total', $total);
        $this->view->assign('systems', $this->_systemList);
        $this->view->assign('sources', $this->_sourceList);
        $this->view->assign('mpCount', count($mpEvalList));
        $this->view->assign('epCount', count($epEvalList));
        $this->view->assign('statusArray', $statusArray);
        $this->view->assign('summary', $summary);
        $this->view->assign('criteria', $criteria);
        $this->view->assign('criteriaUrl', $criteriaUrl);
        $this->render('summary');
        // Disabling the search box for now because it is not working as
        // intended
        //$this->_helper->actionStack('searchbox', 'Remediation', null,
        //    array('action'=>'summary'));
    }
    
    
    /**
     * parse and translate the URL to criterias
     * which can be used by searchBoxAction method and searchAction method.
     *
     * @param boolean $isSearch default false
     *    switch the searching condition between searchbox and search
     * @return array $params the criterias dealt
     */
    protected function parseCriteria($isSearch = false)
    {
        $this->_acl->requirePrivilege('remediation', 'read');
         
        $params = array('system_id' => 0, 'source_id' => 0, 'type' => '',
                        'status' => '', 'ids' => '', 'asset_owner' => 0,
                        'est_date_begin' => '', 'est_date_end' => '',
                        'created_date_begin' => '', 'created_date_end' => '',
                        'ontime' => '', 'sortby' => '', 'order'=> '', 'keywords' => '');
        $req = $this->getRequest();
        $tmp = $req->getParams();
        foreach($params as $k => &$v) {
            if (isset($tmp[$k])) {
                $v = $tmp[$k];
            }
        }
        if ($isSearch) {
            $this->_paging['currentPage'] = $req->getParam('p', 1);
            if (is_numeric($params['system_id'])) {
                $params['systemId'] = $params['system_id'];
            }
            unset($params['system_id']);
            
            if (is_numeric($params['source_id'])) {
                $params['sourceId'] = $params['source_id'];
            }
            unset($params['source_id']);
 
            if (is_numeric($params['asset_owner'])) {
                $params['assetOwner'] = $params['asset_owner'];
            }
            unset($params['asset_owner']);
            
            if (!empty($params['est_date_begin'])) {
                $params['estDateBegin'] = new Zend_Date($params['est_date_begin'], 'Y-m-d');
            }
            unset($params['est_date_begin']);
            
            if (!empty($params['est_date_end'])) {
                $params['estDateEnd'] = new Zend_Date($params['est_date_end'], 'Y-m-d');
            }
            unset($params['est_date_end']);
            
            if (!empty($params['created_date_begin'])) {
                $params['createdDateBegin'] = new Zend_Date($params['created_date_begin'], 'Y-m-d');
            }
            unset($params['created_date_begin']);
            
            if (!empty($params['created_date_end'])) {
                $params['createdDateEnd'] = new Zend_Date($params['created_date_end'], 'Y-m-d');
            }
            unset($params['created_date_end']);

            if (!empty($params['order']) && !empty($params['sortby'])) {
                $order = array();
                array_push($order, $params['sortby']);
                array_push($order, $params['order']);
                $params['order'] = $order;
                unset($order);
            } else {
                unset($params['order']);
                unset($params['sortby']);
            }
            return $params;
        }
        return $params;
    }
    
    /**
    * Do the real searching work. It's a thin wrapper
    * of poam model's search method.
    */
    public function searchAction()
    {
        $link = $this->makeUrlParams($this->parseCriteria());
        $pageUrl = '/panel/remediation/sub/searchbox' . $link;
        $attachUrl = '/remediation/search' . $link;
        
        $params = $this->parseCriteria(true);
        if (!empty($params['status'])) {
            $now = clone parent::$now;
            switch ($params['status']) {
                case 'NEW':
                     $params['status'] = 'NEW'; break;
                case 'DRAFT':
                     $params['status'] = 'DRAFT'; break;
                case 'EN':
                     $params['status'] = 'EN'; break;
                case 'CLOSED':
                     $params['status'] = 'CLOSED'; break;
                case 'NOT-CLOSED':
                     $params['status'] = array('DRAFT', 'MSA', 'EN', 'EP');
                     break;
                case 'NOUP-30':
                     $params['status'] = array('DRAFT', 'MSA', 'EN', 'EP');
                     $params['modify_ts'] = $now->sub(30, Zend_Date::DAY);
                     break;
                case 'NOUP-60':
                     $params['status'] = array('DRAFT', 'MSA', 'EN', 'EP');
                     $params['modify_ts'] = $now->sub(60, Zend_Date::DAY);
                     break;
                case 'NOUP-90':
                     $params['status'] = array('DRAFT', 'MSA', 'EN', 'EP');
                     $params['modify_ts'] = $now->sub(90, Zend_Date::DAY);
                     break;
                default :
                     $evaluation = new Evaluation();
                     $query = $evaluation->select()->from($evaluation, array('precedence_id', 'group'))
                                                   ->where('nickname = ?', $params['status']);
                     $ret = $evaluation->fetchRow($query)->toArray();
                     if (!empty($ret)) {
                         $precedenceId = $ret['precedence_id'];
                         $group = $ret['group'];
                         if ('ACTION' == $group) {
                             $params['mp']     = $precedenceId;
                         }
                         if ('EVIDENCE' == $group) {
                             $params['ep']     = $precedenceId;
                         }
                     }
                     break;
            }
        }

        //Basic Search
        if (!empty($params['keywords'])) {
            $poamIds = $this->searchQuery($params['keywords']);
            if (!empty($poamIds)) {
                $params['ids'] = implode(',', $poamIds);
                $this->view->assign('keywords', $this->getKeywords($params['keywords']));
            } else {
                $params['ids'] = -1;
            }
        }

        $list = $this->_poam->search($this->_me->systems, '*',
                $params, $this->_paging['currentPage'],
                $this->_paging['perPage'], false);
        $total = array_pop($list);

        //select poams whether have attachments
        foreach ($list as &$row) {
            $query = $this->_poam->getAdapter()->select()->from('evidences', 'id')
                                               ->where('poam_id = '.$row['id']);
            $result = $this->_poam->getAdapter()->fetchRow($query);
            if (!empty($result)) {
                $row['attachments'] = 'Y';
            }
        }

        $this->_helper->contextSwitch()->initContext();
        $format = $this->_helper->contextSwitch()->getCurrentContext();
        if ($format == 'pdf' || $format == 'xls') {
            foreach($list as $k => &$v) {
                $v['finding_data'] = trim(html_entity_decode($v['finding_data']));
                $v['action_suggested'] = trim(html_entity_decode($v['action_suggested']));
                $v['action_planned'] = trim(html_entity_decode($v['action_planned']));
                $v['threat_justification'] = trim(html_entity_decode($v['threat_justification']));
                $v['threat_source'] = trim(html_entity_decode($v['threat_source']));
                $v['cmeasure_effectiveness'] = trim(html_entity_decode($v['cmeasure_effectiveness']));
            }
        }
        
        $this->_paging['totalItems'] = $total;
        $this->_paging['fileName'] = "$pageUrl/p/%d";
        $lastSearchUrl = str_replace('%d', $this->_paging['currentPage'],
                                     $this->_paging['fileName']);
        $urlNamespace = new Zend_Session_Namespace('urlNamespace');
        $urlNamespace->lastSearch = $lastSearchUrl;
        $pager = & Pager::factory($this->_paging);
 
        $this->view->assign('list', $list);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('source_list', $this->_sourceList);
        $this->view->assign('network_list', $this->_networkList);
        $this->view->assign('total_pages', $total);
        $this->view->assign('links', $pager->getLinks());
        $this->view->assign('attachUrl', $attachUrl);
        $this->render();
    }
    
    /**
     * @todo english
     * Accept the criterias dealt by parseCriteria method,
     * return the values to advance search page or basic search page.
     * when the criterias cantain the param 'keywords',
     * then this method will render the basic search box,
     * else render the advance search box
     *
     * Basic search url would be /panel/remediation/sub/searchbox/s/search/system_id/1/type/CAP/status/DRAFT...
     * Advanced search url would be /panel/remediation/sub/searchbox/s/search/keywords/firewal
     * User use advanced search to search the basic search results,the url would be 
     *  /panel/remediation/sub/searchbox/s/search/keywords/firewal/system_id/1/type/CAP...
     *
     */
    public function searchboxAction()
    {
        $params = $this->parseCriteria();
        $pageUrl = $this->_pagingBasePath
           .'/panel/remediation/sub/searchbox'
           .$this->makeUrlParams($params);

        $this->view->assign('url', $pageUrl);
        $this->view->assign('params', $params);
        $this->view->assign('systems', $this->_systemList);
        $this->view->assign('sources', $this->_sourceList);
        $this->_helper->actionStack('search', 'Remediation');
        $this->render();
    }
    /**
     Get remediation detail info
     *
     */
    public function viewAction()
    {
        $this->_acl->requirePrivilege('remediation', 'read');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $poamDetail = $this->_poam->getDetail($id);
        if (empty($poamDetail)) {
            throw new Exception_General("POAM($id) is not found,
                Make sure a valid ID is inputed");
        }

        if (!empty($poamDetail['action_est_date'])
            && $poamDetail['action_est_date'] != $poamDetail['action_current_date']) {
            $query = $this->_poam->getAdapter()->select()
                          ->from(array('al'=>'audit_logs'), 'date_format(timestamp, "%Y-%m-%d") as time')
                          ->join(array('u'=>'users'), 'al.user_id = u.id', 'u.account')
                          ->where('al.poam_id = ?', $id)
                          ->where('al.description like "%action_current_date%"')
                          ->order('al.id DESC');
            $justification = $this->_poam->getAdapter()->fetchRow($query);
            $this->view->assign('justification', $justification);
        }
        
        $msEvaluation = $this->_poam->getActEvaluation($id);
        $evalModel = new Evaluation();
        $msEvallist = $evalModel->getEvalList('ACTION');
        $mss = array();
        if (!empty($msEvaluation)) {
            $i = 0;
            foreach ($msEvaluation as $k=>$row) {
                if ($k != 0 && !($row['precedence_id'] > $msEvaluation[$k-1]['precedence_id'])) {
                    $i++;
                }
                $mss[$i][] = $row;
                if ($k == count($msEvaluation)-1) {
                    if ($row['decision'] == 'DENIED') {
                        //If denied, it should start a new round of evaluation 
                        //however, none of this happens in DRAFT
                        if ($poamDetail['status']!= 'DRAFT') {
                            $mss[$i+1] = $msEvallist;
                        }
                    } else {
                        // Get the list of remaining evaluation 
                        $remainingEval = array_slice($msEvallist, $row['precedence_id']+1);
                        // To keep the evaluation in the same round,re-organization the index
                        foreach ($remainingEval as $v) {
                            $mss[$i][] = $v;
                        }
                    }
                }
            }
        } else {
            $mss[] = $msEvallist;
        }

        $evEvaluation = $this->_poam->getEvEvaluation($id);
        // currently we don't need to support the comments for est_date change
        //$act_evaluation = $this->_poam->getActEvaluation($id);
        $evs = array();
        foreach ($evEvaluation as $evEval) {
            $evid = & $evEval['id'];
            if (!isset($evs[$evid]['ev'])) {
                $evs[$evid]['ev'] = array_slice($evEval, 0, 5);
            }
            $evs[$evid]['acl'] = $this->_acl;
            $evs[$evid]['eval'][$evEval['eval_name']] =
                array_slice($evEval, 5);
        }

        $this->view->assign('poam', $poamDetail);
        $this->view->assign('logs', $this->_poam->getLogs($id));
        $this->view->assign('ev_evals', $evs);
        $this->view->assign('ms_evals', $mss);
        $this->view->assign('ms_evaluation', $msEvaluation);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('network_list', $this->_networkList);
        $this->view->assign('keywords', $req->getParam('keywords'));
    }
    
    /**
     * modifyAction() - ???
     *
     * @todo Do fine-grained access-control here
     */
    public function modifyAction()
    {
        $this->_acl->requirePrivilege('remediation', 'update_finding');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        $poam = $req->getPost('poam');
        if (!empty($poam)) {
            try {
                $oldpoam = $this->_poam->find($id)->toArray();
                if (empty($oldpoam)) {
                    throw new Exception_General("incorrect ID specified for poam");
                } else {
                    $oldpoam = $oldpoam[0];
                }
                if (!empty($oldpoam['action_est_date'])
                    && !empty($poam['action_current_date'])
                    && empty($poam['ecd_justification'])) {
                    throw new Exception_General("The ECD date cannot be changed unless you".
                        " provide a justification in the field below the date.");
                }
                $where = $this->_poam->getAdapter()->quoteInto('id = ?', $id);
                $logContent = "Changed:";
                //@todo sanity check
                //@todo this should be encapsulated in a single transaction
                foreach ($poam as $k => $v) {
                    if ($k == 'type' && $oldpoam['status'] == 'NEW') {
                        assert(empty($poam['status']));
                        $poam['status'] = 'DRAFT';
                        $poam['modify_ts'] = self::$now->toString('Y-m-d H:i:s');
                    }
                    ///@todo SSO can only approve the action after all the required
                    // info provided
                }
                $result = $this->_poam->update($poam, $where);
                        
                // Generate notifications and audit records if the update is
                // successful
                $notificationsSent = array();
                if ( $result > 0 ) {
                    foreach ($poam as $k => &$v) {
                        // We shouldn't send the same type of notification twice
                        // in one update. $notificationsSent is a set which
                        // tracks which notifications we have already created.
                        if (array_key_exists($k, $this->_notificationArray)
                            && !array_key_exists($this->_notificationArray[$k],
                                                 $notificationsSent)) {
                            $this->_notification->add($this->_notificationArray[$k],
                                $this->_me->account,
                                "PoamID: $id",
                                isset($poam['system_id'])?$poam['system_id']: $oldpoam['system_id']);
                            $notificationsSent[$this->_notificationArray[$k]] = 1;
                        }

                        $logContent =
                            "Update: $k\nOriginal: \"{$oldpoam[$k]}\" New: \"$v\"";
                        $this->_poam->writeLogs($id, $this->_me->id,
                            self::$now->toString('Y-m-d H:i:s'), 'MODIFICATION',
                            $logContent);
                    }

                    //Update finding index
                    if (!empty($poam['system_id'])) {
                            $poam['system'] = $this->_systemList[$poam['system_id']];
                            unset($poam['system_id']);
                    }
                    Config_Fisma::updateIndex('findings', $id, $poam);
                }
            } catch (Exception_General $e) {
                if ($e instanceof Exception_General) {
                    $message = $e->getMessage();
                } else {
                    $message = "Failed to modify the poam.";
                }
                $this->message($message, self::M_WARNING);
            }
        }
        
        //throw new Fisma_Excpection('POAM not updated for some reason');
        $this->_forward('view', null, null, array('id' => $id));
    }

    /**
     * Mitigation Strategy Approval Process
     */
    public function msaAction()
    {
        $poamId = $this->_request->getParam('id');
        $evalId = $this->_request->getParam('eval_id');
        $isMsa  = $this->_request->getParam('is_msa');
        $decision = $this->_request->getPost('decision');
        $oldpoam = $this->_poam->find($poamId)->toArray();
        if (empty($oldpoam)) {
            throw new Exception_General('incorrect ID specified for poam');
        } else {
                $oldpoam = $oldpoam[0];
        }
        if (isset($isMsa)) {
            if (!in_array($isMsa, array(0, 1))) {
                throw new Exception_General('incorrect mitigation strategy operate');
            }
            //Submit Mitiagtion Strategy
            if (1 == $isMsa) {
                $poam['status'] = 'MSA';
                $poam['mss_ts'] = self::$now->toString('Y-m-d H:i:s');

                $msEvaluation = $this->_poam->getActEvaluation($poamId);
                /** @todo english 
                 * Delete old approval logs while the mitigation strategy was submit after revised.
                 */
                if (!empty($msEvaluation) && 'APPROVED' == $msEvaluation[count($msEvaluation)-1]['decision']) {
                    $this->_poam->getAdapter()->delete('poam_evaluations', 'group_id = '.$poamId.' AND '.
                    ' eval_id IN (SELECT id FROM `evaluations` WHERE `group` = "ACTION")');
                }
                if (empty($oldpoam['action_est_date'])) {
                    $poam['action_est_date'] = $oldpoam['action_current_date'];
                }
            //Revise Mitigation Strategy
            } else {
                $poam['status'] = 'DRAFT';
            }
        }

        if (!empty($decision)) {
            $poamEvalId = $this->_poam->reviewEv($poamId, array('decision' => $decision,
                                                               'eval_id'  => $evalId,
                                                               'user_id'  => $this->_me->id,
                                                               'date'     => self::$now->toString('Y-m-d')));
            $evaluation = new Evaluation();
            $msEvalList = $evaluation->getEvalList('ACTION');
            $ret = $evaluation->find($evalId);
            $evalNickname = $ret->current()->nickname;
            $logContent = "Update: $evalNickname\nOriginal: \"NONE\" New: \"".$decision."\"";
            
            $this->_notification->add($ret->current()->event_id,
                        $this->_me->account,
                        "PoamID: $poamId",
                        $oldpoam['system_id']);

            if ('APPROVED' == $decision) {
                if ($evalId == $msEvalList[count($msEvalList)-1]['id']) {
                    $poam['status'] = 'EN';
                }
            }
            if ('DENIED' == $decision) {
                $poam['status'] = 'DRAFT';
                $comment = $this->_request->getParam('comment');
                $body = $this->_request->getParam('reject');
                $comm = new Comments();
                $comm->insert(array('poam_evaluation_id' => $poamEvalId,
                                    'user_id' => $this->_me->id,
                                    'date' => self::$now->toString('Y-m-d H:i:s'),
                                    'content' => $comment,
                                    'date' => new Zend_Db_Expr('NOW()')));
                $logContent .=" Status: DRAFT. Justification: $comment";
            }

            if (!empty($logContent)) {
                 $this->_poam->writeLogs($poamId, $this->_me->id, 
                     self::$now->toString('Y-m-d H:i:s'), 
                     'MODIFICATION', $logContent);
            }
        }

        if (!empty($poam)) {
            $this->_poam->update($poam, 'id = '. $poamId);
        }
        $this->_redirect('/panel/remediation/sub/view/id/' . $poamId, array(
            'exit'
        ));
    }

    public function uploadevidenceAction()
    {
        $this->_acl->requirePrivilege('remediation', 'update_evidence');
        
        $req = $this->getRequest();
        $id = $req->getParam('id');
        define('EVIDENCE_PATH', APPLICATION_ROOT . '/public/evidence');
        if ($_FILES && $id > 0) {
            $poam = $this->_poam->find($id)->toArray();
            if (empty($poam)) {
                throw new Exception_General('incorrect ID specified for poam');
            } else {
                $poam = $poam[0];
            }
            
            $userId = $this->_me->id;
            $nowStr = self::$now->toString('Y-m-d-his');
            if (!file_exists(EVIDENCE_PATH)) {
                mkdir(EVIDENCE_PATH, 0755);
            }
            if (!file_exists(EVIDENCE_PATH .'/'. $id)) {
                mkdir(EVIDENCE_PATH .'/'. $id, 0755);
            }
            $count = 0;
            $filename = preg_replace('/^([^.]*)(\.[^.]*)?\.([^.]*)$/',
                '$1$2-' . $nowStr . '.$3', $_FILES['evidence']['name'],
                2, $count);
            $absFile = EVIDENCE_PATH ."/{$id}/{$filename}";
            $absFile = EVIDENCE_PATH ."/{$id}/{$filename}";
            if ($count > 0) {
                $resultMove =
                    move_uploaded_file($_FILES['evidence']['tmp_name'],
                        $absFile);
                if ($resultMove) {
                    chmod($absFile, 0755);
                } else {
                    throw new Exception_General('Failed in move_uploaded_file(). '
                        . $absFile . $_FILES['evidence']['error']);
                }
            } else {
                throw new Exception_General('The filename is not valid');
            }
            $today = substr($nowStr, 0, 10);
            $data = array(
                'poam_id' => $id,
                'submission' => $filename,
                'submitted_by' => $userId,
                'submit_ts' => $today
            );
            $db = Zend_Registry::get('db');
            $result = $db->insert('evidences', $data);
            $evidenceId = $db->LastInsertId();
            $this->_notification->add(Notification::EVIDENCE_APPROVAL_1ST,
                $this->_me->account,
                "PoamId: $id",
                $poam['system_id']);

            $updateData = array(
                'status' => 'EA',
                'action_actual_date' => $today
            );
            $result = $this->_poam->update($updateData, "id = $id");
            if ($result > 0) {
                $logContent = "Changed: status: EA . Upload evidence:"
                              ." $filename OK";
                $this->_poam->writeLogs($id, $userId,
                    self::$now->toString('Y-m-d H:i:s'),
                    'UPLOAD EVIDENCE', $logContent);
            }
        }
        $this->_redirect('/panel/remediation/sub/view/id/' . $id);
    }
    /**
     *  Handle the evidence evaluations
     */
    public function evidenceAction()
    {
        $req = $this->getRequest();
        $evalId = $req->getParam('evaluation');
        $precedenceId = $req->getParam('precedence');
        $decision = $req->getParam('decision');
        $eid = $req->getParam('id');
        $ev = new Evidence();
        $evDetail = $ev->find($eid);

        $eval = new Evaluation();
        $evalList = $eval->getEvalList('EVIDENCE');

        // Get the poam data because we need system_id to generate the
        // notification
        $poam = $this->_poam->find($evDetail->current()->poam_id)->toArray();
        if (empty($poam)) {
            throw new Exception_General('incorrect ID specified for poam');
        } else {
            $poam = $poam[0];
        }
        
        if (empty($evDetail)) {
            throw new Exception_General('Wrong evidence id:' . $eid);
        }
        if ($decision == 'APPROVE') {
            $decision = 'APPROVED';
        } else if ($decision == 'DENY') {
            $decision = 'DENIED';
        } else {
            throw new Exception_General('Wrong decision:' . $decision);
        }
        $poamId = $evDetail->current()->poam_id;
        $logContent = "";
        if (in_array($decision, array(
            'APPROVED',
            'DENIED'
        ))) {
            $logContent = "";
            $evvId = $this->_poam->reviewEv($eid, array(
                'decision' => $decision,
                'eval_id' => $evalId,
                'user_id' => $this->_me->id,
                'date' => self::$now->toString('Y-m-d')
            ));

            $logContent.= $evalList[$precedenceId]['nickname'] ." Decision: $decision.";

            if ('APPROVED' == $decision) {
                $this->_notification->add($evalList[$precedenceId]['event_id'], $this->_me->account,
                                          "PoamId: $poamId", $poam['system_id']);

                
                if ($precedenceId == count($evalList)-1) {
                    $logContent.= " Status: CLOSED";
                    $this->_poam->update(array('status' => 'CLOSED'), 'id=' . $poamId);
            
                    $this->_notification->add(Notification::POAM_CLOSED, $this->_me->account, 
                                         "PoamId: $poamId", $poam['system_id']);
                }
            } else {
                $this->_poam->update(array('status' => 'EN', 'action_actual_date' => null), 'id=' . $poamId);
                $content = $req->getParam('comment');
                $body = $req->getParam('reject');
                $comm = new Comments();
                $comm->insert(array('poam_evaluation_id' => $evvId,
                                    'user_id' => $this->_me->id,
                                    'date' => 'CURDATE()',
                                    'content' => $content,
                                    'date' => new Zend_Db_Expr('NOW()')));

                $logContent .= " Status: EN. Justification: $content";
                $this->_notification->add(Notification::EVIDENCE_DENIED,
                                          $this->_me->account,
                                          "PoamId: $poamId",
                                          $poam['system_id']);
            }
            if (!empty($logContent)) {
                $logContent = "Changed: $logContent";
                $this->_poam->writeLogs($poamId, $this->_me->id,
                    self::$now->toString('Y-m-d H:i:s'),
                    'EVIDENCE EVALUATION', $logContent);
            }
        }
        $this->_redirect('/panel/remediation/sub/view/id/' . $poamId, array(
            'exit'
        ));
    }

    /**
     *  Generate RAF report
     *
     *  It can handle different format of RAF report.
     */
    public function rafAction()
    {
        $this->_acl->requirePrivilege('report', 'generate_system_rafs');
        
        $id = $this->_req->getParam('id');
        $poamDetail = $this->_poam->getDetail($id);
        try {
            if (empty($poamDetail)) {
                throw new Exception_General(
                    "Not able to get details for this POAM ID ($id)");
            }

            if ($poamDetail['threat_source'] == '' ||
                $poamDetail['threat_level'] == 'NONE' ||
                $poamDetail['cmeasure'] == '' ||
                $poamDetail['cmeasure_effectiveness'] == 'NONE'){
                throw new Exception_General("The Threat or Countermeasures Information is not "
                    ."completed. An analysis of risk cannot be generated, unless these values are defined.");
            }

            $system = new System();
            $ret = $system->find($poamDetail['system_id']);
            $actOwner = $ret->current()->toArray();

            $securityCategorization = $system->calcSecurityCategory($actOwner['confidentiality'],
                                                                    $actOwner['integrity'],
                                                                    $actOwner['availability']);

            if (NULL == $securityCategorization) {
                throw new Exception_General('The security categorization for ('.$actOwner['id'].')'.
                    $actOwner['name'].' is not defined. An analysis of risk cannot be generated '.
                    'unless these values are defined.');
            }
            $this->view->assign('securityCategorization', $securityCategorization);
        } catch (Exception_General $e) {
            if ($e instanceof Exception_General) {
                $message = $e->getMessage();
            }
            $this->message($message, self::M_WARNING);
            $this->_forward('remediation', 'Panel', null, array('id' => $id, 'sub'=>'view'));
        }

        $this->_helper->contextSwitch()
               ->setHeader('pdf', 'Content-Disposition', "attachement;filename={$id}_raf.pdf")
               ->initContext();
        
        $this->view->assign('poam', $poamDetail);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('source_list', $this->_sourceList);
    }

    /**
     * Create findings Lucene Index
     *
     * @return Object Zend_Search_Lucene
     */
    protected function getIndex()
    {
        if (is_dir(APPLICATION_ROOT . '/data/index/findings')) {
            $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/findings');
        } else {
            $index = new Zend_Search_Lucene(APPLICATION_ROOT . '/data/index/findings', true);
            $list = $this->_poam->search($this->_me->systems, '*');
            set_time_limit(0);
            if (!empty($list)) {
                foreach ($list as $row) {
                    $doc = new Zend_Search_Lucene_Document();
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('key', md5($row['id'])));
                    $doc->addField(Zend_Search_Lucene_Field::UnIndexed('rowId', $row['id']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('finding_data', $row['finding_data']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('action_planned', $row['action_planned']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('action_suggested',
                                $row['action_suggested']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('action_resources',
                                $row['action_resources']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('cmeasure', $row['cmeasure']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('cmeasure_justification',
                                $row['cmeasure_justification']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('threat_source', $row['threat_source']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('threat_justification',
                                $row['threat_justification']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('system',
                                $row['system_name'] . ' ' . $row['system_nickname']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('source',
                                $row['source_name'] . ' ' . $row['source_nickname']));
                    $doc->addField(Zend_Search_Lucene_Field::UnStored('asset', $row['asset_name']));
                    $index->addDocument($doc);
                }
                $index->optimize();
            }
        }
        return $index;
    }

    /**
     * Fuzzy Search by Zend_Search_Lucene
     *
     * @param string $keywords the search conditions
     *      The keywords should be as following format:
     *              a.   keyword (search keyword in all fields)
     *              b.   field:keyword (search keyword in field)
     *              c.   keyword1 field:keyword2 -keyword3 (required keyword1 in all fields,
     *                   required keyword2 in field, not required keyword3 in all fields)
     *              d.   keywor*  (to search for keywor, keyword, keywords, etc.)
     *              e.   keywo?d  (to search for keyword, keywoaed ,etc.)
     *              f.   mod_date:[20080101 TO 20080130] (search mod_date fields between 20080101 and 20080130)
     *              g.   title:{Aida To Carmen} (search whose titles would be sorted between Aida and Carmen)
     *              h.   keywor~  (fuzzy search, search like keyword, leyword, etc.)
     *              i.   keyword1 AND keyword2 (search documents that contain keyword1 and keyword2)
     *              j.   keyword1 OR keyword2 (search docuements that contain keyword1 or keyword2)
     *              k.   keyword1 AND NOT keyword2 (search documents that contain keyword1 but not keywords2)
     *              ... see Zend_Search_Lucene for more format
     * @return array search finding ids
     */
    protected function searchQuery($keywords)
    {
        $cache = Zend_Registry::get('cache');
        $index = $this->getIndex();
        if (!$cache->load('keywords') || $keywords != $cache->load('keywords')) {
            $hits = $index->find($keywords);
            foreach ($hits as $row) {
                $poamIds[] = $row->rowId;
            }
            $cache->save($poamIds, 'poam_ids');
            $cache->save($keywords, 'keywords');
        }
        return $cache->load('poam_ids');
    }

    /**
     * @todo english
     * Get keywords from basic search query for highlight
     *
     * Basic search query is a complicated format string, system should pick-up available keywords to highlight
     */
    protected function getKeywords($query)
    {
        $keywords = '';
        $keywords = strtolower($query);

        //delete not contain keyword (-keyword, NOT keyword)
        $keywords = preg_replace('/-[A-Za-z0-9]+$/', '', $keywords);
        $keywords = preg_replace('/not\s+[A-Za-z0-9]+$/', '', $keywords);

        //delete Zend_Search_Lucene query keywords
        $searchKeys = array('and', 'or', 'not', 'to', '+', '-', '&&', '~', '||', '!');
        foreach ($searchKeys as $row) {
            $keywords = str_replace($row, '', $keywords);
        }
        
        //delete multi-spaces
        $keywords = preg_replace('/\s{2,}/', ' ', $keywords);

        //delete search field
        $keywords = explode(' ', trim($keywords));
        foreach ($keywords as &$word) {
            $word = preg_replace('/^.+:/', '', $word);
        }
        
        $keywords = implode(',', $keywords);
        return $keywords;
    }
}
