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
 * Catalog product model
 *
 * @category   Mage
 * @package    Mage_Catalog
 */
class Mage_Catalog_Model_Product extends Mage_Catalog_Model_Abstract
{
    protected $_attributeUpdates = array();
    /**
     * Product type instance
     *
     * @var Mage_Catalog_Model_Product_Type_Abstract
     */
    protected $_typeInstance;

    /**
     * Product link instance
     *
     * @var Mage_Catalog_Model_Product_Link
     */
    protected $_linkInstance;

    protected $_priceModel = null;
    protected $_urlModel = null;

    protected $_eventPrefix = 'catalog_product';
    protected $_eventObject = 'product';

    protected static $_url;
    protected static $_urlRewrite;

    protected $_cachedLinkedProductsByType = array();
    protected $_linkedProductsForSave = array();

    protected $_errors    = array();

    /**
     * Super product attribute collection
     *
     * @var Mage_Core_Model_Mysql4_Collection_Abstract
     */
    protected $_superAttributeCollection = null;

    /**
     * Super product links collection
     *
     * @var Mage_Eav_Model_Mysql4_Entity_Collection_Abstract
     */
    protected $_superLinkCollection = null;

    /**
     * Initialize resources
     */
    protected function _construct()
    {
        $this->_priceModel = Mage::getSingleton('catalog/product_price');
        $this->_urlModel = Mage::getSingleton('catalog/product_url');
        $this->_init('catalog/product');
    }

    public function validate()
    {
        $this->_getResource()->validate($this);
        return $this;
    }

    /**
     * Retrieve type instance
     *
     * Type instance implement type depended logic
     *
     * @return  Mage_Catalog_Model_Product_Type_Abstract
     */
    public function getTypeInstance()
    {
        $data = $this->getData('_type_instance');
        if (is_null($data)) {
            $data = Mage::getSingleton('catalog/product_type')->factory($this);
            $this->setData('_type_instance', $data);
        }
        return $data;
    }

    public function setTypeInstance($instance)
    {
        $this->setData('_type_instance', $instance);
        return $this;
    }

    /**
     * Retrieve type instance
     *
     * @return  Mage_Catalog_Model_Product_Link
     */
    public function getLinkInstance()
    {
        if (!$this->_linkInstance) {
            $this->_linkInstance = Mage::getSingleton('catalog/product_link');
        }
        return $this->_linkInstance;
    }

    /**
     * Retrive product id by sku
     *
     * @param   string $sku
     * @return  integer
     */
    public function getIdBySku($sku)
    {
        return $this->_getResource()->getIdBySku($sku);
    }

    /**
     * Retrieve product category id
     *
     * @return int
     */
    public function getCategoryId()
    {
        if ($category = Mage::registry('current_category')) {
            return $category->getId();
        }
        return false;
    }

    /**
     * Retrieve product category
     *
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory()
    {
        $category = $this->getData('category');
        if (is_null($category) && $this->getCategoryId()) {
            $category = Mage::getModel('catalog/category')->load($this->getCategoryId());
            $this->setCategory($category);
        }
        return $category;
    }

    public function setCategoryIds($ids)
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        } elseif (!is_array($ids)) {
            Mage::throwException(Mage::helper('catalog')->__('Invalid category IDs'));
        }
        foreach ($ids as $i=>$v) {
            if (empty($v)) {
                unset($ids[$i]);
            }
        }
        $this->setData('category_ids', $ids);
        return $this;
    }

    public function getCategoryIds()
    {
        if ($this->hasData('category_ids')) {
            $ids = $this->getData('category_ids');
            if (!is_array($ids)) {
                $ids = !empty($ids) ? explode(',', $ids) : array();
                $this->setData('category_ids', $ids);
            }
        } else {
            $ids = $this->_getResource()->getCategoryIds($this);
            $this->setData('category_ids', $ids);
        }
        return $this->getData('category_ids');
    }

    /**
     * Retrieve product categories
     *
     * @return Varien_Data_Collection
     */
    public function getCategoryCollection()
    {
        return $this->getResource()->getCategoryCollection($this);
    }

