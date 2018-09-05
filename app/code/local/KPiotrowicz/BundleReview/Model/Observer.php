<?php

/**
 * The module is used to bundle reviews in products that contain different configurations of the same product.
 *
 * @category    KPiotrowicz
 * @package     KPiotrowicz_BundleReview
 * @author      Kamil Piotrowicz
 */

class KPiotrowicz_BundleReview_Model_Observer
{
    /**
     * Adding review to other products
     * @param Varien_Event_Observer $observer
     */
    public function bundleReview(Varien_Event_Observer $observer) {
        /** @var KPiotrowicz_BundleReview_Helper_Data $requestUri */
        $requestUri = Mage::helper('bundlereview/data')->controllerRequestUri;

        if($_SERVER['REQUEST_URI'] == $requestUri) return 0;

        if(Mage::registry('customUpdate')) return 0;
        Mage::register('customUpdate', true);
        
        /** @var Mage_Review_Model_Review $review */
        $review = $observer->getDataObject();
        $data = $review->getData();

        if($data['ratings'] != NULL) {
            /** @var Mage_Catalog_Model_Product $_product */
            $_product = Mage::getModel('catalog/product')->load($review->getEntityPkValue());
        } else {
            /** @var Mage_Catalog_Model_Product $_product */
            $_product = Mage::getModel('catalog/product')->load($data['entity_pk_value']);
        }

        /** @var KPiotrowicz_BundleReview_Helper_Data $result */
        $result = Mage::helper("bundlereview/data")->getOtherSkusBySku($_product->getSku());

        // Delete actual SKU
        unset($result[(array_search($_product->getSku(), $result))]);

        $array = [
            [
                [
                    "sku" => array_filter($result),
                    "review_id" => $review->getId(),
                    "created_at" => $review->getCreatedAt(),
                    "entity_id" => $review->getEntityId(),
                    "entity_pk_value" => $review->getEntityPkValue(),
                    "status_id" => $review->getStatusId(),
                    "customer_email" => $review->getCustomerEmail(),
                    "was_sent" => $review->getWasSent(),
                    "order_id" => $review->getOrderId(),
                    "product_id" => $review->getProductId(),
                    "detail_id" => $review->getDetailId(),
                    "title" => $review->getTitle(),
                    "detail" => $review->getDetail(),
                    "nickname" => $review->getNickname(),
                    "customer_id" => $review->getCustomerId(),
                    "option_id" => $data['ratings'][(max(array_keys($data['ratings'])))],
                    "rating_id" => max(array_keys($data['ratings']))
                ]
            ]
        ];

        if($data['ratings'] == NULL) {
            $array[0][0]['option_id'] = $_POST['ratings'][(key($_POST['ratings']))];
            $array[0][0]['rating_id'] = key($_POST['ratings']);
        }

        /** @var KPiotrowicz_BundleReview_Helper_BundleReview $result */
        $result_ = Mage::helper("bundlereview/bundleReview")->setReview($array, FALSE);
    }
}