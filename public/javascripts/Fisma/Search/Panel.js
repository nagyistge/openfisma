/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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
 * 
 * @fileoverview Implements a UI to display collection of advanced search criteria
 * 
 * @author    Mark E. Haase <mhaase@endeavorsystems.com>
 * @copyright (c) Endeavor Systems, Inc. 2010 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/content/license
 */

/**
 * Constructor
 * 
 * @param advancedSearchOptions Contains searchable fields and pre-defined filters
 */
Fisma.Search.Panel = function (advancedSearchOptions) {
    var index;
    var searchableFields = advancedSearchOptions;

    if (0 === searchableFields.length) {
        throw "Field array cannot be empty";
    }
    
    // Sort fields by name
    searchableFields.sort(
        function (a, b) {
            if (a.label < b.label) {
                return -1;
            } else if (a.label > b.label) {
                return 1;
            } else {
                return 0;
            }
        }
    );

    // Copy all visible (non-hidden) fields into this panel
    this.searchableFields = [];
    
    for (index in searchableFields) {
        var searchableField = searchableFields[index];

        if (searchableField.hidden !== true) {
            this.searchableFields[this.searchableFields.length] = searchableField;
        }
    }

    // If default search criteria is included as a URL parameter, parse that out here.
    this.defaultQueryTokens = null;

    var urlParamString = document.location.search.substring(1); // strip the leading "?" character
    var urlParams = urlParamString.split('&');

    for (var i in urlParams) {
        var urlParam = urlParams[i];
        var keyValuePair = urlParam.split("=");

        // Looking for a parameter called "q"
        if ("q" == keyValuePair[0]) {
            var criteriaString = keyValuePair[1];
            this.defaultQueryTokens = criteriaString.split("/");

            // Remove first element if it's empty
            if (this.defaultQueryTokens[0] === '') {
                this.defaultQueryTokens.splice(0, 1);
            }

            break;
        }
    }
};

