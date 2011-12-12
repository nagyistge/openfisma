<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
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

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * Tests for the abstract migration class.
 *
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_SUBPACKAGE
 */
class Test_Library_Fisma_Migration_Abstract extends Test_Case_Unit
{
    /**
     * Test get version.
     */
    public function testGetVersion()
    {
        $testClassName = Fisma_Migration_Abstract::CLASS_NAME_PREFIX . '021700_TestGetVersion';
        $migration = $this->getMockForAbstractClass('Fisma_Migration_Abstract', array(), $testClassName);

        $this->assertEquals("021700", $migration->getVersion()->getPaddedString());
    }

    /**
     * Test the migration name.
     */
    public function testGetName()
    {
        $testClassName = Fisma_Migration_Abstract::CLASS_NAME_PREFIX . '021700_TestGetName';
        $migration = $this->getMockForAbstractClass('Fisma_Migration_Abstract', array(), $testClassName);

        $this->assertEquals("TestGetName", $migration->getName());
    }

    /**
     * Test db mutator/accessor
     */
    public function testGetSetDb()
    {
        $db = $this->getMock('Mock_Pdo');
        $migration = $this->getMockForAbstractClass('Fisma_Migration_Abstract');
        $migration->setDb($db);

        $this->assertEquals($migration->getDb(), $db);
    }
}
