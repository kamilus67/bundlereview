<?php

/**
 * The module is used to bundle reviews in products that contain different configurations of the same product.
 *
 * @category    KPiotrowicz
 * @package     KPiotrowicz_BundleReview
 * @author      Kamil Piotrowicz
 */

class KPiotrowicz_BundleReview_Helper_BundleReview extends KPiotrowicz_BundleReview_Helper_Data
{
    /**
     * The return final reviews collection for product
     * @return array
     */
    public function getBundleReviews() {
        /** @var KPiotrowicz_BundleReview_Helper_Data $skus */
        $skus = $this->getSkus();

        $reviewsArray = [];
        $i = 0;
        foreach($skus as $bundleSku) {
            foreach($bundleSku as $sku) {
                if($sku == NULL) continue;
                try {
                    /** @var Mage_Catalog_Model_Product $productId */
                    $productId = Mage::getModel('catalog/product')->loadbyAttribute('sku', $sku)->getId();
                } catch (Throwable $e) {
                    continue;
                }

                foreach($this->getReview($productId) as $review) {
                    /** @var KPiotrowicz_BundleReview_Helper_BundleReview $ratingVote */
                    $ratingVote = $this->getRatingVote($review['review_id']);

                    if(count($ratingVote) > 0) {
                        $reviewsArray[$i][] = array_merge(["sku" => array_filter($bundleSku)], $review, $this->getRatingVote($review['review_id'])[0]);
                    } else {
                        $reviewsArray[$i][] = array_merge(["sku" => array_filter($bundleSku)], $review);
                    }
                }

                $i++;
            }
        }

        return $reviewsArray;
    }

    /**
     * The return reviews by product id
     * @param $productId
     * @param $isGetData
     * @return array
     */
    public function getReview($productId, $isGetData = TRUE) {
        try {
            /** @var Mage_Review_Model_Review $reviews */
            $reviews = Mage::getModel('review/review')->getCollection()
                ->addStoreFilter(Mage::app()->getStore()->getId())
                ->addEntityFilter('product', $productId)
                ->setDateOrder()
                ->addRateVotes();
        } catch (Throwable $e) {
            return [];
        }

        if($isGetData == TRUE) {
            return $reviews->getData();
        }

        return $reviews;
    }

    /**
     * The return rating vote by review id
     * @param $reviewId
     * @return array
     */
    public function getRatingVote($reviewId) {
        try {
            /** @var Mage_Rating_Model_Rating_Option_Vote $votesCollection */
            $votesCollection = Mage::getModel('rating/rating_option_vote')
                ->getResourceCollection()
                ->setReviewFilter($reviewId)
                ->setStoreFilter(Mage::app()->getStore()->getId())
                ->load();
        } catch (Throwable $e) {
            return [];
        }

        return $votesCollection->getData();
    }

    /**
     * Adding reviews and votes to the products
     * @param $array
     * @param $isDeleteReview
     * @return boolean
     */
    public function setReview($array, $isDeleteReview = TRUE) {
        foreach($array as $bundle) {
            foreach($bundle as $review) {
                foreach($review['sku'] as $sku) {
                    try {
                        /** @var Mage_Catalog_Model_Product $_product */
                        $_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
                    } catch (Throwable $e) {
                        Mage::log("Not found product SKU $sku", null, 'bundlereview.log');
                        continue;
                    }

                    if($isDeleteReview == TRUE) {
                        /** @var KPiotrowicz_BundleReview_Helper_BundleReview $deleteResult */
                        $deleteResult = $this->deleteReview($_product->getId());
                    }

                    /** @var KPiotrowicz_BundleReview_Helper_BundleReview $updateReviews */
                    $updateReviews = $this->checkUpdateAndGetReviews($review);
                    if($updateReviews != FALSE && $_SERVER['REQUEST_URI'] != $this->controllerRequestUri && Mage::registry('newReview') != TRUE) {
                        /** @var KPiotrowicz_BundleReview_Helper_BundleReview $updateResult */
                        $updateResult = $this->updateReview($review, $updateReviews);
                    } else {
                        if(Mage::registry('newReview') != TRUE) Mage::register('newReview', TRUE);

                        /** @var Mage_Review_Model_Review $_review */
                        $_review = Mage::getModel('review/review')->setData($this->_cropReviewData([
                            "productid" => $_product->getId(),
                            "nickname" => $review['nickname'],
                            "ratings" => [4 => $review['option_id']],
                            "validate_rating" => $review['option_id'],
                            "detail" => $review['detail'],
                            "title" => $review['title']
                        ]));

                        /** @var Mage_Review_Model_Review $validate */
                        $validate = $_review->validate();
                        if ($validate === true) {
                            $_review->setEntityId($review['entity_id'])
                                ->setEntityPkValue($_product->getId())
                                ->setStatusId($review['status_id'])
                                ->setCustomerId($review['customer_id'])
                                ->setStoreId(Mage::app()->getStore()->getId())
                                ->setStores(array(Mage::app()->getStore()->getId()))
                                ->save();

                            if(!empty($review['option_id'])) {
                                /** @var Mage_Rating_Model_Rating $_rating */
                                $_rating = Mage::getModel('rating/rating')
                                    ->setRatingId($review['rating_id'])
                                    ->setReviewId($_review->getId())
                                    ->setCustomerId($review['customer_id'])
                                    ->addOptionVote($review['option_id'], $_product->getId());
                            }

                            $_review->aggregate();
                        } else {
                            Mage::log(print_r($review->validate(), true), null, 'bundlereview.log');
                        }
                    }
                }
            }
        }

        return TRUE;
    }

    /**
     * Removing reviews from products
     * @param $productId
     * @return boolean
     */
    public function deleteReview($productId) {
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        /** @var KPiotrowicz_BundleReview_Helper_BundleReview $reviews */
        $reviews = $this->getReview($productId, FALSE);

        foreach($reviews as $review) {
            $review->delete();
        }

        return TRUE;
    }

    /**
     * Crops POST values
     * Method copied from Mage_Review_ProductController
     * @param array $reviewData
     * @return array
     */
    public function _cropReviewData(array $reviewData)
    {
        $croppedValues = array();
        $allowedKeys = array_fill_keys(array('detail', 'title', 'nickname'), true);

        foreach ($reviewData as $key => $value) {
            if (isset($allowedKeys[$key])) {
                $croppedValues[$key] = $value;
            }
        }

        return $croppedValues;
    }

    /**
     * Checking if the review is updated and return data
     * @param $review
     * @return boolean/array
     */
    public function checkUpdateAndGetReviews($review) {
        /** @var Mage_Review_Model_Review $reviewsCollection */
        $reviewsCollection = Mage::getModel('review/review')->getCollection()
            ->addFilter('nickname', $review['nickname'])
            ->addFilter('detail', $review['detail'])
            ->getData();

        if(count($reviewsCollection) > 0) {
            return $reviewsCollection;
        } else {
            return FALSE;
        }
    }

    /**
     * Update bundle reviews
     * @param $review
     * @param $reviewsToUpdate
     * @return boolean
     */
    public function updateReview($review, $reviewsToUpdate) {
        foreach($reviewsToUpdate as $reviewToUpdate) {
            Mage::getModel('review/review')->load($reviewToUpdate['review_id'])
                ->setDetail($review['detail'])
                ->setNickname($review['nickname'])
                ->setStatusId($review['status_id'])
                ->save();
        }

        return TRUE;
    }
}