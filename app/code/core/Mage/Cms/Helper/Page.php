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
 * @package    Mage_Cms
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Cms page helper
 *
 */
class Mage_Cms_Helper_Page extends Mage_Core_Helper_Abstract
{
    /**
    * Renders CMS page
    *
    * Call from controller action
    *
    * @param Mage_Core_Controller_Front_Action $action
    * @param integer $pageId
    * @return boolean
    */
    public function renderPage(Mage_Core_Controller_Front_Action $action, $pageId=null)
    {
        $page = Mage::getSingleton('cms/page');
        if (!is_null($pageId) && $pageId!==$page->getId()) {
            $page->setStoreId(Mage::app()->getStore()->getId());
            if (!$page->load($pageId)) {
                return false;
            }
        }

        if (!$page->getId()) {
            return false;
        }

//        $customerSession = Mage::getSingleton('customer/session');
//        if (!$customerSession->authenticate($action)) {
//            $customerSession->setBeforeAuthUrl(Mage::getBaseUrl().$page->getIdentifier());
//            return true;
//        }

        if ($page->getCustomTheme()) {
            $apply = true;
            $today = strtotime('today');
            if (($from = $page->getCustomThemeFrom()) && strtotime($from)>$today) {
                $apply = false;
            }
            if ($apply && ($to = $page->getCustomThemeTo()) && strtotime($to)<$today) {
                $apply = false;
            }
            if ($apply) {
                list($package, $theme) = explode('/', $page->getCustomTheme());
                Mage::getSingleton('core/design_package')
                    ->setPackageName($package)
                    ->setTheme($theme);
            }
        }

        $action->loadLayout(array('default', 'cms_page'), false, false);
        $action->getLayout()->getUpdate()->addUpdate($page->getLayoutUpdateXml());
        $action->generateLayoutXml()->generateLayoutBlocks();
        $action->renderLayout();

        return true;
    }
}