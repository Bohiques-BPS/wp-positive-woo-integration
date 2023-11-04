<?php

defined( 'ABSPATH' ) || die( );

require_once __DIR__ . '/scripts.php';
require_once __DIR__ . '/class_woo_controller.php';
require_once __DIR__ . '/class_positive_api_comunication.php';
require_once __DIR__ . '/class_cron.php';


/**
 * Clase principal, se encarga de almacenar las principales funciones para el funcionamiento del plugin
 */
class OEPS_PositiveIntegration {
    public static $instance;

    public static function init( ) {
        self::$instance = OEPS_PositiveAPIComunication::getInstance( );

        add_action('rest_api_init', ['OEPS_PositiveIntegration', 'register_routes']);
        add_action('woocommerce_payment_complete', ['OEPS_PositiveIntegration', 'paymentComplete']);
        add_action('woocommerce_new_order', ['OEPS_PositiveIntegration', 'paymentComplete']);
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
        register_rest_route('OEPS/v1', 'paytest', array(
            'methods' => 'GET',
            'callback' => ['OEPS_PositiveIntegration', 'paymentTest'],
            'permission_callback' => function( ) {
                return true;
            },
        ));
    }

    public static function paymentTest( ) {
        return self::paymentComplete( 628 );
    }

    public static function paymentComplete( int $order_id ) {
        $order = wc_get_order( $order_id );
        if( !$order ) return;
        $localtransactionid = $order_id;
        $amount = $order->get_total( );
        $created_at = date('Y-m-d').'T'.date('H:i:s');
        
        $api = self::$instance;
        $transactionData = array(
            'localtransactionid' => $localtransactionid,
            'customerid' => 28, // Ajusta el ID de cliente según tus necesidades
            'addressid' => 1, // Ajusta el ID de dirección según tus necesidades
            'created_at' => $created_at,
            'updated_at' => $created_at,
            'total' => $amount,
            'linedetails' => self::obtenerLineDetails( $order )
        );

        return $transactionData;

        
        $response_create = $api->transaction_create( $transactionData );
        $status = $response_create['status'];
        if( $status !== 'success' ) {
            throw new Exception( $status );
        }

        $response_payment = $api->transaction_payment( $localtransactionid, $amount, $created_at );
        if( $response_payment['status'] !== 'Success' ) {
            throw new Exception('Error al realizar el pago: '.$response_payment['status']);
        }
        return 'pago exitoso';
    }

    public static function obtenerLineDetails( $order ) {
        $line_details = array( );
        foreach( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $product_id = get_post_meta( $product->get_id(), '_positive_id', true );
            if( !$product_id ) continue;
            $line_details[] = array(
                'productid' => $product_id,
                'sku' => $product->get_sku(),
                'description' => $product->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => $product->get_price(),
                'taxamount' => 0,
                'linetotal' => $item->get_subtotal(),
            );
        }
        return $line_details;
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
        $metaData = array_filter( $product->get_meta_data(), function( $element ) {
            return $element->key == '_positive_id';
        })[0];
        $positiveId = $metaData->value;
        $positiveProduct = self::$instance->getProductById( $positiveId );
        $positiveStock = $positiveProduct ? +$positiveProduct->in_stock : null;
        if( $positiveId === 0 ) {
            $product->set_stock_status('outofstock');
            $class = 'out-of-stock';
            $availability = 'Agotado';
            return "<p class='stock $class'> $availability </p>";
        }
        return $html;
    }
        
    /**
     * Pulls data from the "positive" and "woocommerce" sources and
     * compares the products. If a product exists in "positive" but
     * not in "woocommerce", it is considered a new product. If a
     * product exists in both sources, it is compared for updates.
     *
     * @return array An array containing the updated and created products.
     */
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