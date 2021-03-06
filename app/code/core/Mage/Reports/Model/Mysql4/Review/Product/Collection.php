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
 * @package    Mage_Reports
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Report Products Review collection
 *
 * @category   Mage
 * @package    Mage_Reports
 */

class Mage_Reports_Model_Mysql4_Review_Product_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    public function joinReview()
    {
        $this->addAttributeToSelect('name');
        $this->getSelect()->join(
            array('r' => $this->getTable('review/review')),
            'e.entity_id=r.entity_pk_value',
            array(
                'review_cnt'    => 'COUNT(r.entity_pk_value)',
                'last_created'  => 'MAX(r.created_at)',
                'avg_rating'    => 'entity_id'
            )
        )->group('r.entity_pk_value');

        return $this;
    }
}