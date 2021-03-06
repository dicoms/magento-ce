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
 * @package    Mage_CatalogIndex
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Price index model
 *
 */
class Mage_CatalogIndex_Model_Price extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('catalogindex/price');
        $this->_getResource()->setStoreId(Mage::app()->getStore()->getId());
        $this->_getResource()->setRate(Mage::app()->getStore()->getCurrentCurrencyRate());
        $this->_getResource()->setCustomerGroupId(Mage::getSingleton('customer/session')->getCustomerGroupId());
    }

    public function getMaxValue($attribute, $entityIdsFilter)
    {
        return $this->_getResource()->getMaxValue($attribute, new Zend_Db_Expr($entityIdsFilter));
    }

    public function getCount($attribute, $range, $entityIdsFilter)
    {
        return $this->_getResource()->getCount($range, $attribute, new Zend_Db_Expr($entityIdsFilter));
    }

    public function getFilteredEntities($attribute, $range, $index, $entityIdsFilter)
    {
        return $this->_getResource()->getFilteredEntities($range, $index, $attribute, new Zend_Db_Expr($entityIdsFilter));
    }

    public function addMinimalPrices(Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection)
    {
        $productIds = $collection->getAllIds();

        if (!count($productIds)) {
            return;
        }

        $minimalPrices = $this->_getResource()->getMinimalPrices($productIds);

        $indexValues = array();
        foreach ($minimalPrices as $row) {
            $item = $collection->getItemById($row['entity_id']);
            if ($item)
                $item->setData('minimal_price', $row['value']);
        }
    }
}