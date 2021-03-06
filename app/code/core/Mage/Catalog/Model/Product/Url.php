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
 * Product Url model
 *
 */

class Mage_Catalog_Model_Product_Url extends Varien_Object
{
    protected static $_url;
    protected static $_urlRewrite;

    const CACHE_TAG = 'url_rewrite';

    /**
    * @return Mage_Core_Model_Url
    */
    public function getUrlInstance()
    {
        if (!self::$_url) {
            self::$_url = Mage::getModel('core/url');
        }
        return self::$_url;
    }

    /**
    * @return Mage_Core_Model_Url_Rewrite
    */
    public function getUrlRewrite()
    {
        if (!self::$_urlRewrite) {
            self::$_urlRewrite = Mage::getModel('core/url_rewrite');
        }
        return self::$_urlRewrite;
    }

    /**
     * Get product url
     *
     * @param  Mage_Catalog_Model_Product $product
     * @return string
     */
    public function getProductUrl($product)
    {
        if ($product->hasData('request_path') && $product->getRequestPath() != '') {
            $url = $this->getUrlInstance()->getBaseUrl().$product->getRequestPath();
            return $url;
        }
    
        Varien_Profiler::start('REWRITE: '.__METHOD__);
        $rewrite = $this->getUrlRewrite();
        if ($product->getStoreId()) {
            $rewrite->setStoreId($product->getStoreId());
        }
        $idPath = 'product/'.$product->getId();
        if ($product->getCategoryId() && Mage::getStoreConfig('catalog/seo/product_use_categories')) {
            $idPath .= '/'.$product->getCategoryId();
        }

        $rewrite->loadByIdPath($idPath);

        if ($rewrite->getId()) {
            $url = $this->getUrlInstance()->getBaseUrl().$rewrite->getRequestPath();
        Varien_Profiler::stop('REWRITE: '.__METHOD__);
            return $url;
        }
        Varien_Profiler::stop('REWRITE: '.__METHOD__);
        Varien_Profiler::start('REGULAR: '.__METHOD__);

        $url = $this->getUrlInstance()->getUrl('catalog/product/view',
            array(
                'id'=>$product->getId(),
                's'=>$product->getUrlKey(),
                'category'=>$product->getCategoryId()
            ));
        Varien_Profiler::stop('REGULAR: '.__METHOD__);
        return $url;
    }

    public function formatUrlKey($str)
    {
    	$urlKey = preg_replace('#[^0-9a-z]+#i', '-', $str);
    	$urlKey = strtolower($urlKey);
    	$urlKey = trim($urlKey, '-');

    	return $urlKey;
    }

    public function getUrlPath($product, $category=null)
    {
        $path = $product->getUrlKey();

        if (is_null($category)) {
            /** @todo get default category */
            return $path;
        } elseif (!$category instanceof Mage_Catalog_Model_Category) {
            Mage::throwException('Invalid category object supplied');
        }

        $path = $category->getUrlPath().'/'.$path;

        return $path;
    }

    public function getImageUrl($product)
    {
        $url = false;
        if (!$product->getImage()) {
            $url = Mage::getDesign()->getSkinUrl('images/no_image.jpg');
        }
        elseif ($attribute = $product->getResource()->getAttribute('image')) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }

    public function getCustomImageUrl($product, $size, $extension=null, $watermark=null)
    {
        $url = false;
        if ($attribute = $product->getResource()->getAttribute('image')) {
            $url = Mage::getModel('media/image')
                    ->setConfig(Mage::getSingleton('catalog/product_media_config'))
                    ->getSpecialLink($attribute, $size, $extension, $watermark);
        }
        return $url;
    }

    public function getSmallImageUrl($product)
    {
        $url = false;
        if (!$product->getSmallImage()) {
            $url = Mage::getDesign()->getSkinUrl('images/no_image.jpg');
        }
        elseif ($attribute = $product->getResource()->getAttribute('small_image')) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }

    public function getCustomSmallImageUrl($product, $size, $extension=null, $watermark=null)
    {
        $url = false;
        if ($attribute = $product->getData('small_image')) {
            try {
                $url = Mage::getModel('media/image')
                        ->setConfig(Mage::getSingleton('catalog/product_media_config'))
                        ->getSpecialLink($attribute, $size, $extension, $watermark);
            } catch (Exception $e) {
                $url = false;
            }
        }
        return $url;
    }

    public function getThumbnailUrl($product)
    {
        $url = false;
        if (!$product->getThumbnail()) {
            $url = Mage::getDesign()->getSkinUrl('images/no_image.jpg');
        }
        elseif ($attribute = $product->getResource()->getAttribute('thumbnail')) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }
}