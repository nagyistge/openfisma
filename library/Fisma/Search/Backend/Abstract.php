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
 * Abstract base class for search engine backends
 * 
 * @author     Mark E. Haase <mhaase@endeavorystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Search
 */
abstract class Fisma_Search_Backend_Abstract
{
    /**
     * Delete all indexed documents of the specified type
     * 
     * @param string $type E.g. Finding, Asset, etc.
     */
    //abstract public function deleteAll($type);

    /**
     * Delete all indexed documents of the specified type
     */
    //abstract public function deleteOne($type, $id);

    /**
     * Validate the backend's configuration
     * 
     * The implementing class should use this to exercise basic diagnostics
     * 
     * @return mixed Return TRUE if configuration is valid, or a string error message otherwise
     */
    abstract public function validateConfiguration();
}
