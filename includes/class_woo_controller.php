<?php

defined( 'ABSPATH' ) || die( );

/**
 * Class OEPS_WooController: Se encarga de administrar los datos de los productos de woocommerce de manera interna
 */
class OEPS_WooController {

    public static function getProductsData(  ) {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1, // Obtener todos los productos
        );
    
        $products = get_posts( $args );
        $products = array_map( function( $product ) {
            $metas = get_post_meta( $product->ID );
            // return $metas;
            return [
                'id'            => $product->ID,
                'name'          => $product->post_title,
                'sku'           => $metas['_sku'][0] ?? '',
                'stock_quantity'=> $metas['_stock'][0],
                'regular_price' => $metas['_regular_price'][0] ?? '',
                'weight'        => $metas['_weight'][0] ?? '',
                'positive_id'   => $metas['_positive_id'] ?? '',
            ];
        }, $products );
        return $products;
    }

    public static function insertProducts( $products ) {
        foreach( $products as $product ) {
            $data = [
                'ID' => $product['id'] ?? '',
                'post_title'    => $product['name'],
                'post_status'   => 'publish',
                'post_author'   => 1,
                'post_type'     => 'product',
            ];
            $postId = wp_insert_post( $data );
            if( $postId ) {
                update_post_meta( $postId, '_sku',          $product['sku'] ?? '' );
                update_post_meta( $postId, '_stock',        $product['stock_quantity'] );
                update_post_meta( $postId, '_weight',       $product['weight'] ?? '' );
                update_post_meta( $postId, '_regular_price',$product['regular_price'] );
                update_post_meta( $postId, '_price',        $product['regular_price'] );
                update_post_meta( $postId, '_positive_id',  $product['positive_id'] ?? '' );
                update_post_meta( $postId, '_manage_stock', $product['manage_stock'] );
            }
        };
        return 'elements updated successfully';
    }

}