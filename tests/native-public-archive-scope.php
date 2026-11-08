<?php
/** Public WordPress archives may never widen visibility using editor privileges. */
define('ABSPATH','/unit-wp/');
class WP_Post {public $ID,$post_type;public function __construct($id,$type){$this->ID=$id;$this->post_type=$type;}}
class WP_Query {public function is_main_query(){return true;}}
class LSCH_Policy {
  public static function publicly_visible($id){return $id===1;}
  public static function can_read_post($id){return true;} // editor intentionally sees every restricted post.
}
function is_admin(){return false;}
function require_true($v,$message){if(!$v){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require __DIR__.'/../05-learn-sabri-classical-homeopathy/includes/class-lsch-content.php';
$items=array(new WP_Post(1,LSCH_Content::COURSE),new WP_Post(2,LSCH_Content::COURSE),new WP_Post(3,LSCH_Content::LESSON),new WP_Post(4,'post'));
$visible=LSCH_Content::filter_native_results($items,new WP_Query());
require_true(count($visible)===2&&$visible[0]->ID===1&&$visible[1]->ID===4,'Public archive excludes restricted and unconsented case posts even for logged-in editors');
echo "PASS: Public native archive uses public visibility, never editor-specific read authorization.\n";
