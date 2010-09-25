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
 * OrganizationTable
 *
 * @uses Fisma_Doctrine_Table
 * @package Model
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class OrganizationTable extends Fisma_Doctrine_Table implements Fisma_Search_Searchable
{
    /**
     * Implement the interface for Searchable
     */
    public function getSearchableFields()
    {
        // The org type should show all values *except* system
        $orgTypeEnumValues = $this->getEnumValues('orgType');
        unset($orgTypeEnumValues[array_search('system', $orgTypeEnumValues)]);

        return array (
            'name' => array(
                'initiallyVisible' => true,
                'label' => 'Name',
                'sortable' => true,
                'type' => 'text'
            ),
            'nickname' => array(
                'initiallyVisible' => true,
                'label' => 'Nickname',
                'sortable' => true,
                'type' => 'text'
            ),
            'createdTs' => array(
                'initiallyVisible' => false,
                'label' => 'Creation Date',
                'sortable' => true,
                'type' => 'datetime'
            ),
            'modifiedTs' => array(
                'initiallyVisible' => false,
                'label' => 'Modification Date',
                'sortable' => true,
                'type' => 'datetime'
            ),
            'orgType' => array(
                'enumValues' => $orgTypeEnumValues,
                'initiallyVisible' => true,
                'label' => 'Type',
                'sortable' => true,
                'type' => 'enum'
            ),
            'description' => array(
                'initiallyVisible' => true,
                'label' => 'Description',
                'sortable' => false,
                'type' => 'text'
            ),
            'id' => array(
                'hidden' => true,
                'type' => 'integer'
            )
        );
    }

    /**
     * Return a list of fields which are used for access control
     * 
     * @return array
     */
    public function getAclFields()
    {
        return array('id' => 'OrganizationTable::getOrganizationIds');
    }

    /**
     * Provide ID list for ACL filter
     * 
     * @return array
     */
    static function getOrganizationIds()
    {
        $currentUser = CurrentUser::getInstance();
        
        $organizationIds = $currentUser->getOrganizationsByPrivilege('organization', 'read')
                                       ->toKeyValueArray('id', 'id');

        return $organizationIds;
    }

    /**
     * A callback for lucene searches that involve searching an organization subtree
     *
     * Known implementers: FindingTable
     *
     * @param string $parentOrganization The nickname of the root node of the subtree to return
     * @return array Lucene query terms (key is name of field, value is query expression)
     */
    static function getOrganizationSubtreeLuceneQuery($parentOrganization)
    {
        $organization = Doctrine::getTable('Organization')->findOneByNickname($parentOrganization);

        $searchTerms = array();

        if ($organization !== false) {
            $searchTerms = array(
                'lft'    => "[$organization->lft TO *]",
                'rgt'    => "[* TO $organization->rgt]"
            );
        }

        return $searchTerms;
    }
}
