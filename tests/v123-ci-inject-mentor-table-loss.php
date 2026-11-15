<?php
/** CI-only mutation: simulate a missing already-marked schema table in isolated MariaDB. */
defined('ABSPATH')||exit;
if(!class_exists('LSCH_Mentor_History')||!LSCH_Mentor_History::ready()){fwrite(STDERR,"Precondition: mentor table not ready\n");exit(1);}
global $wpdb;
$table=LSCH_Mentor_History::table();
if(!preg_match('/^[A-Za-z0-9_]+$/',$table)){fwrite(STDERR,"Unexpected table name\n");exit(1);}
$deleted=$wpdb->query("DROP TABLE ".$table);
if(false===$deleted){fwrite(STDERR,"Could not inject isolated missing-table failure\n");exit(1);}
if((int)get_option('lsch_mentor_history_schema',0)!==1){fwrite(STDERR,"Marker must remain stale at 1\n");exit(1);}
echo "PASS: CI-only stale schema state injected (marker 1, physical table missing). No production change.\n";
