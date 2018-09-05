<?php

/**
 * The module is used to bundle reviews in products that contain different configurations of the same product.
 * The controller is used to call the module. The module call is admin password protected.
 *
 * @category    KPiotrowicz
 * @package     KPiotrowicz_BundleReview
 * @author      Kamil Piotrowicz
 */

class KPiotrowicz_BundleReview_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        echo "<pre>";

        /** @var KPiotrowicz_BundleReview_Model_BundleReview $bundleReview */
        $bundleReview = Mage::getModel('bundlereview/bundleReview')->bundle();

        print_r($bundleReview);
    }
}