<?php
/**
 * File 05 → File 06 v12 canonical lesson links.
 *
 * A learning lesson is returned only after a distinct File05 reviewer approves
 * the exact canonical File06 UUID, version, lesson version and source rights.
 * Free-text or title resemblance is NEVER evidence of a scholarly link.
 */
defined( 'ABSPATH' ) || exit;

final class LSCH_File06_Source_Links {
	const META_INDEX = '_lsch_file06_concept_public_id';
	const META_APPROVAL = '_lsch_file06_source_approval_v1';

	public static function hooks() {
		add_filter( 'he_v12_canonical_related_resources', array( __CLASS__, 'collect' ), 10, 2 );
		add_filter( 'he_v12_related_resource_owner_attested_v1', array( __CLASS__, 'attest' ), 10, 3 );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	private static function valid_uuid( $id ) {
		return is_string($id) && (bool) preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $id );
	}

	private static function valid_link( $lesson_id, $concept_public_id, $concept_version ) {
		$lesson_id = absint($lesson_id);
		$post = $lesson_id ? get_post($lesson_id) : null;
		if ( ! $post || LSCH_Content::LESSON !== $post->post_type || ! LSCH_Policy::publicly_visible( $lesson_id ) ) { return null; }
		$approval = get_post_meta($lesson_id, self::META_APPROVAL, true);
		if ( ! is_array($approval) || true !== ($approval['approved'] ?? false)
			|| ! self::valid_uuid((string)$concept_public_id)
			|| ! hash_equals(strtolower($concept_public_id),(string)($approval['concept_public_id']??''))
			|| ! hash_equals(strtolower($concept_public_id),(string)get_post_meta($lesson_id,self::META_INDEX,true))
			|| absint($approval['concept_version']??0)!==absint($concept_version)
			|| absint($approval['lesson_version']??0)!==LSCH_Content::version($lesson_id)
			|| ! in_array((string)($approval['rights_scope']??''),array('original-owned','public-domain','permission-to-link','open-license'),true)
			|| ! preg_match('/^[A-Za-z0-9._:-]{8,120}$/',(string)($approval['rights_evidence_ref']??''))
			|| absint($approval['reviewer_user_id']??0)<1
			|| absint($approval['reviewer_user_id']??0)===(int)$post->post_author
			|| ! user_can(absint($approval['reviewer_user_id']),LSCH_Capabilities::REVIEW_LESSONS) ) { return null; }
		$url=get_permalink($lesson_id);
		if ( ! is_string($url) || ! wp_http_validate_url($url) ) { return null; }
		return array(
			'owner_file'=>'file-05','owner_id'=>$lesson_id,
			'owner_version'=>LSCH_Content::version($lesson_id),
			'source_version'=>absint($concept_version),
			'eligible'=>true,'rights_ok'=>true,'url'=>$url,
			'label'=>get_the_title($lesson_id),
		);
	}

	public static function collect($links,$dto) {
		$links=is_array($links)?$links:array();
		if ( ! is_array($dto) || ! self::valid_uuid((string)($dto['id']??''))
			|| empty($dto['version']) || ! LSCH_Policy::central_policy_ready() ) { return $links; }
		$ids=get_posts(array(
			'post_type'=>LSCH_Content::LESSON, 'post_status'=>'publish',
			'fields'=>'ids','posts_per_page'=>8, 'no_found_rows'=>true,
			'suppress_filters'=>false,
			'meta_key'=>self::META_INDEX,'meta_value'=>strtolower((string)$dto['id']),
		));
		foreach((array)$ids as $id) {
			$link=self::valid_link($id,$dto['id'],$dto['version']);
			if($link)$links[]=$link;
		}
		return $links;
	}

