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
 * @author    Ben Zheng
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 * @package   Migration
 */

/**
 * Add a configuration to control Smtp supports TLS.
 * 
 * @package    Migration
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class AddUseSmtpTls extends Doctrine_Migration_Base
{
    /**
     * Insert new configuration
     */
    public function up()
    {
        $query = Doctrine_Query::create()
                    ->from('Configuration c')
                    ->where('c.name = ?', 'smtp_tls'); 
        $config = $query->fetchOne();

        if (empty($config)) {
            $config = new Configuration();
            $config->name        = 'smtp_tls';
            $config->value       = 0;
            $config->description = 'Use Transport Layer Security (TLS)';
            $config->save();
        }
    }

    /**
     * Remove new configuration
     */
    public function down()
    {
        $query = Doctrine_Query::create()
                    ->from('Configuration c')
                    ->where('c.name = ?', 'smtp_tls'); 
        $config = $query->fetchOne();
        if ($config) {
            $config->delete();
        }
    }
}
