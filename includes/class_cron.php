<?php

defined( 'ABSPATH' ) || die( );

class OEPS_Cron {
    public function __construct( ) {

    }

    public static function init( ) {
        add_filter( 'cron_schedules', ['OEPS_Cron','add_cron_interval_5_seconds'] );
        add_action( 'OEPS_cron_hook', ['OEPS_Cron', 'cron_action']);
        if ( !wp_next_scheduled( 'OEPS_cron_hook' ) ) {
            wp_schedule_event( time(), 'every_minute', 'OEPS_cron_hook' );
        }
    }

    public static function activate() {
       
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'OEPS_cron_hook' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'OEPS_cron_hook' );
        }
    }

    public static function cron_action( ) {
        OEPS_PositiveIntegration::pullData( );
    }

    public static function add_cron_interval_5_seconds( $schedules ) { 
        $schedules['five_seconds'] = array(
            'interval' => 5,
            'display'  => esc_html__( 'Every Five Seconds' ), );
        return $schedules;
    }
}

