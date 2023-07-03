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
        // $positiveProducts = ('{"updated":[{"productid":343,"sku":"000001","in_stock":"2.0000","retail_price":"1.99","description":"OJITOS 20 MM (ARTESCO)","weight":".0000","manage_stock":true},{"productid":344,"sku":"7750082089570","in_stock":"4.0000","retail_price":"1.99","description":"OJITOS 15 MM (ARTESCO)","weight":".0000","manage_stock":true},{"productid":345,"sku":"000002","in_stock":"4.0000","retail_price":"1.99","description":"OJITOS 35MM (ARTESCO)","weight":".0000","manage_stock":true},{"productid":346,"sku":"000003","in_stock":"0.0000","retail_price":"1.99","description":"OJITOS 25 MM (ARTESCO)","weight":".0000","manage_stock":true},{"productid":347,"sku":"7750082023017","in_stock":"9.0000","retail_price":"3.99","description":"TIJERA DE FORMAS (ARTESCO)","weight":".0000","manage_stock":true},{"productid":348,"sku":"7750082106369","in_stock":"12.0000","retail_price":"3.99","description":"HERR. PARA MOLD. (ARTESCO)","weight":".0000","manage_stock":true},{"productid":349,"sku":"7750082104846","in_stock":"2.0000","retail_price":"2.20","description":"FOAMY ARTESCO 50 GRM AZUL","weight":".0000","manage_stock":true},{"productid":350,"sku":"7750082105027","in_stock":"6.0000","retail_price":"1.75","description":"FOAMY ARTESCO 32 GRM","weight":".0000","manage_stock":true},{"productid":351,"sku":"7750082089716","in_stock":"10.0000","retail_price":"2.99","description":"LIMPIA PIPAS 48 UNproductidADES","weight":".0000","manage_stock":true},{"productid":352,"sku":"7750082089747","in_stock":"12.0000","retail_price":"1.75","description":"LIMPIA PIPAS 30 UNproductidADES","weight":".0000","manage_stock":true},{"productid":353,"sku":"7750082089723","in_stock":"12.0000","retail_price":"3.50","description":"LIMPIA PIPAS C/TEXTURA 48 UNI","weight":".0000","manage_stock":true},{"productid":354,"sku":"7750082089730","in_stock":"10.0000","retail_price":"1.75","description":"LIMPIA PIPAS COL CLAROS 30 UNI","weight":".0000","manage_stock":true},{"productid":355,"sku":"7750082119130","in_stock":"3.0000","retail_price":"7.99","description":"PINT ACRILICAS MATE","weight":".0000","manage_stock":true},{"productid":356,"sku":"7750082004559","in_stock":"6.0000","retail_price":"3.40","description":"TEMPERAS ARTESCO 6/UNI+PINCEL+PA","weight":".0000","manage_stock":true},{"productid":357,"sku":"7750082017757","in_stock":"6.0000","retail_price":"3.20","description":"CRAYONES TRIANGULARES ARTESCO","weight":".0000","manage_stock":true},{"productid":358,"sku":"7750082002647","in_stock":"6.0000","retail_price":"3.00","description":"CRAYONES JUMBO ARTESCO","weight":".0000","manage_stock":true},{"productid":359,"sku":"7750082104884","in_stock":"2.0000","retail_price":"2.20","description":"FOAMY ARTESCO 50 GRM ROJO","weight":".0000","manage_stock":true},{"productid":360,"sku":"7750082024809","in_stock":"8.0000","retail_price":"4.95","description":"CERAMICA EN FRIO ARTESCO VERDE","weight":".0000","manage_stock":true},{"productid":361,"sku":"7750082024793","in_stock":"9.0000","retail_price":"4.95","description":"CERAMICA EN FRIO ARTESCO AMARILL","weight":".0000","manage_stock":true},{"productid":362,"sku":"7750082024823","in_stock":"9.0000","retail_price":"4.95","description":"CERAM.EN FRIO ARTESCO AZUL/ROJO","weight":".0000","manage_stock":true},{"productid":363,"sku":"7750082065840","in_stock":"5.0000","retail_price":"8.99","description":"PISTOLA PARA SILICONA ARTESCO","weight":".0000","manage_stock":true},{"productid":364,"sku":"7750082005969","in_stock":"5.0000","retail_price":"3.50","description":"TEMPERAS ARTESCO AMARILLO 250ML","weight":".0000","manage_stock":true},{"productid":365,"sku":"7750082075795","in_stock":"3.0000","retail_price":"7.75","description":"TEMPERA ARTESCO DORADO 250 ML","weight":".0000","manage_stock":true},{"productid":366,"sku":"7750082006003","in_stock":"4.0000","retail_price":"3.50","description":"TEMPERA ARTESCO NEGRO 250 ML","weight":".0000","manage_stock":true},{"productid":367,"sku":"7750082006027","in_stock":"5.0000","retail_price":"3.50","description":"TEMPERA ARTESCO ROJA 250ML","weight":".0000","manage_stock":true},{"productid":368,"sku":"7750082005976","in_stock":"5.0000","retail_price":"3.50","description":"TEMPERA ARTESCO AZUL 250 ML","weight":".0000","manage_stock":true},{"productid":369,"sku":"7750082005990","in_stock":"3.0000","retail_price":"3.50","description":"TEMPERA ARTESCO MARRON 250 ML","weight":".0000","manage_stock":true},{"productid":370,"sku":"7750082006034","in_stock":"5.0000","retail_price":"3.50","description":"TEMPERA ARTESCO VERDE 250 ML7750","weight":".0000","manage_stock":true},{"productid":371,"sku":"7750082005983","in_stock":"5.0000","retail_price":"3.50","description":"TERMPERA ARTESCO BLANCA 250 ML","weight":".0000","manage_stock":true},{"productid":372,"sku":"7750082037564","in_stock":"4.0000","retail_price":"1.99","description":"PINTURA ACRILICA AZUL NEON","weight":".0000","manage_stock":true},{"productid":373,"sku":"7750082037557","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA VIOLETA NEON","weight":".0000","manage_stock":true},{"productid":374,"sku":"7750082037526","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA VERDE NEON","weight":".0000","manage_stock":true},{"productid":375,"sku":"7750082037533","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA NARANJA NEON","weight":".0000","manage_stock":true},{"productid":376,"sku":"7750082037519","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA AMARILLO NEON","weight":".0000","manage_stock":true},{"productid":377,"sku":"7750082037434","in_stock":"3.0000","retail_price":"1.99","description":"PINTURA ACRILICA BLANCO MATE 775","weight":".0000","manage_stock":true},{"productid":378,"sku":"7750082037496","in_stock":"4.0000","retail_price":"1.99","description":"PINTURA ACRILICA NARANJA MATE","weight":".0000","manage_stock":true},{"productid":379,"sku":"7750082037441","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA ROSA MATE","weight":".0000","manage_stock":true},{"productid":380,"sku":"7750082037465","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA AZUL MATE","weight":".0000","manage_stock":true},{"productid":381,"sku":"7750082037472","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA NEGRA MATE","weight":".0000","manage_stock":true},{"productid":382,"sku":"7750082037410","in_stock":"4.0000","retail_price":"1.99","description":"PINTURA ACRILICA AMARILLA MATE","weight":".0000","manage_stock":true},{"productid":383,"sku":"7750082037458","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILCA VERDE MATTE","weight":".0000","manage_stock":true},{"productid":384,"sku":"7750082037595","in_stock":"4.0000","retail_price":"1.99","description":"PINTURA ACRILICA AMARILLO BRILLO","weight":".0000","manage_stock":true},{"productid":385,"sku":"7750082037571","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA AZUL BRILLO","weight":".0000","manage_stock":true},{"productid":386,"sku":"7750082037601","in_stock":"5.0000","retail_price":"1.99","description":"PNTURA ACRILICA ROJA BRILLO","weight":".0000","manage_stock":true},{"productid":387,"sku":"7750082037588","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA VERDE BRILLO","weight":".0000","manage_stock":true},{"productid":388,"sku":"7750082037618","in_stock":"4.0000","retail_price":"1.99","description":"PINTURA ACRILICA NEGRA BRILLO","weight":".0000","manage_stock":true},{"productid":389,"sku":"7750082037625","in_stock":"3.0000","retail_price":"1.99","description":"PINTURA ACRILICA MARRON BRILLO 7","weight":".0000","manage_stock":true},{"productid":390,"sku":"7750082071599","in_stock":"5.0000","retail_price":"1.99","description":"PINTURA ACRILICA BLANCA BRILLO","weight":".0000","manage_stock":true},{"productid":391,"sku":"7750082400146","in_stock":"3.0000","retail_price":"3.50","description":"ARTESCO SEPARADORES INDEX 10/DIV","weight":".0000","manage_stock":true},{"productid":392,"sku":"7750082072954","in_stock":"10.0000","retail_price":"2.25","description":"ARTESCO CINTA CORRECTOR","weight":".0000","manage_stock":true}],"created":[]}');
        // $positiveProducts = json_decode( $positiveProducts )->updated;
        return $positiveProducts;
        $wooProducts = OEPS_WooController::getProductsData( );
        // return ($wooProducts);
    
        $productsToUpdate = [];
        $productsToCreate = [];
        /**
         * Comparamos los productos de positive contra los productos de woocommerce
         * Si el producto se encuentra en positive pero no en woo significa que es un nuevo proucto
         */
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