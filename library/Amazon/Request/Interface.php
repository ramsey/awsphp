<?php
/**
 * A library for interfacing with Amazon Web Services
 *
 * LICENSE
 *
 * This source file is subject to the Simplified BSD License that is bundled
 * with this package in the file LICENSE.
 * It is also available through the World Wide Web at this URL:
 * http://awsphp.googlecode.com/svn/trunk/LICENSE
 *
 * @category awsphp
 * @package Amazon
 * @subpackage Request
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @version $Id$
 */

/**
 * @category awsphp
 * @package Amazon
 * @subpackage Request
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @author Ben Ramsey <ramsey@php.net>
 */
interface Amazon_Request_Interface
{
    /**
     * Sends the request and returns a response
     *
     * @return Amazon_CloudFront_Response_Interface
     */
    public function send();

    /**
     * Sets the body of the request
     *
     * @param string $body Request body
     * @param string $contentType Content type of the request body
     */
    public function setBody($body, $contentType = null);

    /**
     * Sets a cookie parameter
     *
     * @param string $name Cookie parameter name
     * @param string $value Cookie parameter value
     */
    public function setCookie($name, $value = null);

    /**
     * Sets a request header
     *
     * @param string $name Header name or full header string
     * @param string $value Header value or null
     */
    public function setHeader($name, $value = null);

    /**
     * Set the request method
     *
     * @param string $method
     */
    public function setMethod($method);

    /**
     * Sets a POST parameter for a URL-encoded POST request body
     *
     * @param string $name POST parameter name
     * @param string $value POST parameter value
     */
    public function setPost($name, $value = null);

    /**
     * Sets a query string parameter
     *
     * @param string $name Query string parameter name
     * @param string $value Query string parameter value
     */
    public function setQuery($name, $value = null);

    /**
     * Set the URI for the request
     *
     * @param string $uri
     */
    public function setUri($uri);
}
