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
 * <http://www.gnu.org/licenses/>.
 *
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id: index.php 1793 2009-06-19 17:49:33Z mehaase $
 */
 
/**
 * A listener for the User model
 *
 * @package   Listener
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/license.php
 */
class UserListener extends Doctrine_Record_Listener
{
    public function preSave(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        
        $modified = $user->getModified();
        if (is_null($modified)) {
            return;
        }

        if ($user->id == User::currentUser()->id) {
            if ($modified['email'] || $modified['notifyEmail']) {
                $user->emailValidate = false;
                $emailValidation  = new EmailValidation();
                $emailValidation->email          = !empty($email) ? $modified['email'] : $modified['notifyEmail'];
                $emailValidation->validationCode = md5(rand());
                $emailValidation->User           = $user;
                $user->EmailValidation[]         = $emailValidation;
            }
        }

        if (isset($modified['password'])) {
            $user->generateSalt();
            $user->password        = $user->hash($modified['password']);
            $user->passwordTs      = Fisma::now();
            // Generate user's password history
            $pwdHistory = $user->passwordHistory;
            if (3 == substr_count($pwdHistory, ':')) {
                $pwdHistory = substr($pwdHistory, 0, -strlen(strrchr($pwdHistory, ':')));
            }
            $user->passwordHistory = ':' . $user->password . $pwdHistory;

            $user->log("Password changed");
        }
        
        if (isset($modified['lastRob'])) {
            $user->log("Accepted Rules of Behavior");
        }
    }

    public function preInsert(Doctrine_Event $event) {
        $user = $event->getInvoker();
        
        $user->passwordTs = Fisma::now();
        $user->hashType = Configuration::getConfig('hash_type');
    }
    
    public function postInsert(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        Notification::notify(Notification::ACCOUNT_CREATED, $user, User::currentUser());
        Fisma_Lucene::updateIndex('account', $user);
    }

    public function postUpdate(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        Notification::notify(Notification::ACCOUNT_MODIFIED, $user, User::currentUser());
        Fisma_Lucene::updateIndex('account', $user);
    }

    public function postDelete(Doctrine_Event $event)
    {
        $user = $event->getInvoker();
        Notification::notify(Notification::ACCOUNT_DELETED, $user, User::currentUser());
        Fisma_Lucene::deleteIndex('account', $user->id);
    }    

}
