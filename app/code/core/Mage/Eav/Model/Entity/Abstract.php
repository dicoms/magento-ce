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
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Entity/Attribute/Model - entity abstract
 *
 * @category   Mage
 * @package    Mage_Eav
 */
abstract class Mage_Eav_Model_Entity_Abstract
    extends Mage_Core_Model_Resource_Abstract
    implements Mage_Eav_Model_Entity_Interface
{

    /**
     * Read connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_read;

    /**
     * Write connection
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_write;

    /**
     * Entity type configuration
     *
     * @var Mage_Eav_Model_Entity_Type
     */
    protected $_type;

    /**
     * Attributes array by attribute id
     *
     * @var array
     */
    protected $_attributesById = array();

    /**
     * Attributes array by attribute name
     *
     * @var unknown_type
     */
    protected $_attributesByCode = array();

    /**
     * 2-dimentional array by table name and attribute name
     *
     * @var array
     */
    protected $_attributesByTable = array();

    /**
     * Attributes that are static fields in entity table
     *
     * @var array
     */
    protected $_staticAttributes = array();

    /**
     * Enter description here...
     *
     * @var string
     */
    protected $_entityTable;

    /**
     * Enter description here...
     *
     * @var string
     */
    protected $_entityIdField;

    /**
     * Enter description here...
     *
     * @var string
     */
    protected $_valueEntityIdField;

    /**
     * Enter description here...
     *
     * @var string
     */
    protected $_valueTablePrefix;

    /**
     * Enter description here...
     *
     * @var boolean
     */
    protected $_isPartialLoad = false;

    /**
     * Enter description here...
     *
     * @var boolean
     */
    protected $_isPartialSave = false;

    /**
     * Set connections for entity operations
     *
     * @param Zend_Db_Adapter_Abstract $read
     * @param Zend_Db_Adapter_Abstract $write
     * @return Mage_Eav_Model_Entity_Abstract
     */
    public function setConnection(Zend_Db_Adapter_Abstract $read, Zend_Db_Adapter_Abstract $write=null)
    {
        $this->_read = $read;
        $this->_write = $write ? $write : $read;
        return $this;
    }

    /**
     * Resource initialization
     */
    protected function _construct()
    {

    }

    /**
     * Retrieve connection for read data
     *
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _getReadAdapter()
    {
        return $this->_read;
    }

    /**
     * Retrieve connection for write data
     *
     * @return Zend_Db_Adapter_Abstract
     */
    protected function _getWriteAdapter()
    {
        return $this->_write;
    }

    /**
     * Retrieve read DB connection
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getReadConnection()
    {
        return $this->_getReadAdapter();
    }

    /**
     * Retrieve write DB connection
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public function getWriteConnection()
    {
        return $this->_getWriteAdapter();
    }

    /**
     * For compatibility with Mage_Core_Model_Abstract
     *
     * @return string
     */
    public function getIdFieldName()
    {
        return $this->_entityIdField;
    }

    /**
     * Enter description here...
     *
     * @param string $alias
     * @return string
     */
    public function getTable($alias)
    {
        return Mage::getSingleton('core/resource')->getTableName($alias);
    }

    /**
     * Set configuration for the entity
     *
     * Accepts config node or name of entity type
     *
     * @param string|Mage_Eav_Model_Entity_Type $type
     * @return Mage_Eav_Model_Entity_Abstract
     */
    public function setType($type)
    {
        $this->_type = Mage::getSingleton('eav/config')->getEntityType($type);
        $this->_afterSetConfig();
        return $this;
    }

    /**
     * Retrieve current entity config
     *
     * @return Mage_Eav_Model_Entity_Type
     */
    public function getEntityType()
    {
        if (empty($this->_type)) {
            throw Mage::exception('Mage_Eav', Mage::helper('eav')->__('Entity is not initialized'));
        }
        return $this->_type;
    }

    /**
     * Get entity type name
     *
     * @return string
     */
    public function getType()
    {
        return $this->getEntityType()->getEntityTypeCode();
    }

    /**
     * Get entity type id
     *
     * @return integer
     */
    public function getTypeId()
    {
        return (int)$this->getEntityType()->getEntityTypeId();
    }

    /**
     * Unset attributes
     *
     * If NULL or not supplied removes configuration of all attributes
     * If string - removes only one, if array - all specified
     *
     * @param array|string|null $attributes
     * @return Mage_Eav_Model_Entity_Abstract
     */
    public function unsetAttributes($attributes=null)
    {
        if (is_null($attributes)) {
            $this->_attributesByCode = array();
            $this->_attributesById = array();
            $this->_attributesByTable = array();
            return $this;
        }

        if (is_string($attributes)) {
            $attributes = array($attributes);
        }

        if (!is_array($attributes)) {
            throw Mage::exception('Mage_Eav', Mage::helper('eav')->__('Unknown parameter'));
        }

        foreach ($attributes as $attrCode) {
            if (!isset($this->_attributesByCode[$attrCode])) {
                continue;
            }

            $attr = $this->getAttribute($attrCode);
            unset($this->_attributesById[$attr->getId()]);
            unset($this->_attributesByTable[$attr->getBackend()->getTable()][$attrCode]);
            unset($this->_attributesByCode[$attrCode]);
        }

        return $this;
    }

    /**
     * Retrieve attribute instance by name, id or config node
     *
     * This will add the attribute configuration to entity's attributes cache
     *
     * If attribute is not found false is returned
     *
     * @param string|integer|Mage_Core_Model_Config_Element $attribute
     * @return boolean|Mage_Eav_Model_Entity_Attribute_Abstract
     */
    public function getAttribute($attribute)
    {
        if (is_numeric($attribute)) {
            $attributeId = $attribute;

            if (isset($this->_attributesById[$attributeId])) {
                return $this->_attributesById[$attributeId];
            }
            $attributeInstance = Mage::getSingleton('eav/config')->getAttribute($this->getEntityType(), $attributeId);
            if ($attributeInstance) {
                $attributeCode = $attributeInstance->getAttributeCode();
            }

        } elseif (is_string($attribute)) {

            $attributeCode = $attribute;

            if (isset($this->_attributesByCode[$attributeCode])) {
                return $this->_attributesByCode[$attributeCode];
            }
            $attributeInstance = Mage::getSingleton('eav/config')->getAttribute($this->getEntityType(), $attributeCode);

        } elseif ($attribute instanceof Mage_Eav_Model_Entity_Attribute_Abstract) {

            $attributeInstance = $attribute;
            $attributeCode = $attributeInstance->getAttributeCode();
            if (isset($this->_attributesByCode[$attributeCode])) {
                $this->_attributesByCode[$attributeCode]->setAttributeSetId(
                    $attribute->getAttributeSetId()
                );
                return $this->_attributesByCode[$attributeCode];
            }
        }

        if (empty($attributeInstance)
            || !($attributeInstance instanceof Mage_Eav_Model_Entity_Attribute_Abstract)
            || !$attributeInstance->getId()
            ) {
            return false;
        }

        $attribute = clone $attributeInstance;

        if (empty($attributeId)) {
            $attributeId = $attribute->getAttributeId();
        }

        if (!$attribute->getAttributeCode()) {
            $attribute->setAttributeCode($attributeCode);
        }
        if (!$attribute->getAttributeModel()) {
            $attribute->setAttributeModel($this->_getDefaultAttributeModel());
        }

        $this->addAttribute($attribute);

        return $attribute;
    }

    /**
     * Adding attribute to entity
     *
     * @param   Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @return  Mage_Eav_Model_Entity_Abstract
     */
    public function addAttribute(Mage_Eav_Model_Entity_Attribute_Abstract $attribute)
    {
        $attribute->setEntity($this);
        $attributeCode = $attribute->getAttributeCode();

        $this->_attributesByCode[$attributeCode] = $attribute;

        if ($attribute->getBackend()->isStatic()) {
            $this->_staticAttributes[$attributeCode] = $attribute;
        } else {
            $this->_attributesById[$attribute->getId()] = $attribute;

            $attributeTable = $attribute->getBackend()->getTable();
            $this->_attributesByTable[$attributeTable][$attributeCode] = $attribute;
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param boolean $flag
     * @return boolean
     */
    public function isPartialLoad($flag=null)
    {
        $result = $this->_isPartialLoad;
        if (!is_null($flag)) {
            $this->_isPartialLoad = $flag;
        }
        return $result;
    }

    /**
     * Enter description here...
     *
     * @param boolean $flag
     * @return boolean
     */
    public function isPartialSave($flag=null)
    {
        $result = $this->_isPartialSave;
        if (!is_null($flag)) {
            $this->_isPartialSave = $flag;
        }
        return $result;
    }

    /**
     * Retrieve configuration for all attributes
     *
     * @return Mage_Eav_Model_Entity_Attribute_Abstract
     */
    public function loadAllAttributes($object=null)
    {
        if (is_null($object)) {
            $attributeCodes = Mage::getSingleton('eav/config')->getEntityAttributeCodes($this->getEntityType());
            foreach ($attributeCodes as $code) {
            	$this->getAttribute($code);
            }
            return $this;
        }
        elseif($object->getAttributeSetId()) {
            $setId = $object->getAttributeSetId();
        }
        else {
            $setId = $this->getEntityType()->getDefaultAttributeSetId();
        }

        $attributes = $this->getEntityType()->getAttributeCollection($setId);
        $attributes->load();

        foreach ($attributes->getItems() as $attribute) {
            $this->getAttribute($attribute);
        }
        return $this;
    }

    /**
     * Walk through the attributes and run method with optional arguments
     *
     * Returns array with results for each attribute
     *
     * if $method is in format "part/method" will run method on specified part
     * for example: $this->walkAttributes('backend/validate');
     *
     * @param string $method
     * @param array $args
     * @param array $part attribute, backend, frontend, source
     * @return array
     */
    public function walkAttributes($partMethod, array $args=array())
    {
        $methodArr = explode('/', $partMethod);
        switch (sizeof($methodArr)) {
            case 1:
                $part = 'attribute';
                $method = $methodArr[0];
                break;

            case 2:
                $part = $methodArr[0];
                $method = $methodArr[1];
                break;
        }
        $results = array();
        foreach ($this->getAttributesByCode() as $attrCode=>$attribute) {
            switch ($part) {
                case 'attribute':
                    $instance = $attribute;
                    break;

                case 'backend':
                    $instance = $attribute->getBackend();
                    break;

                case 'frontend':
                    $instance = $attribute->getFrontend();
                    break;

                case 'source':
                    $instance = $attribute->getSource();
                    break;
            }
            $results[$attrCode] = call_user_func_array(array($instance, $method), $args);
        }
        return $results;
    }

    /**
     * Get attributes by name array
     *
     * @return array
     */
    public function getAttributesByCode()
    {
        return $this->_attributesByCode;
    }

    /**
     * Get attributes by id array
     *
     * @return array
     */
    public function getAttributesById()
    {
        return $this->_attributesById;
    }

    /**
     * Get attributes by table and name array
     *
     * @return array
     */
    public function getAttributesByTable()
    {
        return $this->_attributesByTable;
    }

    /**
     * Get entity table name
     *
     * @return string
     */
    public function getEntityTable()
    {
        if (empty($this->_entityTable)) {
            $table = $this->getEntityType()->getEntityTable();
            if (empty($table)) {
                $table = Mage_Eav_Model_Entity::DEFAULT_ENTITY_TABLE;
            }
            $this->_entityTable = Mage::getSingleton('core/resource')->getTableName($table);
        }
        return $this->_entityTable;
    }

    /**
     * Get entity id field name in entity table
     *
     * @return string
     */
    public function getEntityIdField()
    {
        if (empty($this->_entityIdField)) {
            $this->_entityIdField = $this->getEntityType()->getEntityIdField();
            if (empty($this->_entityIdField)) {
                $this->_entityIdField = Mage_Eav_Model_Entity::DEFAULT_ENTITY_ID_FIELD;
            }
        }
        return $this->_entityIdField;
    }

    /**
     * Get default entity id field name in attribute values tables
     *
     * @return string
     */
    public function getValueEntityIdField()
    {
        return $this->getEntityIdField();
    }

    /**
     * Get prefix for value tables
     *
     * @return string
     */
    public function getValueTablePrefix()
    {
        if (empty($this->_valueTablePrefix)) {
            $prefix = (string)$this->getEntityType()->getValueTablePrefix();
            if (!empty($prefix)) {
                $this->_valueTablePrefix = $prefix;
                /**
                 * entity type prefix include DB table name prefix
                 */
                //Mage::getSingleton('core/resource')->getTableName($prefix);
            } else {
                $this->_valueTablePrefix = $this->getEntityTable();
            }
        }
        return $this->_valueTablePrefix;
    }

    /**
     * Check whether the attribute is a real field in entity table
     *
     * @see Mage_Eav_Model_Entity_Abstract::getAttribute for $attribute format
     * @param integer|string|Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @return unknown
     */
    public function isAttributeStatic($attribute)
    {
        $attrInstance = $this->getAttribute($attribute);
        return $attrInstance && $attrInstance->getBackend()->isStatic();
    }

    /**
     * Validate all object's attributes against configuration
     *
     * @param Varien_Object $object
     * @return Varien_Object
     */
    public function validate($object)
    {
        $this->loadAllAttributes();
        $this->walkAttributes('backend/validate', array($object));

        return $this;
    }

    /**
     * Enter description here...
     *
     * @param Varien_Object $object
     * @return Mage_Eav_Model_Entity_Abstract
     */
    public function setNewIncrementId(Varien_Object $object)
    {
        if ($object->getIncrementId()) {
            return $this;
        }

        $incrementId = $this->getEntityType()->fetchNewIncrementId($object->getStoreId());

        if (false!==$incrementId) {
            $object->setIncrementId($incrementId);
        }

        return $this;
    }

    /**
     * Enter description here...
     *
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param Varien_Object $object
     * @return boolean
     */
    public function checkAttributeUniqueValue(Mage_Eav_Model_Entity_Attribute_Abstract $attribute, $object)
    {
        if ($attribute->getBackend()->getType()==='static') {
            $select = $this->_getWriteAdapter()->select()
                ->from($this->getEntityTable(), $this->getEntityIdField())
                ->where('entity_type_id=?', $this->getTypeId())
                ->where($attribute->getAttributeCode().'=?', $object->getData($attribute->getAttributeCode()));
        } else {
            $select = $this->_getWriteAdapter()->select()
                ->from($attribute->getBackend()->getTable(), $attribute->getBackend()->getEntityIdField())
                ->where('entity_type_id=?', $this->getTypeId())
                ->where('attribute_id=?', $attribute->getId())
                ->where('value=?', $object->getData($attribute->getAttributeCode()));
        }
        $data = $this->_getWriteAdapter()->fetchCol($select);

        if ($object->getId()) {
            if (isset($data[0])) {
                return $data[0] == $object->getId();
            }
            return true;
        }
        else {
            return !count($data);
        }
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getDefaultAttributeSourceModel()
    {
        return Mage_Eav_Model_Entity::DEFAULT_SOURCE_MODEL;
    }

    /**
     * Load entity's attributes into the object
     *
     * @param   Varien_Object $object
     * @param   integer $entityId
     * @param   array|null $attributes
     * @return  Mage_Eav_Model_Entity_Abstract
     */
    public function load($object, $entityId, $attributes=array())
    {
        /**
         * Load object base row data
         */
        $select = $this->_getLoadRowSelect($object, $entityId);
        $row = $this->_getReadAdapter()->fetchRow($select);
        //$object->setData($row);
        if (is_array($row)) {
            $object->addData($row);
        }

        if (empty($attributes)) {
            $this->loadAllAttributes($object);
        } else {
            foreach ($attributes as $attrCode) {
                $this->getAttribute($attrCode);
            }
        }

        /**
         * Load data for entity attributes
         */
        foreach ($this->getAttributesByTable() as $table=>$attributes) {
            $select = $this->_getLoadAttributesSelect($object, $table);
            $values = $this->_getReadAdapter()->fetchAll($select);

            foreach ($values as $valueRow) {
                $this->_setAttribteValue($object, $valueRow);
            }
        }

        $object->setOrigData();
        $this->_afterLoad($object);
        return $this;
    }

    /**
     * Retrieve select object for loading base entity row
     *
     * @param   Varien_Object $object
     * @param   mixed $rowId
     * @return  Zend_Db_Select
     */
    protected function _getLoadRowSelect($object, $rowId)
    {
        $select = $this->_read->select()
            ->from($this->getEntityTable())
            ->where($this->getEntityIdField()."=?", $rowId);

        return $select;
    }

    /**
     * Retrieve select object for loading entity attributes values
     *
     * @param   Varien_Object $object
     * @param   mixed $rowId
     * @return  Zend_Db_Select
     */
    protected function _getLoadAttributesSelect($object, $table)
    {
        $select = $this->_read->select()
            ->from($table)
            ->where($this->getEntityIdField() . '=?', $object->getId());
        return $select;
    }

    /**
     * Initialize attribute value for object
     *
     * @param   Varien_Object $object
     * @param   array $valueRow
     * @return  Mage_Eav_Model_Entity_Abstract
     */
    protected function _setAttribteValue($object, $valueRow)
    {
        if ($attribute = $this->getAttribute($valueRow['attribute_id'])) {
            $attributeCode = $attribute->getAttributeCode();
            $object->setData($attributeCode, $valueRow['value']);
            $attribute->getBackend()->setValueId($valueRow['value_id']);
        }
        return $this;
    }

    /**
     * Save entity's attributes into the object's resource
     *
     * @param   Varien_Object $object
     * @return  Mage_Eav_Model_Entity_Abstract
     */
    public function save(Varien_Object $object)
    {
        if ($object->isDeleted()) {
            return $this->delete($object);
        }

        if (!$this->isPartialSave()) {
            $this->loadAllAttributes($object);
        }

        if (!$object->getEntityTypeId()) {
            $object->setEntityTypeId($this->getTypeId());
        }

        $object->setParentId((int) $object->getParentId());

        $this->_beforeSave($object);
        $this->_processSaveData($this->_collectSaveData($object));
        $this->_afterSave($object);

        return $this;
    }

    protected function _getOrigObject($object)
    {
        $className  = get_class($object);
        $origObject = new $className();
        $origObject->setData(array());
        $this->load($origObject, $object->getData($this->getEntityIdField()));
        return $origObject;
    }

    /**
     * Prepare entity object data for save
     *
     * result array structure:
     * array (
     *  'newObject', 'entityRow', 'insert', 'update', 'delete'
     * )
     *
     * @param   Varien_Object $newObject
     * @return  array
     */
    protected function _collectSaveData($newObject)
    {
        $newData = $newObject->getData();
        $entityId = $newObject->getData($this->getEntityIdField());
        if (!empty($entityId)) {
            /**
             * get current data in db for this entity
             */
            /*$className  = get_class($newObject);
            $origObject = new $className();
            $origObject->setData(array());
            $this->load($origObject, $entityId);
            $origData = $origObject->getOrigData();*/
            $origData = $this->_getOrigObject($newObject)->getOrigData();

            /**
             * drop attributes that are unknown in new data
             * not needed after introduction of partial entity loading
             */
            foreach ($origData as $k=>$v) {
                if (!array_key_exists($k, $newData)) {
                    unset($origData[$k]);
                }
            }
        }

        foreach ($newData as $k=>$v) {
            /**
             * Check attribute information
             */
            if (is_numeric($k) || is_array($v)) {
                continue;
                throw Mage::exception('Mage_Eav', Mage::helper('eav')->__('Invalid data object key'));
            }

            $attribute = $this->getAttribute($k);
            if (empty($attribute)) {
                continue;
            }

            $attrId = $attribute->getAttributeId();

            /**
             * if attribute is static add to entity row and continue
             */
            if ($this->isAttributeStatic($k)) {
                $entityRow[$k] = $v;
                continue;
            }

            /**
             * Check comparability for attribute value
             */
            if (isset($origData[$k])) {
                if ($attribute->isValueEmpty($v)) {
                    $delete[$attribute->getBackend()->getTable()][] = array(
                        'attribute_id'  => $attrId,
                        'value_id'      => $attribute->getBackend()->getValueId()
                    );
                }
                elseif ($v!==$origData[$k]) {
                    $update[$attrId] = array(
                        'value_id' => $attribute->getBackend()->getValueId(),
                        'value'    => $v,
                    );
                }
            }
            elseif (!$attribute->isValueEmpty($v)) {
                $insert[$attrId] = $v;
            }
        }

        $result = compact('newObject', 'entityRow', 'insert', 'update', 'delete');
        return $result;
    }

    /**
     * Save object collected data
     *
     * @param   array $saveData array('newObject', 'entityRow', 'insert', 'update', 'delete')
     * @return  Mage_Eav_Model_Entity_Abstract
     */
    protected function _processSaveData($saveData)
    {
        extract($saveData);
        $insertEntity   = true;
        $entityIdField  = $this->getEntityIdField();
        $entityId       = $newObject->getId();
        $condition      = $this->_getWriteAdapter()->quoteInto("$entityIdField=?", $entityId);

        if (!empty($entityId)) {
            $select = $this->_getWriteAdapter()->select()
                ->from($this->getEntityTable(), $entityIdField)
                ->where($condition);
            if ($this->_getWriteAdapter()->fetchOne($select)) {
                $insertEntity = false;
            }
        }

        /**
         * Process base row
         */
        if ($insertEntity) {
            $this->_getWriteAdapter()->insert($this->getEntityTable(), $entityRow);
            $entityId = $this->_getWriteAdapter()->lastInsertId();
            $newObject->setId($entityId);
        } else {
            $this->_getWriteAdapter()->update($this->getEntityTable(), $entityRow, $condition);
        }

        /**
         * insert attribute values
         */
        if (!empty($insert)) {
            foreach ($insert as $attrId=>$value) {
                $attribute = $this->getAttribute($attrId);
                $this->_insertAttribute($newObject, $attribute, $value);
            }
        }

        /**
         * update attribute values
         */
        if (!empty($update)) {
            foreach ($update as $attrId=>$v) {
                $attribute = $this->getAttribute($attrId);
                $this->_updateAttribute($newObject, $attribute, $v['value_id'], $v['value']);
            }
        }

        /**
         * delete empty attribute values
         */
        if (!empty($delete)) {
            foreach ($delete as $table=>$values) {
                $this->_deleteAttributes($newObject, $table, $values);
            }
        }

        return $this;
    }

    /**
     * Insert entity attribute value
     *
     * @param   Varien_Object $object
     * @param   Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param   mixed $value
     * @return  Mage_Eav_Model_Entity_Abstract
     */
    protected function _insertAttribute($object, $attribute, $value)
    {
        $entityIdField = $attribute->getBackend()->getEntityIdField();
        $row = array(
            $entityIdField  => $object->getId(),
            'entity_type_id'=> $object->getEntityTypeId(),
            'attribute_id'  => $attribute->getId(),
            'value'         => $value,
        );
        $this->_getWriteAdapter()->insert($attribute->getBackend()->getTable(), $row);
        return $this;
    }

    /**
     * Update entity attribute value
     *
     * @param   Varien_Object $object
     * @param   Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param   mixed $valueId
     * @param   mixed $value
     * @return  Mage_Eav_Model_Entity_Abstract
     */
    protected function _updateAttribute($object, $attribute, $valueId, $value)
    {
        $this->_getWriteAdapter()->update($attribute->getBackend()->getTable(),
            array('value'=>$value),
            'value_id='.(int)$valueId
        );
        return $this;
    }

    /**
     * Delete entity attribute values
     *
     * @param   Varien_Object $object
     * @param   string $table
     * @param   array $info
     * @return  Varien_Object
     */
    protected function _deleteAttributes($object, $table, $info)
    {
        $valueIds = array();
        foreach ($info as $itemData) {
            $valueIds[] = $itemData['value_id'];
        }
        if (!empty($valueIds)) {
            $condition = $this->_getWriteAdapter()->quoteInto('value_id IN (?)', $valueIds);
            $this->_getWriteAdapter()->delete($table, $condition);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param Varien_Object $object
     * @param string $attributeCode
     * @return Mage_Eav_Model_Entity_Abstract
     */
    public function saveAttribute(Varien_Object $object, $attributeCode)
    {
        $attribute = $this->getAttribute($attributeCode);
        $backend = $attribute->getBackend();
        $table = $backend->getTable();
        $entity = $attribute->getEntity();
        $entityIdField = $entity->getEntityIdField();

        $row = array(
            'entity_type_id' => $entity->getTypeId(),
            'attribute_id' => $attribute->getId(),
            $entityIdField=> $object->getData($entityIdField),
        );

        $newValue = $object->getData($attributeCode);
        if ($attribute->isValueEmpty($newValue)) {
            $newValue = null;
        }

        $whereArr = array();
        foreach ($row as $field => $value) {
            $whereArr[] = $this->_read->quoteInto("$field=?", $value);
        }
        $where = '('.join(') AND (', $whereArr).')';

        $this->_getWriteAdapter()->beginTransaction();

        try {
            $select = $this->_getWriteAdapter()->select()
                ->from($table, 'value_id')
                ->where($where);
            $origValueId = $this->_getWriteAdapter()->fetchOne($select);

            if ($origValueId === false && !is_null($newValue)) {
                $this->_insertAttribute($object, $attribute, $newValue);
                $backend->setValueId($this->_getWriteAdapter()->lastInsertId());
            } elseif ($origValueId !== false && !is_null($newValue)) {
                $this->_updateAttribute($object, $attribute, $origValueId, $newValue);
            } elseif ($origValueId !== false && is_null($newValue)) {
                $this->_getWriteAdapter()->delete($table, $where);
            }
            $this->_getWriteAdapter()->commit();
        } catch (Exception $e) {
            $this->_getWriteAdapter()->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * Delete entity using current object's data
     *
     * @return Mage_Eav_Model_Entity_Abstract
     */
    public function delete($object)
    {
        if (is_numeric($object)) {
            $id = (int)$object;
        } elseif ($object instanceof Varien_Object) {
            $id = (int)$object->getId();
        }

        $this->_beforeDelete($object);

        try {
            $this->_getWriteAdapter()->delete($this->getEntityTable(), $this->getEntityIdField()."=".$id);
            $this->loadAllAttributes();
            foreach ($this->getAttributesByTable() as $table=>$attributes) {
                $this->_getWriteAdapter()->delete($table, $this->getEntityIdField()."=".$id);
            }
        } catch (Exception $e) {
            throw $e;
        }

        $this->_afterDelete($object);
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param Varien_Object $object
     */
    protected function _afterLoad(Varien_Object $object)
    {
        $this->walkAttributes('backend/afterLoad', array($object));
    }

    /**
     * Enter description here...
     *
     * @param Varien_Object $object
     */
    protected function _beforeSave(Varien_Object $object)
    {
        $this->walkAttributes('backend/beforeSave', array($object));
    }

    /**
     * Enter description here...
     *
     * @param Varien_Object $object
     */
    protected function _afterSave(Varien_Object $object)
    {
        $this->walkAttributes('backend/afterSave', array($object));
    }

    /**
     * Enter description here...
     *
     * @param Varien_Object $object
     */
    protected function _beforeDelete(Varien_Object $object)
    {
        $this->walkAttributes('backend/beforeDelete', array($object));
    }

    /**
     * Enter description here...
     *
     * @param Varien_Object $object
     */
    protected function _afterDelete(Varien_Object $object)
    {
        $this->walkAttributes('backend/afterDelete', array($object));
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    protected function _getDefaultAttributeModel()
    {
        return Mage_Eav_Model_Entity::DEFAULT_ATTRIBUTE_MODEL;
    }

    /**
     * Enter description here...
     *
     * @return array
     */
    protected function _getDefaultAttributes()
    {
        return array('entity_type_id', 'attribute_set_id', 'created_at', 'updated_at', 'parent_id', 'increment_id');
    }

    /**
     * Enter description here...
     *
     */
    protected function _afterSetConfig()
    {
        //return;
        $defaultAttributes = $this->_getDefaultAttributes();
        $defaultAttributes[] = $this->getEntityIdField();

        $attributes = $this->getAttributesByCode();
        foreach ($defaultAttributes as $attr) {
            if (empty($attributes[$attr]) && !$this->getAttribute($attr)) {
                $attribute = Mage::getModel($this->getEntityType()->getAttributeModel());
                $attribute->setAttributeCode($attr)
                    ->setBackendType('static')
                    ->setEntityType($this->getEntityType())
                    ->setEntityTypeId($this->getEntityType()->getId());
                $this->addAttribute($attribute);
            }
        }
    }

}
