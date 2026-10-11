<?php
/** File05 note rotation never reports a successful completion for unavailable DB reads. */
define('ABSPATH','/wordpress/');
define('ARRAY_A','ARRAY_A');
class LSCH_Policy {const NOTE_KEY_VERSION=2;
	public static function note_write_key_version(){return 3;}
	public static function note_encryption_ready(){return true;}
}
class LSCH_Database {public static function tables(){return array('notes'=>'wp_lsch_notes');}}
class LSCH_Events {public static $audit=array();public static function audit($name,$type,$id,$data=array(),$purpose=''){self::$audit[]=$name;}}
class FakeRotationDB {
	public $failRead=true;public $failCount=false;
	public function prepare($query,...$args){return vsprintf($query,$args);}
	public function get_results($query,$mode=null){return $this->failRead?null:array();}
	public function get_var($query){return $this->failCount?null:0;}
}
$GLOBALS['wpdb']=new FakeRotationDB();
function absint($x){return abs((int)$x);}
function check_rot($condition,$message){if(!$condition){fwrite(STDERR,"FAIL: ".$message."\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-operations.php';
$res=LSCH_Operations::rotate_note_keys();
check_rot('failed'===$res['status']&&null===$res['remaining']&&$res['failed']>0,'DB row read failure explicitly reported');
check_rot(in_array('private_note_key_rotation_read_failed',LSCH_Events::$audit,true),'DB row failure audited');
$GLOBALS['wpdb']->failRead=false;$GLOBALS['wpdb']->failCount=true;
$res=LSCH_Operations::rotate_note_keys();
check_rot('partial'===$res['status']&&null===$res['remaining']&&$res['failed']>0,'DB remaining count failure cannot report fully rotated');
$GLOBALS['wpdb']->failCount=false;
$res=LSCH_Operations::rotate_note_keys();
check_rot('ok'===$res['status']&&$res['remaining']===0,'healthy empty rotation is successful');
echo "PASS: File05 note rotation read/count failure and healthy-empty regressions.\n";
