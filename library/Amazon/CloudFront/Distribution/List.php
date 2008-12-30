<?php
/**
 * A library for interfacing with Amazon Web Services
 *
 * LICENSE
 *
 * This source file is subject to the Simplified BSD License that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://awsphp.googlecode.com/svn/trunk/LICENSE
 *
 * @package Amazon
 * @subpackage CloudFront
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @version $Id$
 */

/**
 * @see Amazon_CloudFront_Distribution
 */
require_once 'Amazon/CloudFront/Distribution.php';

/**
 * @package Amazon
 * @subpackage CloudFront
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @author Ben Ramsey <ramsey@php.net>
 */
class Amazon_CloudFront_Distribution_List extends ArrayObject
{
    /**
     * A flag indicating whether more distributions remain
     * @var bool
     */
    protected $_isTruncated;

    /**
     * The value provided for the Marker request parameter
     * @var string
     */
    protected $_marker;

    /**
     * The value provided for the MaxItems request parameter.
     * @var string
     */
    protected $_maxItems;

    /**
     * If $_isTruncated is true, this variable contains the value to use for the
     * Marker request parameter to continue listing distributions
     * @var string
     */
    protected $_nextMarker;

    /**
     * Creates an instance of an Amazon CloudFront DistributionList
     *
     * @param SimpleXMLElement $sxe The DistributionList XML document
     * @return void
     */
    public function __construct(SimpleXMLElement $sxe)
    {
        $this->_isTruncated = ((string) $sxe->IsTruncated == 'true' ? true : false);
        $this->_marker = (string) $sxe->Marker;
        $this->_maxItems = (string) $sxe->MaxItems;
        $this->_nextMarker = (string) $sxe->NextMarker;

        // Get distribution summaries to add to this ArrayObject
        $distributions = array();
        foreach ($sxe->DistributionSummary as $distribution) {
            $distributions[] = new Amazon_CloudFront_Distribution($distribution);
        }

        parent::__construct($distributions);
    }

    /**
     * Returns the value provided for the Marker request parameter
     *
     * @return string
     */
    public function getMarker()
    {
        return $this->_marker;
    }

    /**
     * Returns the value provided for the MaxItems request parameter
     *
     * @return string
     */
    public function getMaxItems()
    {
        return $this->_maxItems;
    }

    /**
     * Returns the Marker value to use to continue listing distributions
     *
     * @return string
     */
    public function getNextMarker()
    {
        return $this->_nextMarker;
    }

    /**
     * Returns true if more distributions remain to be listed
     *
     * @return bool
     */
    public function isTruncated()
    {
        return $this->_isTruncated;
    }
}
