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
 * Catalog product linked products collection
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    protected $_product;
    protected $_linkModel;
    protected $_linkTypeId;
    protected $_isStrongMode;
    protected $_hasLinkFilter = false;

    /**
     * Declare link model and initialize type attributes join
     *
     * @param   Mage_Catalog_Model_Product_Link $linkModel
     * @return  Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
     */
    public function setLinkModel($linkModel)
    {
        $this->_linkModel = $linkModel;
        if ($linkModel->getLinkTypeId()) {
            $this->_linkTypeId = $linkModel->getLinkTypeId();
        }
        return $this;
    }

    public function setIsStrongMode()
    {
        $this->_isStrongMode = true;
        return $this;
    }

    /**
     * Retrieve collection link model
     *
     * @return  Mage_Catalog_Model_Product_Link
     */
    public function getLinkModel()
    {
        return $this->_linkModel;
    }

    /**
     * Initialize collection parent product and add limitation join
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection
     */
    public function setProduct($product)
    {
        $this->_product = $product;
        return $this;
    }

    /**
     * Retrieve collection base product object
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return $this->_product;
    }

    public function addExcludeProductFilter($products)
    {
        if (is_array($products) && !empty($products)) {
            $this->_hasLinkFilter = true;
            $this->getSelect()->where('links.linked_product_id NOT IN (?)', $products);
        }
        return $this;
    }

    public function addProductFilter($products)
    {
        $this->_hasLinkFilter = true;
        if (is_array($products) && !empty($products)) {
            $this->getSelect()->where('links.product_id IN (?)', $products);
        }
        elseif (is_string($products) || is_numeric($products)) {
        	$this->getSelect()->where('links.product_id=?', $products);
        }
        return $this;
    }

    public function setRandomOrder()
    {
        $this->getSelect()->order(new Zend_Db_Expr('RAND()'));
        $this->_setIdFieldName('link_id');
        return $this;
    }

    protected function _beforeLoad()
    {
        parent::_beforeLoad();
        if ($this->getLinkModel()) {
            $this->_joinLinks();
        }
        return $this;
    }

    protected function _joinLinks()
    {
        $joinCondition = 'links.linked_product_id=e.entity_id AND links.link_type_id='.$this->_linkTypeId;

        if ($this->getProduct() && $this->getProduct()->getId()) {
            if ($this->_isStrongMode) {
                $joinType = 'join';
                $this->getSelect()->where('links.product_id=?', $this->getProduct()->getId());
            }
            else {
                $joinType = 'joinLeft';
                $joinCondition.= ' AND links.product_id='. $this->getProduct()->getId();
            }
            $this->getSelect()->where('e.entity_id!=?', $this->getProduct()->getId());

            $this->getSelect()->$joinType(
                array('links'=>$this->getTable('catalog/product_link')),
                $joinCondition,
                array('link_id')
            );

            $attributes = $this->getLinkModel()->getAttributes();
            $attributesByType = array();
            foreach ($attributes as $attribute) {
                $table = $this->getLinkModel()->getAttributeTypeTable($attribute['type']);
                $alias = 'link_attribute_'.$attribute['code'].'_'.$attribute['type'];
                $this->getSelect()->joinLeft(
                    array($alias => $table),
                    $alias.'.link_id=links.link_id AND '.$alias.'.product_link_attribute_id='.$attribute['id'],
                    array($attribute['code'] => 'value')
                );
            }
        }
        else {
            if ($this->_isStrongMode) {
                $this->getSelect()->where('e.entity_id=-1');
            }
            elseif($this->_hasLinkFilter) {
                $this->getSelect()->join(
                    array('links'=>$this->getTable('catalog/product_link')),
                    'links.linked_product_id=e.entity_id AND links.link_type_id='.$this->_linkTypeId,
                    array('link_id')
                );
            }
        }

        return $this;
    }
}