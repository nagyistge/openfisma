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
 * Dashboard for vulnerabilities
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Vulnerability
 */
class Vm_DashboardController extends Fisma_Zend_Controller_Action_Security
{
    /**
     * Restrict permissions
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $module = Doctrine::getTable('Module')->findOneByName('Vulnerability Management');

        if (!$module->enabled) {
            throw new Fisma_Zend_Exception('This module is not enabled.');
        }

        $this->_acl->requireArea('vulnerability');
    }

    public function indexAction()
    {
    }
}
