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
 * @subpackage Response
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @version $Id$
 */

/**
 * @category awsphp
 * @package Amazon
 * @subpackage Response
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @author Ben Ramsey <ramsey@php.net>
 */
interface Amazon_Response_Interface
{
    /**
     * Return the response body as a string
     *
     * @return string
     */
    public function getBody();

    /**
     * Returns a specific header as string, or null if it is not set
     *
     * @param string $header
     * @return string|null
     */
    public function getHeader($header);

    /**
     * Returns the response headers
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Returns the HTTP response status code
     *
     * @return int
     */
    public function getStatus();
}
