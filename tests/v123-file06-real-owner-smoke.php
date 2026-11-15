<?php
/** Real File06 2.4.20 candidate + File05 4.1.0 negative source-owner test, disposable WP. */
defined('ABSPATH')||exit;
function f05_f06_check($ok,$why){$GLOBALS['f05_f06_checks']++;if(!$ok){fwrite(STDERR,"FAILED: ".$why."\n");exit(1);}}
$GLOBALS['f05_f06_checks']=0;
f05_f06_check(defined('HE_VERSION')&&HE_VERSION==='2.4.20','Pinned real File06 2.4.20 candidate loaded');
f05_f06_check(defined('LSCH_VERSION')&&LSCH_VERSION==='4.1.0','File05 candidate loaded');
f05_f06_check(class_exists('HE_V12_Case_Consent_Owner'),'File06 canonical assertion owner code loaded');
f05_f06_check(class_exists('LSCH_File06_Source_Links'),'File05 scholarly-link integration present');
f05_f06_check(LSCH_Dependencies::runtime_ready(),'Real core peers loaded');
$uuid='11111111-1111-4111-8111-111111111111';
$invalid=apply_filters('he_v12_file06_concept_assertion_v1',null,'not-a-valid-uuid',1);
f05_f06_check(null===$invalid,'Malformed canonical concept cannot be approved');
$missing=apply_filters('he_v12_file06_concept_assertion_v1',null,$uuid,1);
f05_f06_check(null===$missing,'Unknown/unpublished File06 concept cannot be attested');
$stale=apply_filters('he_v12_file06_concept_assertion_v1',null,$uuid,9999);
f05_f06_check(null===$stale,'Unverified high version cannot be attested');
$links=LSCH_File06_Source_Links::collect(array(),array('id'=>$uuid,'version'=>1));
f05_f06_check(is_array($links)&&count($links)===0,'File05 cannot invent related lesson links');
$outside=LSCH_File06_Source_Links::collect(array(),array('id'=>'invalid','version'=>1));
f05_f06_check(is_array($outside)&&count($outside)===0,'Malformed File06 DTO cannot yield File05 source link');
$he_status=HE_V2_Schema::runtime_status();
echo 'FILE06 CANONICAL RUNTIME '.sanitize_key((string)$he_status)."\n";
f05_f06_check('active'===$he_status,'Real File06 candidate canonical schema must be active');
echo "PASS: Real File06 candidate denies unknown, unpublished, malformed and stale canonical claims, ".
    $GLOBALS['f05_f06_checks']." checks. NOT editorial/staging/live acceptance.\n";
