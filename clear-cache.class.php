<?php
/**
 * FrontPup Clear Cache Class
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class FrontPup_Clear_Cache {

  private $settings = [];

  private $last_error = '';
  private $result = null;

  /**
   * Constructor
   */
  public function __construct( $settings = [] ) {
      $this->settings = $settings;
  }

  /**
   * Clear Cache method
   */
  public function clear_cache() {
    // Implement the logic to clear the cache using AWS CloudFront API
    $initOptions = ['version' => 'latest', 'region'  => FRONTPUP_REGION ];

    // We cannot move forward if we do not have a distribution ID
    if( empty($this->settings['distribution_id']) ) {
        $this->set_last_error( 'Distribution ID not set in settings' );
        return false;
    }

    $mode = isset($this->settings['credentials_mode']) ? $this->settings['credentials_mode'] : 'policy';
    switch( $mode ) {
      case 'wpconfig': {
        if( defined('CLOUDFRONT_ACCESS_KEY_ID') && defined('CLOUDFRONT_SECRET_ACCESS_KEY') ) {
            $initOptions['credentials'] = [
              'key'    => CLOUDFRONT_ACCESS_KEY_ID,
              'secret' => CLOUDFRONT_SECRET_ACCESS_KEY,
            ];
        } else {
            $this->set_last_error( 'CLOUDFRONT_ACCESS_KEY_ID or CLOUDFRONT_SECRET_ACCESS_KEY not defined in wp-config.php' );
            return false;
        }
      } break;
      case 'database': {
        if( isset($this->settings['access_key_id']) && isset($this->settings['secret_access_key']) ) {
            $initOptions['credentials'] = [
              'key'    => $this->settings['access_key_id'],
              'secret' => $this->settings['secret_access_key'],
            ];
        } else {
            $this->set_last_error( 'Access Key ID or Secret Access Key not set in settings' );
            return false;
        }
      } break;
      case 'policy':
      default: {
        // Use IAM Role or environment variables
        break;
      }
    }

    /**
     * Load AWS SDK (minimal)
     * ref: https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.AwsClient.html#method___construct
     */
    if ( !class_exists('Aws\\Sdk')) { // AWS SDK was not loaded by another plugin
      if( !file_exists( plugin_dir_path( __FILE__ ) . 'aws/aws-autoloader.php' ) ) {
        $this->set_last_error( __('The AWS SDK is not available.', 'frontpup') );
        return false;
      }
      
      // Lets load our version of the AWS SDK
      require_once plugin_dir_path( __FILE__ ) . 'aws/aws-autoloader.php';
    }
    
    if ( !class_exists('Aws\\CloudFront\\CloudFrontClient')) {
      $this->set_last_error( __('The AWS CloudFront Client is not available.', 'frontpup') );
      return false;
    }

    try {
      $client = new Aws\CloudFront\CloudFrontClient($initOptions);

    
      $this->result = $client->createInvalidation([
          'DistributionId' => $this->settings['distribution_id'],
          'InvalidationBatch' => [
              'CallerReference' => (string) time(),
              'Paths' => [
                  'Quantity' => 1,
                  'Items' => ['/*'],
              ],
          ],
      ]);

    } catch (AwsException $e) {
      // Handle the exception if an error occurs
      // You can also get specific details about the AWS error
      //echo "AWS Error Code: " . $e->getAwsErrorCode() . "\n";
      //echo "AWS Error Message: " . $e->getAwsErrorMessage() . "\n";
      //echo "HTTP Status Code: " . $e->getStatusCode() . "\n";
      if( $e->getMessage() ) {
        $this->set_last_error( $e->getMessage() );
        return false;
      }
      $this->set_last_error( 'Unknown error occurred creating invalidation.' );
      return false;
    } catch (\Exception $e) {
      $this->set_last_error( $e->getMessage() );
      return false;
    }

    // For demonstration, let's assume it returns true on success
    return true;
  }

  /**
   * Get last error message
   */
  public function get_last_error() {
    return $this->last_error;
  }

  /**
   * Set last error message
   */
  public function set_last_error( $message ) {
    $this->last_error = $message;
  }

  /**
   * Get result of last clear_cache operation
   */
  public function get_result() {
    return $this->result;
  }
}

// eof