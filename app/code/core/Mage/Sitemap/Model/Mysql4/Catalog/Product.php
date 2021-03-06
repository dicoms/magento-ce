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
 * @package    Mage_Sitemap
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sitemap resource product collection model
 *
 * @category   Mage
 * @package    Mage_Sitemap
 */

class Mage_Sitemap_Model_Mysql4_Catalog_Product extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Collection Zend Db select
     * 
     * @var Zend_Db_Select
     */
    protected $_select;
    
    /**
     * Attribute cache
     * 
     * @var array
     */
    protected $_attributesCache = array();
    
    /**
     * Init resource model (catalog/category)
     */
    protected function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
    }
    
    /**
     * Add attribute to filter
     * 
     * @param int $storeId
     * @param string $attributeCode
     * @param mixed $value
     * @param string $type
     * 
     * @return Zend_Db_Select
     */
    protected function _addFilter($storeId, $attributeCode, $value, $type = '=')
    {
        if (!isset($this->_attributesCache[$attributeCode])) {
            $attribute = Mage::getSingleton('catalog/product')->getResource()->getAttribute($attributeCode);
            
            $this->_attributesCache[$attributeCode] = array(
                'entity_type_id'    => $attribute->getEntityTypeId(),
                'attribute_id'      => $attribute->getId(),
                'table'             => $attribute->getBackend()->getTable(),
                'is_global'         => $attribute->getIsGlobal(),
                'backend_type'      => $attribute->getBackendType()
            );
        }
        
        $attribute = $this->_attributesCache[$attributeCode];

        if (!$this->_select instanceof Zend_Db_Select) {
            return false; 
        }
        
        switch ($type) {
            case '=':
                $conditionRule = '=?';
                break;
            case 'in':
                $conditionRule = ' IN(?)';
                break;
            default:
                return false;
                break;
        }
        
        if ($attribute['backend_type'] == 'static') {
            $this->_select->where('e.' . $attributeCode . $conditionRule, $value);
        }
        else {
            if ($attribute['is_global']) {
                
            }
            else {
                $this->_select->join(
                    array('t1_'.$attributeCode => $attribute['table']),
                    'e.entity_id=t1_'.$attributeCode.'.entity_id AND t1_'.$attributeCode.'.store_id=0',
                    array()
                )
                ->joinLeft(
                    array('t2_'.$attributeCode => $attribute['table']),
                    $this->_getWriteAdapter()->quoteInto('t1_'.$attributeCode.'.entity_id = t2_'.$attributeCode.'.entity_id AND t1_'.$attributeCode.'.attribute_id = t2_'.$attributeCode.'.attribute_id AND t2_'.$attributeCode.'.store_id=?', $storeId),
                    array()
                )
                ->where('t1_'.$attributeCode.'.attribute_id=?', $attribute['attribute_id'])
                ->where('IFNULL(t2_'.$attributeCode.'.value, t1_'.$attributeCode.'.value)'.$conditionRule, $value);
            }
        }
        
        return $this->_select;
    }
    
    
    /**
     * Get category collection array
     * 
     * @return array
     */
    public function getCollection($storeId)
    {
        $products = array();
        
        $store = Mage::app()->getStore($storeId);
        /* @var $store Mage_Core_Model_Store */
        
        if (!$store) {
            return false;
        }
        
        $urCondions = array(
            'e.entity_id=ur.product_id',
            'ur.category_id IS NULL',
            $this->_getWriteAdapter()->quoteInto('ur.store_id=?', $store->getId()),
            $this->_getWriteAdapter()->quoteInto('ur.is_system=?', 1),
        );
        $this->_select = $this->_getWriteAdapter()->select()
            ->from(array('e' => $this->getMainTable()), array($this->getIdFieldName()))
            ->join(
                array('w' => $this->getTable('catalog/product_website')),
                'e.entity_id=w.product_id',
                array()
            )
            ->where('w.website_id=?', $store->getWebsiteId())
            ->joinLeft(
                array('ur' => $this->getTable('core/url_rewrite')),
                join(' AND ', $urCondions),
                array('url' => 'request_path')
            )
            ;
        
        $this->_addFilter($storeId, 'visibility', array(2,4), 'in');
        $this->_addFilter($storeId, 'status', 1);
        
        $query = $this->_getWriteAdapter()->query($this->_select);
        while ($row = $query->fetch()) {
            $product = $this->_prepareProduct($row);
            $products[$product->getId()] = $product;
        }
        
        return $products;
    }
    
    /**
     * Prepare product
     * 
     * @param array $productRow
     * @return Varien_Object
     */
    protected function _prepareProduct(array $productRow)
    {
        $product = new Varien_Object();
        $product->setId($productRow[$this->getIdFieldName()]);
        $productUrl = !empty($productRow['url']) ? $productRow['url'] : 'catalog/product/view/id/' . $product->getId();
        $product->setUrl($productUrl);
        return $product;
    }
}