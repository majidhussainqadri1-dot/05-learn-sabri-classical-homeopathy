<?php
/** File 05 canonical access metadata: malformed administrative input must not declassify objects. */
define( 'ABSPATH', '/unit-wordpress/' );
$GLOBALS['test_meta'] = array( '_lsch_access'=>'restricted', '_lsch_version'=>1 );
$GLOBALS['test_actions']=array();
class LSCH_Policy { public static function can_use_learning_actions( $id=0 ) { return true; } }
function get_post_type( $id ) { return 11===(int)$id ? 'lsch_lesson' : null; }
function get_post_meta( $id, $name, $single=true ) { return $GLOBALS['test_meta'][$name] ?? ''; }
function update_post_meta( $id, $name, $value ) { $GLOBALS['test_meta'][$name]=$value; return true; }
function wp_is_post_revision( $id ) { return false; }
function wp_is_post_autosave( $id ) { return false; }
function wp_verify_nonce( $nonce, $action ) { return $nonce === 'valid'; }
function wp_unslash( $data ) { return $data; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string)$value ) ); }
function sanitize_text_field( $value ) { return is_scalar($value) ? trim((string)$value) : ''; }
function sanitize_textarea_field( $value ) { return sanitize_text_field($value); }
function absint( $value ) { return abs((int)$value); }
function current_user_can( $name, $object_id=0 ) { return true; }
function wp_json_encode( $value, $flags=0 ) { return json_encode($value,$flags); }
function do_action( $hook, ...$args ) { $GLOBALS['test_actions'][]=$hook; }
function __( $text,$domain='' ) { return $text; }
function test_guard( $condition, $message ) { if(!$condition){fwrite(STDERR, 'FAIL: '.$message."\n");exit(1);} }
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php';
require __DIR__ . '/../05-learn-sabri-classical-homeopathy/includes/class-lsch-admin.php';
$admin=new LSCH_Admin();
$_POST=array('lsch_governance_nonce'=>'valid','lsch_meta'=>array('_lsch_access'=>'malformed-private-value'));
$admin->save_governance_meta(11,(object)array('ID'=>11));
test_guard('restricted'===$GLOBALS['test_meta']['_lsch_access'],'malformed string must not declassify');
test_guard(1===$GLOBALS['test_meta']['_lsch_version'],'rejected access must not cause content version drift');
$_POST['lsch_meta']['_lsch_access']=array('public');
$admin->save_governance_meta(11,(object)array('ID'=>11));
test_guard('restricted'===$GLOBALS['test_meta']['_lsch_access'],'array-valued access input rejected');
$_POST['lsch_meta']['_lsch_access']='account';
$admin->save_governance_meta(11,(object)array('ID'=>11));
test_guard('account'===$GLOBALS['test_meta']['_lsch_access'],'valid access transition persists');
test_guard(2===$GLOBALS['test_meta']['_lsch_version'],'valid access transition versions content once');
echo "PASS: File 05 administrative access allowlist and no-declassification.\n";
