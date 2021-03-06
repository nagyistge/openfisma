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
 * A YUI AutoComplete box 
 * 
 * @author     Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Yui
 * @version    $Id$
 */
class Fisma_Yui_Form_AutoComplete extends Zend_Form_Element
{
    /**
     * The text displayed in the autocomplete field by default.
     * 
     * @var string
     */
    private $_displayText;

    /**
     * When this element is expressed as a string, it renders itself as a convenience. This allows the element to
     * be used as a parameter to echo, print, or string interpolation expressions.
     * 
     * @return string The string presentation of the YUI Autocomplete box element
     */
    function __toString() 
    {
        return $this->renderSelf();
    }

    /**
     * Set the text displayed in the autocomplete field by default
     * 
     * Notice that the "value" of this element is stored in a hidden field and is distinct from the displayText
     * 
     * @param string $displayText
     * @return Fluent interface
     */
    public function setDisplayText($displayText)
    {
        $this->_displayText = $displayText;

        return $this;
    }

    /**
     * A default implementation of render() that creates an autocomplete text field 
     * 
     * @return string The HTML snippet of the YUI Autocomplete box rendered
     */
    function renderSelf() 
    {
        $disabled = "";

        if ($this->readOnly) {
            $disabled = "disabled=\"true\"";
        }

        $name = $this->getName();

        $hiddenField = $this->getAttrib('hiddenField');
        $hiddenValue = $this->getValue();
        $hiddenValueAttrib = empty($hiddenValue) ? '' : "value=\"$hiddenValue\"";

        $displayedValueAttrib = empty($this->_displayText) ? '' : "value=\"$this->_displayText\"";
                
        $render  = "<div>
                    <input type='hidden' 
                           id='{$this->getAttrib('hiddenField')}' 
                           name='{$this->getAttrib('hiddenField')}'
                           $hiddenValueAttrib>
                    <input type=\"text\" 
                           name=\"$name\" 
                           id=\"$name\" 
                           $displayedValueAttrib
                           $disabled>
                    <img class='spinner'
                         id='{$this->getAttrib('containerId')}Spinner' 
                         src='/images/spinners/small.gif'>
                    <div id=\"{$this->getAttrib('containerId')}\"></div>                    
                    </div>
                    <script type='text/javascript'>
                        YAHOO.util.Event.onDOMReady(Fisma.AutoComplete.init,
                          { schema: [\"{$this->getAttrib('resultsList')}\", \"{$this->getAttrib('fields')}\"],
                            xhr : \"{$this->getAttrib('xhr')}\",
                            fieldId : \"$name\",
                            containerId: \"{$this->getAttrib('containerId')}\",
                            hiddenFieldId: \"$hiddenField\",
                            queryPrepend: \"{$this->getAttrib('queryPrepend')}\",
                            callback : '{$this->getAttrib('callback')}'
                          } );
                    </script>";

        return $render;
    } 
}
