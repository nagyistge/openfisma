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

require_once(realpath(dirname(__FILE__) . '/../../FismaUnitTest.php'));

/**
 * Tests for the system model
 * 
 * @author     Mark E. Haase
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Model
 * @version    $Id$
 */
class Test_Application_Models_System extends Test_FismaUnitTest
{
    /**
     * FIPS category is the high water mark of CIA
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testFipsCategoryIsHighWaterMarkOfCia()
    {
        $system = new System();
        
        $system->confidentiality = System::CIA_MODERATE;
        $system->integrity = System::CIA_LOW;
        $system->availability = System::CIA_HIGH;
        $this->assertEquals(System::CIA_HIGH, $system->fipsCategory);

        $system->availability = System::CIA_LOW;
        $this->assertEquals(System::CIA_MODERATE, $system->fipsCategory);

        $system->confidentiality = System::CIA_LOW;
        $this->assertEquals(System::CIA_LOW, $system->fipsCategory);
    }

    /**
     * If one, but not all, CIA values are set, then FIPS security category should still be defined
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testFipsCategoryDefinedIfAnyCiaDefined()
    {
        $system = new System();

        $system->confidentiality = System::CIA_LOW;
        $this->assertEquals(System::CIA_LOW, $system->fipsCategory);
    }
    
    /**
     * Confidentiality can be N/A but FIPS category is still defined
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testFipsCategoryWhenConfidentialityIsNa()
    {
        $system = new System();
        
        $system->confidentiality = System::CIA_NA;
        $system->integrity = System::CIA_LOW;
        $system->availability = System::CIA_MODERATE;
        $this->assertEquals(System::CIA_MODERATE, $system->fipsCategory);
    }
    
    /**
     * If all CIA values are null, than FIPS category is null
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testFipsCategoryIsNullWhenCiaIsNull()
    {
        $system = new System();
        
        $this->assertNull($system->fipsCategory);
    }
    
    /**
     * FIPS category cannot be set directly
     * 
     * @return void
     * @expectedException Fisma_Zend_Exception
     */
    public function testCannotSetFipsCategoryDirectly()
    {
        $system = new System();
        
        $system->fipsCategory = System::CIA_MODERATE;
    }
    
    /**
     * Exhibit 53 Unique Project Id has automatic formatting
     * 
     * @return void
     * @throws PHPUnit_Framework_ExpectationFailedException if assertion fails
     */
    public function testUpiFormatting()
    {
        $system = new System();
        
        // Should automatically hyphenate
        $system->uniqueProjectId = '0123456789ABCDEFG';
        $this->assertEquals('012-34-56-78-9A-BCDE-FG', $system->uniqueProjectId);
        
        // If the UPI is short, then it should pad out to fit the format
        $system->uniqueProjectId = '0123456789';
        $this->assertEquals('012-34-56-78-90-0000-00', $system->uniqueProjectId);
    }
}