    /**
     * Retrieve product websites identifiers
     *
     * @return array
     */
    public function getWebsiteIds()
    {
        if (!$this->hasWebsiteIds()) {
            $ids = $this->_getResource()->getWebsiteIds($this);
            $this->setWebsiteIds($ids);
        }
        return $this->getData('website_ids');
    }

    public function getStoreIds()
    {
        if (!$this->hasStoreIds()) {
            $storeIds = array();
            if ($websiteIds = $this->getWebsiteIds()) {
                foreach ($websiteIds as $websiteId) {
                    $websiteStores = Mage::app()->getWebsite($websiteId)->getStoreIds();
                    $storeIds = array_merge($storeIds, $websiteStores);
                }
            }
            $this->setStoreIds($storeIds);
        }
        return $this->getData('store_ids');
    }

    /**
     * Retrieve product attributes
     *
     * if $groupId is null - retrieve all product attributes
     *
     * @param   int $groupId
     * @return  array
     */
    public function getAttributes($groupId = null, $skipSuper=false)
    {
        $productAttributes = $this->getTypeInstance()->getEditableAttributes();
        if ($groupId) {
            $attributes = array();
            foreach ($productAttributes as $attribute) {
                if ($attribute->getAttributeGroupId() == $groupId) {
                    $attributes[] = $attribute;
                }
            }
        }
        else {
            $attributes = $productAttributes;
        }

        return $attributes;
    }

    protected function _beforeSave()
    {
        $this->cleanCache();
        parent::_beforeSave();
    }

    /**
     * Saving product type related data
     *
     * @return unknown
     */
    protected function _afterSave()
    {
        $this->getLinkInstance()->saveProductRelations($this);
        $this->getTypeInstance()->save();
        parent::_afterSave();
    }

    protected function _beforeDelete()
    {
        $this->cleanCache();
        parent::_beforeDelete();
    }

    public function cleanCache()
    {
        Mage::app()->cleanCache('catalog_product_'.$this->getId());
    }

/*******************************************************************************
 ** Price API
 */
    /**
     * Get product pricing value
     *
     * @param   array $value
     * @return  double
     */
    public function getPricingValue($value, $qty = null)
    {
        return $this->_priceModel->getPricingValue($value, $this, $qty);
    }

    /**
     * Get product tier price by qty
     *
     * @param   double $qty
     * @return  double
     */
    public function getTierPrice($qty=null)
    {
        return $this->_priceModel->getTierPrice($qty, $this);
    }

    /**
     * Count how many tier prices we have for the product
     *
     * @return  int
     */
    public function getTierPriceCount()
    {
        return $this->_priceModel->getTierPriceCount($this);
    }

    /**
     * Get formated by currency tier price
     *
     * @param   double $qty
     * @return  array || double
     */
    public function getFormatedTierPrice($qty=null)
    {
        return $this->_priceModel->getFormatedTierPrice($qty, $this);
    }

    /**
     * Get formated by currency product price
     *
     * @return  array || double
     */
    public function getFormatedPrice()
    {
        return $this->_priceModel->getFormatedPrice($this);
    }

    /**
     * Get product final price
     *
     * @param double $qty
     * @return double
     */
    public function getFinalPrice($qty=null)
    {
        return $this->_priceModel->getFinalPrice($qty, $this);
    }

    /**
     * Get calculated product price
     *
     * @param array $options
     * @return double
     */
    public function getCalculatedPrice(array $options)
    {
        return $this->_priceModel->getCalculatedPrice($options, $this);
    }

/*******************************************************************************
 ** Linked products API
 */
    /**
     * Retrieve array of related roducts
     *
     * @return array
     */
    public function getRelatedProducts()
    {
        if (!$this->hasRelatedProducts()) {
            $products = array();
            $collection = $this->getRelatedProductCollection();
            foreach ($collection as $product) {
                $products[] = $product;
            }
            $this->setRelatedProducts($products);
        }
        return $this->getData('related_products');
    }

