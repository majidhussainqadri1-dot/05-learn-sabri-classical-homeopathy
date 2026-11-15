<?php
/**
 * Real File00/01/20 cross-file contract smoke with pinned MAIN source snapshots.
 * Runs only on a disposable WordPress + MariaDB instance. Not Hostinger staging.
 */
defined('ABSPATH') || exit;
function f05_peer_check($ok,$reason){if(!$ok){fwrite(STDERR,"BLOCKED: {$reason}\n");exit(1);} }
$versions=array(
  'file00'=>defined('SMC_VERSION')?SMC_VERSION:'not_loaded',
  'file00_contract'=>defined('SMC_CONTRACT_VERSION')?SMC_CONTRACT_VERSION:'not_loaded',
  'file01'=>defined('SPF_VERSION')?SPF_VERSION:'not_loaded',
  'file20'=>defined('SABRI_SHELL_VERSION')?SABRI_SHELL_VERSION:'not_loaded',
  'file05'=>defined('LSCH_VERSION')?LSCH_VERSION:'not_loaded',
);
echo 'PINNED PEER SOURCE VERSIONS '.wp_json_encode($versions)."\n";
f05_peer_check(defined('SMC_VERSION')&&class_exists('SMC_Contracts'),'File00 real PHP owner did not boot');
f05_peer_check(defined('SPF_VERSION'),'File01 real PHP owner did not boot');
f05_peer_check(defined('SABRI_SHELL_VERSION'),'File20 real PHP owner did not boot');
f05_peer_check(function_exists('smc_policy'),'File00 smc_policy() owner is absent');
f05_peer_check(class_exists('LSCH_Dependencies'),'File05 dependencies class did not boot');
$missing=LSCH_Dependencies::missing();
echo 'FILE05 MISSING OWNERS '.wp_json_encode(array_keys($missing))."\n";
echo 'FILE00 DEFERRED MIGRATION STATUS '.sanitize_key((string)(get_option('smc_activation_bootstrap_state_v2',array())['status']??'unknown'))."\n";
f05_peer_check(is_array($missing)&&count($missing)===0,'File05 fails closed until File00/01/20 actual runtime owners are ready');
f05_peer_check(LSCH_Dependencies::runtime_ready(),'File05 does not attest peer readiness');
f05_peer_check(LSCH_Policy::central_policy_ready(),'Actual File00 policy does not match File05 free-tier/owner contract');
f05_peer_check((int)get_option('lsch_schema_version',0)===18,'Real peer WordPress does not have expected File05 core schema');
echo "PASS: File05 boots with pinned real File00/01/20 source in disposable WordPress; not staging acceptance.\n";
