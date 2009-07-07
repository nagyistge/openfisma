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
 * @author    Ryan Yang <ryan@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Controller
 */

/**
 * The report controller creates the multitude of reports available in
 * OpenFISMA.
 *
 * @package   Controller
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class ReportController extends SecurityController
{
    /**
     * init() - Create the additional pdf and xls contexts for this class.
     *
     * @todo Why are the contexts duplicated in init() and predispatch()? I think the init() method is the right place
     * for it.
     */
    public function init()
    {
        parent::init();
        $swCtx = $this->_helper->contextSwitch();
        if (!$swCtx->hasContext('pdf')) {
            $swCtx->addContext('pdf', array(
                'suffix' => 'pdf',
                'headers' => array(
                    'Content-Disposition' => 
                        'attachement;filename="export.pdf"',
                    'Content-Type' => 'application/pdf'
                )
            ));
        }
        if (!$swCtx->hasContext('xls')) {
            $swCtx->addContext('xls', array(
                'suffix' => 'xls',
                'headers' => array(
                    'Content-type' => 'application/vnd.ms-excel',
                    'Content-Disposition' => 'filename=Fisma_Report.xls'
                )
            ));
        }
    }
    
    /**
     * preDispatch() - Add the action contexts for this controller.
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $this->req = $this->getRequest();
        $swCtx = $this->_helper->contextSwitch();
        $swCtx->addActionContext('poam', array('pdf', 'xls'))
              ->addActionContext('fisma', array('pdf', 'xls'))
              ->addActionContext('blscr', array('pdf', 'xls'))
              ->addActionContext('fips', array('pdf', 'xls'))
              ->addActionContext('prods', array('pdf', 'xls'))
              ->addActionContext('swdisc', array('pdf', 'xls'))
              ->addActionContext('total', array('pdf', 'xls'))
              ->addActionContext('overdue', array('pdf', 'xls'))
              ->addActionContext('plugin-report', array('pdf', 'xls'))
              ->addActionContext('fisma-quarterly', 'xls')
              ->addActionContext('fisma-annual', 'xls')
              ->initContext();
    }

    /**
     * Returns the due date for the next quarterly FISMA report
     * 
     * @return Zend_Date
     */
    public function getNextQuarterlyFismaReportDate()
    {
        // The quarterly reports are due on 3/1, 6/1, 9/1 and 12/1
        $reportDate = new Zend_Date();
        if (1 == (int)$reportDate->getDay()->toString('d')) {
            $reportDate->subMonth(1);
        }
        $reportDate->setDay(1);
        switch ((int)$reportDate->getMonth()->toString('m')) {
            case 12:
                $reportDate->addYear(1);
            case 1:
            case 2:
                $reportDate->setMonth(3);
                break;
            case 3:
            case 4:
            case 5:
                $reportDate->setMonth(6);
                break;
            case 6:
            case 7:
            case 8:
                $reportDate->setMonth(9);
                break;
            case 9:
            case 10:
            case 11:
                $reportDate->setMonth(12);
                break;
        }
        return $reportDate;
    }

    /**
     * Returns the due date for the next annual FISMA report
     * 
     * @return Zend_Date
     */
    public function getNextAnnualFismaReportDate()
    {
        // The annual report is due Oct 1 of each year
        $reportDate = new Zend_Date();
        $reportDate->setMonth(10);
        $reportDate->setDay(1);
        if (-1 == $reportDate->compare(new Zend_Date())) {
            $reportDate->addYear(1);
        }
        return $reportDate;
    }

    /**
     * fismaAction() - Genenerate fisma report
     */
    public function fismaAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_fisma_report');
        
        $this->view->nextQuarterlyReportDate = $this->getNextQuarterlyFismaReportDate()->toString('Y-m-d');
        $this->view->nextAnnualReportDate = $this->getNextAnnualFismaReportDate()->toString('Y-m-d');
    }
    
    /**
     * Generate the quarterly FISMA report
     * 
     * The data in this action is calculated in roughly the same order as it is laid out in the report itself.
     */
    public function fismaQuarterlyAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_fisma_report');

        // Agency Name
        $agency = Organization::getAgency();
        $this->view->agencyName = $agency->name;
        
        // Submission Date
        $this->view->submissionDate = date('Y-m-d');
        
        // Bureau Statistics
        $bureaus = Organization::getBureaus();
        $stats = array();
        foreach ($bureaus as $bureau) {
            $stats[] = $bureau->getFismaStatistics();
        }
        $this->view->stats = $stats;
    }
    
    /**
     * Generate the annual FISMA report
     */
    public function fismaAnnualAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_fisma_report');
    }
    
    /**
     * poamAction() - Generate poam report
     */
    public function poamAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_poam_report');
        
        $req = $this->getRequest();
        $params['system_id'] = $req->getParam('system_id');
        $params['source_id'] = $req->getParam('source_id');
        $params['type'] = $req->getParam('type');
        $params['year'] = $req->getParam('year');
        $params['status'] = $req->getParam('status');
        $this->view->assign('source_list', $this->_sourceList);
        $this->view->assign('system_list', $this->_systemList);
        $this->view->assign('network_list', $this->_networkList);
        $this->view->assign('params', $params);
        $isExport = $req->getParam('format');

        if ('search' == $req->getParam('s') || isset($isExport)) {
            $criteria = array();
            if (!empty($params['system_id'])) {
                $criteria['systemId'] = $params['system_id'];
            }
            if (!empty($params['source_id'])) {
                $criteria['sourceId'] = $params['source_id'];
            }
            if (!empty($params['type'])) {
                $criteria['type'] = $params['type'];
            }
            if (!empty($params['status'])) {
                if ('OPEN' == $params['status']) {
                    $criteria['status'] = array('NEW', 'DRAFT', 'MSA', 'EN', 'EA');
                } else {
                    $criteria['status'] = $params['status'];
                }
            }
            $this->_pagingBasePath.= '/panel/report/sub/poam/s/search';
            if (isset($isExport)) {
                $this->_paging['currentPage'] = 
                    $this->_pagging['perPage'] = null;
            }
            $this->makeUrl($params);
            if (!empty($params['year'])) {
                $criteria['createdDateBegin'] = new 
                    Zend_Date($params['year'], Zend_Date::YEAR);
                $criteria['createdDateEnd'] = clone $criteria['createdDateBegin'];
                $criteria['createdDateEnd']->add(1, Zend_Date::YEAR);
            }
            $list = & $this->_poam->search($this->_me->systems, array(
                'id',
                'finding_data',
                'system_id',
                'network_id',
                'source_id',
                'asset_id',
                'type',
                'ip',
                'port',
                'status',
                'action_suggested',
                'action_planned',
                'action_current_date',
                'action_est_date',
                'cmeasure',
                'threat_source',
                'threat_level',
                'threat_source',
                'threat_justification',
                'cmeasure',
                'cmeasure_effectiveness',
                'cmeasure_justification',
                'blscr_id',
                'duetime',
                'count' => 'count(*)'), 
                $criteria, $this->_paging['currentPage'], 
                $this->_paging['perPage'],
                false);
            $total = array_pop($list);
            $this->_paging['totalItems'] = $total;
            $this->_paging['fileName'] = "{$this->_pagingBasePath}/p/%d";
            $pager = & Pager::factory($this->_paging);
            if ($isExport) {
                foreach ($list as $k => &$v) {
                    $v['finding_data'] = trim(html_entity_decode($v['finding_data']));
                    $v['action_suggested'] = trim(html_entity_decode($v['action_suggested']));
                    $v['action_planned'] = trim(html_entity_decode($v['action_planned']));
                    $v['threat_justification'] = trim(html_entity_decode($v['threat_justification']));
                    $v['threat_source'] = trim(html_entity_decode($v['threat_source']));
                    $v['cmeasure_effectiveness'] = trim(html_entity_decode($v['cmeasure_effectiveness']));
                }
            }
            $this->view->assign('poam', $this->_poam);
            $this->view->assign('poam_list', $list);
            $this->view->assign('links', $pager->getLinks());
        }
    }
    
    /**
     * overdueAction() - Overdue report
     */
    public function overdueAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_overdue_report');
        
        // Get request variables
        $req = $this->getRequest();
        $params['orgSystemId'] = $req->getParam('orgSystemId');
        $params['sourceId'] = $req->getParam('sourceId');
        $params['overdueType'] = $req->getParam('overdueType');
        $params['overdueDay'] = $req->getParam('overdueDay');
        $params['year'] = $req->getParam('year');

        $this->view->assign('source_list', Doctrine::getTable('Source')->findAll()->toKeyValueArray('id', 'name'));
        $this->view->assign('system_list', $this->_me->getOrganizations()->toKeyValueArray('id', 'name'));
        $this->view->assign('network_list', Doctrine::getTable('Network')->findAll()->toKeyValueArray('id', 'name'));
        $this->view->assign('params', $params);
        $this->view->assign('url', '/report/overdue' . $this->_helper->makeUrlParams($params));
        $isExport = $req->getParam('format');
        
        if ('search' == $req->getParam('s') || isset($isExport)) {
            // Search for overdue items according to the criteria
            $q = Doctrine_Query::create()
                    ->select('f.*')
                    ->addSelect('DATEDIFF(NOW(), f.nextDueDate) diffDay')
                    ->from('Finding f')
                    ->where('DATEDIFF(NOW(), f.nextDueDate) > 0');
            if (!empty($params['orgSystemId'])) {
                $q->andWhere('f.responsibleOrganizationId = ?', $params['orgSystemId']);
            }
            if (!empty($params['sourceId'])) {
                $q->andWhere('f.sourceId = ?', $params['sourceId']);
            }
            if ($params['overdueType'] == 'sso') {
                $q->whereIn('f.status', array('NEW', 'DRAFT', 'MSA'));
            } elseif ($params['overdueType'] == 'action') {
                $q->whereIn('f.status', array('EN', 'EA'));
            } else {
                $q->whereIn('f.status', array('NEW', 'DRAFT', 'MSA', 'EN', 'EA'));
            }
            $list = $q->execute();
            // Assign view outputs
            $this->view->assign('poam_list', $this->_helper->overdueStatistic($list));
            $this->view->criteria = $params;
            $this->view->columns = array('orgSystemName' => 'System', 'type' => 'Overdue Action Type', 'lessThan30' => '<30 Days',
                                         'moreThan30' => '30-59 Days', 'moreThan60' => '60-89 Days', 'moreThan90' => '90-119 Days',
                                         'moreThan120' => '120+ Days', 'total' => 'Total Overdue', 'average' => 'Average (days)',
                                         'max' => 'Maximum (days)');
        }
    }

    /**
     * generalAction() - Generate general report
     */
    public function generalAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_general_report');
        
        $req = $this->getRequest();
        $type = $req->getParam('type', '');
        $this->view->assign('type', $type);
        $this->render();
        if (!empty($type) && ('search' == $req->getParam('s'))) {
            define('REPORT_GEN_BLSCR', 1);
            define('REPORT_GEN_FIPS', 2);
            define('REPORT_GEN_PRODS', 3);
            define('REPORT_GEN_SWDISC', 4);
            define('REPORT_GEN_TOTAL', 5);
            
            if (REPORT_GEN_BLSCR == $type) {
                $this->_forward('blscr');
            }
            if (REPORT_GEN_FIPS == $type) {
                $this->_forward('fips');
            }
            if (REPORT_GEN_PRODS == $type) {
                $this->_forward('prods');
            }
            if (REPORT_GEN_SWDISC == $type) {
                $this->_forward('swdisc');
            }
            if (REPORT_GEN_TOTAL == $type) {
                $this->_forward('total');
            }
        }
    }
    
    /**
     * blscrAction() - Generate BLSCR report
     */
    public function blscrAction() {
        Fisma_Acl::requirePrivilege('report', 'generate_general_report');
        
        $db = $this->_poam->getAdapter();
        $system = new system();
        $rpdata = array();
        $query = $db->select()->from(array(
            'p' => 'poams'
        ), array(
            'num' => 'count(p.id)'
        ))->join(array(
            'b' => 'blscrs'
        ), 'b.code = p.blscr_id', array(
            'blscr' => 'b.code'
        ))->where("b.class = 'MANAGEMENT'")->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $query->reset();
        $query = $db->select()->from(array(
            'p' => 'poams'
        ), array(
            'num' => 'count(p.id)'
        ))->join(array(
            'b' => 'blscrs'
        ), 'b.code = p.blscr_id', array(
            'blscr' => 'b.code'
        ))->where("b.class = 'OPERATIONAL'")->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $query->reset();
        $query = $db->select()->from(array(
            'p' => 'poams'
        ), array(
            'num' => 'count(p.id)'
        ))->join(array(
            'b' => 'blscrs'
        ), 'b.code = p.blscr_id', array(
            'blscr' => 'b.code'
        ))->where("b.class = 'TECHNICAL'")->group("b.code");
        $rpdata[] = $db->fetchAll($query);
        $this->view->assign('rpdata', $rpdata);
    }
    
    /**
     * fipsAction() - FIPS report
     */
    public function fipsAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_general_report');
        
        $sysObj = new System();
        $systems = $sysObj->getList(array(
            'name' => 'name',
            'type' => 'type',
            'conf' => 'confidentiality',
            'avail' => 'availability',
            'integ' => 'integrity'
        ));
        $fipsTotals = array();
        $fipsTotals['LOW'] = 0;
        $fipsTotals['MODERATE'] = 0;
        $fipsTotals['HIGH'] = 0;
        $fipsTotals['n/a'] = 0;
        foreach ($systems as $sid => & $system) {
            if (strtolower($system['conf']) != 'none') {
                $fips = $sysObj->calcSecurityCategory($system['conf'], $system['integ'], $system['avail']);
            } else {
                $fips = 'n/a';
            }
            $qry = $this->_poam->select()->from('poams', array(
                'last_update' => 'MAX(modify_ts)'
            ))->where('poams.system_id = ?', $sid);
            $result = $this->_poam->fetchRow($qry);
            if (!empty($result)) {
                $ret = $result->toArray();
                $system['last_update'] = $ret['last_update'];
            }
            $system['fips'] = $fips;
            $fipsTotals[$fips]+= 1;
            $system['crit'] = $system['avail'];
        }
        $rpdata = array();
        $rpdata[] = $systems;
        $rpdata[] = $fipsTotals;
        $this->view->assign('rpdata', $rpdata);
    }
    
    /**
     * prodsAction() - Generate products report
     */
    public function prodsAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_general_report');
        
        $db = $this->_poam->getAdapter();
        $query = $db->select()->from(array(
            'prod' => 'products'
        ), array(
            'Vendor' => 'prod.vendor',
            'Product' => 'prod.name',
            'Version' => 'prod.version',
            'NumoOV' => 'count(prod.id)'
        ))->join(array(
            'p' => 'poams'
        ), 'p.status IN ("DRAFT","MSA", "EN","EA")', array())->join(array(
            'a' => 'assets'
        ), 'a.id = p.asset_id AND a.prod_id = prod.id', array())
            ->group("prod.vendor")->group("prod.name")->group("prod.version");
        $rpdata = $db->fetchAll($query);
        $this->view->assign('rpdata', $rpdata);
    }
    
    /**
     * swdiscAction() - Software discovered report
     */
    public function swdiscAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_general_report');
        
        $db = $this->_poam->getAdapter();
        $query = $db->select()->from(array(
            'p' => 'products'
        ), array(
            'Vendor' => 'p.vendor',
            'Product' => 'p.name',
            'Version' => 'p.version'
        ))->join(array(
            'a' => 'assets'
        ), 'a.source = "SCAN" AND a.prod_id = p.id', array());
        $rpdata = $db->fetchAll($query);
        $this->view->assign('rpdata', $rpdata);
    }
    
    /**
     * totalAction() - ???
     */
    public function totalAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_general_report');
        
        $db = $this->_poam->getAdapter();
        $system = new system();
        $rpdata = array();
        $query = $db->select()->from(array(
            'sys' => 'systems'
        ), array(
            'sysnick' => 'sys.nickname',
            'vulncount' => 'count(sys.id)'
        ))->join(array(
            'p' => 'poams'
        ), 'p.type IN ("CAP","AR","FP") AND
            p.status IN ("DRAFT", "MSA", "EN", "EA") AND p.system_id = sys.id',
            array())->join(array(
            'a' => 'assets'
        ), 'a.id = p.asset_id', array())->group("p.system_id");
        $sysVulncounts = $db->fetchAll($query);
        $sysNicks = $system->getList('nickname');
        $systemTotals = array();

        foreach ($sysNicks as $nickname) {
            $systemNick = $nickname['nickname'];
            $systemTotals[$systemNick] = 0;
        }
        $totalOpen = 0;
        foreach ((array)$sysVulncounts as $svRow) {
            $systemNick = $svRow['sysnick'];
            $systemTotals[$systemNick] = $svRow['vulncount'];
            $totalOpen++;
        }
        $systemTotalArray = array();
        foreach (array_keys($systemTotals) as $key) {
            $val = $systemTotals[$key];
            $thisRow = array();
            $thisRow['nick'] = $key;
            $thisRow['num'] = $val;
            array_push($systemTotalArray, $thisRow);
        }
        array_push($rpdata, $totalOpen);
        array_push($rpdata, $systemTotalArray);
        $this->view->assign('rpdata', $rpdata);
    }
    /**
     * rafsAction() - Batch generate RAFs for each system
     */
    public function rafsAction()
    {
        Fisma_Acl::requirePrivilege('report', 'generate_system_rafs');
        $sid = $this->_req->getParam('system_id', 0);
        $this->view->assign('system_list', $this->_systemList);
        if (!empty($sid)) {
            $query = $this->_poam->select()->from($this->_poam, array(
                'id'
            ))->where('system_id=?', $sid)
                ->where('threat_level IS NOT NULL AND threat_level != \'NONE\'')
                ->where('cmeasure_effectiveness IS NOT NULL AND 
                                    cmeasure_effectiveness != \'NONE\'');
            $poamIds = $this->_poam->getAdapter()->fetchCol($query);
            $count = count($poamIds);
            if ($count > 0) {
                $fname = tempnam('/tmp/', "RAFs");
                @unlink($fname);
                $rafs = new Archive_Tar($fname, true);
                $path = $this->_helper->viewRenderer
                        ->getViewScript('raf', array(
                                        'controller' => 'remediation',
                                        'suffix' => 'pdf.phtml'));
                try {
                    $system = new System();
                    foreach ($poamIds as $id) {
                        $poamDetail = & $this->_poam->getDetail($id);
                        $this->view->assign('poam', $poamDetail);
                        $ret = $system->find($poamDetail['system_id']);
                        $actOwner = $ret->current()->toArray();
                        $securityCategorization = $system->calcSecurityCategory($actOwner['confidentiality'],
                                                                                $actOwner['integrity'],
                                                                                $actOwner['availability']);
                        if (NULL == $securityCategorization) {
                            throw new Fisma_Exception('The security categorization for ('.$actOwner['id'].')'.
                                $actOwner['name'].' is not defined. An analysis of risk cannot be generated '.
                                'unless these values are defined.');
                        }
                        $this->view->assign('securityCategorization', $securityCategorization);
                        $rafs->addString("raf_{$id}.pdf", $this->view->render($path));
                    }
                    $this->_helper->layout->disableLayout(true);
                    $this->_helper->viewRenderer->setNoRender();
                    header("Content-type: application/octetstream");
                    header('Content-Length: ' . filesize($fname));
                    header("Content-Disposition: attachment; filename=RAFs.tgz");
                    header("Content-Transfer-Encoding: binary");
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate, post-check=0,".
                        " pre-check=0");
                    header("Pragma: public");
                    echo file_get_contents($fname);
                    @unlink($fname);
                } catch (Fisma_Exception $e) {
                    if ($e instanceof Fisma_Exception) {
                        $message = $e->getMessage();
                    }
                    $this->message($message, self::M_WARNING);
                }
            } else {
                $this->view->sid = $sid;
                /** @todo english */
                $this->message('No finding', self::M_WARNING);
                $this->_forward('report', 'panel', null, array('sub' => 'rafs', 'system_id' => ''));
            }
        }
    }
    
    /**
     * pluginAction() - Display the available plugin reports
     *
     * @todo Use Zend_Cache for the report menu
     */         
    public function pluginAction() 
    {
        Fisma_Acl::requirePrivilege('report', 'read');
        
        // Build up report menu
        $reportsConfig = new Zend_Config_Ini(Fisma::getPath('application') . '/config/reports.conf');
        $reports = $reportsConfig->toArray();
        $this->view->assign('reports', $reports);
    }

    /**
     * pluginReportAction() - Execute and display the specified plug-in report
     */         
    public function pluginReportAction()
    {
        Fisma_Acl::requirePrivilege('report', 'read');
        
        // Verify a plugin report name was passed to this action
        $reportName = $this->getRequest()->getParam('name');
        if (!isset($reportName)) {
            $this->_forward('plugin');
            return;
        }
        
        // Verify that the user has permission to run this report
        $reportConfig = new Zend_Config_Ini(Fisma::getPath('application') . '/config/reports.conf', $reportName);
        if ($this->_me->username != 'root') {
            $reportRoles = $reportConfig->roles;
            $report = $reportConfig->toArray();
            $reportRoles = $report['roles'];
            if (!is_array($reportRoles)) {
                $reportRoles = array($reportRoles);
            }
            if (!in_array($this->_me->Roles, $reportRoles)) {
                throw new Fisma_Exception("User \"{$this->_me->account}\" does not have permission to view"
                                          . " the \"$reportName\" plug-in report.");
            }
        }
        
        // Execute the report script
        $reportScriptFile = Fisma::getPath('application') . "/config/reports/$reportName.sql";
        $reportScriptFileHandle = fopen($reportScriptFile, 'r');
        if (!$reportScriptFileHandle) {
            throw new Fisma_Exception("Unable to load plug-in report SQL file: $reportScriptFile");
        }
        $reportScript = '';
        while (!feof($reportScriptFileHandle)) {
            $reportScript .= fgets($reportScriptFileHandle);
        }
        $myOrganizations = array();
        foreach ($this->_me->getOrganizations() as $organization) {
            $myOrganizations[] = $organization->id;
        }
        $reportScript = str_replace('##ORGANIZATIONS##', implode(',', $myOrganizations), $reportScript);
        $dbh = Doctrine_Manager::connection()->getDbh(); 
        $rawResults = $dbh->query($reportScript, PDO::FETCH_ASSOC);
        $reportData = array();
        foreach ($rawResults as $rawResult) {
            $reportData[] = $rawResult;
        }
        
        // Render the report results
        if (isset($reportData[0])) {
            $columns = array_keys($reportData[0]);
        } else {
            $msg = "The report could not be created because the report query did not return any data.";
            $this->message($msg, self::M_WARNING);
            $this->_forward('plugin');
            return;
        }
        
        $this->view->assign('title', $reportConfig->title);
        $this->view->assign('columns', $columns);
        $this->view->assign('rows', $reportData);
        $this->view->assign('url', "/panel/report/sub/plugin-report/name/$reportName");
    }
}