	public static function attest($verified,$link,$dto) {
		if ( ! is_array($link) || 'file-05' !== ($link['owner_file']??'') ) { return $verified; }
		if ( ! is_array($dto) || ! isset($dto['id'],$dto['version']) ) { return false; }
		$current=self::valid_link(absint($link['owner_id']??0),(string)$dto['id'],absint($dto['version']));
		return $current && hash_equals((string)$current['url'],(string)($link['url']??''))
			&& absint($current['owner_version'])===absint($link['owner_version']??0);
	}

	public static function routes() {
		register_rest_route( LSCH_REST::NS,'/file06/lesson/(?P<id>\d+)/approve-link',array(
			'methods'=>'POST','callback'=>array(__CLASS__,'approve'),
			'permission_callback'=>static function($request) {
				return get_current_user_id()>0 && LSCH_Capabilities::can_review(get_current_user_id())
					&& current_user_can(LSCH_Capabilities::REVIEW_LESSONS);
			},
		));
	}

	public static function approve($request) {
		if ( ! wp_verify_nonce((string)$request->get_header('X-WP-Nonce'),'wp_rest') ) {
			return new WP_Error('lsch_f06_nonce','Valid session nonce required',array('status'=>403));
		}
		$id=absint($request['id']);$post=$id?get_post($id):null;
		$actor=get_current_user_id();
		if(!$post||LSCH_Content::LESSON!==$post->post_type||$actor===(int)$post->post_author
			|| ! LSCH_Capabilities::can_review($actor)||!LSCH_Policy::publicly_visible($id)) {
			return new WP_Error('lsch_f06_review_denied','Only an independent authorized reviewer can approve a publicly eligible lesson',array('status'=>403));
		}
		$data=(array)$request->get_json_params();
		$uuid=strtolower(sanitize_text_field((string)($data['concept_public_id']??'')));
		$cv=absint($data['concept_version']??0);
		$lv=absint($data['lesson_version']??0);
		$rights=sanitize_key($data['rights_scope']??'');
		$proof=sanitize_text_field((string)($data['rights_evidence_ref']??''));
		if(!self::valid_uuid($uuid)||!$cv||$lv!==LSCH_Content::version($id)
			|| !in_array($rights,array('original-owned','public-domain','permission-to-link','open-license'),true)
			||!preg_match('/^[A-Za-z0-9._:-]{8,120}$/',$proof)) {
			return new WP_Error('lsch_f06_link_invalid','Canonical ID, current versions and rights attestation required',array('status'=>422));
		}
		// File 06 is the owner. Do not approve arbitrary unreviewed IDs.
		$canonical=apply_filters('he_v12_file06_concept_assertion_v1',null,$uuid,$cv);
		if(!is_array($canonical)||true!==($canonical['published']??false)
			||'file-06'!==($canonical['owner_file']??'')
			||!hash_equals($uuid,(string)($canonical['public_id']??''))) {
			return new WP_Error('lsch_f06_owner_unavailable','Canonical File06 publication/version proof required',array('status'=>503));
		}
		$record=array('approved'=>true,'concept_public_id'=>$uuid,'concept_version'=>$cv,
			'lesson_version'=>$lv,'rights_scope'=>$rights,'rights_evidence_ref'=>$proof,
			'reviewer_user_id'=>$actor,'reviewed_at_utc'=>gmdate('c'));
		if(false===update_post_meta($id,self::META_APPROVAL,$record)
			&&get_post_meta($id,self::META_APPROVAL,true)!==$record) {
			return new WP_Error('lsch_f06_review_store','The independent link approval could not be saved',array('status'=>503));
		}
		if(false===update_post_meta($id,self::META_INDEX,$uuid)
			&&get_post_meta($id,self::META_INDEX,true)!==$uuid) {
			return new WP_Error('lsch_f06_link_index','The canonical relation index could not be saved',array('status'=>503));
		}
		return rest_ensure_response(array('approved'=>true,'concept_public_id'=>$uuid,
			'concept_version'=>$cv,'lesson_id'=>$id,'lesson_version'=>$lv));
	}
}
