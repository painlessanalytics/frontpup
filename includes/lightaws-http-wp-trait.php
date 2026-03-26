<?php
/**
 * Light AWS HTTP WordPress Trait
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LightAWS_HTTP_WP_Trait
 * 
 * As we extend other classes we can re-use this trait
 */
trait LightAWS_HTTP_WP_Trait {

    /**
     * Http request function
     */
    protected function http_request( string $method, string $url, array $args = [] ) {

        // Check that $args has the method set, if not set it to the provided $method
        if( !isset( $args['method'] ) ) {
            $args['method'] = strtoupper( $method );
        }

        $response = wp_remote_request( $url, $args );
        if( $response === false ) {
            $this->set_last_error( 'Unknown error occurred during HTTP request.' );
            return false;
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );
        if( !is_wp_error( $response ) ) {
            // Return the response parsed by the child class's parse_response method
            $body = wp_remote_retrieve_body( $response );
            return $this->parse_response( $body, $status_code );
        }

        $this->set_last_error( $response->get_error_message(), $status_code );
        return false;
    }
}

// eof