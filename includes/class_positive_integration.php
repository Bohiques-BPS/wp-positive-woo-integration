<?php

defined( 'ABSPATH' ) || die( );

require_once __DIR__ . '/scripts.php';
require_once __DIR__ . '/class_woo_controller.php';
require_once __DIR__ . '/class_positive_api_comunication.php';
require_once __DIR__ . '/class_cron.php';

class OEPS_PositiveIntegration {
    public static $instance;

    public static function init( ) {
        self::$instance = OEPS_PositiveAPIComunication::getInstance( );

        add_action('rest_api_init', ['OEPS_PositiveIntegration', 'register_routes']);
        add_filter('woocommerce_get_stock_html', ['OEPS_PositiveIntegration', 'getStockProduct']);
        OEPS_Cron::init( );
        
        // echo json_encode(self::$instance->getProducts( ));
    }

    public static function activation( ) {

    }

    public static function deactivation( ) {
        OEPS_Cron::deactivate( );
    }

    public static function register_routes( ) {
        register_rest_route('OEPS/v1', 'pullData', array(
            'methods' => 'GET',
            'callback' => ['OEPS_PositiveIntegration', 'pullData'],
            'permission_callback' => function( ) {
                return true;
            },
        ));
    }
    
    /**
     * Esta funcion se engancha al hook woocommerce_get_stock_html.
     * Se encarga de mostrar una salida html de un single-product para indicar si el producto se encuentra disponible en stock.
     * Esta funcion se comunica con positive para pedir la informacion del stock de un producto y mostrarlo como agotado o no
     * @param string $html salida actual del producto
     * @return string
     */
    public static function getStockProduct( $html ) {
        global $product;
        $positiveProduct = self::$instance->getProductById( $product->get_id() );
        // $wooStock = $product->get_stock_quantity( );
        $positiveStock = $positiveProduct ? $positiveProduct->in_stock : null;
        if( $positiveStock ) {
            $product->set_stock_status('instock');
            $class = 'in-stock';
            $availability = "$positiveStock disponibles";
        }
        else {
            $product->set_stock_status('outofstock');
            $class = 'out-of-stock';
            $availability = 'Agotado';
        }
        return "<p class='stock $class'> $availability </p>";
    }
        
    public static function pullData( ) {
        $positiveProducts = self::$instance->getProducts( );
        $wooProducts = OEPS_WooController::getProductsData( );
    
        $productsToUpdate = [];
        $productsToCreate = [];
        /**
         * Comparamos los productos de positive contra los productos de woocommerce
         * Si el producto se encuentra en positive pero no en woo significa que es un nuevo proucto
         */
        if( !$positiveProducts ) return 'error de conexion';
        foreach( $positiveProducts as $positiveProduct ) {
            /**
             * Ubicamos si el producto se encuentra en WooCommerce
             */
            $wooProduct = array_find( $wooProducts, function( $wooProduct ) use ($positiveProduct) {
                return $wooProduct['sku'] == $positiveProduct->sku;
            });
        
            if( $wooProduct ) {
                /**
                 * Si el producto existe lo comparamos para actualizacion
                 */
                if(
                    $wooProduct['stock_quantity'] != $positiveProduct->in_stock ||
                    $wooProduct['regular_price'] != $positiveProduct->retail_price ||
                    $wooProduct['name'] != $positiveProduct->description ||
                    $wooProduct['weight'] !== $positiveProduct->weight 
                ) 
                {
                    $productsToUpdate[] = [
                        'id'              => $wooProduct['id'],
                        'positive_id'     => $positiveProduct->productid,
                        'sku'             => $positiveProduct->sku,
                        'stock_quantity'  => $positiveProduct->in_stock,
                        'regular_price'   => $positiveProduct->retail_price,
                        'name'            => $positiveProduct->description,
                        'weight'          => $positiveProduct->weight,
                        'manage_stock'    => true,
                    ];
                }
            }
            else {
                /**
                 * Sino lo creamos en woo
                 */
                $productsToCreate[] = [
                    'positive_id'     => $positiveProduct['productid'],
                    'sku'             => $positiveProduct->sku,
                    'stock_quantity'  => $positiveProduct->in_stock,
                    'regular_price'   => $positiveProduct->retail_price,
                    'name'            => $positiveProduct->description,
                    'weight'          => $positiveProduct->weight,
                    'manage_stock'    => true,
                ];
            }
        }
        if( count( $productsToUpdate ) ) {
            OEPS_WooController::insertProducts( $productsToUpdate );
        }
        if( count( $productsToCreate ) ) {
            OEPS_WooController::insertProducts( $productsToCreate );
        }
        return [
        'updated' => $productsToUpdate,
        'created' => $productsToCreate
        ];
    }
}