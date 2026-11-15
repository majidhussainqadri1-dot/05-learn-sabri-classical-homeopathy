<?php
/**
 * Disposable WordPress + real MariaDB boundary test for File05 4.1.0.
 * Does NOT modify a real site; independent from historical deployed state.
 * Checks historical mentor feedback (0/99/100/101/500/1000), exact preservation,
 * append-only sequencing and fail-closed incomplete/tampered imports.
 */
defined('ABSPATH') || exit;
function f05_h_assert($v,$label){$GLOBALS['f05_history_checks']++;if(!$v){fwrite(STDERR,"FAIL: ".$label."\n");exit(1);}}
$GLOBALS['f05_history_checks']=0;
f05_h_assert(class_exists('LSCH_Mentor_History')&&LSCH_Mentor_History::ready(),'History schema is ready in disposable WP');
global $wpdb;
$table=LSCH_Mentor_History::table();
$cases=array(0,99,100,101,500,1000);
$base=85000;
foreach($cases as $case_index=>$size){
    $id=$base+$case_index;
    $entries=array();
    for($j=0;$j<$size;$j++){
        $entries[]=array('mentor_id'=>77,'at'=>'2026-01-02T03:04:05+00:00',
            'comment'=>'historic-'.$id.'-'.$j,'score'=>$j%6);
    }
    $json=wp_json_encode($entries,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $parent=array('id'=>$id,'mentor_id'=>77,'created_at'=>'2026-01-02 03:04:05','feedback_json'=>$json);
    $start=$wpdb->query('START TRANSACTION');
    f05_h_assert(false!==$start,'InnoDB outer transaction begins');
    $next=LSCH_Mentor_History::append($parent,77,array('comment'=>'next'));
    if(is_wp_error($next)){
        $wpdb->query('ROLLBACK');
        f05_h_assert(false,'No append for size '.$size.'; '. $next->get_error_code());
    }
    f05_h_assert((int)$next['sequence_no']===$size+1,'Legacy records all retained and next sequence assigned, size '.$size);
    $committed=$wpdb->query('COMMIT');
    f05_h_assert(false!==$committed,'InnoDB outer transaction commits');
    $rows=$wpdb->get_results($wpdb->prepare(
        "SELECT sequence_no,origin,feedback_json FROM {$table} WHERE mentorship_id=%d ORDER BY sequence_no ASC",
        $id
    ),ARRAY_A);
    f05_h_assert(is_array($rows)&&count($rows)===$size+1,'No truncated or duplicated history entries, size '.$size);
    f05_h_assert($rows[$size]['origin']==='native'&&(int)$rows[$size]['sequence_no']===$size+1,'Native append is latest only');
    foreach(array(0,(int)floor($size/2),$size-1) as $pos){
        if($size===0)break;
        $actual=json_decode($rows[$pos]['feedback_json'],true);
        f05_h_assert($actual===$entries[$pos],'Historical JSON preserved exactly at '.$pos.' of '.$size);
    }
    // A repeated read-only check cannot mutate old entries.
    $prefix=$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE mentorship_id=%d AND origin='legacy'",$id
    ));
    f05_h_assert((int)$prefix===$size,'Legacy prefix count retained, size '.$size);
}
// Incomplete normalization must stop rather than silently refill or append.
$partial=array();
for($j=0;$j<5;$j++){$partial[]=array('mentor_id'=>77,'at'=>'2026-01-02T03:04:05+00:00','comment'=>'partial-'.$j);}
$partial_id=87001;
$wpdb->insert($table,array('mentorship_id'=>$partial_id,'sequence_no'=>1,'mentor_id'=>77,
    'feedback_json'=>wp_json_encode($partial[0]),'origin'=>'legacy',
    'recorded_at'=>'2026-01-02 03:04:05','created_at'=>'2026-01-02 03:04:05'));
$p=array('id'=>$partial_id,'mentor_id'=>77,'created_at'=>'2026-01-02 03:04:05','feedback_json'=>wp_json_encode($partial));
$wpdb->query('START TRANSACTION');
$blocked=LSCH_Mentor_History::append($p,77,array('comment'=>'must-not-write'));
$wpdb->query('ROLLBACK');
f05_h_assert($blocked instanceof WP_Error&&$blocked->get_error_code()==='lsch_mentor_history_migration_partial',
    'Incomplete historical normalization blocks new writes');
f05_h_assert((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE mentorship_id=%d",$partial_id))===1,
    'Partial historical row left unchanged after rollback');
// Full but tampered normalized rows cannot be silently approved.
$tamper_id=87002;
for($j=0;$j<3;$j++){
    $entry=array('mentor_id'=>77,'at'=>'2026-01-02T03:04:05+00:00','comment'=>$j===1?'tampered':'original-'.$j);
    $wpdb->insert($table,array('mentorship_id'=>$tamper_id,'sequence_no'=>$j+1,'mentor_id'=>77,
       'feedback_json'=>wp_json_encode($entry),'origin'=>'legacy',
       'recorded_at'=>'2026-01-02 03:04:05','created_at'=>'2026-01-02 03:04:05'));
}
$original=array();
for($j=0;$j<3;$j++){$original[]=array('mentor_id'=>77,'at'=>'2026-01-02T03:04:05+00:00','comment'=>'original-'.$j);}
$parent=array('id'=>$tamper_id,'mentor_id'=>77,'created_at'=>'2026-01-02 03:04:05','feedback_json'=>wp_json_encode($original));
$wpdb->query('START TRANSACTION');
$blocked=LSCH_Mentor_History::append($parent,77,array('comment'=>'must-not-write'));
$wpdb->query('ROLLBACK');
f05_h_assert($blocked instanceof WP_Error&&$blocked->get_error_code()==='lsch_mentor_history_prefix_mismatch',
    'Tampered historical middle entry blocks new writes');
f05_h_assert((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE mentorship_id=%d",$tamper_id))===3,
    'Historical tamper proof remains without new row');
echo "PASS: File05 real MariaDB mentor history 0/99/100/101/500/1000, ".
    $GLOBALS['f05_history_checks']." assertions; NOT live/staging acceptance.\n";
