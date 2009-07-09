<?php

/**
 * BaseSystem
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property enum $type
 * @property enum $confidentiality
 * @property string $confidentialityDescription
 * @property enum $integrity
 * @property string $integrityDescription
 * @property enum $availability
 * @property string $availabilityDescription
 * @property enum $fipsCategory
 * @property enum $controlledBy
 * @property date $securityAuthorizationDt
 * @property date $contingencyPlanTestDt
 * @property date $controlAssessmentDt
 * @property enum $hasFiif
 * @property enum $hasPii
 * @property enum $piaRequired
 * @property string $piaUrl
 * @property enum $sornRequired
 * @property string $sornUrl
 * @property string $uniqueProjectId
 * @property Doctrine_Collection $Documents
 * @property Doctrine_Collection $Organization
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
abstract class BaseSystem extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('system');
        $this->hasColumn('type', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'gss', 1 => 'major', 2 => 'minor'), 'comment' => 'General Support System, Major Application, or Minor Application'));
        $this->hasColumn('confidentiality', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'na', 1 => 'LOW', 2 => 'MODERATE', 3 => 'HIGH'), 'comment' => 'The FIPS-199 confidentiality impact'));
        $this->hasColumn('confidentialityDescription', 'string', null, array('type' => 'string'));
        $this->hasColumn('integrity', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'LOW', 1 => 'MODERATE', 2 => 'HIGH'), 'comment' => 'The FIPS-199 integrity impact'));
        $this->hasColumn('integrityDescription', 'string', null, array('type' => 'string'));
        $this->hasColumn('availability', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'LOW', 1 => 'MODERATE', 2 => 'HIGH'), 'comment' => 'The FIPS-199 availability impact'));
        $this->hasColumn('availabilityDescription', 'string', null, array('type' => 'string'));
        $this->hasColumn('fipsCategory', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'LOW', 1 => 'MODERATE', 2 => 'HIGH'), 'comment' => 'The FIPS-199 security categorization. Automatically updated by OpenFISMA based on the CIA above'));
        $this->hasColumn('controlledBy', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'AGENCY', 1 => 'CONTRACTOR'), 'comment' => 'Is this system principally controlled by the agency or by a contractor?'));
        $this->hasColumn('securityAuthorizationDt', 'date', null, array('type' => 'date', 'comment' => 'The last date on which this system underwent a security authorization (formerly known as C&A)'));
        $this->hasColumn('contingencyPlanTestDt', 'date', null, array('type' => 'date', 'comment' => 'The last date on which the contingency plan for this system was tested'));
        $this->hasColumn('controlAssessmentDt', 'date', null, array('type' => 'date', 'comment' => 'The last time the security controls were tested for this system'));
        $this->hasColumn('hasFiif', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'YES', 1 => 'NO'), 'comment' => 'Whether the system contains any Federal Information in Identifiable Form'));
        $this->hasColumn('hasPii', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'YES', 1 => 'NO'), 'comment' => 'Whether the system contains any Personally Identifiable Information'));
        $this->hasColumn('piaRequired', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'YES', 1 => 'NO'), 'comment' => 'Whether this system requires a Privacy Impact Analysis'));
        $this->hasColumn('piaUrl', 'string', null, array('type' => 'string', 'extra' => array('purify' => 'plaintext'), 'comment' => 'A URL pointing to the Privacy Impact Analysis'));
        $this->hasColumn('sornRequired', 'enum', null, array('type' => 'enum', 'values' => array(0 => 'YES', 1 => 'NO'), 'comment' => 'Whether a System Of Record Notice is required'));
        $this->hasColumn('sornUrl', 'string', null, array('type' => 'string', 'extra' => array('purify' => 'plaintext'), 'comment' => 'A URL pointing to the System Of Record Notice'));
        $this->hasColumn('uniqueProjectId', 'string', null, array('type' => 'string', 'extra' => array('purify' => 'plaintext'), 'comment' => 'The Unique Project Identifier (UPI) correlates information systems to their corresponding fiscal budget items. The UPI always has the folLOWing format: "xxx-xx-xx-xx-xx-xxxx-xx"'));
    }

    public function setUp()
    {
        $this->hasMany('SystemDocument as Documents', array('local' => 'id',
                                                            'foreign' => 'systemId'));

        $this->hasMany('Organization', array('local' => 'id',
                                             'foreign' => 'systemId'));

    $this->addListener(new XssListener(), 'XssListener');
    $this->addListener(new BaseListener(), 'BaseListener');
    $this->addListener(new SystemListener(), 'SystemListener');
    }
}