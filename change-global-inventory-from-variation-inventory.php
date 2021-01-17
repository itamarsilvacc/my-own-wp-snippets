<?php
/* Making the global quantity in stock of the product equal to zero when the number of variations in its inventory reaches zero in Woocommerce */
// Hide "Out of stock" variable product archive pages
function wc_get_variable_product_stock_quantity( $output = 'raw', $product_id = 0 ){
    if ( is_woocommerce() ) {
        global $wpdb, $product;

        // Get the product ID (can be defined)
        $product_id = $product_id > 0 ? $product_id : get_the_id();

        // Check and get the instance of the WC_Product Object
        $product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product($product_id);

        // Only for variable product type
        if( $product->is_type('variable') ){

            // Get the stock quantity sum of all product variations (children)
            $stock_quantity = $wpdb->get_var("
                SELECT SUM(pm.meta_value)
                FROM {$wpdb->prefix}posts as p
                JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
                WHERE p.post_type = 'product_variation'
                AND p.post_status = 'publish'
                AND p.post_parent = '$product_id'
                AND pm.meta_key = '_stock'
                AND pm.meta_value IS NOT NULL
            ");

            if ( $stock_quantity <= 0 ){
                $product->set_stock_quantity(0);
                $product->set_stock_status( 'outofstock' );
                $product->save();
            }
        }
    }
}

add_action( 'wp', 'wc_get_variable_product_stock_quantity', 1 );

// Hide "Out of stock" variable product single pages
add_action( 'template_redirect', 'hide_out_of_stock_variable_product_single_pages' );
function hide_out_of_stock_variable_product_single_pages(){
    if ( is_woocommerce() ) {
        global $product;

        if( $product->get_stock_quantity() <= 0 ) {
            // Redirect to Shop page
            wp_redirect( wc_get_page_permalink( 'shop' ) );
            exit();
        }
    }
}