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
 * @package    Mage_Core
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Core_Model_Mysql4_Layout extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('core/layout_update', 'layout_update_id');
    }

    /**
     * Retrieve layout updates by handle
     *
     * @param string $handle
     * @param array $params
     */
    public function fetchUpdatesByHandle($handle, $params = array())
    {
        $storeId = isset($params['store_id']) ? $params['store_id'] : Mage::app()->getStore()->getId();
        $package = isset($params['package']) ? $params['package'] : Mage::getSingleton('core/design_package')->getPackageName();
        $theme = isset($params['theme']) ? $params['theme'] : Mage::getSingleton('core/design_package')->getTheme('layout');

        $read = $this->_getReadAdapter();
        $updateStr = '';
        
        if ($read) {
            $select = $read->select()->from(array('update'=>$this->getMainTable()), 'xml')
                ->join(array('link'=>$this->getTable('core/layout_link')), 'link.layout_update_id=update.layout_update_id', '')
                ->where('link.store_id=?', $storeId)
                ->where('link.package=?', $package)
                ->where('link.theme=?', $theme);
    
            if ($updates = $read->fetchAll($select)) {
                foreach ($updates as $update) {
                    $updateStr .= $update['xml'];
                }
            }
        }
        return $updateStr;
    }
}