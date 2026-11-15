<?php
/**
 * File05 / real File26 1.3.0 proposed connector acceptance in disposable WP+MariaDB.
 * Mandatory: a proposed owner adapter must never be active or index-eligible.
 * This is NOT a cutover, Hostinger staging, or live-deployed verification.
 */
defined('ABSPATH')||exit;
function f05_f26_check($v,$label){$GLOBALS['f05_f26_checks']++;if(!$v){fwrite(STDERR,"FAILED: ".$label."\n");exit(1);}}
$GLOBALS['f05_f26_checks']=0;
f05_f26_check(defined('SABRI_FILE26_VERSION')&&SABRI_FILE26_VERSION==='1.3.0','Pinned File26 source loaded');
f05_f26_check(defined('LSCH_VERSION')&&LSCH_VERSION==='4.1.0','File05 candidate source loaded');
f05_f26_check(class_exists('Sabri\\File26\\Plugin'),'File26 real owner plugin registered');
f05_f26_check(class_exists('LSCH_File26_Connector'),'File05 connector present');
f05_f26_check(class_exists('LSCH_Dependencies')&&LSCH_Dependencies::runtime_ready(),'Core owner dependencies valid');
$owner=\Sabri\File26\Plugin::instance();
$registry=$owner->connectors();
f05_f26_check(is_object($registry),'Real File26 connector registry ready');
$slug=LSCH_File26_Connector::SLUG;
$manifest=$registry->get($slug);
f05_f26_check(is_array($manifest)&&$manifest['owner_file']==='File 05','File05 visible in real File26 registry');
f05_f26_check(($manifest['status']??'')==='proposed','File05 proposal status persists under File26 lifecycle');
f05_f26_check($registry->is_active($slug)===false,'Proposed File05 connector is NOT active');
f05_f26_check($registry->is_index_eligible($slug)===false,'Proposed connector cannot enter global search index');
f05_f26_check(LSCH_File26_Connector::manifest()['status']==='proposed','File05 code cannot unilaterally approve index activation');
f05_f26_check(LSCH_File26_Connector::health()['status']==='proposed','File05 owner health says proposed');
global $wpdb;
$table=\Sabri\File26\DB::table('connectors');
$persisted=$wpdb->get_row($wpdb->prepare("SELECT slug,owner_file,contract_version,status FROM {$table} WHERE slug=%s",$slug),ARRAY_A);
f05_f26_check(is_array($persisted)&&$persisted['status']==='proposed','Real File26 database persists proposed state');
$doc=array(
  'connector_slug'=>$slug,'domain'=>'learning','object_id'=>'17','object_version'=>1,
  'entity_type'=>'lesson','locale'=>'en-US','state'=>'published',
  'visibility'=>'public','title'=>'CI-only blocked document','canonical_url'=>'https://example.invalid/lesson/17'
);
$result=$registry->validate_document($doc);
f05_f26_check($result instanceof WP_Error&&$result->get_error_code()==='file26_connector_not_eligible',
  'Real File26 rejects indexing a proposed owner document');
$rows=(int)$wpdb->get_var($wpdb->prepare(
  "SELECT COUNT(*) FROM ".\Sabri\File26\DB::table('documents')." WHERE connector_slug=%s",$slug
));
f05_f26_check($rows===0,'No File05 document indexed or exposed by proposal');
echo "PASS: Real File26 1.3.0 File05 proposed owner registration, no-index governance, ".
  $GLOBALS['f05_f26_checks']." checks. NOT staging/live.\n";
