<?php
/**
 * Executed by WP-CLI eval-file inside an EPHEMERAL CI WordPress + MariaDB site.
 * File00/01/20 are simulated by a test-only mu-plugin — not staging acceptance.
 */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress not loaded\n"); exit(1); }
function f05_ci_assert($condition,$name) {
    $GLOBALS['f05_ci_assertions']++;
    if (!$condition) {
        fwrite(STDERR, "FAIL: ".$name."\n");
        exit(1);
    }
}
$GLOBALS['f05_ci_assertions']=0;
f05_ci_assert(defined('LSCH_VERSION') && LSCH_VERSION==='4.1.0','Exact candidate File05 plugin loaded');
f05_ci_assert(class_exists('LSCH_Dependencies') && LSCH_Dependencies::runtime_ready(),'Disposable owners satisfy specified contract');
f05_ci_assert(LSCH_Policy::central_policy_ready(),'Exact free-tier and File26 ownership policy checked');
f05_ci_assert((int)get_option('lsch_schema_version',0)===18,'Core schema marker 18');
f05_ci_assert((int)get_option('lsch_state_schema_version',0)===3,'Auxiliary state schema marker 3');
f05_ci_assert((int)get_option('lsch_future18_schema',0)===2,'Future18 schema marker 2');
f05_ci_assert((int)get_option('lsch_mentor_history_schema',0)===1,'Mentor history schema marker 1');
f05_ci_assert(!get_option('lsch_runtime_failure',false),'No failed runtime upgrade marker');
foreach (array('lsch_program','lsch_course','lsch_book','lsch_lesson') as $type) {
    f05_ci_assert(post_type_exists($type),'Registered WordPress learning post type '.$type);
}
global $wpdb;
$all=array_merge(
    LSCH_Database::tables(),
    LSCH_State::tables(),
    LSCH_Future18::tables(),
    array('mentor_history'=>LSCH_Mentor_History::table())
);
foreach($all as $key=>$table){
    $exists=$wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=%s',
        $table
    ));
    f05_ci_assert((int)$exists===1,'Physical schema table '.$key);
}
$routes=rest_get_server()->get_routes();
$ns='/'.LSCH_REST::NS;
f05_ci_assert(isset($routes[$ns.'/catalog']),'Actual WordPress REST catalog registered');
f05_ci_assert(isset($routes[$ns.'/dashboard']),'Actual WordPress private dashboard route registered');
f05_ci_assert(isset($routes[$ns.'/future18/mastery']),'Actual Future18 REST route registered');
f05_ci_assert(isset($routes[$ns.'/file06/lesson/(?P<id>\d+)/approve-link']),'File06 approval route registered');
f05_ci_assert(LSCH_File26_Connector::manifest()['status']==='proposed','File26 adapter remains non-active');
f05_ci_assert(LSCH_File26_Connector::health()['state']==='healthy','Basic owner adapter health under disposable stub');
$empty=LSCH_File26_Connector::list_batch();
f05_ci_assert(is_array($empty)&&$empty['done']===true,'Empty public learning inventory scanned in real DB');
$id=wp_insert_post(array(
  'post_type'=>'lsch_lesson','post_status'=>'draft','post_title'=>'CI-only private draft',
  'post_content'=>'Test-owned disposable lesson'
),true);
f05_ci_assert(!is_wp_error($id)&&absint($id)>0,'WP creates disposable draft lesson');
f05_ci_assert(!LSCH_Policy::publicly_visible($id),'Draft never appears publicly');
wp_delete_post($id,true);
echo "PASS: Disposable WP+MariaDB File05 schema/REST/owner smoke, ".$GLOBALS['f05_ci_assertions']." assertions. NOT staging acceptance.\n";
