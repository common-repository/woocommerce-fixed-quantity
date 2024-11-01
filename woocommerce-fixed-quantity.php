<?php
/**
 * Plugin Name: WooCommerce Fixed Quantity
 * Plugin URI: http://habibillah.github.io/woocommerce-fixed-quantity/
 * Description: Customize price based on fixed quantity.
 * Author: Habibillah
 * Author URI: https://www.kalicode.com/blog/author/habibillah/
 * Version: 1.2.8
 * Stable tag: 1.2.7
 * Tested up to: 5.1
 * Requires at least: 3.0.1
 * WC requires at least: 2.2
 * WC tested up to: 3.4
 * Text Domain: woofix
 * Domain Path: /languages/
 */

if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/vendor/symfony/polyfill-mbstring/bootstrap.php";

if (!function_exists('mb_convert_encoding')) {
    function mb_convert_encoding($s, $to, $from = null)
    {
        return \Symfony\Polyfill\Mbstring\Mbstring::mb_convert_encoding($s, $to, $from);
    }
}

$active_plugins = get_option('active_plugins', array());
$active_network_plugins = array_keys(get_site_option('active_sitewide_plugins', array()));
$active_plugins = array_merge($active_plugins, $active_network_plugins);
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', $active_plugins)))
    return;

if (!class_exists('WooFixedQuantity')) {

    define("WOOFIXCONF_QTY_DESC", "{qty} items @{price} {total}");
    define("WOOFIXCONF_SHOW_DISC", "yes");
    define("WOOFIXCONF_SHOW_STOCK", "yes");
    define("WOOFIXCONF_CHECK_STOCK", "no");
    define("WOOFIXCONF_DEFAULT_ROLE", "customer");
    define("WOOFIXCONF_ADD_TO_CART_AS_NEW", "no");

    define("WOOFIXOPT_QTY_DESC", "woofix_qty_desc");
    define("WOOFIXOPT_SHOW_DISC", "woofix_show_disc");
    define("WOOFIXOPT_SHOW_STOCK", "woofix_show_stock");
    define("WOOFIXOPT_CHECK_STOCK", "woofix_check_stock");
    define("WOOFIXOPT_DEFAULT_ROLE", "woofix_default_role");
    define("WOOFIXOPT_AVAILABLE_ROLES", "woofix_available_roles");
    define("WOOFIXOPT_ADD_TO_CART_AS_NEW", "woofix_add_as_new");


    class WooFixedQuantity
    {
        public $woo_admin_fixed_price;
        public $woo_client_fixed_price;

        public function __construct()
        {
            add_action('init', array($this, 'load_init'));
            add_action('admin_enqueue_scripts', array($this, 'load_admin_scripts'));
            add_action('wp_enqueue_scripts', array($this, 'load_public_scripts'));
        }

        public function load_init()
        {
            load_plugin_textdomain('woofix', false, dirname(plugin_basename(__FILE__)) . '/languages/');

            require_once('classes/woofix-utility.php');
            require_once('classes/admin-fixed-quantity-price.php');
            require_once('classes/client-fixed-quantity-price.php');

            if (is_admin()) {
                $this->woo_admin_fixed_price = new WooAdminFixedQuantity(__FILE__);
            }

            $this->woo_client_fixed_price = new WooClientFixedQuantity(__FILE__);
        }

        public function load_admin_scripts()
        {
            if (!$screen = get_current_screen()) {
                return;
            }

            if (!in_array($screen->id, wc_get_screen_ids())) {
                return;
            }

            $params = array(
                'decimal_point' => wc_get_price_decimal_separator(),
                'num_decimals' => wc_get_price_decimals()
            );

            if (!wp_script_is('underscore', 'registered')) {
                wp_register_script('underscore', plugins_url('/assets/js/lodash.min.js', __FILE__), array(), '1.8.3');
            }

            wp_register_script('woofix_serializer',
                plugins_url('/assets/js/woofix-serializer.js', __FILE__),
                array('jquery', 'underscore', 'woocommerce_admin'),
                '1.2.7');
            wp_register_script('woofix_admin_js',
                plugins_url('/assets/js/admin-woofix.js', __FILE__),
                array('jquery', 'underscore', 'postbox', 'woocommerce_admin', 'woofix_serializer'),
                '1.2.7');

            wp_localize_script('woofix_admin_js', 'woofix_admin', $params);

            wp_enqueue_script('underscore');
            wp_enqueue_script('woofix_serializer');
            wp_enqueue_script('woofix_admin_js');

            wp_enqueue_style('woofix_admin_css', plugins_url('/assets/css/admin-woofix.css', __FILE__));
        }

        public function load_public_scripts()
        {
            wp_enqueue_script('woofix_public_js', plugins_url('/assets/js/woofix.js', __FILE__), array('jquery'));
        }
    }
}

new WooFixedQuantity();

