<?php
/** CI-only readback after injection on a new WordPress request. */
defined('ABSPATH')||exit;
$failure=get_option('lsch_runtime_failure',false);
if(!is_array($failure)||($failure['code']??'')!=='database_upgrade_failed'){
    fwrite(STDERR,"FAILED: partial DB loss must trigger retained runtime failure marker and safe pause\n");
    exit(1);
}
if(!class_exists('LSCH_Mentor_History')||LSCH_Mentor_History::ready()){
    fwrite(STDERR,"FAILED: stale schema must not be reported healthy\n");exit(1);
}
if(!class_exists('LSCH_Plugin')){
    fwrite(STDERR,"FAILED: candidate class files should load without authorizing runtime\n");exit(1);
}
echo "PASS: File05 rejects stale marker/physical-table parity in fresh WP request, retains diagnostics, does not mark ready.\n";
