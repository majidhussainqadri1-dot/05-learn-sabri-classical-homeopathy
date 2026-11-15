<?php
/** Canonical File05 → File26 public-only learning connector; proposed until source events and owner acceptance. */
defined( 'ABSPATH' ) || exit;
final class LSCH_File26_Connector {
    const SLUG='file05-public-learning';
    const TYPES=array('lsch_lesson'=>'lesson','lsch_course'=>'course','lsch_book'=>'book');
    public static function register(){
        add_filter('sabri_file26_connector_manifests',array(__CLASS__,'append'),20);
        add_action('sabri_file26_connectors_ready',array(__CLASS__,'ready'),20);
        if(function_exists('sabri_file26_register_connector')){sabri_file26_register_connector(self::manifest());}
    }
    public static function manifest(){
        return array(
            'slug'=>self::SLUG,'owner_file'=>'File 05','contract_version'=>'1.0',
            'entity_types'=>array('lesson','course','book'),
            'privacy_classes'=>array('public'),'visibility_fields'=>array('public'),
            'deletion_semantics'=>'owner_publication_tombstone_gate_pending',
            'status'=>'proposed','list_batch'=>array(__CLASS__,'list_batch'),
            'can_view'=>array(__CLASS__,'can_view'),'health'=>array(__CLASS__,'health'),
            'fetch_object'=>array(__CLASS__,'fetch_object'),
            'event_contract'=>'File05ContentVersion.v1.pending-file26-cutover',
            'index_schema'=>'sabri.file26.document.v1.1'
        );
    }
    public static function append($manifests){
        $manifests=is_array($manifests)?$manifests:array();
        foreach($manifests as $row){if(is_array($row)&&($row['slug']??'')===self::SLUG){return $manifests;}}
        $manifests[]=self::manifest(); return $manifests;
    }
    public static function ready($registry){if(is_object($registry)&&method_exists($registry,'register')){$registry->register(self::manifest());}}
    private static function eligible($post){
        if(!($post instanceof WP_Post)||!isset(self::TYPES[$post->post_type])||$post->post_status!=='publish'
            ||!class_exists('LSCH_Policy')||!class_exists('LSCH_Content')||!LSCH_Policy::publicly_visible($post->ID)){return false;}
        // Patient case educational material is never indexed even if a page could
        // be accessed after special consent; searchable public exposure is distinct.
        $terms=wp_get_post_terms($post->ID,'lsch_topic',array('fields'=>'slugs'));
        if(is_wp_error($terms)||!is_array($terms)){return new WP_Error('file05_search_taxonomy_unavailable','Learning source taxonomy is unavailable; retry the complete owner snapshot.');}
        return !in_array('patient-case-learning',$terms,true);
    }
    private static function project($post){
        $access=self::eligible($post);
        if(is_wp_error($access)){return $access;}
        if(!$access){return null;}
        $url=get_permalink($post);
        if(!is_string($url)||!wp_http_validate_url($url)){return null;}
        $title=sanitize_text_field(get_the_title($post));
        if($title===''){return null;}
        $excerpt=wp_strip_all_tags((string)$post->post_excerpt,true);
        $version=LSCH_Content::version($post->ID);
        if($version<1){return null;}
        return array(
            'connector_slug'=>self::SLUG,'domain'=>'learning','object_id'=>(string)$post->ID,
            'object_version'=>$version,'source_event_sequence'=>$version,
            'entity_type'=>self::TYPES[$post->post_type],
            'locale'=>substr(sanitize_text_field(get_post_meta($post->ID,'_lsch_language',true)?:'en-US'),0,20),
            'state'=>'published','visibility'=>'public','title'=>$title,
            'excerpt'=>$excerpt,'search_text'=>$title.' '.$excerpt,
            'canonical_url'=>$url,'safety_class'=>'education',
        );
    }
    /** Exact monotonic ID cursor with at most 50 scanned owner rows per page. */
    public static function list_batch($cursor='',$limit=50,$scope=array()){
        global $wpdb;
        if(!self::native_ready()){return new WP_Error('file05_search_unavailable','Canonical learning policy is unavailable.');}
        if(!is_array($scope)||count($scope)>0){return new WP_Error('file05_search_partial_scope','Only complete File05 owner snapshots are supported.');}
        if($cursor!==''&&(!is_string($cursor)||!preg_match('/^[0-9]{1,18}$/D',$cursor))){
            return new WP_Error('file05_search_invalid_cursor','Invalid source cursor.');
        }
        $id=(int)$cursor; $max=max(1,min(50,(int)$limit));
        $types=array_keys(self::TYPES);
        $types_placeholders=implode(',',array_fill(0,count($types),'%s'));
        $sql=$wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID>%d AND post_type IN ($types_placeholders) ORDER BY ID ASC LIMIT %d",
            array_merge(array($id),$types,array($max+1)));
        $wpdb->last_error='';
        $rows=$wpdb->get_col($sql);
        if(!is_array($rows)||''!==(string)$wpdb->last_error){return new WP_Error('file05_search_owner_db_error','Cannot read public learning source.');}
        $more=count($rows)>$max; if($more){array_pop($rows);}
        $items=array();
        foreach($rows as $row_id){
            $row_id=(int)$row_id;
            $wpdb->last_error='';
            $post=get_post($row_id);
            if(''!==(string)$wpdb->last_error){return new WP_Error('file05_search_owner_post_read_failed','Cannot read the source object consistently.');}
            $doc=self::project($post);
            if(is_wp_error($doc)){return $doc;}
            if(''!==(string)$wpdb->last_error){return new WP_Error('file05_search_owner_projection_failed','Cannot verify the source projection; retry.');}
            if($doc){$items[]=$doc;}
            $id=max($id,$row_id);
        }
        return array('items'=>$items,'done'=>!$more,'next_cursor'=>$more?(string)$id:'');
    }
    private static function native_ready(){
        return class_exists('LSCH_Dependencies')&&LSCH_Dependencies::runtime_ready()&&
            class_exists('LSCH_Policy')&&LSCH_Policy::central_policy_ready()&&
            class_exists('LSCH_Content')&&post_type_exists('lsch_lesson')&&
            post_type_exists('lsch_course')&&post_type_exists('lsch_book')&&!get_option('lsch_runtime_failure',false);
    }
    public static function fetch_object($entity_type,$public_id=''){
        if(!self::native_ready()){return null;}
        if(!is_string($entity_type)||!isset(self::TYPES['lsch_'.$entity_type])||!(is_int($public_id)||is_string($public_id))||!preg_match('/^[1-9][0-9]*$/D',(string)$public_id)||(string)(int)$public_id!==(string)$public_id){return null;}
        global $wpdb;
        $wpdb->last_error='';
        $post=get_post((int)$public_id);
        if(''!==(string)$wpdb->last_error||!($post instanceof WP_Post)||$post->post_type!=='lsch_'.$entity_type){return null;}
        $doc=self::project($post);
        return ''===(string)$wpdb->last_error&&!is_wp_error($doc)?$doc:null;
    }
    public static function can_view($document,$audience=array()){
        if(!is_array($document)||($document['connector_slug']??'')!==self::SLUG||($document['visibility']??'')!=='public'||($document['state']??'')!=='published'
            || !isset($document['entity_type'],$document['object_id'],$document['object_version'],$document['canonical_url'])
            || !is_string($document['entity_type']) || !(is_string($document['object_id'])||is_int($document['object_id']))
            || !(is_string($document['object_version'])||is_int($document['object_version']))
            || !is_string($document['canonical_url'])){return false;}
        $current=self::fetch_object((string)($document['entity_type']??''),(string)($document['object_id']??''));
        return is_array($current)
            && (string)($document['object_version']??'')===(string)$current['object_version']
            && (string)($document['canonical_url']??'')!==''
            && hash_equals((string)$current['canonical_url'],(string)$document['canonical_url']);
    }
    public static function health(){
        if(!self::native_ready()){return array('state'=>'degraded','owner_file'=>'File 05','reason'=>'learning_runtime_or_policy_unavailable');}
        return array('state'=>'healthy','owner_file'=>'File 05','status'=>'proposed',
            'requires_event_reconciliation'=>true,'public_only'=>true);
    }
}
