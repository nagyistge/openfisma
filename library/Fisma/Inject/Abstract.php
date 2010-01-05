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
 * {@link http://www.gnu.org/licenses/}.
 */

/**
 * An abstract class for creating injection plug-ins
 * 
 * @author     Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Inject
 * @version    $Id$
 */
abstract class Fisma_Inject_Abstract
{
    /**
     * The full xml file path to be used to the injection plugin
     * 
     * @var string
     */
    protected $_file;
    
    /**
     * The network id to be used for injection
     * 
     * @var string
     */
    protected $_networkId;
    
    /**
     * The organization id to be used for injection
     * 
     * @var string
     */
    protected $_orgSystemId;
    
    /**
     * The finding source id to be used for injection
     * 
     * @var string
     */
    protected $_findingSourceId;
    
    /**
     * The asset id to be used for injection
     * 
     * @var string
     */
    protected $_assetId;
    
    /**
     * insert finding ids
     * 
     * @var array
     */
    private $_findingIds = array();
    
    /**
     * The summary counts array
     * 
     * @var array
     */
    private $_totalFindings = array('created' => 0,
                                    'deleted' => 0,
                                    'reviewed' => 0);
    
    /**
     * The constant defines the possible specific finding action the finding should be created and set to NEW status.
     */
    const CREATE_FINDING = 1;
    
    /**
     * The constant defines the possible specific finding action the finding should be deleted.
     */
    const DELETE_FINDING = 2;
    
    /**
     * The constant defines the possible specific finding action the finding should be created and set to PEND status.
     */
    const REVIEW_FINDING = 3;
    
    /**
     * Create and initialize a new plug-in instance for the specified file
     * 
     * @param string $file The specified xml file path
     * @param string $networkId The specified network id
     * @param string $systemId The specified organization id
     * @param string $findingSourceId The specified finding source id
     * @return void
     */
    public function __construct($file, $networkId, $systemId, $findingSourceId) 
    {
        $this->_file            = $file;
        $this->_networkId       = $networkId;
        $this->_orgSystemId     = $systemId;
        $this->_findingSourceId = $findingSourceId;
    }

    /**
     * Conditionally commit the specified finding data
     *
     * The finding is evaluated with respect to the Injection Filtering rules. The finding may be committed or it may be
     * deleted based on the filter rules.
     * 
     * Subclasses should call this function to commit findings rather than committing new findings directly.
     *
     * @param array $findingData Column data for the new finding object
     * @return void
     */
    protected function _commit($findingData) 
    {
        $finding = new Finding();
        $finding->merge($findingData);
        
        // Handle duplicated findings
        $duplicateFinding = $this->_getDuplicateFinding($finding);
        $action = ($duplicateFinding) ? $this->_getDuplicateAction($finding, $duplicateFinding) : self::CREATE_FINDING;
        
        // Take the specified action on the current finding
        switch ($action) {
            case self::CREATE_FINDING:
                $this->_totalFindings['created']++;
                $finding->save();
                break;
            case self::DELETE_FINDING:
                $this->_totalFindings['deleted']++;
                // Deleted findings are not saved
                break;
            case self::REVIEW_FINDING:
                $this->_totalFindings['reviewed']++;
                $finding->status = 'PEND';
                $finding->save();
                break;
        }
        
    }

    /**
     * Get a duplicate of the specified finding
     * 
     * @param $finding A finding to check for duplicates
     * @return bool|Finding Return a duplicate finding or FALSE if none exists
     */
    private function _getDuplicateFinding($finding)
    {
        $duplicateFindings = Doctrine::getTable('Finding')->findByDql('description LIKE ?', $finding->description);
        
        return ($duplicateFindings) ? $duplicateFindings->getLast() : FALSE;
    }
    
    /**
     * Evaluate duplication rules for two findings
     * 
     * @param Finding $newFinding
     * @param Finding $duplicateFinding
     * @return int One of the constants: CREATE_FINDING, DELETE_FINDING, or REVIEW_FINDING
     */
    private function _getDuplicateAction(Finding $newFinding, Finding $duplicateFinding)
    {
        $action  = NULL;
        $orgSame = ($newFinding->ResponsibleOrganization == $duplicateFinding->ResponsibleOrganization) ? TRUE : FALSE;
        
        switch ($duplicateFinding->type) {
            case 'CAP':
            case 'FP':
            case 'NONE':
                if ($orgSame) {
                    $action = ($duplicateFinding->status == 'CLOSED') ? self::CREATE_FINDING : self::DELETE_FINDING;
                } else {
                    $action = self::REVIEW_FINDING;
                }
                break;
            case 'AR':
                $action = ($orgSame) ? self::DELETE_FINDING : self::REVIEW_FINDING;
                break;
            default:
                throw new Fisma_Exception('No duplicate finding action defined for mitigation type: '
                    . $duplicateFinding->type);
        }

        return $action;
    }
    
    /**
     * The get handler method is overridden in order to provide read-only access to the summary counts for
     * this plug-in.
     *
     * Example: echo "Created {$plugin->created} findings";
     * 
     * @param string $field The specified summary counts key
     * @return int|null The summary count value of the specified key
     */
    public function __get($field) 
    {
        return (!empty($this->_totalFindings[$field])) ? $this->_totalFindings[$field] : NULL;
    }

    /** 
     * Parse all the data from the specified file, and load it into the database.
     *
     * Throws an exception if the file is an invalid format.
     *
     * @param string $uploadId The primary key for the upload object associated with this file
     * @return void
     * @throws Fisma_Exception_InvalidFileFormat if the file is an invalid format
     */
    abstract public function parse($uploadId);

    /**
     * Save or get the asset id which is associated with the address ip and port
     * If there has the same ip, port and network, get the exist asset id, else create a new one
     *
     * @param array $assetData The asset data to save
     * @return void
     */
    protected function _saveAsset($assetData)
    {
        $assetData['networkId']   = $this->_networkId;
        $assetData['orgSystemId'] = $this->_orgSystemId;
        $assetData['source']      = 'SCAN';

        // Verify whether asset exists or not
        $assetRecord  = Doctrine_Query::create()
                         ->select()
                         ->from('Asset a')
                         ->where('a.networkId = ?', $assetData['networkId'])
                         ->andWhere('a.addressIp = ?', $assetData['addressIp'])
                         ->andWhere('a.addressPort = ?', $assetData['addressPort'])
                         ->execute()
                         ->toArray();

        if ($assetRecord) {
            $this->_assetId = $assetRecord[0]['id'];
        } else {
            $asset = new Asset();

            $asset->merge($assetData);
            $asset->save();

            $this->_assetId = $asset->id;
        }
    }
    
    /**
     * Save product and update asset's product
     *
     * @param array $productData The product data to save
     * @return void
     */
    protected function _saveProduct($productData)
    {
        $product = new Product();
        $asset   = new Asset();
        $asset   = $asset->getTable()->find($this->_assetId);

        if (empty($asset->productId)) {
            $existingProduct = $product->getTable()->findOneByCpeName($productData['cpeName']);

            if ($existingProduct) {
                // Use the existing product if one is found
                $asset->productId = $existingProduct->id;
            } else {
                // If no existing product, create a new one
                $product->merge($productData);
                $product->save();
                $asset->productId = $product->id;
            }

            $asset->getTable()->getRecordListener()->setOption('disabled', true);
            $asset->save();
        } else {
            // If the asset does have a product, then do not modify it unless the CPE name is null,
            // in which case update the CPE name.
            $product = $product->getTable()->find($asset->productId);

            if ($product && empty($product->cpeName)) {
                $product->cpeName = $productData['cpeName'];
                $product->save();
            }
        }
    }
}
