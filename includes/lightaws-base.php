<?php
/**
 * Lightweight AWS Base Class
 *
 * Handles AWS Signature Version 4 signing and common HTTP methods (GET, POST,
 * PUT, DELETE) using PHP built-in HTTP methods.
 * 
 * Ref: https://docs.aws.amazon.com/general/latest/gr/sigv4-calculate-signature.html for signature calculation details.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class LightAWS_Base {

    private static bool $EXCEPTIONS_ENABLED = true; // Set to false to disable exceptions and use get_last_error() instead
 
    /** @var string AWS service identifier (e.g. cloudfront, s3) */
    protected $service;

    /** @var string AWS Access Key ID */
    protected $access_key = '';
 
    /** @var string AWS Secret Access Key */
    protected $secret_key = '';
 
    /** @var string Optional session token (STS / IAM role) */
    protected $session_token = '';

    /** @var string Optional profile name for credential loading (not implemented) */
    protected $profile = '';
 
    /** @var string AWS region (e.g. us-east-1) */
    protected $region;

    /** @var string AWS API version (default: 'latest') */
    protected $version = 'latest';

    /** @var float HTTP request timeout in seconds */
    protected $timeout = 5.0; // seconds

    /** @var int Number of retries for HTTP requests */
    protected $retries = 1;

    /** @var string Last Error */
    protected $last_error = '';
    protected $last_error_code = 0;

    public static function disable_exceptions( bool $disable = true ) {
        self::$EXCEPTIONS_ENABLED = !$disable;
    }
 
    /**
     * @param string $region  AWS region
     * @param string $service AWS service name used in SigV4 scope
     */
    public function __construct( $options = [], $service = '') {
        $this->service = $service;    
        // Options in order found on this page: https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.AwsClient.html#method___construct
        
        /**
         * $options settings from the full AWS SDK AwsClient constructor (not all supported, see comments below):
         */
        // 'api_provider' not used
        $this->access_key = $options['credentials']['key'] ?? '';
        $this->secret_key = $options['credentials']['secret'] ?? '';
        $this->session_token = $options['credentials']['token'] ?? '';
        // 'token' (session token) is not supported as an option but we do create a session token property for use with IAM roles / STS credentials loaded via load_iam_credentials()
        // 'csm' not supported
        // 'debug' not supported
        // 'stats' not supported
        // 'disable_host_prefix_injection' not supported
        // 'endpoint' not supported
        // 'endpoint_discovery' not supported
        // 'endpoint_provider' not supported
        // 'handler' not supported
        // 'http' not supported
        // 'http_handler' not supported
        // 'idempotency_auto_fill' not supported
        $this->profile = $options['profile'] ?? ''; // Not supported
        $this->region  = $options['region'] ?? '';
        $this->retries = (int) ($options['retries'] ?? 3);
        // 'scheme' not supported (we always use https)
        // 'signature_provider' not supported (we only support SigV4 signing)
        // 'signature_version' not supported (we only support SigV4)
        // 'use_aws_shared_config_files' not supported (we do not read shared config files)
        // 'validate' not supported (we do not perform client-side validation of parameters)
        $this->version = $options['version'] ?? 'not-set'; // This should be set by the child class constructor if not provided here
        // 'account_id_endpoint_mode' not supported
        // 'ua_append' not supported
        // 'app_id' not supported
        
        $this->timeout = (float) ($options['timeout'] ?? 5.0);
    }

    /**
     * Get the last error message.
     *
     * @return string Last error message.
     */
    public function getLastError() {
        return $this->get_last_error();
    }

    // -------------------------------------------------------------------------
    // Internal helpers, available to child classes only
    // -------------------------------------------------------------------------

    /**
     * Get the API version.
     *
     * @return string API version.
     */
    public function get_api_version() {
        return $this->version;
    }
 
    /**
    * Get the last error message.
    *
    * @return string Last error message.
    */
    public function get_last_error(): string {
        return $this->last_error;
    }

    /**
    * Get the last error code.
    *
    * @return int Last error code.
    */
    public function get_last_error_code(): int {
        return $this->last_error_code;
    }

    /**
    * Set the last error message.
    *
    * @param string $message Error message to store.
    * @param int $code Error code.
    */
    protected function set_last_error( string $message, int $code = 0 ): void {
        $this->last_error = $message;
        $this->last_error_code = $code;

        if ( self::$EXCEPTIONS_ENABLED ) {
            throw new \Exception( $message, $code );
        }
    }

    /**
     * Clear last error message and code.
     */
    protected function clear_last_error(): void {
        $this->last_error = '';
        $this->last_error_code = 0;
    }
 
    // -------------------------------------------------------------------------
    // Credential helpers
    // -------------------------------------------------------------------------

    /**
     * Load credentials automatically from the environment or the EC2/ECS
     * instance metadata service (IMDSv2).  Falls through in order:
     *
     *   1. AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY environment variables
     *   2. EC2 Instance Metadata Service v2 (IMDSv2)
     *   3. ECS task credentials (AWS_CONTAINER_CREDENTIALS_RELATIVE_URI)
     *
     * @return bool True on success, false if no credentials could be resolved.
     */
    protected function load_iam_credentials(): bool {

        if( !empty($this->access_key ) && !empty($this->secret_key) ) {
            // Credentials already set (e.g. via constructor options or set_credentials), so skip loading
            return true;
        }

        // 1. Environment variables (works for Lambda, EKS pods, CI, etc.)
        $env_key    = getenv( 'AWS_ACCESS_KEY_ID' );
        $env_secret = getenv( 'AWS_SECRET_ACCESS_KEY' );
        if ( $env_key && $env_secret ) {
            $this->access_key    = $env_key;
            $this->secret_key    = $env_secret;
            $this->session_token = (string) getenv( 'AWS_SESSION_TOKEN' );
            return true;
        }
 
        // 2. ECS task credential endpoint
        $ecs_uri = getenv( 'AWS_CONTAINER_CREDENTIALS_RELATIVE_URI' );
        if ( $ecs_uri ) {
            $response = $this->http_request( 'GET', 'http://169.254.170.2' . $ecs_uri, [
                'timeout' => $this->timeout,
                'user-agent' => 'FrontPup/'. FRONTPUP_VERSION .' (https://www.frontpup.com)',
            ] );
            if( $response && !empty($response['body']) ) {
                $creds = json_decode( $response['body'], true );
                if ( isset( $creds['AccessKeyId'] ) ) {
                    $this->access_key    = $creds['AccessKeyId'];
                    $this->secret_key    = $creds['SecretAccessKey'];
                    $this->session_token = $creds['Token'] ?? '';
                    return true;
                }
            }
        }
 
        // 3. IMDSv2 (EC2 instance profile)
        return $this->load_imdsv2_credentials();
    }
 
    /**
     * Fetch credentials via the EC2 Instance Metadata Service v2 (IMDSv2).
     *
     * @return bool True on success.
     */
    protected function load_imdsv2_credentials(): bool {
        // Step 1 – acquire a session token (required for IMDSv2)
        $token_response = $this->http_request( 'PUT', 'http://169.254.169.254/latest/api/token', [
            'headers' => [ 'X-aws-ec2-metadata-token-ttl-seconds' => '21600' ],
            'body'    => '',
            'timeout' => $this->timeout,
            'user-agent' => 'FrontPup/'. FRONTPUP_VERSION .' (https://www.frontpup.com)',
        ] );
        if ( $token_response === false ) {
            // Error message should already be set by http_request
            return false;
        }
        $imds_token = trim( $token_response['body'] );
        if ( empty( $imds_token ) ) {
            $this->set_last_error( 'Failed to retrieve IMDSv2 token.', 0 );
            return false;
        }
 
        $imds_headers = [ 'X-aws-ec2-metadata-token' => $imds_token ];
 
        // Step 2 – discover the attached IAM role name
        $role_response = $this->http_request( 'GET', 'http://169.254.169.254/latest/meta-data/iam/security-credentials/', [
            'headers' => $imds_headers,
            'timeout' => $this->timeout,
            'user-agent' => 'FrontPup/'. FRONTPUP_VERSION .' (https://www.frontpup.com)',
        ] );
        if ( $role_response === false ) {
            // Error message should already be set by http_request
            return false;
        }
        $role_name = trim( $role_response['body'] );
        if ( empty( $role_name ) ) {
            $this->set_last_error( 'Failed to retrieve IAM role name.', 0 );
            return false;
        }
 
        // Step 3 – retrieve temporary credentials for the role
        $creds_response = $this->http_request( 'GET', 'http://169.254.169.254/latest/meta-data/iam/security-credentials/' . rawurlencode( $role_name ), [
            'headers' => $imds_headers,
            'timeout' => $this->timeout,
            'user-agent' => 'FrontPup/'. FRONTPUP_VERSION .' (https://www.frontpup.com)',
        ] );
        if ( $creds_response === false ) {
            // Error message should already be set by http_request
            return false;
        }
        $creds = json_decode( $creds_response['body'], true );
        if ( !isset( $creds['AccessKeyId'] ) ) {
            $this->set_last_error( 'Failed to retrieve IAM role credentials.', 0 );
            return false;
        }

        $this->access_key    = $creds['AccessKeyId'];
        $this->secret_key    = $creds['SecretAccessKey'];
        $this->session_token = $creds['Token'] ?? '';
         return true;
    }
 
    // -------------------------------------------------------------------------
    // AWS Signature Version 4
    // -------------------------------------------------------------------------
 
    /**
     * Build all required HTTP headers (including the Authorization header) for
     * an AWS Signature Version 4 signed request.
     *
     * @param string $method  HTTP verb (GET, POST, PUT, DELETE, …)
     * @param string $url     Full request URL
     * @param string $payload Request body (empty string for GET/DELETE)
     * @param array  $headers Additional headers to include in the signature
     *
     * @return array Associative array of signed headers ready for wp_remote_*
     */
    protected function sign_request( string $method, string $url, string $payload = '', array $headers = [] ): array {
        $parsed       = parse_url( $url );
        $host         = $parsed['host'];
        $path         = $parsed['path'] ?? '/';
        $query_string = $parsed['query'] ?? '';
 
        $datetime = gmdate( 'Ymd\THis\Z' );
        $date     = gmdate( 'Ymd' );
 
        // Merge required headers
        $headers['host']        = $host;
        $headers['x-amz-date'] = $datetime;
 
        if ( ! empty( $this->session_token ) ) {
            $headers['x-amz-security-token'] = $this->session_token;
        }
 
        // Canonical headers require lowercase, sorted keys
        ksort( $headers );
 
        $canonical_headers = '';
        $signed_headers_parts = [];
        foreach ( $headers as $name => $value ) {
            $lc_name = strtolower( $name );
            $canonical_headers      .= $lc_name . ':' . trim( (string) $value ) . "\n";
            $signed_headers_parts[]  = $lc_name;
        }
        $signed_headers = implode( ';', $signed_headers_parts );
 
        $payload_hash = hash( 'sha256', $payload );
 
        // Task 1: Canonical request
        $canonical_request = implode( "\n", [
            strtoupper( $method ),
            $path,
            $query_string,
            $canonical_headers,
            $signed_headers,
            $payload_hash,
        ] );
 
        // Task 2: String to sign
        $credential_scope = implode( '/', [ $date, $this->region, $this->service, 'aws4_request' ] );
        $string_to_sign   = implode( "\n", [
            'AWS4-HMAC-SHA256',
            $datetime,
            $credential_scope,
            hash( 'sha256', $canonical_request ),
        ] );
 
        // Task 3: Signing key (derived HMAC chain)
        $signing_key = $this->derive_signing_key( $date );
 
        // Task 4: Signature
        $signature = hash_hmac( 'sha256', $string_to_sign, $signing_key );
 
        // Task 5: Authorization header
        $headers['Authorization'] = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $this->access_key,
            $credential_scope,
            $signed_headers,
            $signature
        );
 
        return $headers;
    }
 
    /**
     * Derive the SigV4 signing key via the standard HMAC-SHA256 derivation chain.
     *
     * @param string $date Date string in Ymd format (e.g. 20240101)
     * @return string Raw binary signing key
     */
    protected function derive_signing_key( string $date ): string {
        $k_date    = hash_hmac( 'sha256', $date,              'AWS4' . $this->secret_key, true );
        $k_region  = hash_hmac( 'sha256', $this->region,     $k_date,   true );
        $k_service = hash_hmac( 'sha256', $this->service,    $k_region, true );
        $k_signing = hash_hmac( 'sha256', 'aws4_request',    $k_service, true );
        return $k_signing;
    }
 
    // -------------------------------------------------------------------------
    // HTTP helpers
    // -------------------------------------------------------------------------
 
    protected function http_request_curl( string $method, string $url, array $args = [] ) {

        $curl = curl_init();
        curl_setopt_array( $curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper( $method ),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'FrontPup/'. FRONTPUP_VERSION .' (https://www.frontpup.com)',
        ] );
        if ( isset( $args['headers'] ) && is_array( $args['headers'] ) ) {
            $headersAsArrayOfStrings = [];
            foreach ( $args['headers'] as $key => $value ) {
                $headersAsArrayOfStrings[] = ucwords($key, '-') . ': ' . $value;
            }
            curl_setopt( $curl, CURLOPT_HTTPHEADER, $headersAsArrayOfStrings );
        }
        if ( isset( $args['body'] ) ) {
            curl_setopt( $curl, CURLOPT_POSTFIELDS, $args['body'] );
        }
        $response = curl_exec( $curl );
        if ( $response === false ) {
            $error = curl_error( $curl );
            $error_code = curl_errno( $curl );
            curl_close( $curl );
            $this->set_last_error( $error, $error_code );
            return false;
        }
        $status_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close( $curl );
        return $this->parse_response( $response, $status_code );
    }

    /**
     * Http request function
     */
    protected function http_request( string $method, string $url, array $args = [] ) {

        // If curl library is available, use that logic to pass to parse_response and return
        if( function_exists('curl_version') ) {
            return $this->http_request_curl( $method, $url, $args );
        }

        $headersAsArrayOfStrings = [];
        if ( isset( $args['headers'] ) && is_array( $args['headers'] ) ) {
            foreach ( $args['headers'] as $key => $value ) {
                $headersAsArrayOfStrings[] = ucwords($key, '-') . ': ' . $value;
            }
        } else {
            $args['headers'] = [];
        }
        
        // Make sure Content-Type is found in the headers
        if ( ! array_key_exists( 'Content-Type', $args['headers'] ) && ! array_key_exists( 'content-type', $args['headers'] ) ) {
            $headersAsArrayOfStrings[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        // Use native PHP functions stream_context_create() rather than WordPress HTTP
        $options = [
            'http' => [
                'ignore_errors' => true, // Get the response body even on HTTP error status codes
                'method'  => strtoupper( $method ),
                'header'  => $headersAsArrayOfStrings,
                'content' => $args['body'] ?? '',
                'timeout' => $this->timeout,
                'user_agent' => 'FrontPup/'. FRONTPUP_VERSION .' (https://www.frontpup.com)',
            ],
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $status_code = 0;
        if (isset($http_response_header)) {
            // Extract the HTTP status code from the response headers
            foreach ($http_response_header as $header) {
                if (preg_match('#HTTP/\d+\.\d+\s+(\d+)#', $header, $matches)) {
                    $status_code = (int) $matches[1];
                    break;
                }
            }
        }

        if ($result === false) {
            $error = error_get_last();
            $this->set_last_error( $error['message'] ?? 'Unknown error occurred during HTTP request.', $status_code );
            return false;
        }
        if( $status_code == 0 ) {
            // No status code found, treat as error
             $this->set_last_error( 'No HTTP response received.' );
             return false;
        }
        return $this->parse_response( $result, $status_code );
    }

    /**
     * Execute a signed HTTP request using the internal http_request() transport.
     *
     * The base implementation prefers cURL and falls back to PHP stream
     * functions (stream_context_create()/file_get_contents()). WordPress-
     * specific subclasses may override this method to route requests via the
     * WordPress HTTP API instead.
     *
     * @param string $method        HTTP verb
     * @param string $url           Request URL
     * @param string $body          Request body
     * @param array  $extra_headers Additional headers (e.g. Content-Type)
     *
     * @return array|false Response array on success, false on failure (use get_last_error() for details)
     */
    protected function request( string $method, string $url, string $body = '', array $extra_headers = [] ) {

        if( !$this->load_iam_credentials() ) {
            // Failed to load credentials, error message should already be set by load_iam_credentials()
            return false;
        }

        $headers_to_sign = $extra_headers;
        $response = null;

        // Retry $this->retries times in case of transient errors (e.g. IMDS timeouts, network issues)
        for( $attempt = 1; $attempt <= $this->retries; $attempt++ ) {
            $this->clear_last_error(); // Clear any previous error before each attempt
            
            // Let SigV4 sign the full header set (including host & x-amz-date)
            $signed = $this->sign_request( $method, $url, $body, $headers_to_sign );
    
            // Build the final header array, dropping 'host' (WordPress / cURL adds it)
            $wp_headers = [];
            foreach ( $signed as $name => $value ) {
                if ( strtolower( $name ) === 'host' ) {
                    continue;
                }
                $wp_headers[ $name ] = $value;
            }
    
            $args = [
                'method'  => strtoupper( $method ),
                'headers' => $wp_headers,
                'timeout' => $this->timeout,
                'user-agent' => 'FrontPup/'. FRONTPUP_VERSION .' (https://www.frontpup.com)',
            ];
    
            if ( $body !== '' ) {
                $args['body'] = $body;
            }

            // We are catching our own thrown errors, we will need to re-throw on the last retry if it fails all attempts
            if( self::$EXCEPTIONS_ENABLED ) {
                try {
                    $response = $this->http_request( $method, $url, $args );
                    if( $response !== false ) {
                        break;
                    }
                } catch ( \Exception $e ) {
                    // If an exception was thrown, check if we have retries left before re-throwing
                    if( $attempt >= $this->retries ) {
                        // Last attempt, re-throw the exception
                        throw $e;
                    }
                }
            } else {
                $response = $this->http_request( $method, $url, $args );
                if( $response !== false ) {
                    break;
                }
            }
        }

        return $response; // Returns false on failure, or the parsed response array on success. The child class's http_request method is responsible for calling parse_response and returning the final result.
    }
 
    /**
     * Signed HTTP GET request.
     *
     * @param string $url
     * @param array  $headers
     * @return array|WP_Error
     */
    protected function get( string $url, array $headers = [] ) {
        return $this->request( 'GET', $url, '', $headers );
    }
 
    /**
     * Signed HTTP POST request.
     *
     * @param string $url
     * @param string $body
     * @param array  $headers
     * @return array|WP_Error
     */
    protected function post( string $url, string $body = '', array $headers = [] ) {
        return $this->request( 'POST', $url, $body, $headers );
    }
 
    /**
     * Signed HTTP PUT request.
     *
     * @param string $url
     * @param string $body
     * @param array  $headers
     * @return array|WP_Error
     */
    protected function put( string $url, string $body = '', array $headers = [] ) {
        return $this->request( 'PUT', $url, $body, $headers );
    }
 
    /**
     * Signed HTTP DELETE request.
     *
     * @param string $url
     * @param array  $headers
     * @return array|WP_Error
     */
    protected function delete( string $url, array $headers = [] ) {
        return $this->request( 'DELETE', $url, '', $headers );
    }

    /**
     * Signed HTTP PATCH request.
     *
     * @param string $url
     * @param string $body
     * @param array  $headers
     * @return array|WP_Error
     */
    protected function patch( string $url, string $body = '', array $headers = [] ) {
        return $this->request( 'PATCH', $url, $body, $headers );
    }

    /**
     * Signed HTTP HEAD request.
     *
     * @param string $url
     * @param array  $headers
     * @return array|WP_Error
     */
    protected function head( string $url, array $headers = [] ) {
        return $this->request( 'HEAD', $url, '', $headers );
    }

    /**
     * Parse an HTTP response from wp_remote_*.
     *
     * Returns an associative array with:
     *   - status_code (int)
     *   - body (string)
     *   - parsed (SimpleXMLElement|null)  – parsed XML on success
     *
     * @param array|WP_Error $response WordPress HTTP response
     *
     * @return array
     * @throws \Exception on HTTP 4xx/5xx status codes.
     */
    protected function parse_response( $body, $status_code ): array|false {
 
        if ( $status_code >= 400 ) {
            $this->set_last_error( $this->extract_error_message( $body, $status_code ), $status_code );
            return false;
        }
 
        return [
            'status_code' => $status_code,
            'body'        => $body,
            'parsed'      => $this->parse_xml( $body ),
        ];
    }
 
    /**
     * Attempt to parse an XML string; returns null on failure.
     *
     * @param string $xml_string
     * @return SimpleXMLElement|null
     */
    protected function parse_xml( string $xml_string ): ?\SimpleXMLElement {
        if ( $xml_string === '' ) {
            return null;
        }
 
        $prev = libxml_use_internal_errors( true );
        $xml  = simplexml_load_string( $xml_string );
        libxml_use_internal_errors( $prev );
 
        return ( $xml instanceof \SimpleXMLElement ) ? $xml : null;
    }
 
    /**
     * Extract a human-readable error message from a CloudFront XML error body.
     *
     * @param string $body        Raw response body
     * @param int    $status_code HTTP status code
     *
     * @return string
     */
    protected function extract_error_message( string $body, int $status_code ): string {
        $xml = $this->parse_xml( $body );
 
        if ( $xml !== null ) {
            // CloudFront error envelope: <ErrorResponse><Error><Message>…
            if ( isset( $xml->Error->Message ) ) {
                return (string) $xml->Error->Message;
            }
            // Simpler envelope: <ErrorResponse><Message>…
            if ( isset( $xml->Message ) ) {
                return (string) $xml->Message;
            }
        }
 
        return 'CloudFront API error (HTTP ' . $status_code . ')';
    }
}
 
// eof