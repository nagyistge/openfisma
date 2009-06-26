<?php

/**
 * BaseProduct
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @property string $vendor
 * @property string $name
 * @property string $version
 * @property string $cpeName
 * @property Doctrine_Collection $Assets
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
abstract class BaseProduct extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setTableName('product');
        $this->hasColumn('vendor', 'string', 255, array('type' => 'string', 'extra' => array('purify' => 'plaintext'), 'comment' => 'The name of the vendor who brands this product', 'length' => '255'));
        $this->hasColumn('name', 'string', 255, array('type' => 'string', 'extra' => array('purify' => 'plaintext'), 'comment' => 'Name of the product', 'length' => '255'));
        $this->hasColumn('version', 'string', 255, array('type' => 'string', 'extra' => array('purify' => 'plaintext'), 'comment' => 'Version of the product', 'length' => '255'));
        $this->hasColumn('cpeName', 'string', 255, array('type' => 'string', 'extra' => array('purify' => 'plaintext'), 'comment' => 'The common platform enumeration (CPE) for this product, if known', 'length' => '255'));
    }

    public function setUp()
    {
        $this->hasMany('Asset as Assets', array('local' => 'id',
                                                'foreign' => 'productId'));

    $this->addListener(new XssListener(), 'XssListener');
    }
}