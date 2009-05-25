<?php

/**
 * BaseAccountLog
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property timestamp $createdTs
 * @property integer $userId
 * @property string $ip
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
        $this->hasColumn('message', 'string', null, array('type' => 'string', 'comment' => 'A description of the event'));
    }

    public function setUp()
    {
        $this->hasOne('User', array('local' => 'userId',
                                    'foreign' => 'id'));
    }
}