    /**
     * Retrieve related products identifiers
     *
     * @return array
     */
    public function getRelatedProductIds()
    {
        if (!$this->hasRelatedProductIds()) {
            $ids = array();
            foreach ($this->getRelatedProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setRelatedProductIds($ids);
        }
        return $this->getData('related_product_ids');
    }

    /**
     * Retrieve collection related product
     */
    public function getRelatedProductCollection()
    {
        $collection = $this->getLinkInstance()->useRelatedLinks()
            ->getProductCollection()
            ->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

    /**
     * Retrieve array of up sell products
     *
     * @return array
     */
    public function getUpSellProducts()
    {
        if (!$this->hasUpSellProducts()) {
            $products = array();
            foreach ($this->getUpSellProductCollection() as $product) {
                $products[] = $product;
            }
            $this->setUpSellProducts($products);
        }
        return $this->getData('up_sell_products');
    }

    /**
     * Retrieve up sell products identifiers
     *
     * @return array
     */
    public function getUpSellProductIds()
    {
        if (!$this->hasUpSellProductIds()) {
            $ids = array();
            foreach ($this->getUpSellProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setUpSellProductIds($ids);
        }
        return $this->getData('up_sell_product_ids');
    }

    /**
     * Retrieve collection up sell product
     */
    public function getUpSellProductCollection()
    {
        $collection = $this->getLinkInstance()->useUpSellLinks()
            ->getProductCollection()
            ->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

    /**
     * Retrieve array of cross sell roducts
     *
     * @return array
     */
    public function getCrossSellProducts()
    {
        if (!$this->hasCrossSellProducts()) {
            $products = array();
            foreach ($this->getCrossSellProductCollection() as $product) {
                $products[] = $product;
            }
            $this->setCrossSellProducts($products);
        }
        return $this->getData('cross_sell_products');
    }

    /**
     * Retrieve cross sell products identifiers
     *
     * @return array
     */
    public function getCrossSellProductIds()
    {
        if (!$this->hasCrossSellProductIds()) {
            $ids = array();
            foreach ($this->getCrossSellProducts() as $product) {
                $ids[] = $product->getId();
            }
            $this->setCrossSellProductIds($ids);
        }
        return $this->getData('cross_sell_product_ids');
    }

    /**
     * Retrieve collection cross sell product
     */
    public function getCrossSellProductCollection()
    {
        $collection = $this->getLinkInstance()->useCrossSellLinks()
            ->getProductCollection()
            ->setIsStrongMode();
        $collection->setProduct($this);
        return $collection;
    }

/*******************************************************************************
 ** Media API
 */
    /**
     * Retrive attributes for media gallery
     *
     * @return array
     */
    public function getMediaAttributes()
    {
        if (!$this->hasMediaAttributes()) {
            $mediaAttributes = array();
            foreach ($this->getAttributes() as $attribute) {
                if($attribute->getFrontend()->getInputType() == 'media_image') {
                    $mediaAttributes[] = $attribute;
                }
            }
            $this->setMediaAttributes($mediaAttributes);
        }
        return $this->getData('media_attributes');
    }

    /**
     * Retrive media gallery images
     *
     * @return Varien_Data_Collection
     */
    public function getMediaGalleryImages()
    {
        if(!$this->hasData('media_gallery_images') && is_array($this->getMediaGallery('images'))) {
            $images = new Varien_Data_Collection();
            foreach ($this->getMediaGallery('images') as $image) {
                if ($image['disabled']) {
                    continue;
                }
                $image['url'] = $this->getMediaConfig()->getMediaUrl($image['file']);
                $image['id'] = $image['value_id'];
                $image['path'] = $this->getMediaConfig()->getMediaPath($image['file']);
                $images->addItem(new Varien_Object($image));
            }
            $this->setData('media_gallery_images', $images);
        }

        return $this->getData('media_gallery_images');
    }

    /**
     * Add image to media gallery
     *
     * @param string        $file              file path of image in file system
     * @param string|array  $mediaAttribute    code of attribute with type 'media_image',
     *                                         leave blank if image should be only in gallery
     * @param boolean       $move              if true, it will move source file
     * @param boolean       $exclude           mark image as disabled in product page view
     */
    public function addImageToMediaGallery($file, $mediaAttribute=null, $move=false, $exclude=true)
    {
        $attributes = $this->getTypeInstance()->getSetAttributes();
        if (!isset($attributes['media_gallery'])) {
            return $this;
        }
        $mediaGalleryAttribute = $attributes['media_gallery'];
        /* @var $mediaGalleryAttribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $mediaGalleryAttribute->getBackend()->addImage($this, $file, $mediaAttribute, $move, $exclude);
        return $this;
    }

    /**
     * Retrive product media config
     *
     * @return Mage_Catalog_Model_Product_Media_Config
     */
    public function getMediaConfig()
    {
        return Mage::getSingleton('catalog/product_media_config');
    }

    /**
     * Create duplicate
     *
     * @return unknown
     */
    public function duplicate()
    {
        $this->getWebsiteIds();
        $this->getCategoryIds();

        Mage::dispatchEvent('catalog_model_product_duplicate', array($this->_eventObject=>$this));
        $newProduct = Mage::getModel('catalog/product')
            ->setData($this->getData())
            ->setSku(null)
            ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
            ->setId(null)
            ->save();
        $newId = $newProduct->getId();

        /*if ($storeIds = $this->getWebsiteIds()) {
            foreach ($storeIds as $storeId) {
                $this->setStoreId($storeId)
                   ->load($this->getId());

                $newProduct->setData($this->getData())
                    ->setSku(null)
                    ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
                    ->setId($newId)
                    ->save();
            }
        }*/

        return $newProduct;
    }

    public function isBundle()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE;
    }

    public function isSuperGroup()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED;
    }

    public function isSuperConfig()
    {
        return $this->isConfigurable();
    }
    /**
     * Check is product grouped
     *
     * @return bool
     */
    public function isGrouped()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED;
    }

    /**
     * Check is product configurable
     *
     * @return bool
     */
    public function isConfigurable()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    public function isSuper()
    {
        return $this->isConfigurable() || $this->isGrouped();
    }

    public function getVisibleInCatalogStatuses()
    {
        return Mage::getSingleton('catalog/product_status')->getVisibleStatusIds();
    }

    public function isVisibleInCatalog()
    {
        return in_array($this->getStatus(), $this->getVisibleInCatalogStatuses());
    }

    public function getVisibleInSiteVisibilities()
    {
        return Mage::getSingleton('catalog/product_visibility')->getVisibleInSiteIds();
    }

    public function isVisibleInSiteVisibility()
    {
        return in_array($this->getVisibility(), $this->getVisibleInSiteVisibilities());
    }

    /**
     * Check is product available for sale
     *
     * @return bool
     */
    public function isSalable()
    {
        return $this->getTypeInstance()->isSalable();
    }

    public function isSaleable()
    {
        return $this->isSalable();
    }

    public function isInStock()
    {
        return $this->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
    }

    public function getAttributeText($attributeCode)
    {
        return $this->getResource()
            ->getAttribute($attributeCode)
                ->getSource()
                    ->getOptionText($this->getData($attributeCode));
    }

    public function getCustomDesignDate()
    {
        $result = array();
        $result['from'] = $this->getData('custom_design_from');
        $result['to'] = $this->getData('custom_design_to');

        return $result;
    }

    /**
     * Get product url
     *
     * @return string
     */
    public function getProductUrl()
    {
        return $this->_urlModel->getProductUrl($this);
    }

    public function formatUrlKey($str)
    {
        return $this->_urlModel->formatUrlKey($str);
    }

    public function getUrlPath($category=null)
    {
        return $this->_urlModel->getUrlPath($this, $category);
    }

    public function getImageUrl()
    {
        return $this->_urlModel->getImageUrl($this);
    }

    public function getCustomImageUrl($size, $extension=null, $watermark=null)
    {
        return $this->_urlModel->getCustomImageUrl($this, $size, $extension, $watermark);
    }

    public function getSmallImageUrl()
    {
        return $this->_urlModel->getSmallImageUrl($this);
    }

    public function getCustomSmallImageUrl($size, $extension=null, $watermark=null)
    {
        return $this->_urlModel->getCustomSmallImageUrl($this, $size, $extension, $watermark);
    }

    public function getThumbnailUrl()
    {
        return $this->_urlModel->getThumbnailUrl($this);
    }

    public function importFromTextArray(array $row)
    {
        $hlp = Mage::helper('catalog');
        $line = $row['i'];
        $row = $row['row'];
        $isError = false;
        $this->unsetData();
        $catalogConfig = Mage::getSingleton('catalog/config');
        unset($row['entity_id']);
        $productId = null;
        // validate SKU
        if (empty($row['sku'])) {
            //$this->printError($hlp->__('SKU is required'), $line);
            //return ;
            $this->addError($hlp->__('SKU is required line: %s', $line));
        } else {
            $productId = $this->getIdBySku($row['sku']);
        }

        if ($productId) {
            $this->unsetData();
            $this->load($productId);
            if (isset($row['store'])) {
                $storeId = Mage::app()->getStore($row['store'])->getId();
                if ($storeId) $this->setStoreId($storeId);
            }
        } else {
            if ($row['store'] && $storeId = Mage::app()->getStore($row['store'])->getId()) {
                $this->setStoreId($storeId);
            } else {
                $this->setStoreId(0);
            }

            // if attribute_set not set use default
            if (empty($row['attribute_set'])) {
                $row['attribute_set'] = !empty($row['attribute_set_id']) ? $row['attribute_set_id'] : 'Default';
            }

            if ($row['attribute_set']) {
                // get attribute_set_id, if not throw error
                $attributeSetId = $catalogConfig->getAttributeSetId('catalog_product', $row['attribute_set']);
            }
            if (!isset($attributeSetId)) {
//                $this->printError($hlp->__("Invalid attribute set specified"), $line);
//                return;
                  $this->addError($hlp->__("Invalid attribute set specified line: %s", $line));
            }

            $this->setAttributeSetId($attributeSetId);

            if (empty($row['type'])) {
                $row['type'] = !empty($row['type_id']) ? $row['type_id'] : 'Simple Product';
            }
            // get product type_id, if not throw error
            $typeId = $catalogConfig->getProductTypeId($row['type']);
            if (!$typeId) {
                  $this->addError($hlp->__("Invalid product type specified line: %s", $line));
//                $this->printError($hlp->__("Invalid product type specified"), $line);
//                return;
            }
            $this->setTypeId($typeId);
        }

        if ($errors = $this->getErrors()) {
            $this->unsetData();
            $this->printError(join("<br />",$errors));
            $this->resetErrors();
            return;
        }

        $entity = $this->getResource();

        //print_r($entity);
        foreach ($row as $field=>$value) {
            $attribute = $entity->getAttribute($field);
            if (!$attribute) {
                continue;
            }

            if ($attribute->usesSource()) {
                $source = $attribute->getSource();
                $optionId = $catalogConfig->getSourceOptionId($source, $value);
                if (is_null($optionId)) {
                    $this->printError($hlp->__("Invalid attribute option specified for attribute attribute %s (%s)", $field, $value), $line);
                }
                $value = $optionId;
            }

            $this->setData($field, $value);
        }

        $postedStores = array(0=>0);
        if (isset($row['store'])) {
            foreach (explode(',', $row['store']) as $store) {
                $storeId = Mage::app()->getStore($store)->getId();
                if (!$this->hasStoreId()) {
                    $this->setStoreId($storeId);
                }
                $postedStores[$storeId] = $this->getStoreId();
            }
        }

        $this->setPostedStores($postedStores);

        if (isset($row['categories'])) {
            $this->setCategoryIds($row['categories']);
        }
        return $this;
    }

    public function importFromTextArraySilently(array $row)
    {
        $hlp = Mage::helper('catalog');
        $line = $row['i'];
        $row = $row['row'];
        $isError = false;
        $this->unsetData();
        $catalogConfig = Mage::getSingleton('catalog/config');
        unset($row['entity_id']);
        $productId = null;
        // validate SKU
        if (empty($row['sku'])) {
            //$this->printError($hlp->__('SKU is required'), $line);
            //return ;
            $this->addError($hlp->__('SKU is required line: %s', $line));
        } else {
            $productId = $this->getIdBySku($row['sku']);
        }

        if ($productId) {
            $this->unsetData();
            $this->load($productId);
            if (isset($row['store'])) {
                $storeId = Mage::app()->getStore($row['store'])->getId();
                if ($storeId) $this->setStoreId($storeId);
            }
        } else {
            if ($row['store'] && $storeId = Mage::app()->getStore($row['store'])->getId()) {
                $this->setStoreId($storeId);
            } else {
                $this->setStoreId(0);
            }

            // if attribute_set not set use default
            if (empty($row['attribute_set'])) {
                $row['attribute_set'] = !empty($row['attribute_set_id']) ? $row['attribute_set_id'] : 'Default';
            }

            if ($row['attribute_set']) {
                // get attribute_set_id, if not throw error
                $attributeSetId = $catalogConfig->getAttributeSetId('catalog_product', $row['attribute_set']);
            }
            if (!isset($attributeSetId)) {
//                $this->printError($hlp->__("Invalid attribute set specified"), $line);
//                return;
                  $this->addError($hlp->__("Invalid attribute set specified line: %s", $line));
            }

            $this->setAttributeSetId($attributeSetId);

            if (empty($row['type'])) {
                $row['type'] = !empty($row['type_id']) ? $row['type_id'] : 'Simple Product';
            }
            // get product type_id, if not throw error
            $typeId = $catalogConfig->getProductTypeId($row['type']);
            if (!$typeId) {
                  $this->addError($hlp->__("Invalid product type specified line: %s", $line));
//                $this->printError($hlp->__("Invalid product type specified"), $line);
//                return;
            }
            $this->setTypeId($typeId);
        }

        if ($errors = $this->getErrors()) {
            $this->unsetData();
//            $this->printError(join("<br />",$errors));
            $this->resetErrors();
            return false;
        }

        $entity = $this->getResource();

        //print_r($entity);
        foreach ($row as $field=>$value) {
            $attribute = $entity->getAttribute($field);
            if (!$attribute) {
                continue;
            }

            if ($attribute->usesSource()) {
                $source = $attribute->getSource();
                $optionId = $catalogConfig->getSourceOptionId($source, $value);
                if (is_null($optionId)) {
                    //$this->printError($hlp->__("Invalid attribute option specified for attribute attribute %s (%s)", $field, $value), $line);
                }
                $value = $optionId;
            }

            $this->setData($field, $value);
        }

        $postedStores = array(0=>0);
        if (isset($row['store'])) {
            foreach (explode(',', $row['store']) as $store) {
                $storeId = Mage::app()->getStore($store)->getId();
                if (!$this->hasStoreId()) {
                    $this->setStoreId($storeId);
                }
                $postedStores[$storeId] = $this->getStoreId();
            }
        }

        $this->setPostedStores($postedStores);

        if (isset($row['categories'])) {
            $this->setCategoryIds($row['categories']);
        }
        return $this;
    }

    function addError($error)
    {
        $this->_errors[] = $error;
    }

    function getErrors()
    {
        return $this->_errors;
    }

    function resetErrors()
    {
        $this->_errors = array();
    }

    function printError($error, $line = null)
    {
        if ($error == null) return false;
        $img = 'error_msg_icon.gif';
        $liStyle = 'background-color:#FDD; ';
        echo '<li style="'.$liStyle.'">';
        echo '<img src="'.Mage::getDesign()->getSkinUrl('images/'.$img).'" class="v-middle"/>';
        echo $error;
        if ($line) {
            echo '<small>, Line: <b>'.$line.'</b></small>';
        }
        echo "</li>";
    }

    public function addAttributeUpdate($code, $value, $store)
    {
        $oldValue = $this->getData($code);
        $oldStore = $this->getStoreId();

        $this->setData($code, $value);
        $this->setStoreId($store);
        $this->getResource()->saveAttribute($this, $code);

        $this->setData($code, $oldValue);
        $this->setStoreId($oldStore);
    }

    public function toArray(array $arrAttributes=array())
    {
        $data = parent::toArray($arrAttributes);
        if ($stock = $this->getStockItem()) {
            $data['stock_item'] = $stock->toArray();
        }
        unset($data['stock_item']['product']);
        return $data;
    }

    public function fromArray($data)
    {
        if (isset($data['stock_item'])) {
            $stockItem = Mage::getModel('cataloginventory/stock_item')
                ->setData($data['stock_item'])
                ->setProduct($this);
            $this->setStockItem($stockItem);
            unset($data['stock_item']);
        }
        $this->setData($data);
        return $this;
    }

    public function loadParentProductIds()
    {
        return $this->setParentProductIds($this->_getResource()->getParentProductIds($this));
    }

    public function delete()
    {
        parent::delete();
        Mage::dispatchEvent($this->_eventPrefix.'_delete_after_done', array($this->_eventObject=>$this));
        return $this;
    }
}