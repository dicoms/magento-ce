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
 * @package    Mage_Review
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Review resource model
 *
 * @category    Mage
 * @package     Mage_Review
 */
class Mage_Review_Model_Mysql4_Review extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_reviewTable;
    protected $_reviewDetailTable;
    protected $_reviewStatusTable;
    protected $_reviewEntityTable;
    protected $_reviewStoreTable;

    protected function _construct()
    {
        $this->_init('review/review', 'review_id');
        $this->_reviewTable         = $this->getTable('review/review');
        $this->_reviewDetailTable   = $this->getTable('review/review_detail');
        $this->_reviewStatusTable   = $this->getTable('review/review_status');
        $this->_reviewEntityTable   = $this->getTable('review/review_entity');
        $this->_reviewStoreTable    = $this->getTable('review/review_store');
        $this->_aggregateTable      = $this->getTable('review/review_aggregate');
    }

    /**
     * Retrieve select object for load object data
     *
     * @param   string $field
     * @param   mixed $value
     * @return  Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
           $select = parent::_getLoadSelect($field, $value, $object);
        $select->join($this->_reviewDetailTable, $this->getMainTable().".review_id = {$this->_reviewDetailTable}.review_id");
        return $select;
    }

    /**
     * Perform actions before object save
     *
     * @param Varien_Object $object
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        if (!$object->getId()) {
            $object->setCreatedAt(Mage::getSingleton('core/date')->gmtDate());
        }
        if ($object->hasData('stores') && is_array($object->getStores())) {
            $stores = $object->getStores();
            $stores[] = 0;
            $object->setStores($stores);
        } elseif ($object->hasData('stores')) {
            $object->setStores(array($object->getStores(), 0));
        }
        return $this;
    }

    /**
     * Perform actions after object save
     *
     * @param Varien_Object $object
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        /**
         * save detale
         */
        $detail = array(
            'title'     => $object->getTitle(),
            'detail'    => $object->getDetail(),
            'nickname'  => $object->getNickname(),
        );
        $select = $this->_getWriteAdapter()->select()
            ->from($this->_reviewDetailTable, 'detail_id')
            ->where('review_id=?', $object->getId());
        $detailId = $this->_getWriteAdapter()->fetchOne($select);

        if ($detailId) {
            $this->_getWriteAdapter()->update($this->_reviewDetailTable,
                $detail,
                'detail_id='.$detailId
            );
        }
        else {
            $detail['store_id']   = $object->getStoreId();
            $detail['customer_id']= $object->getCustomerId();
            $detail['review_id']  = $object->getId();
            $this->_getWriteAdapter()->insert($this->_reviewDetailTable, $detail);
        }


        /**
         * save stores
         */
        $stores = $object->getStores();
        if(!empty($stores)) {
            $condition = $this->_getWriteAdapter()->quoteInto('review_id = ?', $object->getId());
            $this->_getWriteAdapter()->delete($this->_reviewStoreTable, $condition);

            $insertedStoreIds = array();
            foreach ($stores as $storeId) {
                if (in_array($storeId, $insertedStoreIds)) {
                    continue;
                }

                $insertedStoreIds[] = $storeId;
                $storeInsert = array(
                    'store_id' => $storeId,
                    'review_id'=> $object->getId()
                );
                $this->_getWriteAdapter()->insert($this->_reviewStoreTable, $storeInsert);
            }
        }
        return $this;
    }

    /**
     * Perform actions after object load
     *
     * @param Varien_Object $object
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->_reviewStoreTable, array('store_id'))
            ->where('review_id=?', $object->getId());
        $stores = $this->_getReadAdapter()->fetchCol($select);
        $object->setStores($stores);
        return $this;
    }

    /**
     * Perform actions after object delete
     *
     * @param Varien_Object $object
     */
    protected function _afterDelete(Mage_Core_Model_Abstract $object)
    {
        $this->aggregate($object);
        return $this;
    }

    public function getTotalReviews($entityPkValue, $approvedOnly=false, $storeId=0)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->_reviewTable, "COUNT(*)")
            ->where("{$this->_reviewTable}.entity_pk_value = ?", $entityPkValue);

        if($storeId > 0) {
            $select->join(array('store'=>$this->_reviewStoreTable),
                $this->_reviewTable.'.review_id=store.review_id AND store.store_id=' . (int)$storeId, array());
        }
        if( $approvedOnly ) {
            $select->where("{$this->_reviewTable}.status_id = ?", 1);
        }
        return $this->_getReadAdapter()->fetchOne($select);
    }

    public function aggregate($object)
    {
        if( !$object->getEntityPkValue() && $object->getId() ) {
            $object->load($object->getReviewId());
        }

        $ratingModel    = Mage::getModel('rating/rating');
        $ratingSummaries= $ratingModel->getEntitySummary($object->getEntityPkValue(), false);

        $nonDelete = array();
        foreach($ratingSummaries as $ratingSummaryObject) {
            if( $ratingSummaryObject->getCount() ) {
                $ratingSummary = round($ratingSummaryObject->getSum() / $ratingSummaryObject->getCount());
            } else {
                $ratingSummary = $ratingSummaryObject->getSum();
            }

            $reviewsCount = $this->getTotalReviews($object->getEntityPkValue(), true, $ratingSummaryObject->getStoreId());
            $select = $this->_getReadAdapter()->select()
                ->from($this->_aggregateTable)
                ->where("{$this->_aggregateTable}.entity_pk_value = ?", $object->getEntityPkValue())
                ->where("{$this->_aggregateTable}.entity_type = ?", $object->getEntityId())
                ->where("{$this->_aggregateTable}.store_id = ?", $ratingSummaryObject->getStoreId());

            $oldData = $this->_getReadAdapter()->fetchRow($select);

            $data = new Varien_Object();

            $data->setReviewsCount($reviewsCount)
                ->setEntityPkValue($object->getEntityPkValue())
                ->setEntityType($object->getEntityId())
                ->setRatingSummary( ($ratingSummary > 0) ? $ratingSummary : 0 )
                ->setStoreId($ratingSummaryObject->getStoreId());

            $this->_getWriteAdapter()->beginTransaction();
            try {
                if( $oldData['primary_id'] > 0 ) {
                    $condition = $this->_getWriteAdapter()->quoteInto("{$this->_aggregateTable}.primary_id = ?", $oldData['primary_id']);
                    $this->_getWriteAdapter()->update($this->_aggregateTable, $data->getData(), $condition);
                } else {
                    $this->_getWriteAdapter()->insert($this->_aggregateTable, $data->getData());
                }
                $this->_getWriteAdapter()->commit();
            } catch (Exception $e) {
                $this->_getWriteAdapter()->rollBack();
            }
        }
    }
}