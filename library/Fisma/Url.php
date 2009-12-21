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
 * Url functions for OpenFISMA
 * 
 * @author     Ben Zheng <benzheng@users.sourceforge.net>
 * @copyright  (c) Endeavor Systems, Inc. 2009 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Fisma
 * @subpackage Fisma_Url
 * @version    $Id$
 */
class Fisma_Url
{
    /**
     * Return the base URL.
     * 
     * @return string baseUrl string
     */    
    static function baseUrl()
    {
        // Get the scheme http or https
        $scheme = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';

        // Get the http host
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else if (isset($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'])) {
            $name = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];

            if (($scheme == 'http' && $port == 80) || ($scheme == 'https' && $port == 443)) {
                $host = $name;
            } else {
                $host = $name . ':' . $port;
            }
        }

        $baseUrl = $scheme . '://' . $host;
        return $baseUrl;
    }

    /**
     * Return the current page URL.
     * 
     * @return string currentUrl string
     */
    static function currentUrl()
    {
        // Returns URI between the BaseUrl and QueryString.
        $uri = Zend_Controller_Front::getInstance()->getRequest()->getPathInfo();

        $currentUrl = self::baseUrl() . rtrim($uri, '/');
        return $currentUrl;
    }

    /**
     * Return the custom URL.
     * like http://site.com/test, the $requestUri is /test.
     * 
     * @param  string $requestUri  The relatively request path
     * @return string customUrl string
     */
    static function customUrl($requestUri)
    {
        // If the string of requestUri includes '/', './' or '../' will be cut off at the outset. 
        if (!empty($requestUri) && is_string($requestUri)) {
            $path = preg_replace('/^\.{0,2}\//', '', $requestUri);
        }

        $customUrl = self::baseUrl() . '/' . $path;
        return $customUrl;
    }
}
