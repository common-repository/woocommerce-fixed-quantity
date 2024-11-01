<?php
if (!defined('ABSPATH'))
    exit;

define("WOOFIX_DISCOUNT_RAW", "woofix_price_raw_discount");
define("WOOFIX_DISCOUNT_FORMATTED", "woofix_price_formatted_discount");
define("WOOFIX_PRICE_RAW_BEFORE_DISCOUNT", "woofix_price_raw_before_discount");
define("WOOFIX_PRICE_RAW_AFTER_DISCOUNT", "woofix_price_raw_after_discount");
define("WOOFIX_PRICE_FORMATTED_BEFORE_DISCOUNT", "woofix_price_formatted_before_discount");
define("WOOFIX_PRICE_FORMATTED_AFTER_DISCOUNT", "woofix_price_formatted_after_discount");

if (!class_exists('WooAdminFixedQuantity')) {
    class WoofixUtility
    {
        /**
         * @param WC_Product | array $product
         * @return array|bool|mixed
         */
        public static function isFixedQtyPrice($product)
        {
            $current_user = wp_get_current_user();
            $current_user_roles = $current_user->roles;
            $default_role = get_option(WOOFIXOPT_DEFAULT_ROLE, WOOFIXCONF_DEFAULT_ROLE);

            $postId = self::getActualId($product);

            $returnValue = self::constructQtyData($postId, $current_user_roles);

            if (empty($returnValue)) {
                if (!in_array($default_role, $current_user_roles)) {
                    array_unshift($current_user_roles, $default_role);
                }
                $returnValue = self::constructQtyData($postId, $current_user_roles);
            }

            if (empty($returnValue))
                return false;

            $woofixData = array_unique($returnValue, SORT_REGULAR);
            usort($woofixData, function ($a, $b) {
                if ($a['woofix_qty'] == $b['woofix_qty'])
                    return 0;

                return ($a['woofix_qty'] < $b['woofix_qty']) ? -1 : 1;
            });
            $woofixData = apply_filters('woofix_sort_qty_data_to_show', $woofixData);
            return array('woofix' => $woofixData);
        }

        /**
         * @param WC_Product | array $product
         * @return int
         */
        public static function getActualId($product)
        {
            $productId = null;

            if (is_object($product)) {
                $productId = self::getProductId($product);
            } elseif (!empty($product['variation_id'])) {
                $productId = intval($product['variation_id']);
            } elseif (!empty($product['product_id'])) {
                $productId = intval($product['product_id']);
            }

            /** get original product_id if WPML installed */
            if (defined('WCML_VERSION') && function_exists('wpml_object_id_filter')) {
                global $woocommerce_wpml;
                $productId = $woocommerce_wpml->products->get_original_product_id($productId);
            }

            return $productId;
        }

        /**
         * @param $postId
         * @param $current_user_roles
         * @return array
         */
        private static function constructQtyData($postId, $current_user_roles)
        {
            $returnValue = array();

            $fixedQuantityText = get_post_meta($postId, '_woofix', true);
            $fixedQuantityData = json_decode(html_entity_decode($fixedQuantityText), true);
            if (!empty($fixedQuantityData['woofix'])) {

                foreach ($fixedQuantityData['woofix'] as $key => $value) {

                    if (is_numeric($key)) {
                        $returnValue[] = $value;
                        continue;
                    }

                    if (!in_array($key, $current_user_roles))
                        continue;

                    foreach ($value as $qty_data) {
                        if (empty($qty_data['woofix_qty']))
                            continue;

                        if (empty($qty_data['woofix_disc']) && empty($qty_data['woofix_price']))
                            continue;

                        // used for WPML multi currency
                        $qty_data['woofix_price'] = apply_filters('wcml_raw_price_amount', $qty_data['woofix_price']);
                        $returnValue[] = $qty_data;
                    }
                }
            }
            return $returnValue;
        }

        /**
         * @param WC_Product $product
         * @param int $qty
         * @return array
         */
        public static function calculatePrice($product, $qty)
        {
            $fixedPriceData = WoofixUtility::isFixedQtyPrice($product);
            if ($fixedPriceData !== false) {
                $discount = 0;
                foreach ($fixedPriceData['woofix'] as $disc) {
                    if ($disc['woofix_qty'] == $qty) {
                        $discount = $disc['woofix_disc'];
                    }
                }

	            $_product = is_callable(array($product, 'get_product')) ? $product->get_product() : $product;
                $rawPriceAfterDisc = $_product->get_price();
                $regularPrice = $_product->get_regular_price('');

                $formattedPriceAfterDisc = wc_price($rawPriceAfterDisc);
                $rawPriceBeforeDisc = ($discount < 100) ? ($rawPriceAfterDisc * 100) / (100 - $discount) : $regularPrice;
                $formattedPriceBeforeDisc = wc_price($rawPriceBeforeDisc);

                return array(
                    WOOFIX_DISCOUNT_RAW => $discount,
                    WOOFIX_DISCOUNT_FORMATTED => "$discount%",
                    WOOFIX_PRICE_RAW_BEFORE_DISCOUNT => $rawPriceBeforeDisc,
                    WOOFIX_PRICE_RAW_AFTER_DISCOUNT => $rawPriceAfterDisc,
                    WOOFIX_PRICE_FORMATTED_BEFORE_DISCOUNT => $formattedPriceBeforeDisc,
                    WOOFIX_PRICE_FORMATTED_AFTER_DISCOUNT => $formattedPriceAfterDisc,
                );
            } else {
                return array();
            }

        }

        public static function isDeprecated()
        {
            if (defined('WC_VERSION') && version_compare(WC_VERSION, '3.0.0', '<')) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * @param WC_Product $product
         * @return mixed
         */
        public static function getProductId($product)
        {
            if (self::isDeprecated()) {
                return $product->id;
            } else {
                return $product->get_id();
            }
        }
    }
}