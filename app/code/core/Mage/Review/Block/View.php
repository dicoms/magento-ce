<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    Mage_Review
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Review detailed view block
 *
 * @category   Mage
 * @package    Mage_Review
 */

class Mage_Review_Block_View extends Mage_Core_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('review/view.phtml');

        $this->setReviewId($this->getRequest()->getParam('id', false));
    }

    public function getProductData()
    {
        if( $this->getReviewId() && !$this->getProductCacheData() ) {
            $this->setProductCacheData(Mage::getModel('catalog/product')->load($this->getReviewData()->getEntityPkValue()));
        }
        return $this->getProductCacheData();
    }

    public function getReviewData()
    {
        if( $this->getReviewId() && !$this->getReviewCachedData() ) {
            $this->setReviewCachedData(Mage::getModel('review/review')->load($this->getReviewId()));
        }
        return $this->getReviewCachedData();
    }

    public function getBackUrl()
    {
        return Mage::getUrl('*/*/list', array('id' => $this->getProductData()->getId()));
    }

    public function getRating()
    {
        if( !$this->getRatingCollection() ) {
            $ratingCollection = Mage::getModel('rating/rating_option_vote')
                ->getResourceCollection()
                ->setReviewFilter($this->getReviewId())
                ->setStoreFilter(Mage::app()->getStore()->getId())
                ->addRatingInfo(Mage::app()->getStore()->getId())
                ->load();
            $this->setRatingCollection( ( $ratingCollection->getSize() ) ? $ratingCollection : false );
        }
        return $this->getRatingCollection();
    }

    public function getRatingSummary()
    {
        if( !$this->getRatingSummaryCache() ) {
            $this->setRatingSummaryCache(Mage::getModel('rating/rating')->getEntitySummary($this->getProductData()->getId()));
        }
        return $this->getRatingSummaryCache();
    }

    public function getTotalReviews()
    {
        if( !$this->getTotalReviewsCache() ) {
            $this->setTotalReviewsCache(Mage::getModel('review/review')->getTotalReviews($this->getProductData()->getId(), false, Mage::app()->getStore()->getId()));
        }
        return $this->getTotalReviewsCache();
    }

    public function dateFormat($date)
    {
        return $this->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_LONG);
    }
}