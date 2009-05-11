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
 * @author    woody
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Fisma
 *
 */
 
/**
 * Initialize the web application front only for installing
 * 
 * This requires that the directory layout be 
 * controllers, models, views, layouts, plugins
 * the error controller is different from others
 */
class Fisma_Controller_Plugin_Install extends Fisma_Controller_Plugin_Web
{
    //overload the parent initDb doing nothing here
    public function initDb() { }

    public function initPlugins()
    {
        // The installer has its own error handler which is registered here:
        $this->_front->registerPlugin(new Zend_Controller_Plugin_ErrorHandler(
            array( 'controller' => 'Install', 'action' => 'error')));
    }
}