<?php
/**
 * Retorna el primer elemento en el array que cumple con la condición dada por la función de callback .
 *
 * @param array    $array     El array en el cual buscar el elemento.
 * @param callable $callback  La función de callback que define la condición de búsqueda.
 * @return mixed|null         El primer elemento que cumple con la condición, o null si no se encuentra ningún elemento.
 */
function array_find(array $array, callable $callback) {
    foreach ($array as $item) {
        if ($callback($item)) {
            return $item;
        }
    }
    return null;
}

/**
 * Retorna el índice del primer elemento en el array que cumple con la condición dada por la función de callback.
 *
 * @param array    $array     El array en el cual buscar el elemento.
 * @param callable $callback  La función de callback que define la condición de búsqueda.
 * @return int|null           El índice del primer elemento que cumple con la condición, o null si no se encuentra ningún elemento.
 */
function array_findIndex(array $array, callable $callback) {
    foreach ($array as $index => $item) {
        if ($callback($item)) {
            return $index;
        }
    }
    return null;
}

/**
 * Función para eliminar elementos duplicados de un array de objetos.
 *
 * @param array $array El array de objetos.
 * @param string $key La clave por la cual se determina la duplicidad.
 * @return array El array sin elementos duplicados.
 */
function array_remove_duplicates(array $array, string $key)
{
    $uniqueKeys = [];
    $result = [];

    foreach ($array as $item) {
        $value = $item->{$key};

        if (!in_array($value, $uniqueKeys)) {
            $uniqueKeys[] = $value;
            $result[] = $item;
        }
    }

    return $result;
}

function HTTPRequest( $url, $data=[] ) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        // CURLOPT_HTTPHEADER => [
        //     'Content-Type: application/json',
        //     'Authorization: Basic ' . base64_encode('POS1:POS1') // Reemplazar con tus credenciales de autenticación
        // ],
        // CURLOPT_POST => true,
        // CURLOPT_POSTFIELDS => json_encode( $data['body'] ),
    ]);
    $response = curl_exec($curl);
    if ($response === false) {
        $error_message = curl_error($curl);
        echo "Error: $error_message";
        return null;
    }
    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if ($response_code !== 200) {
        echo "Error en la respuesta HTTP. Código: $response_code";
        return null;
    }
    curl_close($curl);
    return $response;
}
