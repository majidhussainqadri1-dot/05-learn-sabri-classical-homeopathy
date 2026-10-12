<?php
/** Cycle K: private learning reads and certificate evidence fail closed on database outage. */
define('ABSPATH','/unit-wordpress/');define('ARRAY_A','ARRAY_A');
class WP_Error {public $code;function __construct($code,$message='',$data=array()){$this->code=$code;}}
class LSCH_Content {
	const COURSE='lsch_course';const LESSON='lsch_lesson';const ASSESSMENT='lsch_assessment';const ASSIGNMENT='lsch_assignment';
	public static function version($id){return 3;}
	public static function object_type($id){return LSCH_Content::LESSON;}
}
class LSCH_Policy {
	public static function can_use_learning_actions($user=0){return true;}
	public static function can_read_post($id,$user=0){return true;}
	public static function access_model(){return 'single-free-tier-v2';}
	public static function encrypt_note($note,$uid,$lid){return array('ciphertext'=>'cipher','iv'=>'iv','tag'=>'tag','key_version'=>2);}
	public static function request_id(){return 'cycle-k-trace';}
}
class LSCH_Dependencies {public static function claims($uid){return array('identity_verified'=>true);}}
class LSCH_Database {
	public static function now(){return '2026-10-12 10:00:00';}
	public static function tables(){return array('notes'=>'wp_notes','progress'=>'wp_progress','bookmarks'=>'wp_bookmarks','enrollments'=>'wp_enrollments','completions'=>'wp_completions','attempts'=>'wp_attempts');}
}
class LSCH_Events {public static function audit($a,$t,$i,$d,$p){}}
class ReadDB {
	public $last_error='',$mode='notes',$writes=0;
	public function prepare($sql,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];return vsprintf($sql,$args);}
	public function get_row($sql,$type=null){
		$this->last_error='';
		if(strpos($sql,'wp_enrollments')!==false)return array('status'=> $this->mode==='certificate'?'completed':'active','course_version'=>3,'terms_version'=>'single-free-tier-v2','version'=>1);
		if(strpos($sql,'wp_completions')!==false)return array('status'=>'earned','identity_assurance'=>'verified','integrity_status'=>'clear');
		if(strpos($sql,'wp_notes')!==false || strpos($sql,'wp_progress')!==false){
			$this->last_error='SQL connection unavailable';
			return null;
		}
		return null;
	}
	public function get_var($sql){
		if(strpos($sql,'wp_bookmarks')!==false)return false;
		if(strpos($sql,'wp_attempts')!==false)return false;
		return null;
	}
	public function insert($table,$row,$formats){$this->writes++;return 1;}
	public function update($table,$row,$where,$formats,$whereFormats){$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new ReadDB();
function __( $t,$d=''){return $t;}
function absint($x){return abs((int)$x);}
function is_wp_error($x){return $x instanceof WP_Error;}
function get_post_type($id){if((int)$id===7)return LSCH_Content::LESSON;if((int)$id===31)return LSCH_Content::ASSESSMENT;return LSCH_Content::COURSE;}
function get_post_meta($id,$key,$single=true){$meta=array('_lsch_course_id'=>9,'_lsch_required_components'=>'["content"]','_lsch_required'=>1,'_lsch_pass_mark'=>50,'_lsch_certificate_jurisdiction'=>'PK','_lsch_certificate_wording'=>'Learning award','_lsch_certificate_wording_approved'=>1);return isset($meta[$key])?$meta[$key]:'';}
function get_posts($args){if($args['post_type']===LSCH_Content::LESSON)return array(7);if($args['post_type']===LSCH_Content::ASSESSMENT)return array(31);return array();}
function sanitize_text_field($t){return (string)$t;}
function sanitize_textarea_field($t){return (string)$t;}
function check_k($yes,$msg){if(!$yes){fwrite(STDERR,"FAIL: $msg\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-services.php';
$note=LSCH_Services::save_note(7,22,'private draft',0);
check_k($note instanceof WP_Error&&$note->code==='lsch_note_read_failed','failed previous-note read must not try insert');
check_k($GLOBALS['wpdb']->writes===0,'unknown note state makes no DB insert');
$progress=LSCH_Services::progress(7,22,array('complete'=>true));
check_k($progress instanceof WP_Error&&$progress->code==='lsch_progress_read_failed','failed progress read prevents unverified version-one write');
check_k($GLOBALS['wpdb']->writes===0,'unknown progress state makes no DB insert');
$bookmark=LSCH_Services::toggle_bookmark('lsch_lesson',7,22);
check_k($bookmark instanceof WP_Error&&$bookmark->code==='lsch_bookmark_read_failed','failed bookmark read is not interpreted as absent');
check_k($GLOBALS['wpdb']->writes===0,'failed bookmark lookup must not toggle state');
$GLOBALS['wpdb']->mode='certificate';
$cert=LSCH_Services::certificate_readiness(9,22);
check_k($cert instanceof WP_Error&&$cert->code==='lsch_certificate_assessment_read_failed','unverified exam grade must not become a truthful zero in certificate readiness');
echo "PASS: File05 Cycle K private note/progress/bookmark and certificate evidence DB outage guards.\n";
