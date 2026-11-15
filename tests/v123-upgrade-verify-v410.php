<?php
/** Disposable code replacement v4.0.0 -> v4.1.0; not evidence of actual Live upgrade. */
defined('ABSPATH')||exit;
function up_assert($ok,$msg){$GLOBALS['up_checks']++;if(!$ok){fwrite(STDERR,"FAILED AFTER: ".$msg."\n");exit(1);}}
$GLOBALS['up_checks']=0;
up_assert(defined('LSCH_VERSION')&&LSCH_VERSION==='4.1.0','Current source code 4.1.0 loaded');
up_assert((int)get_option('lsch_schema_version',0)===18,'Core schema preserved');
up_assert((int)get_option('lsch_state_schema_version',0)===3,'Auxiliary state schema preserved');
up_assert((int)get_option('lsch_future18_schema',0)===2,'Future18 schema preserved');
up_assert(class_exists('LSCH_Mentor_History')&&LSCH_Mentor_History::ready(),'Mentor history v1 table now present');
up_assert((string)get_option('lsch_plan_version','')===LSCH_PLAN_VERSION,
    'The source provenance marker is refreshed on exact-source replacement, not misrepresented as V12.3 acceptance');
up_assert((string)get_option('lsch_version','')==='4.1.0',
    'Persistent installed-version marker must not remain 4.0.0 after a successful source upgrade');
global $wpdb;
$mentorship_id=(int)get_option('lsch_ci_old_mentor_id',0);
$t=LSCH_Future18::tables()['mentorship'];
$parent=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE id=%d",$mentorship_id),ARRAY_A);
up_assert(is_array($parent)&&count(json_decode($parent['feedback_json'],true))===101,'All old mentorship items retained in parent');
up_assert(hash_equals((string)get_option('lsch_ci_old_mentor_hash',''),hash('sha256',(string)$parent['feedback_json'])),
    'Exact historical parent JSON hash unchanged');
$bookmarks=LSCH_Database::tables()['bookmarks'];
up_assert((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$bookmarks} WHERE user_id=%d AND object_type=%s AND object_id=%d",
    88,'lsch_lesson',999))===1,'Original bookmark preserved');
$wpdb->query('START TRANSACTION');
$append=LSCH_Mentor_History::append($parent,77,array('comment'=>'new post-upgrade feedback'));
if(is_wp_error($append)){$wpdb->query('ROLLBACK');up_assert(false,'Append after upgrade failed '.$append->get_error_code());}
$wpdb->query('COMMIT');
up_assert((int)$append['sequence_no']===102,'Old 101 records prefixed and new record 102 appended');
$history=LSCH_Mentor_History::table();
$stats=$wpdb->get_results($wpdb->prepare(
    "SELECT origin,COUNT(*) AS total FROM {$history} WHERE mentorship_id=%d GROUP BY origin",$mentorship_id
),ARRAY_A);
$counts=array();foreach((array)$stats as $row){$counts[$row['origin']]=(int)$row['total'];}
up_assert(($counts['legacy']??0)===101 && ($counts['native']??0)===1,
    'Historic 101 entries migrated without truncation and one native append');
$latest=$wpdb->get_row($wpdb->prepare(
    "SELECT sequence_no,origin,feedback_json FROM {$history} WHERE mentorship_id=%d ORDER BY sequence_no DESC LIMIT 1",
    $mentorship_id),ARRAY_A);
up_assert((int)$latest['sequence_no']===102 && $latest['origin']==='native',
    'Latest historical sequence remains monotonic');
$unchanged=$wpdb->get_var($wpdb->prepare("SELECT feedback_json FROM {$t} WHERE id=%d",$mentorship_id));
up_assert(hash_equals((string)get_option('lsch_ci_old_mentor_hash',''),hash('sha256',(string)$unchanged)),
    'Historical parent JSON remains immutable after append');
echo "PASS: File05 v4.0.0->4.1.0 disposable schema/data/version/101-mentor upgrade: {$GLOBALS['up_checks']} checks.\n";
