<?php

class MageProfis_SkuSearch_Model_Observer
{

    /**
     * 
     * redirect to product view if sku matches 
     */
    public function redirectToProductPage($event)
    {
        $q = $this->getParam('q', false);
        $sql = $this->_getConnection()
                ->select()
                ->from($this->getTableName('catalog_product_entity'), 'entity_id')
                ->where('sku = ?', $q)
                ->limit(1)
        ;
        $id = (int) $this->_getConnection()->fetchOne($sql);
        if ($id)
        {
            $ids = array($id);
            $sql = $this->_getConnection()
                    ->select()
                    ->from($this->getTableName('catalog_product_relation'), 'parent_id')
                    ->Where('child_id = ?', $id)
                    ->limit(100)
            ;
            foreach ($this->_getConnection()->fetchCol($sql) as $_parent_id)
            {
                $ids[] = (int) $_parent_id;
            }
            $collection = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                    ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->addAttributeToFilter('visibility', array('in' => array(
                            Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                            Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG,
                            Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_SEARCH,
                )))
                    ->addUrlRewrite(0)
                    ->addAttributeToFilter('entity_id', array('in' => $ids))
                    //->addAttributeToSort('type_id', 'ASC') // get "configurable" before "simple"
                    ->setPage(1, 1)
            ;
            $collection->getSelect()->where("FIELD(type_id, 'simple', 'configurable', 'bundle')");
            $product = $collection->getFirstItem();
            ;
            /* @var $product Mage_Catalog_Model_Product */
            if ($product && $product->getId())
            {
                $this->getResponse()
                        ->clearHeaders()
                        ->setRedirect($product->getProductUrl(), 302)
                        ->sendResponse();
                exit;
            }
        }
    }

    /**
     * 
     * @return Mage_Core_Controller_Request_Http
     */
    protected function getRequest()
    {
        return Mage::app()->getRequest();
    }

    /**
     * 
     * @return Zend_Controller_Response_Http
     */
    protected function getResponse()
    {
        return Mage::app()->getResponse();
    }

    protected function getParam($key, $default = null)
    {
        return $this->getRequest()->getParam($key, $default);
    }

    /**
     * 
     * @return Mage_Core_Model_Resource
     */
    protected function _resource()
    {
        return Mage::getSingleton('core/resource');
    }

    /**
     * 
     * @param string $name
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getConnection($name = 'core_read')
    {
        return $this->_resource()->getConnection($name);
    }

    /**
     * Get resource table name, validated by db adapter
     *
     * @param   string|array $modelEntity
     * @return  string
     */
    protected function getTableName($modelEntity)
    {
        return $this->_resource()->getTableName($modelEntity);
    }

}
