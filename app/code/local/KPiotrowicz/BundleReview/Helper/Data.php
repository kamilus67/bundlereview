<?php

/**
 * The module is used to bundle reviews in products that contain different configurations of the same product.
 *
 * @category    KPiotrowicz
 * @package     KPiotrowicz_BundleReview
 * @author      Kamil Piotrowicz
 */

class KPiotrowicz_BundleReview_Helper_Data extends Mage_Core_Helper_Abstract
{
    /** @var string $controllerRequestUri */
    public $controllerRequestUri = "/bundlereview";

    /**
     * The return bundle skus
     * @return array
     */
    public function getSkus() {
        /** @var Mage $skus */
        $skus = Mage::getStoreConfig('bundlereview/bundlereview/skus');
        $lines = explode("\n", $skus);
        $csv = [];

        foreach($lines as $line)
            $csv[] = str_getcsv($line);

        return $csv;
    }

    /**
     * Searching other products SKU related by SKU
     * @param $sku
     * @return array/boolean
     */
    public function getOtherSkusBySku($sku) {
        /** @var KPiotrowicz_BundleReview_Helper_Data $skus */
        $skus = $this->getSkus();

        for($i = 0;$i <= count(current($skus));$i++) {
            $result = array_search($sku, array_column($skus, $i));

            if(is_bool($result) == FALSE) {
                return $skus[$result];
            }
        }

        return FALSE;
    }
}
	 