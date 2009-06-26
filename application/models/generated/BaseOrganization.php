<?php

/**
 * BaseOrganization
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property timestamp $createdTs
 * @property timestamp $modifiedTs
 * @property string $name
 * @property string $nickname
 * @property enum $orgType
 * @property integer $systemId
 * @property string $description
 * @property System $System
 * @property Doctrine_Collection $Users
 * @property Doctrine_Collection $Assets
 * @property Doctrine_Collection $Findings
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
abstract class BaseOrganization extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('organization');
        $this->hasColumn('createdTs', 'timestamp', null, array('type' => 'timestamp'));
        $this->hasColumn('modifiedTs', 'timestamp', null, array('type' => 'timestamp'));
        $this->hasColumn('name', 'string', null, array('type' => 'string', 'extra' => array('purify' => 'plaintext')));
        $this->hasColumn('nickname', 'string', null, array('type' => 'string', 'unique' => 'true;', 'extra' => array('purify' => 'plaintext')));
        $this->hasColumn('orgType', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'agency', 1 => 'bureau', 2 => 'organization', 3 => 'system'), 'length' => ''));
        $this->hasColumn('systemId', 'integer', null, array('type' => 'integer'));
        $this->hasColumn('description', 'string', null, array('type' => 'string', 'extra' => array('purify' => 'plaintext')));
    }

    public function setUp()
    {
        $this->hasOne('System', array('local' => 'systemId',
                                      'foreign' => 'id'));

        $this->hasMany('User as Users', array('refClass' => 'UserOrganization',
                                              'local' => 'organizationId',
                                              'foreign' => 'userId'));

        $this->hasMany('Asset as Assets', array('local' => 'id',
                                                'foreign' => 'orgSystemId'));

        $this->hasMany('Finding as Findings', array('local' => 'id',
                                                    'foreign' => 'responsibleOrganizationId'));

        $nestedset0 = new Doctrine_Template_NestedSet();
        $softdelete0 = new Doctrine_Template_SoftDelete();
        $timestampable0 = new Doctrine_Template_Timestampable(array('created' => array('name' => 'createdTs', 'type' => 'timestamp'), 'updated' => array('name' => 'modifiedTs', 'type' => 'timestamp')));
        $this->actAs($nestedset0);
        $this->actAs($softdelete0);
        $this->actAs($timestampable0);

    $this->addListener(new XssListener(), 'XssListener');
    }
}