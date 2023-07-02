<?php
/**
* Plugin Name: positive Woocommerce integration
* Description: Sincroniza la base de datos de Woocommerce con el servicio de Positive Anywhere.
* Version: 1.0.0
* Author: Moises Rodriguez
*/
defined( 'ABSPATH' ) || die( );

require __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/includes/scripts.php';
include __DIR__ . '/includes/class_positive_integration.php';

use Automattic\WooCommerce\Client;


add_action('init', ['OEPS_PositiveIntegration', 'init']);