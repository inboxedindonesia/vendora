<?php
namespace Opencart\Catalog\Controller\Cron;
class AutoDiscount extends \Opencart\System\Engine\Controller {
    public function index(int $cron_id, string $code, string $cycle, string $date_added, string $date_modified): void {
        
        // 1. Find products that have auto-discount days set
        $sql = "SELECT `product_id`, `date_expired`, `price`, `auto_discount_days` FROM `" . DB_PREFIX . "product` WHERE `auto_discount_days` > 0 AND `date_expired` IS NOT NULL";
        
        $product_query = $this->db->query($sql);

        if (!$product_query->num_rows) {
            echo "No products found with auto-discount configured." . PHP_EOL;
            return;
        }
        
        $products_to_discount = [];
        $today = date('Y-m-d');

        // 2. For each product, check if it's within the discount window
        foreach ($product_query->rows as $product) {
            $days_before_expiry = (int)$product['auto_discount_days'];
            $discount_date_trigger = date('Y-m-d', strtotime('+' . $days_before_expiry . ' days'));

            if ($product['date_expired'] <= $discount_date_trigger && $product['date_expired'] > $today) {
                $products_to_discount[] = $product;
            }
        }

        if (empty($products_to_discount)) {
            echo "No products are currently within their discount window." . PHP_EOL;
            return;
        }

        echo "Found " . count($products_to_discount) . " products to discount." . PHP_EOL;

        // 3. Apply a discount to these products
        foreach ($products_to_discount as $product) {
            $product_id = $product['product_id'];

            // Check if a special auto-discount already exists to avoid duplicates
            $existing_discount_query = $this->db->query("SELECT `product_discount_id` FROM `" . DB_PREFIX . "product_discount` WHERE `product_id` = '" . (int)$product_id . "' AND `priority` = '99'");

            if ($existing_discount_query->num_rows) {
                echo "Auto-discount already exists for product_id: " . $product_id . ". Skipping." . PHP_EOL;
                continue;
            }

            // Apply 50% discount
            $discount_price = $product['price'] * 0.5;

            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_discount` SET `product_id` = '" . (int)$product_id . "', `customer_group_id` = '" . (int)$this->config->get('config_customer_group_id') . "', `quantity` = '1', `priority` = '99', `price` = '" . (float)$discount_price . "', `date_start` = '" . $this->db->escape($today) . "', `date_end` = '" . $this->db->escape($product['date_expired']) . "'");

            echo "Applied 50% discount to product_id: " . $product_id . PHP_EOL;
        }
    }
} 