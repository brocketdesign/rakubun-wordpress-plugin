<?php
/**
 * Migration script to update prices from USD to JPY
 * Run this once to convert existing installations
 */

// Update article price from $5.00 to ¥750
$current_article_price = get_option('rakubun_ai_article_price', 5.00);
if ($current_article_price <= 10) { // If it's still in USD range
    update_option('rakubun_ai_article_price', 750);
}

// Update image price from $2.00 to ¥300  
$current_image_price = get_option('rakubun_ai_image_price', 2.00);
if ($current_image_price <= 10) { // If it's still in USD range
    update_option('rakubun_ai_image_price', 300);
}

// Optional: Add a flag to indicate migration was completed
update_option('rakubun_ai_currency_migrated_to_jpy', true);

echo "Currency migration completed: Prices converted from USD to JPY\n";
echo "Article price: " . get_option('rakubun_ai_article_price') . " JPY\n";
echo "Image price: " . get_option('rakubun_ai_image_price') . " JPY\n";
?>