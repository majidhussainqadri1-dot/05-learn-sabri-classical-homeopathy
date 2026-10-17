<?php
/** Exact-source regression: private note GET must propagate WP_Error without fatal header calls. */
define( 'ABSPATH', '/file05-test/' );

class WP_Error {
    public $code;
    public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; }
}
class WP_REST_Request extends ArrayObject {}
class WP_REST_Response {
    public $data;
    public $headers = array();
    public function __construct( $data ) { $this->data = $data; }
    public function header( $name, $value ) { $this->headers[ $name ] = $value; }
}
class LSCH_Services {
    public static $next;
    public static function get_note( $lesson_id, $user_id ) {
        if ( 17 !== $lesson_id || 5 !== $user_id ) { throw new RuntimeException( 'Wrong note identity.' ); }
        return self::$next;
    }
}
function absint( $value ) { return abs( (int) $value ); }
function get_current_user_id() { return 5; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
/** WordPress rest_ensure_response() explicitly preserves WP_Error objects. */
function rest_ensure_response( $value ) {
    return is_wp_error( $value ) ? $value : new WP_REST_Response( $value );
}

require dirname( __DIR__ ) . '/05-learn-sabri-classical-homeopathy/includes/class-lsch-rest.php';

$request = new WP_REST_Request( array( 'id' => 17 ) );
$handler = new LSCH_REST();
foreach ( array( 'lsch_note_read_failed', 'lsch_note_authentication_failed' ) as $code ) {
    $failure = new WP_Error( $code );
    LSCH_Services::$next = $failure;
    try {
        $actual = $handler->note_get( $request );
    } catch ( Throwable $e ) {
        fwrite( STDERR, 'FAIL: private note error triggered a fatal response-header call: ' . $e->getMessage() . PHP_EOL );
        exit( 1 );
    }
    if ( $actual !== $failure ) {
        fwrite( STDERR, 'FAIL: private note WP_Error was not propagated unchanged.' . PHP_EOL );
        exit( 1 );
    }
}
LSCH_Services::$next = array( 'lesson_id' => 17, 'note' => 'private', 'version' => 2 );
$success = $handler->note_get( $request );
if ( ! $success instanceof WP_REST_Response || 'private, no-store' !== ( $success->headers['Cache-Control'] ?? null ) ) {
    fwrite( STDERR, 'FAIL: successful private note response lost its no-store header.' . PHP_EOL );
    exit( 1 );
}
echo 'PASS: private note REST errors propagate and successful responses remain no-store.' . PHP_EOL;
