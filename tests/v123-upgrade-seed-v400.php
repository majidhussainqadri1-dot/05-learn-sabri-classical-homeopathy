<?php
/** Disposable old File05 4.0.0 DB fixture. Not an actual site's predecessor. */
defined('ABSPATH')||exit;
function old_assert($ok,$msg){if(!$ok){fwrite(STDERR,"FAILED BEFORE: ".$msg."\n");exit(1);}}
old_assert(defined('LSCH_VERSION')&&LSCH_VERSION==='4.0.0','Exact old source loaded');
old_assert((string)get_option('lsch_version','')==='4.0.0','Old installed version marker persisted');
old_assert((int)get_option('lsch_schema_version',0)===18,'Old core schema marker 18');
old_assert((int)get_option('lsch_future18_schema',0)===2,'Old future schema marker 2');
global $wpdb;
$t=LSCH_Future18::tables()['mentorship'];
$entries=array();
for($i=0;$i<101;$i++){$entries[]=array('mentor_id'=>77,'at'=>'2026-01-02T03:04:05+00:00','comment'=>'original-'.$i);}
$json=wp_json_encode($entries,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$now='2026-01-02 03:04:05';
$ok=$wpdb->insert($t,array('mentor_id'=>77,'learner_id'=>88,'course_id'=>0,
    'status'=>'active','goals_json'=>'[]','feedback_json'=>$json,'version'=>1,
    'created_at'=>$now,'updated_at'=>$now));
old_assert($ok===1,'Inserted old 101-record mentorship');
$id=(int)$wpdb->insert_id;
update_option('lsch_ci_old_mentor_id',$id,false);
update_option('lsch_ci_old_mentor_hash',hash('sha256',$json),false);
$bookmarks=LSCH_Database::tables()['bookmarks'];
$ok=$wpdb->insert($bookmarks,array('user_id'=>88,'object_type'=>'lsch_lesson',
    'object_id'=>999,'created_at'=>$now));
old_assert($ok===1,'Inserted old bookmark owned by test learner');
echo "PASS: File05 v4.0.0 historical parent 101 entries and 1 bookmark seeded in disposable MariaDB.\n";
