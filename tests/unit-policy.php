<?php
error_reporting( E_ALL );
define( 'ABSPATH', __DIR__ . '/' ); define( 'AUTH_KEY', 'unit-auth-key' ); define( 'SECURE_AUTH_SALT', 'unit-secure-salt' );
function __( $s ) { return $s; } function apply_filters( $tag, $value ) { return $value; } function get_current_user_id(){ return 7; }
function absint($v){return abs((int)$v);} function sanitize_key($v){return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$v));}
function sanitize_text_field($v){return trim(strip_tags((string)$v));} function wp_unslash($v){return $v;} function wp_strip_all_tags($v){return strip_tags((string)$v);}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);} function is_wp_error($v){return $v instanceof WP_Error;} function wp_generate_uuid4(){return '11111111-2222-4333-8444-555555555555';}
class WP_Error { private $code; private $message; public function __construct($c='',$m='',$d=null){$this->code=$c;$this->message=$m;} public function get_error_message(){return $this->message;} }
require_once __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-database.php';
require_once __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-policy.php';
if ( 'single-free-tier-v2' !== LSCH_Policy::access_model() ) { throw new RuntimeException('Free-tier law failed'); }
$key=LSCH_Policy::idempotency_key('abcDEF-1234567890',7,'enroll'); if(is_wp_error($key)||64!==strlen($key)){throw new RuntimeException('Idempotency failed');}
$bad=LSCH_Policy::idempotency_key('short',7,'enroll'); if(!is_wp_error($bad)){throw new RuntimeException('Invalid idempotency accepted');}
$json=LSCH_Policy::sanitize_json(array('a'=>1,'b'=>'دو')); if(is_wp_error($json)||json_decode($json,true)['b']!=='دو'){throw new RuntimeException('JSON failed');}
$enc=LSCH_Policy::encrypt_note('private note',7,99); if(is_wp_error($enc)){throw new RuntimeException($enc->get_error_message());}
$dec=LSCH_Policy::decrypt_note($enc,7,99); if('private note'!==$dec){throw new RuntimeException('Encryption round-trip failed');}
if ( LSCH_Database::uuid() !== '11111111-2222-4333-8444-555555555555' ) { throw new RuntimeException('UUID failed'); }
echo "PASS: policy, idempotency, JSON, encryption and UUID tests\n";
