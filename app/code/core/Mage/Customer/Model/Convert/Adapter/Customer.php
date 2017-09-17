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
 * @package    Mage_Customer
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Customer_Model_Convert_Adapter_Customer
    extends Mage_Eav_Model_Convert_Adapter_Entity
{
    const MULTI_DELIMITER = ' , ';

    /**
     * Product model
     *
     * @var Mage_Customer_Model_Customer
     */
    protected $_customerModel;
    protected $_stores;
    protected $_attributes = array();
    protected $_customerGroups;

    protected $_billingAddressModel;
    protected $_shippingAddressModel;

    protected $_requiredFields = array(
        'firstname', 'lastname'
    );

    protected $_ignoreFields = array(
        'entity_id', 'attribute_set', 'attribute_set_id', 'type', 'type_id',
        'increment_id', 'store', 'group_id', 'created_in'
    );

    protected $_billingFields = array(
        'billing_street1', 'billing_street2', 'billing_city', 'billing_region',
        'billing_country', 'billing_postcode', 'billing_telephone',
        'billing_firstname', 'billing_lastname', 'billing_company',
        'billing_fax'
    );

    protected $_billingMappedFields = array(
        'billing_firstname' => 'firstname',
        'billing_lastname'  => 'lastname'
    );

    protected $_billingSteetFields = array(
        'billing_street1', 'billing_street2'
    );

    protected $_billingRequiredFields = array(
        'billing_country', 'billing_postcode'
    );

    protected $_shippingFields = array(
        'shipping_street1', 'shipping_street2', 'shipping_city', 'shipping_region',
        'shipping_country', 'shipping_postcode', 'shipping_telephone',
        'shipping_firstname', 'shipping_lastname', 'shipping_company',
        'shipping_fax'
    );

    protected $_shippingMappedFields = array(
        'shipping_firstname' => 'firstname',
        'shipping_lastname'  => 'lastname'
    );

    protected $_shippingSteetFields = array(
        'shipping_street1', 'shipping_street2'
    );

    protected $_shippingRequiredFields = array(
        'shipping_country', 'shipping_postcode'
    );

    protected $_regions;
    protected $_websites;

    protected $_customer = null;
    protected $_address = null;

    protected $_customerId = '';

    /**
     * Retrieve customer model cache
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomerModel()
    {
        if (is_null($this->_customerModel)) {
            $object = Mage::getModel('customer/customer');
            $this->_customerModel = Varien_Object_Cache::singleton()->save($object);
        }
        return Varien_Object_Cache::singleton()->load($this->_customerModel);
    }

    /**
     * Retrieve customer address model cache
     *
     * @return Mage_Customer_Model_Address
     */
    public function getBillingAddressModel()
    {
        if (is_null($this->_billingAddressModel)) {
            $object = Mage::getModel('customer/address');
            $this->_billingAddressModel = Varien_Object_Cache::singleton()->save($object);
        }
        return Varien_Object_Cache::singleton()->load($this->_billingAddressModel);
    }

    /**
     * Retrieve customer address model cache
     *
     * @return Mage_Customer_Model_Address
     */
    public function getShippingAddressModel()
    {
        if (is_null($this->_shippingAddressModel)) {
            $object = Mage::getModel('customer/address');
            $this->_shippingAddressModel = Varien_Object_Cache::singleton()->save($object);
        }
        return Varien_Object_Cache::singleton()->load($this->_shippingAddressModel);
    }

    /**
     * Retrieve store object by code
     *
     * @param string $store
     * @return Mage_Core_Model_Store
     */
    public function getStoreByCode($store)
    {
        if (is_null($this->_stores)) {
            $this->_stores = Mage::app()->getStores(true, true);
        }
        if (isset($this->_stores[$store])) {
            return $this->_stores[$store];
        }
        return false;
    }

    /**
     * Retrieve website model by code
     *
     * @param int $websiteId
     * @return Mage_Core_Model_Website
     */
    public function getWebsiteByCode($websiteCode)
    {
        if (is_null($this->_websites)) {
            $this->_websites = Mage::app()->getWebsites(true, true);
        }
        if (isset($this->_websites[$websiteCode])) {
            return $this->_websites[$websiteCode];
        }
        return false;
    }

    /**
     * Retrieve eav entity attribute model
     *
     * @param string $code
     * @return Mage_Eav_Model_Entity_Attribute
     */
    public function getAttribute($code)
    {
        if (!isset($this->_attributes[$code])) {
            $this->_attributes[$code] = $this->getCustomerModel()->getResource()->getAttribute($code);
        }
        return $this->_attributes[$code];
    }

    /**
     * Retrieve region id by country code and region name (if exists)
     *
     * @param string $country
     * @param string $region
     * @return int
     */
    public function getRegionId($country, $regionName)
    {
        if (is_null($this->_regions)) {
            $this->_regions = array();

            $collection = Mage::getModel('directory/region')
                ->getCollection();
            foreach ($collection as $region) {
                if (!isset($this->_regions[$region->getCountryId()])) {
                    $this->_regions[$region->getCountryId()] = array();
                }

                $this->_regions[$region->getCountryId()][$region->getDefaultName()] = $region->getId();
            }
        }

        if (isset($this->_regions[$country][$regionName])) {
            return $this->_regions[$country][$regionName];
        }

        return 0;
    }

    /**
     * Retrieve customer group collection array
     *
     * @return array
     */
    public function getCustomerGoups()
    {
        if (is_null($this->_customerGroups)) {
            $this->_customerGroups = array();
            $collection = Mage::getModel('customer/group')
                ->getCollection()
                ->addFieldToFilter('customer_group_id', array('gt'=> 0));
            foreach ($collection as $group) {
                $this->_customerGroups[$group->getCustomerGroupCode()] = $group->getId();
            }
        }
        return $this->_customerGroups;
    }



    public function __construct()
    {
        $this->setVar('entity_type', 'customer/customer');

        if (!Mage::registry('Object_Cache_Customer')) {
            $this->setCustomer(Mage::getModel('customer/customer'));
        }
        //$this->setAddress(Mage::getModel('catalog/'))
    }

    public function load()
    {
        $addressType = $this->getVar('filter/addressType');
        if ($addressType=='both') {
           $addressType = array('default_billing','default_shipping');
        }
        $attrFilterArray = array();
        $attrFilterArray ['firstname']                  = 'like';
        $attrFilterArray ['lastname']                   = 'like';
        $attrFilterArray ['email']                      = 'like';
        $attrFilterArray ['group']                      = 'eq';
        $attrFilterArray ['customer_address/telephone'] = array(
            'type'  => 'like',
            'bind'  => $addressType
        );
        $attrFilterArray ['customer_address/postcode']  = array(
            'type'  => 'like',
            'bind'  => $addressType
        );
        $attrFilterArray ['customer_address/country']   = array(
            'type'  => 'eq',
            'bind'  => $addressType
        );
        $attrFilterArray ['customer_address/region']    = array(
            'type'  => 'like',
            'bind'  => $addressType
        );
        $attrFilterArray ['created_at']                 = 'dateFromTo';

        $attrToDb = array(
            'group'                     => 'group_id',
            'customer_address/country'  => 'customer_address/country_id',
        );

        // Added store filter
        if ($storeId = $this->getStoreId()) {
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            if ($websiteId) {
                $this->_filter[] = array(
                    'attribute' => 'website_id',
                    'eq'        => $websiteId
                );
            }
        }

        parent::setFilter($attrFilterArray, $attrToDb);
        return parent::load();
    }

    /**
     * Not use :(
     */
    public function parse()
    {
        $batchModel = Mage::getSingleton('dataflow/batch');
        /* @var $batchModel Mage_Dataflow_Model_Batch */

        $batchImportModel = $batchModel->getBatchImportModel();
        $importIds = $batchImportModel->getIdCollection();

        foreach ($importIds as $importId) {
            $batchImportModel->load($importId);
            $importData = $batchImportModel->getBatchData();

            $this->saveRow($importData);
        }
    }

    public function setCustomer(Mage_Customer_Model_Customer $customer)
    {
        $id = Varien_Object_Cache::singleton()->save($customer);
        Mage::register('Object_Cache_Customer', $id);
    }

    public function getCustomer()
    {
        return Varien_Object_Cache::singleton()->load(Mage::registry('Object_Cache_Customer'));
    }

    public function save()
    {
        $stores = array();
        foreach (Mage::getConfig()->getNode('stores')->children() as $storeNode) {
            $stores[(int)$storeNode->system->store->id] = $storeNode->getName();
        }

        $collections = $this->getData();
        if ($collections instanceof Mage_Customer_Model_Entity_Customer_Collection) {
            $collections = array($collections->getEntity()->getStoreId()=>$collections);
        } elseif (!is_array($collections)) {
            $this->addException(Mage::helper('customer')->__('No product collections found'), Mage_Dataflow_Model_Convert_Exception::FATAL);
        }

        foreach ($collections as $storeId=>$collection) {
            $this->addException(Mage::helper('customer')->__('Records for "'.$stores[$storeId].'" store found'));

            if (!$collection instanceof Mage_Customer_Model_Entity_Customer_Collection) {
                $this->addException(Mage::helper('customer')->__('Customer collection expected'), Mage_Dataflow_Model_Convert_Exception::FATAL);
            }
            try {
                $i = 0;
                foreach ($collection->getIterator() as $model) {
                    $new = false;
                    // if product is new, create default values first
                    if (!$model->getId()) {
                        $new = true;
                        $model->save();
                        #Mage::getResourceSingleton('catalog_entity/convert')->addProductToStore($model->getId(), 0);
                    }
                    if (!$new || 0!==$storeId) {

//                        if (0!==$storeId) {
//                            Mage::getResourceSingleton('catalog_entity/convert')->addProductToStore($model->getId(), $storeId);
//                        }

                        $model->save();
                    }
                    $i++;
                }
                $this->addException(Mage::helper('customer')->__("Saved ".$i." record(s)"));
            } catch (Exception $e) {
                if (!$e instanceof Mage_Dataflow_Model_Convert_Exception) {
                    $this->addException(Mage::helper('customer')->__('Problem saving the collection, aborting. Error: %s', $e->getMessage()),
                        Mage_Dataflow_Model_Convert_Exception::FATAL);
                }
            }
        }
        return $this;
    }

    /*
     * saveRow function for saving each customer data
     *
     * params args array
     * return array
     */
    public function saveRow($importData)
    {
        $customer = $this->getCustomerModel();
        $customer->setId(null);

        if (empty($importData['website'])) {
            $message = Mage::helper('customer')->__('Skip import row, required field "%s" not defined', 'website');
            Mage::throwException($message);
        }

        $website = $this->getWebsiteByCode($importData['website']);

        if ($website === false) {
            $message = Mage::helper('customer')->__('Skip import row, website "%s" field not exists', $importData['website']);
            Mage::throwException($message);
        }
        if (empty($importData['email'])) {
            $message = Mage::helper('customer')->__('Skip import row, required field "%s" not defined', 'email');
            Mage::throwException($message);
        }

        $customer->setWebsiteId($website->getId())
            ->loadByEmail($importData['email']);
        if (!$customer->getId()) {
            $customerGroups = $this->getCustomerGoups();
            /**
             * Check customer group
             */
            if (empty($importData['group_id']) || !isset($customerGroups[$importData['group_id']])) {
                $value = isset($importData['group_id']) ? $importData['group_id'] : '';
                $message = Mage::helper('catalog')->__('Skip import row, is not valid value "%s" for field "%s"', $value, 'group_id');
                Mage::throwException($message);
            }
            $customer->setGroupId($customerGroups[$importData['group_id']]);

            foreach ($this->_requiredFields as $field) {
                if (!isset($importData[$field])) {
                    $message = Mage::helper('catalog')->__('Skip import row, required field "%s" for new customer not defined', $field);
                    Mage::throwException($message);
                }
            }

            $customer->setWebsiteId($website->getId());

            if (empty($importData['created_in']) || !$this->getStoreByCode($importData['created_in'])) {
                $customer->setStoreId(0);
            }
            else {
                $customer->setStoreId($this->getStoreByCode($importData['created_in'])->getId());
            }

            if (empty($importData['password_hash'])) {
                $customer->setPasswordHash($customer->hashPassword($customer->generatePassword(8)));
            }
        }
        elseif (!empty($importData['group_id'])) {
            $customerGroups = $this->getCustomerGoups();
            /**
             * Check customer group
             */
            if (isset($customerGroups[$importData['group_id']])) {
                $customer->setGroupId($customerGroups[$importData['group_id']]);
            }
        }

        foreach ($this->_ignoreFields as $field) {
            if (isset($importData[$field])) {
                unset($importData[$field]);
            }
        }

        foreach ($importData as $field => $value) {
            if (in_array($field, $this->_billingFields)) {
                continue;
            }
            if (in_array($field, $this->_shippingFields)) {
                continue;
            }

            $attribute = $this->getAttribute($field);
            if (!$attribute) {
                continue;
            }

            $isArray = false;
            $setValue = $value;

            if ($attribute->getFrontendInput() == 'multiselect') {
                $value = split(self::MULTI_DELIMITER, $value);
                $isArray = true;
                $setValue = array();
            }

            if ($attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions(false);

                if ($isArray) {
                    foreach ($options as $item) {
                        if (in_array($item['label'], $value)) {
                            $setValue[] = $item['value'];
                        }
                    }
                }
                else {
                    $setValue = null;
                    foreach ($options as $item) {
                        if ($item['label'] == $value) {
                            $setValue = $item['value'];
                        }
                    }
                }
            }

            $customer->setData($field, $setValue);
        }

        $importBillingAddress = $importShippingAddress = true;
        $savedBillingAddress = $savedShippingAddress = false;
        /**
         * Billing address
         */
        foreach ($this->_billingRequiredFields as $field) {
            if (empty($importData[$field])) {
                $importBillingAddress = false;
            }
        }

        if ($importBillingAddress) {
            $billingAddress = $this->getBillingAddressModel();
            if ($customer->getDefaultBilling()) {
                $billingAddress->load($customer->getDefaultBilling());
            }
            else {
                $billingAddress->setData(array());
            }

            $billingStreet = array();
            foreach ($this->_billingFields as $field) {
                $cleanField = substr($field, 8);

                if (in_array($field, $this->_billingSteetFields) && isset($importData[$field])) {
                    $billingStreet[] = $importData[$field];
                    continue;
                }

                if (isset($importData[$field])) {
                    $billingAddress->setData($cleanField, $importData[$field]);
                }
                elseif (isset($this->_billingMappedFields[$field])
                    && isset($importData[$this->_billingMappedFields[$field]])) {
                    $billingAddress->setData($cleanField, $importData[$this->_billingMappedFields[$field]]);
                }
            }

            $billingAddress->setStreet($billingStreet);
            $billingAddress->implodeStreetAddress();

            $billingAddress->setCountryId($importData['billing_country']);
            $regionName = isset($importData['billing_region']) ? $importData['billing_region'] : '';
            if ($regionName) {
                $regionId = $this->getRegionId($importData['billing_country'], $regionName);
                $billingAddress->setRegionId($regionId);
            }

            if ($customer->getId()) {
                $billingAddress->setCustomerId($customer->getId());
                $billingAddress->save();
                $customer->setDefaultBilling($billingAddress->getId());

                $savedBillingAddress = true;
            }
        }
        /**
         * Shipping address
         */
        foreach ($this->_shippingRequiredFields as $field) {
            if (empty($importData[$field])) {
                $importShippingAddress = false;
            }
        }
        if ($importShippingAddress) {
            $shippingAddress = $this->getShippingAddressModel();
            if ($customer->getDefaultShipping() && $customer->getDefaultBilling() != $customer->getDefaultShipping()) {
                $shippingAddress->load($customer->getDefaultShipping());
            }
            else {
                $shippingAddress->setData(array());
            }

            $shippingStreet = array();

            foreach ($this->_shippingFields as $field) {
                $cleanField = substr($field, 9);

                if (in_array($field, $this->_shippingSteetFields) && isset($importData[$field])) {
                    $shippingStreet[] = $importData[$field];
                    continue;
                }

                if (isset($importData[$field])) {
                    $shippingAddress->setData($cleanField, $importData[$field]);
                }
                elseif (isset($this->_shippingMappedFields[$field])
                    && isset($importData[$this->_shippingMappedFields[$field]])) {
                    $shippingAddress->setData($cleanField, $importData[$this->_shippingMappedFields[$field]]);
                }
            }

            $shippingAddress->setStreet($shippingStreet);
            $shippingAddress->implodeStreetAddress();

            $shippingAddress->setCountryId($importData['shipping_country']);
            $regionName = isset($importData['shipping_region']) ? $importData['shipping_region'] : '';
            if ($regionName) {
                $regionId = $this->getRegionId($importData['shipping_country'], $regionName);
                $shippingAddress->setRegionId($regionId);
            }

            if ($customer->getId()) {
                $shippingAddress->setCustomerId($customer->getId());
                $shippingAddress->save();
                $customer->setDefaultShipping($shippingAddress->getId());

                $savedShippingAddress = true;
            }
        }

        $customer->save();
        $saveCustomer = false;

        if ($importBillingAddress && !$savedBillingAddress) {
            $saveCustomer = true;
            $billingAddress->setCustomerId($customer->getId());
            $billingAddress->save();
            $customer->setDefaultBilling($billingAddress->getId());
        }
        if ($importShippingAddress && !$savedShippingAddress) {
            $saveCustomer = true;
            $shippingAddress->setCustomerId($customer->getId());
            $shippingAddress->save();
            $customer->setDefaultShipping($shippingAddress->getId());
        }
        if ($saveCustomer) {
            $customer->save();
        }

        return $this;

        /* ########### THE CODE BELOW AT THIS METHOD DON'T USED ############# */

        $mem = memory_get_usage(); $origMem = $mem; $memory = $mem;
        $customer = $this->getCustomer();
        set_time_limit(240);
        $row = $args;
        $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;
        $customer->importFromTextArray($row);

        if (!$customer->getData()) {
            return;
        }

        $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;
        try {
            $customer->save();
            $this->_customerId = $customer->getId();
            $customer->unsetData();
            $customer->cleanAllAddresses();
            $customer->unsetSubscription();
            $newMem = memory_get_usage(); $memory .= ', '.($newMem-$mem); $mem = $newMem;

        } catch (Exception $e) {
        }
        unset($row);
        return array('memory'=>$memory);
    }

    public function getCustomerId()
    {
        return $this->_customerId;
    }
}