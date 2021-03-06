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
 * Fisma_Symfony_Components_ServiceContainerLoaderAnnotations 
 * 
 * @uses sfServiceContainerLoader
 * @package Fisma_Symfony_Components 
 * @version $Id$
 * @copyright (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @author Josh Boyd <joshua.boyd@endeavorsystems.com> 
 * @author Loïc Frering <loic.frering@gmail.com>
 * @license http://www.openfisma.org/content/license GPLv3
 */
class Fisma_Symfony_Components_ServiceContainerLoaderAnnotations extends sfServiceContainerLoader
{
    /**
     * _definitions 
     * 
     * @var array
     */
    protected $_definitions = array();

    /**
     * _annotations 
     * 
     * @var array
     */
    protected $_annotations = array();

    /**
     * __construct 
     * 
     * @param sfServiceContainerBuilder $container 
     * @return void
     */
    public function  __construct(sfServiceContainerBuilder $container = null)
    {
        $this->_annotations = array(
            new Fisma_Symfony_Components_ServiceContainerLoaderAnnotationInject(),
            new Fisma_Symfony_Components_ServiceContainerLoaderAnnotationValue()
        );
        parent::__construct($container);
    }
    
    /**
     * doLoad 
     * 
     * @param mixed $path 
     * @return array 
     */
    public function doLoad($path)
    {
        try {
            $directoryIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($directoryIterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $suffix = strtolower(pathinfo($fileInfo->getPathname(), PATHINFO_EXTENSION));
                    if ($suffix == 'php') {
                        $this->_reflect($fileInfo->getPathname());
                    }
                }
            }
        }
        catch(UnexpectedValueException $e) {
            
        }

        return array($this->_definitions, array());
    }

    /**
     * _reflect 
     * 
     * @param mixed $file 
     * @return void
     */
    protected function _reflect($file)
    {
        require_once $file;
        $r = new Zend_Reflection_File($file);
        try {
            $r = $r->getClass();
            if ($r->getDocblock()->hasTag('Service')) {
                $serviceName = $this->_reflectServiceName($r);
                $definition = $this->_reflectDefinition($r);
                $this->_definitions[$serviceName] = $definition;
            }
        }
        catch(Zend_Reflection_Exception $e) {
        }
        catch(ReflectionException $e) {
        }
    }

    /**
     * _reflectDefinition 
     * 
     * @param Zend_Reflection_Class $r 
     * @return sfServiceDefinition 
     */
    protected function _reflectDefinition(Zend_Reflection_Class $r)
    {
        $definition = new sfServiceDefinition($r->getName());

        $this->_reflectConstructor($r, $definition);
        $this->_reflectProperties($r, $definition);
        $this->_reflectMethods($r, $definition);

        return $definition;
    }

    /**
     * _reflectConstructor 
     * 
     * @param Zend_Reflection_Class $r 
     * @param sfServiceDefinition $definition 
     * @return void
     */
    protected function _reflectConstructor(Zend_Reflection_Class $r, sfServiceDefinition $definition)
    {
        try {
            $constructor = $r->getMethod('__construct');
            if (null !== $constructor) {
                foreach ($this->_annotations as $annotation) {
                    if ($constructor->getDocblock()->hasTag($annotation->getName())) {
                        $annotation->reflectConstructor($constructor, $definition);
                    }
                }
            }
        }
        catch(Zend_Reflection_Exception $e) {
        }
        catch(ReflectionException $e) {
        }
    }

    /**
     * _reflectProperties 
     * 
     * @param Zend_Reflection_Class $r 
     * @param sfServiceDefinition $definition 
     * @return void
     */
    protected function _reflectProperties(Zend_Reflection_Class $r, sfServiceDefinition $definition)
    {
        $properties = $r->getProperties();
        foreach ($properties as $property) {
            if ($property->getDocComment() && $property->getDeclaringClass()->getName() == $r->getName()) {
                $docblock = $property->getDocComment();
                foreach ($this->_annotations as $annotation) {
                    if ($docblock->hasTag($annotation->getName())) {
                        $annotation->reflectProperty($property, $definition);
                    }
                }
            }
        }
    }

    /**
     * _reflectMethods 
     * 
     * @param Zend_Reflection_Class $r 
     * @param sfServiceDefinition $definition 
     * @return void
     */
    protected function _reflectMethods(Zend_Reflection_Class $r, sfServiceDefinition $definition)
    {
        $methods = $r->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() == $r->getName() && strpos($method->getName(), 'set') === 0) {
                try {
                    foreach ($this->_annotations as $annotation) {
                        if ($method->getDocblock()->hasTag($annotation->getName())) {
                            $annotation->reflectMethod($method, $definition);
                        }
                    }
                }
                catch(Zend_Reflection_Exception $e) {
                }
            }
        }
    }

    /**
     * _reflectServiceName 
     * 
     * @param Zend_Reflection_Class $r 
     * @return string 
     */
    protected function _reflectServiceName(Zend_Reflection_Class $r)
    {
        $className = $r->getName();
        $serviceTagDescription = trim($r->getDocblock()->getTag('Service')->getDescription());
        if (!empty($serviceTagDescription)) {
            return $serviceTagDescription;
        } elseif (false !== ($pos = strrpos($className, '_'))) {
            return Fisma_String::lcfirst(substr($className, $pos + 1));
        }
        return Fisma_String::lcfirst($className);
    }
}
