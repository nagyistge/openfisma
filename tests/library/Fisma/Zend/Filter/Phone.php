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

require_once(realpath(dirname(__FILE__) . '/../../../../FismaUnitTest.php'));

/**
 * Tests for the Fisma_Zend_Filter_Phone class
 * 
 * @author     Andrew Reeves <andrew.reeves@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Fisma_Zend_Validate
 * @version    $Id$
 */
class Test_Library_Fisma_Zend_Validate_Phone extends Test_FismaUnitTest
{
    /**
     * Tests for the filter() method.
     */
    public function testFilter()
    {
        $filter = new Fisma_Zend_Filter_Phone();
        $testCases = array(
            '' => '',
            '321' => '(321) -',
            '3213214321' => '(321) 321-4321',
            '321-321-4321' => '(321) 321-4321',
            '321.321.4321' => '(321) 321-4321',
            '()3()2()1.3.2.1.1..234' => '(321) 321-1234'
        );
        foreach ($testCases as $input => $output) {
            $this->assertEquals($filter->filter($input), $output);
        }
    }
}