Fisma.Search.Panel.prototype = {
    
    /**
     * The parent container for this search panel
     */
    container : null,
    
    /**
     * A list of current selected criteria
     */
    criteria : [],
    
    /**
     * Render the advanced search box
     * 
     * @param container The HTML container to place the search box inside of
     */
    render : function (container) {
        this.container = container;
        var Dom = YAHOO.util.Dom;
        var Lang = YAHOO.lang;
        var QueryState = Fisma.Search.QueryState;
        var queryState = new QueryState(Dom.get("modelName").value);

        if (this.defaultQueryTokens) {
            var index = 0;
            
            // If a default query is specified, then switch to advanced mode and set up the UI for those criteria
            while (this.defaultQueryTokens.length > index) {
                var field = this.defaultQueryTokens[index];
                index++;
                
                var operator = this.defaultQueryTokens[index];
                index++;
                
                // Load up this criteria definition and see how many operands it takes
                var fieldDefinition = this.getFieldDefinition(field);
                
                var criterion = new Fisma.Search.Criteria(this, this.searchableFields);
                var criterionDefinition = criterion.getCriteriaDefinition(fieldDefinition);
                
                var numberOfOperands = this.getNumberOfOperands(fieldDefinition, operator, criterionDefinition);
                
                // Now we know how many operands there, push that number of tokens onto a stack
                var operands = [];
                
                for (; numberOfOperands > 0; numberOfOperands--) {
                    operands.push(this.defaultQueryTokens[index]);
                    index ++; 
                }
                
                // URI Decode the operands
                var unescapedOperands = $P.array_map(decodeURIComponent, operands);

                // Render the element and then set its default values
                var criterionElement = criterion.render(field, operator, unescapedOperands);
                
                this.container.appendChild(criterion.container);
                this.criteria.push(criterion);
            }

            // If only one criterion, disable its "minus" button
            if (1 == this.criteria.length) {
                this.criteria[0].setRemoveButtonEnabled(false);
            }

            // Display the advanced search UI and submit the initial query request XHR
            Fisma.Search.toggleAdvancedSearchPanel();
            Lang.later(null, null, function() { Fisma.Search.updateQueryState(queryState, Dom.get('searchForm')); });
        } else if (queryState.getSearchType() === QueryState.TYPE_ADVANCED) {
            var advancedQuery = queryState.getAdvancedQuery();

            for (var i in advancedQuery) {
                var advancedCriterion = new Fisma.Search.Criteria(this, this.searchableFields);
                this.criteria.push(advancedCriterion);
                this.container.appendChild(
                    advancedCriterion.render(
                        advancedQuery[i].field,
                        advancedQuery[i].operator,
                        advancedQuery[i].operands));
            }
            // Display the advanced search UI and submit the initial query request XHR
            Fisma.Search.toggleAdvancedSearchPanel();
        } else if (Fisma.Search.searchPreferences.type === 'advanced') {
            var fields = Fisma.Search.searchPreferences.fields;
            for (var i in fields) {
                var advancedCriterion = new Fisma.Search.Criteria(this, this.searchableFields);
                this.criteria.push(advancedCriterion);
                this.container.appendChild(
                    advancedCriterion.render(i, fields[i]));
            }
            // Display the advanced search UI and submit the initial query request XHR
            Fisma.Search.toggleAdvancedSearchPanel();
        } else {
            // If not default query is specified, then just show 1 default criterion
            var initialCriteria = new Fisma.Search.Criteria(this, this.searchableFields);
            this.criteria.push(initialCriteria);

            // Update DOM
            var criteriaElement = initialCriteria.render(this.searchableFields[0].name);
            initialCriteria.setRemoveButtonEnabled(false);
            this.container.appendChild(criteriaElement);
        }

        Fisma.Search.onSetTable(function () {
            var searchForm = document.getElementById('searchForm');
        
            // YUI renders the UI after this function returns, so a minimal delay is required to allow YUI to run
            setTimeout(function () {Fisma.Search.executeSearch(searchForm);}, 1);
        });
    },
  
    /**
     * Add a criteria row below the specified row
     * 
     * @param currentRow The HTML container for the row that the new row will be placed under
     */
    addCriteria : function (currentRow) {
        // Update internal state
        if (1 == this.criteria.length) {
            this.criteria[0].setRemoveButtonEnabled(true);
        }

        if (!this.searchableFields[this.criteria.length]) throw "No field defined for search";

        var criteria = new Fisma.Search.Criteria(this, this.searchableFields);
        this.criteria.push(criteria);
        
        // Update DOM
        var defaultFieldIndex = this.criteria.length - 1;

        var criteriaElement = criteria.render(this.searchableFields[defaultFieldIndex].name);

        this.container.insertBefore(criteriaElement, currentRow.nextSibling);
    },
    
    /**
     * Remove the specified criteria row
     * 
     * @param currentRow The HTML container for the row that needs to be removed
     */
    removeCriteria : function (currentRow) {
        // Update internal state
        for (var index in this.criteria) {
            var criterion = this.criteria[index];
            
            if (criterion.container == currentRow) {
                this.criteria.splice(index, 1);
                
                break;
            }
        }
        
        // Disable the remove button when there is only 1 criterion left
        if (1 == this.criteria.length) {
            this.criteria[0].setRemoveButtonEnabled(false);
        }

        // Update DOM
        this.container.removeChild(currentRow);
    },
    
    /**
     * Get the URL query string for the current filter status
     */
    getQuery : function () {
        var query = new Array();
        
        for (var index in this.criteria) {
            var criterion = this.criteria[index];
            if (criterion.hasBlankOperands()) {
                continue;
            }
            query.push(criterion.getQuery());
        }
        
        return query;
    },

    /**
     * Get the search panel's state
     */
    getPanelState: function () {
        var state = new Array();
        
        for (var index in this.criteria) {
            var criterion = this.criteria[index];
            state.push(criterion.getQuery());
        }
        
        return state;
    },
    
    /**
     * Returns search metadata for a field (specified by name)
     * 
     * @param fieldName
     */
    getFieldDefinition : function (fieldName) {
        for (var index in this.searchableFields) {
            if (this.searchableFields[index].name == fieldName) {
                return this.searchableFields[index];
            }
        }
        
        throw "No definition for field: " + fieldName;
    },
    
    /**
     * Returns the number of operands required for the specified field and operator
     * 
     * @param field Definition of the field
     * @param operator The operator applied to the field
     * @param criteriaDefinition
     */
    getNumberOfOperands : function (field, operator, criteriaDefinition) {
        var criterionQueryDefinition = criteriaDefinition[operator];

        if (!criterionQueryDefinition) {
            throw "No criteria defined for field (" + field.name + ") and operator (" + operator + ")";
        }
        
        var queryFunction = criterionQueryDefinition.query;
        
        switch (queryFunction) {
            case 'noInputs':
                return 0;
                break;
                
            // The following cases intentionally fall through
            case 'enumSelect':
            case 'oneInput':
                return 1;
                break;
                
            case 'twoInputs':
                return 2;
                break;
            
            default:
                throw "Number of operands not defined for query function: " + queryFunction;
                break;
        }
        
        throw "Number of operands not defined for query function: " + queryFunction;
    }
};
