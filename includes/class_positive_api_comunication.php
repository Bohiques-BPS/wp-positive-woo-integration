<?php

defined( 'ABSPATH' ) || die( );

/**
 * OEPS_PositiveAPIComunication: esta clase sigue el patron Singleton.
 * Se encarga de realizar la comunicacion con positive
 */
class OEPS_PositiveAPIComunication {
    private static $instance;
    private $username = 'POS1';
    private $password = 'POS1';
    private $api_url = 'http://oepc.positiveanywhere.com';

    private function __construct( ) {
        // $this->username = 'POS1';
        // $this->password = 'POS1';
        // $this->api_url = 'http://oepc.positiveanywhere.com';
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
        return null;
    }

    public function getProducts( ) {
        $api_url = $this->api_url."/product_list";
    
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
            'Content-Type' => 'application/json',
        );
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
                    'timeout' => 1000,
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
}