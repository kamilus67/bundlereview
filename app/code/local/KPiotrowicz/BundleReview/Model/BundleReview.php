<?php

/**
 * The module is used to bundle reviews in products that contain different configurations of the same product.
 *
 * @category    KPiotrowicz
 * @package     KPiotrowicz_BundleReview
 * @author      Kamil Piotrowicz
 */

class KPiotrowicz_BundleReview_Model_BundleReview
{
    /**
     * Combining product reviews
     * @return array
     */
    public function bundle() {
        /** @var KPiotrowicz_BundleReview_Helper_BundleReview $reviewsAndVotesCollection */
        $reviewsAndVotesCollection = Mage::helper("bundlereview/bundleReview")->getBundleReviews();

        /** @var KPiotrowicz_BundleReview_Helper_BundleReview $result */
        $result = Mage::helper("bundlereview/bundleReview")->setReview($reviewsAndVotesCollection);

        return $reviewsAndVotesCollection;
    }
}