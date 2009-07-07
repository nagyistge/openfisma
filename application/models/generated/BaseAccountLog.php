<?php

/**
 * BaseAccountLog
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property timestamp $createdTs
 * @property integer $userId
 * @property string $ip
 * @property enum $event
 * @property string $message
 * @property User $User
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
abstract class BaseAccountLog extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('account_log');
        $this->hasColumn('createdTs', 'timestamp', null, array('type' => 'timestamp', 'comment' => 'The time at which this event occurred'));
        $this->hasColumn('userId', 'integer', null, array('type' => 'integer', 'comment' => 'The user who caused this event, if applicable'));
        $this->hasColumn('ip', 'string', 15, array('type' => 'string', 'notnull' => true, 'comment' => 'The IP address where this event originated from', 'length' => '15'));
        $this->hasColumn('event', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'create user', 1 => 'modify user', 2 => 'delete user', 3 => 'lock user', 4 => 'unlock user', 5 => 'login failure', 6 => 'login', 7 => 'logout', 8 => 'accept rob', 9 => 'change password', 10 => 'validate email'), 'notnull' => true, 'comment' => 'The account log event type'));
        $this->hasColumn('message', 'string', null, array('type' => 'string', 'comment' => 'A description of the event', 'extra' => array('purify' => 'plaintext')));
    }

    public function setUp()
    {
        $this->hasOne('User', array('local' => 'userId',
                                    'foreign' => 'id'));

        $timestampable0 = new Doctrine_Template_Timestampable(array('created' => array('name' => 'createdTs', 'type' => 'timestamp'), 'updated' => array('disabled' => true)));
        $this->actAs($timestampable0);

    $this->addListener(new XssListener(), 'XssListener');
    }
}