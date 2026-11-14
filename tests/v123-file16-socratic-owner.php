<?php
/** File05 FUT17 File16 provider/citation source-only contract; NOT a real AI or medical test. */
declare(strict_types=1);define('ABSPATH',__DIR__);
class WP_Error {
    private $c; public function __construct($code,$message='',$data=array()){$this->c=$code;}
    public function get_error_code(){return $this->c;}
}
function is_wp_error($v){return $v instanceof WP_Error;}
function absint($v){return abs((int)$v);}
function __($s,$domain=''){return $s;}
function sanitize_textarea_field($s){return trim((string)$s);}
function sanitize_text_field($s){return trim((string)$s);}
function esc_url_raw($s){return trim((string)$s);}
function wp_kses_post($s){return strip_tags((string)$s);}
function get_post_type($id){return $id===17?'lsch_lesson':'';}
function get_post_meta($id,$key,$single=true){return $key==='_lsch_objectives'?'Learn source criticism':'';}
class LSCH_Content {const LESSON='lsch_lesson';public static function version($id){return 8;}}
class LSCH_Policy {
    public static function can_use_learning_actions($id){return $id===23;}
    public static function can_read_post($id,$user_id){return $id===17&&$user_id===23;}
}
class LSCH_Value {
    public static function sources($lesson_id){
        return array(array('title'=>'Approved Original Source','url'=>'https://example.org/sources/original',
            'author'=>'A. Scholar','edition'=>'1890','year'=>'1890','pages'=>'21'));
    }
}
class LSCH_Events {public static function audit($a,$b,$c,$d,$e){$GLOBALS['audits']++;}}
function apply_filters($name,$value,...$args){
    if($name==='lsch_file16_socratic_tutor'){
        $GLOBALS['context']=$args[0];
        if($GLOBALS['provider']==='throw')throw new RuntimeException('provider connection failed');
        if($GLOBALS['provider']==='absent')return null;
        if($GLOBALS['provider']==='bad_shape')return array('text'=>array('unsafe'),'citations'=>array());
        if($GLOBALS['provider']==='too_long')return array('text'=>str_repeat('A',12001),'citations'=>array());
        $citation=array('owner_file'=>'','object_id'=>'','title'=>'Claim','url'=>'https://example.org/sources/original');
        if($GLOBALS['provider']==='unapproved')$citation['url']='https://unapproved.example.net/fiction';
        if($GLOBALS['provider']==='array_field')$citation['url']=array('not'=>'scalar');
        if($GLOBALS['provider']==='long_list'){
            $citation['url']='https://unapproved.example.net/fiction';
            $copies=array_fill(0,21,$citation);
            $copies[20]['url']='https://example.org/sources/original';
            return array('text'=>'Education only','citations'=>$copies);
        }
        return array('text'=>'Education only','citations'=>array($citation));
    }
    if($name==='lsch_future18_tutor_citation_approved'){
        if(!empty($GLOBALS['throw_review']))throw new RuntimeException('reviewer offline');
        return $GLOBALS['review_decision'];
    }
    return $value;
}
function must($v,$label){$GLOBALS['checks']++;if(!$v){fwrite(STDERR,"FAILED: {$label}\n");exit(1);}}
$GLOBALS['checks']=0;$GLOBALS['audits']=0;$GLOBALS['review_decision']=true;$GLOBALS['throw_review']=false;
require dirname(__DIR__).'/05-learn-sabri-classical-homeopathy/includes/class-lsch-future18.php';
$call=function(){return LSCH_Future18::socratic_tutor(23,17,'What is the original source?','hint');};
$GLOBALS['provider']='absent';$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_provider_unavailable','Absent File16 provider fails closed');
$GLOBALS['provider']='throw';$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_provider_unavailable','Thrown provider failure degrades safely');
$GLOBALS['provider']='bad_shape';$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_provider_invalid','Non-string response refused');
$GLOBALS['provider']='too_long';$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_provider_invalid','Oversized answer refused');
$GLOBALS['provider']='unapproved';$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_source_required',
  'An external true filter must never upgrade an unrelated URL');
$GLOBALS['provider']='array_field';$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_source_required','Array fields cannot be coerced into valid citation');
$GLOBALS['provider']='long_list';$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_source_required','Provider may not hide an approved URL beyond the 20-citation cap');
$GLOBALS['provider']='approved';$GLOBALS['review_decision']=false;$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_source_required','Reviewer veto still enforced');
$GLOBALS['review_decision']=true;$GLOBALS['throw_review']=true;$r=$call();
must($r instanceof WP_Error&&$r->get_error_code()==='lsch_future18_tutor_source_required','Reviewer exception vetoes citation');
$GLOBALS['throw_review']=false;$r=$call();
must(is_array($r)&&count($r['citations'])===1&&$r['provider_owner']==='file16','Approved exact URL citation accepted');
must($GLOBALS['context']['contract']==='File16.SocraticTutor.v1'&&
    $GLOBALS['context']['safety']['diagnosis_authority']===false&&
    $GLOBALS['context']['safety']['prescription_authority']===false,'No clinical authority in provider context');
must($GLOBALS['audits']===1,'Only accepted educational answer audited as used');
echo "PASS: File05->File16 Socratic tutor provider/citation isolation: {$GLOBALS['checks']} checks.\n";
