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
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Category resource collection
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Collection_Abstract
{

    protected $_productTable;

    /**
     * Store id, that we should count products on
     *
     * @var int
     */
    protected $_productStoreId;

    protected $_productWebsiteTable;

    protected $_loadWithProductCount = false;

    /**
     * Init collection and determine table names
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/category');

        /**
         * @todo Why 'core/resource' is used here ? What if catalog will use another resource ?
         */
        $this->_productWebsiteTable = Mage::getSingleton('core/resource')->getTableName('catalog/product_website');
        $this->_productTable = Mage::getSingleton('core/resource')->getTableName('catalog/category_product');
    }

    /**
     * Enter description here...
     *
     * @param array $categoryIds
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    public function addIdFilter($categoryIds)
    {
        if (is_array($categoryIds)) {
            if (empty($categoryIds)) {
                $condition = '';
            } else {
                $condition = array('in' => $categoryIds);
            }
        } elseif (is_numeric($categoryIds)) {
            $condition = $categoryIds;
        } elseif (is_string($categoryIds)) {
            $ids = explode(',', $categoryIds);
            if (empty($ids)) {
                $condition = $categoryIds;
            } else {
                $condition = array('in' => $ids);
            }
        }
        $this->addFieldToFilter('entity_id', $condition);
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param boolean $flag
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    public function setLoadProductCount($flag)
    {
        $this->_loadWithProductCount = $flag;
        return $this;
    }

    /**
     * Set id of the store that we should count products on
     *
     * @param int $storeId
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    public function setProductStoreId($storeId)
    {
        $this->_productStoreId = $storeId;
        return $this;
    }

    /**
     * Get id of the store that we should count products on
     *
     * @return int
     */
    public function getProductStoreId()
    {
        if (is_null($this->_productStoreId)) {
            $this->_productStoreId = 0;
        }
        return $this->_productStoreId;
    }

    /**
     * Enter description here...
     *
     * @param boolean $printQuery
     * @param boolean $logQuery
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->_loadWithProductCount) {
            $this->addAttributeToSelect('all_children');
            $this->addAttributeToSelect('is_anchor');
        }

        parent::load($printQuery, $logQuery);

        if ($this->_loadWithProductCount) {
            $this->_loadProductCount();
        }

        return $this;
    }

    /**
     * Load categories product count
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    protected function _loadProductCount()
    {
        $anchor     = array();
        $regular    = array();

        foreach ($this->_items as $item) {
            if ($item->getIsAnchor()) {
                $anchor[$item->getId()] = $item;
            } else {
                $regular[$item->getId()] = $item;
            }
        }

        // Retrieve regular categories product counts
        $regularIds = array_keys($regular);
        if (!empty($regularIds)) {
            $select = $this->_conn->select();
            $select->from(
                    array('main_table'=>$this->_productTable),
                    array('category_id', new Zend_Db_Expr('COUNT(main_table.product_id)'))
                )
                ->where($this->_conn->quoteInto('main_table.category_id IN(?)', $regularIds))
                ->group('main_table.category_id');
            $counts = $this->_conn->fetchPairs($select);
            foreach ($regular as $item) {
                if (isset($counts[$item->getId()])) {
                    $item->setProductCount($counts[$item->getId()]);
                } else {
                    $item->setProductCount(0);
                }
            }
        }

        // Retrieve Anchor categories product counts
        foreach ($anchor as $item) {
            if ($allChildren = $item->getAllChildren()) {
                $select = $this->_conn->select();
                $select->from(
                        array('main_table'=>$this->_productTable),
                        new Zend_Db_Expr('COUNT( DISTINCT main_table.product_id)')
                    )
                    ->where($this->_conn->quoteInto('main_table.category_id IN(?)', explode(',', $item->getAllChildren())));

                $item->setProductCount((int) $this->_conn->fetchOne($select));
            } else {
                $item->setProductCount(0);
            }
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param string $regexp
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    public function addPathFilter($regexp)
    {
        $this->getSelect()->where(new Zend_Db_Expr("path regexp '{$regexp}'"));
        return $this;
    }

    /**
     * Joins url rewrite rules to collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    public function joinUrlRewrite()
    {
        $this->joinTable(
            'core/url_rewrite',
            'category_id=entity_id',
            array('request_path'),
            '{{table}}.is_system="1" AND {{table}}.product_id IS NULL AND {{table}}.store_id="'.Mage::app()->getStore()->getId().'"',
            'left'
        );
        return $this;
    }
}
