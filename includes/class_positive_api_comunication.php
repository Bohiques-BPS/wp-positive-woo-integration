<?php

defined( 'ABSPATH' ) || die( );

/**
 * OEPS_PositiveAPIComunication: esta clase sigue el patron Singleton.
 * Se encarga de realizar la comunicacion con la API de positive
 */
class OEPS_PositiveAPIComunication {
    private static $instance;
    private $username = 'SbYT4x3#G%';
    private $password = 'bjZX7pP$I9';
    private $api_url = 'http://oepc.positiveanywhere.com';
    private $headers;

    private function __construct( ) {
        $this->headers = array(
            'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
            'Content-Type' => 'application/json',
        );
    }

    public static function getInstance( ) {
        if( !self::$instance ) {
            self::$instance = new OEPS_PositiveAPIComunication();
        }
        return self::$instance;
    }

    /**
     * 
     */
    public function getProductById( int $product_id ) {
        $api_url = $this->api_url."/product_list";
        $headers = $this->headers;
        $body = [
            'options' => [
                'productid' => $product_id
            ]
        ];
        $args = array(
            'headers' => $headers,
            'timeout' => 10000,
            'body' => json_encode( $body )
        );
        $response = wp_remote_post( $api_url, $args );
        $response_code = wp_remote_retrieve_response_code($response);

        if( is_wp_error($response) ) {
            $error_message = $response->get_error_message();
            // echo "Error: $error_message";
            return null;
        }
        if( $response_code != 200 ) {
            // echo "Error en la respuesta HTTP. Código: $response_code";
            return null;
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode( utf8_encode($body) );
        $positiveProducts = $data->product_list_response->product_list->products;
        return isset( $positiveProducts[0] ) ? $positiveProducts[0] : null;
    }

    public function getProducts( ) {
        $api_url = $this->api_url."/product_list";
        $headers = $this->headers;

        try {
            $currentBlock = 1;
            $allElements = [];
            while( $currentBlock == 1 ) {
                $body = [
                    "options" => array(
                        "blocksize" => 50,
                        "block"     => $currentBlock,
                    )
                ];
                $args = array(
                    'headers' => $headers,
                    'timeout' => 10000,
                    'body' => json_encode( $body )
                );
                $response = wp_remote_post( $api_url, $args );
                
                if( is_wp_error($response) ) {
                    $error_message = $response->get_error_message();
                    echo "Error: $error_message";
                    return null;
                } else {
                    $response_code = wp_remote_retrieve_response_code($response);
                    if ($response_code === 200) {
                        $body = wp_remote_retrieve_body($response);
                        $data = json_decode( utf8_encode($body) );
                        if( $data ) {
                            // Procesar los datos de la respuesta de la API
                            $positiveProducts = $data->product_list_response->product_list->products;
                            $positiveProducts = array_remove_duplicates( $positiveProducts, 'productid' );
                            $allElements = array_merge( $allElements, $positiveProducts );
                            $currentBlock >= $data->product_list_response->result->total_blocks
                                ? $currentBlock = 0 
                                : $currentBlock++;
        
                        } else {
                            echo 'Error al decodificar la respuesta JSON.';
                            return null;
                        }
                    } else {
                        echo "Error en la respuesta HTTP. Código: $response_code";
                        return null;
                    }
                }
            }
            return $allElements;
        }
        catch (\Throwable $th) {
            throw $th;
        }
    }

    
    public function transaction_create( $transactionData ) {
        $api_url = $this->api_url."/transaction_create";
        // echo $transactionData['localtransactionid'];
        $body = array(
            'transaction' => array(
                'localtransactionid' => $transactionData['localtransactionid'],
                'customerid' => $transactionData['customerid'],
                'addressid' => $transactionData['addressid'],
                'transactiontype' => 'N',
                'created_at' => $transactionData['created_at'],
                'updated_at' => $transactionData['updated_at'],
                'deliverymethod' => 'C',
                'purchaseordernumber' => '',
                'taxamount' => '0',
                'total' => $transactionData['total'],
                'note' => 'pago a traves de positive anywhere',
            ),
            'linedetail' => $transactionData['linedetails']
        );
        $args = array(
            'headers' => $this->headers,
            'timeout' => 10000,
            'body' => json_encode( $body )
        );
        $response = wp_remote_post( $api_url, $args );
        $response_code = wp_remote_retrieve_response_code($response);

        if( is_wp_error($response) ) {
            $error_message = $response->get_error_message();
            return ['status' => $error_message];
        }
        if( $response_code != 200 ) {
            return ['status' => 'error en la respuesta HTTP. Código: '.$response_code];
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode( utf8_encode($body), true);
        if ($data['order_create_response']['result']['status'] !== 'success') {
            return ['status' => 'error al crear la transacción: ' . $data['order_create_response']['result']['status']];
        }
        return $data['order_create_response']['result'];
    }


    //cambiar en las funciones para que devuelva un campo ok para mejor validacion
    public function transaction_payment( $localtransactionid, $amount, $created_at ) {
        $api_url = $this->api_url."/transaction_payment_create";
        $body = [
            'transactionpayment' => [
                'localtransactionid'    => $localtransactionid,
                'created_at'            => $created_at,
                'paymenttype'           => 'C',
                'amount'                => $amount,
                'vataamount'            => 0
            ]
        ];
        $args = array(
            'headers' => $this->headers,
            'timeout' => 10000,
            'body' => json_encode( $body )
        );
        $response = wp_remote_post( $api_url, $args );
        $response_code = wp_remote_retrieve_response_code($response);

        if( is_wp_error($response) ) {
            $error_message = $response->get_error_message();
            return ['status' => $error_message];
        }
        if( $response_code != 200 ) {
            return ['status' => 'error en la respuesta HTTP. Código: '.$response_code];
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode( utf8_encode($body), true);
        if ($data['transaction_payment_create_response']['result'][0]['status'] !== 'Success') {
            return ['status' => 'error al crear la transacción: ' . $data['transaction_payment_create_response']['result'][0]['status']];
        }
        return $data['transaction_payment_create_response']['result'][0];
    }
}
