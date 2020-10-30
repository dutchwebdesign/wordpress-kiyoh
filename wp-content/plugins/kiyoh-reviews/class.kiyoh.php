<?php

class Kiyoh
{
    const KIYOH_INVITE_URL = "/v1/invite/external";
    const KIYOH_COMPANY_REVIEW_URL = "/v1/publication/review/external/location/statistics";
    const KIYOH_PRODUCT_REVIEWS_URL = "/v1/publication/product/review/external";
    const KIYOH_PRODUCT_UPDATE_URL = "/v1/location/product/external/bulk";

    public static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }

    private static function init_hooks()
    {
        self::$initiated = true;

        add_action('update_kiyoh_catalog_crontab', array('Kiyoh', 'update_kiyoh_catalog'));
        add_action('fetch_company_review_data_crontab', array('Kiyoh', 'fetch_company_review_data'));
        add_action('woocommerce_order_status_changed', array('Kiyoh', 'invite_kiyoh_review'), 10, 3);

        add_action('admin_init', array('Kiyoh', 'kiyoh_register_settings'));
        add_action('admin_menu', array('Kiyoh', 'kiyoh_register_settings_page'));

        add_action('woocommerce_before_single_product', array('Kiyoh', 'before_product_load'));
        add_filter('comments_template', array('Kiyoh', 'comments_template_loader'), 99);

        add_action('wp_ajax_sync_products', array('Kiyoh', 'kiyoh_sync_products'));
    }

    public static function init_widgets()
    {
        add_action('widgets_init', array('Kiyoh', 'register_kiyoh_widget'));
        add_action('init', array('Kiyoh', 'load_plugin_textdomain_kiyoh'));
    }

    public static function register_kiyoh_widget()
    {
        register_widget('Kiyoh_Company_Widget');
    }

    public static function load_plugin_textdomain_kiyoh()
    {
        load_plugin_textdomain('kiyoh', false, basename(dirname(__FILE__)) . '/languages/');
    }

    public static function comments_template_loader($template)
    {
        if (get_post_type() !== 'product') {
            return $template;
        }

        $check_dirs = array(
            trailingslashit(get_stylesheet_directory()) . WC()->template_path(),
            trailingslashit(get_template_directory()) . WC()->template_path(),
            trailingslashit(get_stylesheet_directory()),
            trailingslashit(get_template_directory()),
            trailingslashit(KIYOH__PLUGIN_DIR) . 'templates/',
        );

        foreach ($check_dirs as $dir) {
            if (file_exists(trailingslashit($dir) . 'single-product-reviews.php')) {
                return trailingslashit($dir) . 'single-product-reviews.php';
            }
        }

        return null;
    }

    public static function before_product_load()
    {
        global $product;

        $kiyoh_reviews = self::get_product_review_data($product->get_id());
        if ($kiyoh_reviews) {
            $kiyoh_review_count = count($kiyoh_reviews);

            $rating = [];
            foreach ($kiyoh_reviews as $kiyoh_review) {
                if (isset($kiyoh_review["rating"])) {
                    $rating[] = $kiyoh_review["rating"];
                }
            }

            $avg_rating = (array_sum($rating) / count($rating)) / 2;
            $product->set_rating_counts(count($rating));
            $product->set_average_rating($avg_rating);

            $product->set_review_count($kiyoh_review_count);
        }
    }

    public static function kiyoh_register_settings()
    {
        add_option("kiyoh_reviews_enabled", "1");
        add_option("kiyoh_api_key", "da9deacf-c4b7-43e9-88e1-b4e8334a6330");
        add_option("kiyoh_location", "1063244");
        add_option("kiyoh_server", "https://www.kiyoh.com");
        add_option("kiyoh_order_state_trigger", "completed");
        add_option("kiyoh_delay", "0");
        add_option("kiyoh_email_lang", "nl");
        add_option("kiyoh_debug", "1");
        add_option("kiyoh_review_products", "1");

        register_setting('kiyoh_settings', 'kiyoh_reviews_enabled');
        register_setting('kiyoh_settings', 'kiyoh_api_key');
        register_setting('kiyoh_settings', 'kiyoh_location');
        register_setting('kiyoh_settings', 'kiyoh_server');
        register_setting('kiyoh_settings', 'kiyoh_order_state_trigger');
        register_setting('kiyoh_settings', 'kiyoh_delay');
        register_setting('kiyoh_settings', 'kiyoh_email_lang');
        register_setting('kiyoh_settings', 'kiyoh_debug');
        register_setting('kiyoh_settings', 'kiyoh_review_products');
    }

    public static function kiyoh_register_settings_page()
    {
        add_options_page(
            'Kiyoh reviews settings',
            'Kiyoh',
            'manage_options',
            'kiyoh',
            array('Kiyoh', 'kiyoh_settings_page')
        );
    }

    public static function kiyoh_settings_page()
    {
        ob_start();
        include(dirname(__FILE__) . '/templates/admin/kiyoh-settings.php');
        $output = ob_get_clean();

        echo $output;
    }

    public static function kiyoh_sync_products()
    {
        if (self::update_kiyoh_catalog()) {
            echo json_encode(array('status' => 'success'));
        }
        exit;
    }

    public static function invite_kiyoh_review($order_id, $prev_status, $status)
    {
        if (!get_option('kiyoh_reviews_enabled') || ($status !== get_option('kiyoh_order_state_trigger'))) {
            return false;
        }

        $order = new WC_Order($order_id);

        $invite_data = array(
            "location_id" => get_option('kiyoh_location'),
            "invite_email" => $order->get_billing_email(),
            "delay" => get_option('kiyoh_delay'),
            "first_name" => $order->get_billing_first_name(),
            "last_name" => $order->get_billing_last_name(),
            "ref_code" => md5(uniqid(rand(), true)),
            "language" => get_option('kiyoh_email_lang')
        );

        if (get_option('kiyoh_review_products')) {
            $products = [];
            $order_items = $order->get_items();
            $max_products = 5;
            $i = 0;
            foreach ($order_items as $key => $order_item) {
                if ($i >= $max_products) {
                    break;
                }
                $products[] = $order_item->get_product_id();
                $i++;
            }
            $invite_data = array_merge($invite_data, array("product_code" => $products));
        }

        $http_args = array(
            'body' => json_encode($invite_data),
            'headers' => array(
                "Content-Type" => "application/json",
                'X-Publication-Api-Token' => get_option('kiyoh_api_key'),
            ),
            'httpversion' => '1.0',
            'timeout' => 45,
        );

        try {
            $response_body = null;
            $response = wp_remote_post(self::get_api_url(self::KIYOH_INVITE_URL), $http_args);
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if (self::debug_enabled()) {
                if ($response_code == 200) {
                    error_log(sprintf("Kiyoh success(order %s): %s", $order_id, print_r($response_body, true)));
                } else {
                    if (isset($response_body['detailedError'])) {
                        error_log(
                            sprintf(
                                "Kiyoh error(order %s): %s",
                                $order_id,
                                print_r($response_body['detailedError'], true)
                            )
                        );
                    } else {
                        if (is_wp_error($response)) {
                            error_log(
                                sprintf(
                                    "Kiyoh error(order %s): %s",
                                    $order_id,
                                    print_r($response->get_error_message(), true)
                                )
                            );
                        } else {
                            error_log(sprintf("Kiyoh error(order %s): %s", $order_id, print_r($response_body, true)));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if (self::debug_enabled()) {
                error_log(sprintf("Kiyoh exception(order %s): %s", $order_id, print_r($response_body, true)));
            }
        }

        return null;
    }

    public static function get_company_review_template()
    {
        ob_start();
        include(dirname(__FILE__) . '/templates/company-review.php');

        return ob_get_clean();
    }

    public static function get_company_review_data()
    {
        return get_option("company_review_data");
    }

    public static function fetch_company_review_data()
    {
        $http_args = array(
            'body' => array('locationId' => get_option('kiyoh_location')),
            'headers' => array(
                "Content-Type" => "application/json",
                'X-Publication-Api-Token' => get_option('kiyoh_api_key'),
            ),
            'httpversion' => '1.0',
            'timeout' => 30
        );

        try {
            $response_body = null;
            $response = wp_remote_get(self::get_api_url(self::KIYOH_COMPANY_REVIEW_URL), $http_args);

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);

            if ($response_code == 200) {
                $company_review_data = $response_body;
                update_option("company_review_data", $company_review_data);
                if (self::debug_enabled()) {
                    error_log("Cron: Kiyoh company review data updated");
                }
                return $company_review_data;
            } elseif (self::debug_enabled()) {
                if (isset($response_body['detailedError'])) {
                    error_log(sprintf("Kiyoh error: %s", print_r($response_body['detailedError'], true)));
                } else {
                    error_log(sprintf("Kiyoh error: %s", print_r($response_body, true)));
                }
            }
        } catch (Exception $e) {
            if (self::debug_enabled()) {
                error_log(sprintf("Kiyoh exception: %s", print_r($response_body, true)));
            }
        }
        return false;
    }

    public static function get_product_review_data($product_id)
    {
        $cache_timestamp = get_post_meta($product_id, "kiyoh_reviews_timestamp", true);
        if (strtotime($cache_timestamp) <= strtotime('-1 hours')) {
            $product_review_data = self::fetch_product_review_data($product_id);
        } else {
            $product_review_data = get_post_meta($product_id, "kiyoh_reviews", true);
        }
        return $product_review_data;
    }

    private static function fetch_product_review_data($product_id)
    {
        $http_args = array(
            'body' => array('locationId' => get_option('kiyoh_location'), 'productCode' => $product_id),
            'headers' => array(
                "Content-Type" => "application/json",
                'X-Publication-Api-Token' => get_option('kiyoh_api_key'),
            ),
            'httpversion' => '1.0',
            'timeout' => 30
        );

        $response = wp_remote_get(self::get_api_url(self::KIYOH_PRODUCT_REVIEWS_URL), $http_args);

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code == 200) {
            update_post_meta($product_id, "kiyoh_reviews", $response_body['reviews']);
            update_post_meta($product_id, "kiyoh_reviews_timestamp", date('Y-m-d\TH:i:s'));
            if (self::debug_enabled()) {
                error_log(sprintf("Cron: product(%s) reviews updated", $product_id));
            }
            return $response_body['reviews'];
        } elseif (self::debug_enabled()) {
            if (isset($response_body['detailedError'])) {
                error_log(sprintf("Kiyoh error: %s", print_r($response_body['detailedError'], true)));
            } else {
                error_log(sprintf("Kiyoh error: %s", print_r($response_body, true)));
            }
        }

        return null;
    }

    public static function update_kiyoh_catalog()
    {
        $products = wc_get_products(array('status' => 'publish', 'limit' => -1));
        $img_placeholder = wc_placeholder_img_src('woocommerce_thumbnail');
        $product_data = [];

        foreach ($products as $product) {
            $image = ($image_id = $product->get_image_id()) ? wp_get_attachment_url($image_id) : $img_placeholder;

            $product_data[] = array(
                "location_id" => get_option('kiyoh_location'),
                "product_code" => $product->get_id(),
                "product_name" => $product->get_name(),
                "image_url" => $image,
                "source_url" => $product->get_permalink(),
                "active" => true
            );
        }

        foreach (array_chunk($product_data, 100) as $products) {
            self::push_products($products);
        }

        return true;
    }

    private static function push_products($products)
    {
        $http_args = array(
            'method' => 'PUT',
            'body' => json_encode(array("location_id" => get_option('kiyoh_location'), "products" => $products)),
            'headers' => array(
                "Content-Type" => "application/json",
                'X-Publication-Api-Token' => get_option('kiyoh_api_key'),
            ),
            'httpversion' => '1.0',
            'timeout' => 45,
        );

        try {
            $response_body = null;
            $response = wp_remote_post(self::get_api_url(self::KIYOH_PRODUCT_UPDATE_URL), $http_args);
            if (self::debug_enabled()) {
                $response_code = wp_remote_retrieve_response_code($response);
                $response_body = json_decode(wp_remote_retrieve_body($response), true);

                if ($response_code == 200) {
                    error_log("Cron: Kiyoh products synced");
                } else {
                    if (isset($response_body['detailedError'])) {
                        error_log(
                            sprintf("Kiyoh error products sync: %s", print_r($response_body['detailedError'], true))
                        );
                    } else {
                        if (is_wp_error($response)) {
                            error_log(
                                sprintf("Kiyoh products sync failed: %s", print_r($response->get_error_message(), true))
                            );
                        } else {
                            error_log(sprintf("Kiyoh products sync failed: %s", print_r($response_body, true)));
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if (self::debug_enabled()) {
                error_log(sprintf("Kiyoh products sync failed: %s", print_r($response_body, true)));
            }
        }
    }

    public static function get_email_lang_codes()
    {
        return [
            'en' => 'English',
            'nl' => 'Nederlands',
            'fi-FI' => 'Suomalainen',
            'fr' => 'Français',
            'be' => 'Vlaams',
            'de' => 'German',
            'hu' => 'Hungarian',
            'bg' => 'Bulgarian',
            'ro' => 'Romanian',
            'hr' => 'Croatian',
            'ja' => 'Japanese',
            'es-ES' => 'Spanish',
            'it' => 'Italian',
            'pt-PT' => 'Portuguese',
            'tr' => 'Turkish',
            'nn-NO' => 'Norwegian',
            'sv-SE' => 'Swedish',
            'da' => 'Danish',
            'pt-BR' => 'Brazilian Portuguese',
            'pl' => 'Polish',
            'sl' => 'Slovenian',
            'zh-CN' => 'Chinese',
            'ru' => 'Russian',
            'el' => 'Greek',
            'cs' => 'Czech',
            'et' => 'Estonian',
            'lt' => 'Lithuanian',
            'lv' => 'Latvian',
            'sk' => 'Sloviak'
        ];
    }

    private static function get_api_url($action)
    {
        return get_option('kiyoh_server') . $action;
    }

    private static function debug_enabled()
    {
        return get_option('kiyoh_debug');
    }

    public static function get_kiyoh_order_state_triggers()
    {
        return array("completed", "processing");
    }

    public static function plugin_activation()
    {
        self::fetch_company_review_data();
        self::update_kiyoh_catalog();

        if (!wp_next_scheduled('fetch_company_review_data_crontab')) {
            wp_schedule_event(time(), 'hourly', 'fetch_company_review_data_crontab');
        }
        if (!wp_next_scheduled('update_kiyoh_catalog_crontab')) {
            wp_schedule_event(time(), 'hourly', 'update_kiyoh_catalog_crontab'); //daily
        }
    }
}