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
 * Add incident actor table
 * 
 * This file contains generated code... skip standards check.
 * @codingStandardsIgnoreFile
 * 
 * @package Migration
 * @version $Id: 1271182224_version37.php 3206 2010-04-13 23:48:46Z jboyd $
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Mark E. Haase <mhaase@endeavorsystems.com> 
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Version44 extends Doctrine_Migration_Base
{
    public function up()
    {
		$this->createTable('ir_incident_actor', array(
             'incidentid' => 
             array(
              'type' => 'integer',
              'primary' => true,
              'length' => 8,
             ),
             'userid' => 
             array(
              'type' => 'integer',
              'primary' => true,
              'length' => 8,
             ),
             ), array(
             'indexes' => 
             array(
             ),
             'primary' => 
             array(
              0 => 'incidentid',
              1 => 'userid',
             ),
             ));
    }

    public function down()
    {
		$this->dropTable('ir_incident_actor');
    }
}