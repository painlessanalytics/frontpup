<?php
/**
 * Lightweight AWS CloudFront Class
 *
 * Extends LightweightAWS_Base to provide CloudFront cache-management API calls
 * without requiring the full AWS SDK for PHP.  Only cache-related operations
 * (invalidations) are implemented.
 *
 * Supported operations:
 *   - createInvalidation()  – submit a new cache invalidation batch
 *   - getInvalidation()     – retrieve the status of an existing invalidation
 *   - listInvalidations()   – list invalidations for a distribution
 */
if ( ! defined( 'ABSPATH' ) ) exit;
 
require_once __DIR__ . '/lightaws-base.php';

/**
 * Class LightweightAWS_CloudFront
 * 
 * Ref: https://docs.aws.amazon.com/aws-sdk-php/v3/api/ for API versions for other services if needed in the future.
  */
class LightAWS_CloudFront extends LightAWS_Base {
 
    /** CloudFront global endpoint (always us-east-1, regardless of origin region) */
    const ENDPOINT = 'https://cloudfront.amazonaws.com';
 
    /** @var string CloudFront Distribution ID */
    protected $distribution_id = '';
 
    /**
     * CloudFront signing always targets us-east-1 regardless of where your
     * origin/resources are located.
     *
     * @param string $region Unused – kept for API consistency; always us-east-1.
     */
    public function __construct( array $options = [] ) {
        // CloudFront is a global service; SigV4 scope is always us-east-1

        if( empty($options['region']) ) {
            $options['region'] = 'us-east-1';
        }
        if( empty($options['version']) || $options['version'] === 'latest') {
            $options['version'] = '2020-05-31';
        }

        parent::__construct( $options, 'cloudfront' );
    }
 
    /**
     * Set the CloudFront Distribution ID to target.
     *
     * @param string $distribution_id
     */
    public function set_distribution_id( string $distribution_id ): void {
        $this->distribution_id = $distribution_id;
    }

 
    // -------------------------------------------------------------------------
    // Cache Invalidation API
    // -------------------------------------------------------------------------
 
    /**
     * Submit a CloudFront cache invalidation request.
     *
     * @param string[] $paths            List of paths to invalidate (default: all paths via '/*')
     * @param string|null $caller_reference Unique string for idempotency; defaults to current Unix timestamp
     *
     * @return array{status_code: int, body: string, parsed: SimpleXMLElement|null}
     * @throws \Exception On HTTP error or missing distribution ID.
     */
    public function createInvalidation( string $distribution_id, array $paths = [ '/*' ], ?string $caller_reference = null ): array {
 
        if ( $caller_reference === null ) {
            $caller_reference = (string) time();
        }
 
        $quantity  = count( $paths );
        $items_xml = '';
        foreach ( $paths as $path ) {
            $items_xml .= '<Path>' . esc_xml( $path ) . '</Path>';
        }
 
        $body = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<InvalidationBatch xmlns="http://cloudfront.amazonaws.com/doc/' . $this->get_api_version() . '/">'
            .   '<CallerReference>' . esc_xml( $caller_reference ) . '</CallerReference>'
            .   '<Paths>'
            .     '<Quantity>' . (int) $quantity . '</Quantity>'
            .     '<Items>' . $items_xml . '</Items>'
            .   '</Paths>'
            . '</InvalidationBatch>';
 
        $url = $this->build_url( '/distribution/' . rawurlencode( $distribution_id ) . '/invalidation' );
 
        return $this->post( $url, $body, [ 'content-type' => 'text/xml' ] );
 
        //return $this->parse_response( $response );
    }
 
    /**
     * Retrieve the status/details of a specific invalidation.
     *
     * @param string $distribution_id The CloudFront distribution ID (must match the one used in create_invalidation)
     * @param string $invalidation_id The invalidation ID returned by create_invalidation()
     *
     * @return array{status_code: int, body: string, parsed: SimpleXMLElement|null}
     * @throws \Exception On HTTP error or missing distribution ID.
     */
    public function getInvalidation( string $distribution_id, string $invalidation_id ): array {
 
        $url = $this->build_url(
            '/distribution/' . rawurlencode( $distribution_id )
            . '/invalidation/' . rawurlencode( $invalidation_id )
        );
 
        return $this->get( $url );
 
        //return $this->parse_response( $response );
    }
 
    /**
     * List all invalidations for the configured distribution.
     *
     * @param string $distribution_id The CloudFront distribution ID (must match the one used in create_invalidation)
     * @param int $max_items Maximum number of items to return (1–100)
     *
     * @return array{status_code: int, body: string, parsed: SimpleXMLElement|null}
     * @throws \Exception On HTTP error or missing distribution ID.
     */
    public function listInvalidations( string $distribution_id, int $max_items = 100 ): array {
 
        $url = $this->build_url(
            '/distribution/' . rawurlencode( $distribution_id ) . '/invalidation',
            [ 'MaxItems' => max( 1, min( 100, $max_items ) ) ]
        );
 
        return $this->get( $url );
 
        //return $this->parse_response( $response );
    }
 
    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------
 
    /**
     * Build a fully-qualified CloudFront API URL.
     *
     * @param string $path         API path (leading slash required)
     * @param array  $query_params Optional query-string parameters
     *
     * @return string
     */
    protected function build_url( string $path, array $query_params = [] ): string {
        $url = self::ENDPOINT . '/' . $this->get_api_version() . $path;
        if ( ! empty( $query_params ) ) {
            $url .= '?' . http_build_query( $query_params );
        }
        return $url;
    }
}
 
// eof