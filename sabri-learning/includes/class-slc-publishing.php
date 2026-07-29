<?php
/** Governed front-end lesson submission. */

defined( 'ABSPATH' ) || exit;

final class SLC_Publishing {
	const CONSENT_VERSION = '2026-07-29-v1';

	public function hooks() {
		add_shortcode( 'slc_submit_lesson', array( $this, 'form' ) );
		add_action( 'admin_post_slc_submit_lesson', array( $this, 'submit' ) );
	}

	public function form() {
		if ( ! is_user_logged_in() ) {
			return '<div class="slc-notice"><p>' . esc_html__( 'An account is required to submit a lesson.', 'sabri-learning' ) . '</p><a class="slc-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log In', 'sabri-learning' ) . '</a></div>';
		}
		if ( ! SLC_Permissions::can_submit() ) {
			return '<div class="slc-notice"><strong>' . esc_html__( 'Lesson publishing is restricted.', 'sabri-learning' ) . '</strong><p>' . esc_html__( 'Only the Founder, learning administrators, and currently verified doctors may submit lessons.', 'sabri-learning' ) . '</p></div>';
		}
		$books = get_posts( array( 'post_type' => SLC_Content::BOOK, 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) );
		ob_start();
		?>
		<div class="slc-shell" data-slc-module="submit">
			<header class="slc-page-head"><span><?php esc_html_e( 'Learning Management', 'sabri-learning' ); ?></span><h1><?php esc_html_e( 'Submit Learning Lesson', 'sabri-learning' ); ?></h1><p><?php esc_html_e( 'Use American English. Verified doctor contributions remain pending until independent review.', 'sabri-learning' ); ?></p></header>
			<form class="slc-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="slc_submit_lesson"><?php wp_nonce_field( 'slc_submit_lesson', 'slc_nonce' ); ?>
				<label><?php esc_html_e( 'Lesson title', 'sabri-learning' ); ?><input name="title" maxlength="180" required></label>
				<label><?php esc_html_e( 'Learning level', 'sabri-learning' ); ?><select name="level" required><option value=""><?php esc_html_e( 'Select level', 'sabri-learning' ); ?></option><?php foreach ( SLC_Content::levels() as $slug => $name ) : ?><option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Learning topic', 'sabri-learning' ); ?><select name="topic" required><option value=""><?php esc_html_e( 'Select topic', 'sabri-learning' ); ?></option><?php foreach ( SLC_Content::topics() as $slug => $name ) : ?><option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Related book', 'sabri-learning' ); ?><select name="book_id"><option value="0"><?php esc_html_e( 'No specific book', 'sabri-learning' ); ?></option><?php foreach ( $books as $book ) : ?><option value="<?php echo absint( $book->ID ); ?>"><?php echo esc_html( $book->post_title ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Chapter or section', 'sabri-learning' ); ?><input name="chapter" maxlength="160"></label>
				<label><?php esc_html_e( 'Featured image', 'sabri-learning' ); ?><input type="file" name="image" accept="image/jpeg,image/png,image/webp"><small><?php esc_html_e( 'JPG, PNG, or WebP; 320×180 to 8,000×8,000 pixels; maximum 5 MB.', 'sabri-learning' ); ?></small></label>
				<label class="slc-full"><?php esc_html_e( 'Short summary', 'sabri-learning' ); ?><textarea name="excerpt" rows="3" maxlength="500" required></textarea></label>
				<label class="slc-full"><?php esc_html_e( 'Complete lesson', 'sabri-learning' ); ?><textarea name="content" rows="15" required></textarea></label>
				<label class="slc-full"><?php esc_html_e( 'Learning objectives — one per line', 'sabri-learning' ); ?><textarea name="objectives" rows="4" required></textarea></label>
				<label><?php esc_html_e( 'Important terms', 'sabri-learning' ); ?><input name="terms" maxlength="500"></label>
				<label><?php esc_html_e( 'Estimated study time', 'sabri-learning' ); ?><input name="study_time" maxlength="40"></label>
				<label class="slc-full"><?php esc_html_e( 'References — one per line', 'sabri-learning' ); ?><textarea name="references" rows="5" required></textarea></label>
				<label class="slc-full"><?php esc_html_e( 'Optional knowledge-check questions', 'sabri-learning' ); ?><textarea name="quiz" rows="6" placeholder="Question | Option A | Option B | Option C | A | Explanation"></textarea><small><?php esc_html_e( 'Maximum five lines.', 'sabri-learning' ); ?></small></label>
				<div class="slc-full slc-case-check" hidden data-slc-case>
					<strong><?php esc_html_e( 'Patient Case Learning safeguards', 'sabri-learning' ); ?></strong>
					<label class="slc-check"><input type="checkbox" name="case_anonymized" value="1"> <?php esc_html_e( 'Patient identity and direct identifiers have been removed.', 'sabri-learning' ); ?></label>
					<label class="slc-check"><input type="checkbox" name="case_consent" value="1"> <?php esc_html_e( 'Valid permission exists for the submitted case material.', 'sabri-learning' ); ?></label>
					<label><?php esc_html_e( 'Consent source', 'sabri-learning' ); ?><select name="consent_source"><option value=""><?php esc_html_e( 'Select source', 'sabri-learning' ); ?></option><option value="patient"><?php esc_html_e( 'Patient', 'sabri-learning' ); ?></option><option value="guardian"><?php esc_html_e( 'Parent or guardian', 'sabri-learning' ); ?></option><option value="institution"><?php esc_html_e( 'Authorized institution', 'sabri-learning' ); ?></option></select></label>
					<label><?php esc_html_e( 'Consent evidence reference', 'sabri-learning' ); ?><input name="consent_evidence" maxlength="190" placeholder="Internal reference only"></label>
					<label class="slc-full"><?php esc_html_e( 'Permitted scope', 'sabri-learning' ); ?><textarea name="consent_scope" rows="3" maxlength="1000" placeholder="Text, images, educational publication, and any restrictions"></textarea></label>
				</div>
				<label class="slc-check slc-full"><input type="checkbox" name="medical_notice" value="1" required> <?php esc_html_e( 'This lesson is educational, does not provide a personal prescription, and does not replace emergency or qualified medical care.', 'sabri-learning' ); ?></label>
				<button class="slc-button" type="submit"><?php echo esc_html( 'publish' === SLC_Permissions::initial_status() ? __( 'Publish Lesson', 'sabri-learning' ) : __( 'Submit for Review', 'sabri-learning' ) ); ?></button>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function submit() {
		$user_id = get_current_user_id();
		if ( ! $user_id || ! SLC_Permissions::can_submit( $user_id ) ) {
			wp_die( esc_html__( 'You are not allowed to submit lessons.', 'sabri-learning' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'slc_submit_lesson', 'slc_nonce' );
		if ( ! SLC_Database::allow( 'submit:' . $user_id, 5, HOUR_IN_SECONDS ) ) {
			wp_die( esc_html__( 'The lesson submission limit has been reached. Please try again later.', 'sabri-learning' ), '', array( 'response' => 429 ) );
		}

		$data = $this->validated_request();
		if ( is_wp_error( $data ) ) {
			$this->fail( $data->get_error_message() );
		}

		$status = SLC_Permissions::initial_status( $user_id );
		$id     = wp_insert_post(
			array(
				'post_type'      => SLC_Content::LESSON,
				'post_status'    => $status,
				'post_author'    => $user_id,
				'post_title'     => $data['title'],
				'post_excerpt'   => $data['excerpt'],
				'post_content'   => $data['content'],
				'comment_status' => 'open',
				'ping_status'    => 'closed',
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			$this->fail( __( 'The lesson could not be saved.', 'sabri-learning' ) );
		}

		$attachment_id = 0;
		try {
			if ( ! SLC_Content::assign( $id, $data['topic'], SLC_Content::TOPIC ) || ! SLC_Content::assign( $id, $data['level'], SLC_Content::LEVEL ) ) {
				throw new RuntimeException( __( 'The approved topic or level could not be assigned.', 'sabri-learning' ) );
			}
			foreach ( array( 'objectives', 'terms', 'study_time', 'references', 'chapter' ) as $key ) {
				update_post_meta( $id, '_slc_' . $key, $data[ $key ] );
			}
			update_post_meta( $id, '_slc_book_id', $data['book_id'] );
			update_post_meta( $id, '_slc_language', 'en-US' );
			update_post_meta( $id, '_slc_quiz', $data['quiz'] );
			update_post_meta( $id, '_slc_medical_notice_version', self::CONSENT_VERSION );
			update_post_meta( $id, '_slc_workflow_state', 'publish' === $status ? 'published' : 'submitted' );
			update_post_meta( $id, '_slc_row_version', 1 );

			if ( 'patient-case-learning' === $data['topic'] ) {
				$this->store_consent( $id, $user_id, $data );
			}

			$attachment_id = $this->upload_image( $id );
			if ( $attachment_id ) {
				set_post_thumbnail( $id, $attachment_id );
			}
		} catch ( Throwable $error ) {
			if ( $attachment_id ) {
				wp_delete_attachment( $attachment_id, true );
			}
			wp_delete_post( $id, true );
			SLC_Dependencies::audit( 'submission_rolled_back', array( 'user_id' => $user_id, 'error' => $error->getMessage() ) );
			$this->fail( $error->getMessage() );
		}

		SLC_Admin::audit( $id, 'publish' === $status ? 'published' : 'submitted', '', '', 'publish' === $status ? 'published' : 'submitted' );
		SLC_Dependencies::audit( 'lesson_submitted', array( 'lesson_id' => $id, 'author_id' => $user_id, 'status' => $status ) );

		if ( 'publish' === $status ) {
			wp_safe_redirect( get_permalink( $id ) );
		} else {
			$pages = (array) get_option( 'slc_page_map', array() );
			wp_safe_redirect( add_query_arg( 'submitted', '1', ! empty( $pages['submit'] ) ? get_permalink( $pages['submit'] ) : home_url( '/' ) ) );
		}
		exit;
	}

	private function validated_request() {
		$text_fields = array( 'title' => 180, 'terms' => 500, 'study_time' => 40, 'chapter' => 160 );
		$data        = array();
		foreach ( $text_fields as $key => $max ) {
			$value        = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$data[ $key ] = $this->limit( $value, $max );
		}
		$data['excerpt']    = $this->limit( isset( $_POST['excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excerpt'] ) ) : '', 500 );
		$data['content']    = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$data['objectives'] = isset( $_POST['objectives'] ) ? sanitize_textarea_field( wp_unslash( $_POST['objectives'] ) ) : '';
		$data['references'] = isset( $_POST['references'] ) ? sanitize_textarea_field( wp_unslash( $_POST['references'] ) ) : '';
		$data['topic']      = isset( $_POST['topic'] ) ? sanitize_title( wp_unslash( $_POST['topic'] ) ) : '';
		$data['level']      = isset( $_POST['level'] ) ? sanitize_title( wp_unslash( $_POST['level'] ) ) : '';
		$data['book_id']    = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
		$data['quiz']       = $this->quiz( isset( $_POST['quiz'] ) ? wp_unslash( $_POST['quiz'] ) : '' );

		if ( '' === $data['title'] || '' === trim( wp_strip_all_tags( $data['content'] ) ) || '' === trim( $data['excerpt'] ) || '' === trim( $data['objectives'] ) || '' === trim( $data['references'] ) ) {
			return new WP_Error( 'slc_required', __( 'Title, summary, complete lesson, objectives, and references are required.', 'sabri-learning' ) );
		}
		if ( ! SLC_Content::allowed( $data['topic'], SLC_Content::TOPIC ) || ! SLC_Content::allowed( $data['level'], SLC_Content::LEVEL ) ) {
			return new WP_Error( 'slc_classification', __( 'An approved topic and learning level are required.', 'sabri-learning' ) );
		}
		if ( empty( $_POST['medical_notice'] ) ) {
			return new WP_Error( 'slc_medical_notice', __( 'The educational and medical-safety confirmation is required.', 'sabri-learning' ) );
		}
		if ( $data['book_id'] && ( SLC_Content::BOOK !== get_post_type( $data['book_id'] ) || 'publish' !== get_post_status( $data['book_id'] ) ) ) {
			return new WP_Error( 'slc_book', __( 'The selected related book is unavailable.', 'sabri-learning' ) );
		}
		if ( 'patient-case-learning' === $data['topic'] ) {
			$data['consent_source']   = isset( $_POST['consent_source'] ) ? sanitize_key( wp_unslash( $_POST['consent_source'] ) ) : '';
			$data['consent_evidence'] = isset( $_POST['consent_evidence'] ) ? $this->limit( sanitize_text_field( wp_unslash( $_POST['consent_evidence'] ) ), 190 ) : '';
			$data['consent_scope']    = isset( $_POST['consent_scope'] ) ? $this->limit( sanitize_textarea_field( wp_unslash( $_POST['consent_scope'] ) ), 1000 ) : '';
			if ( empty( $_POST['case_anonymized'] ) || empty( $_POST['case_consent'] ) || ! in_array( $data['consent_source'], array( 'patient', 'guardian', 'institution' ), true ) || '' === $data['consent_evidence'] || '' === $data['consent_scope'] ) {
				return new WP_Error( 'slc_consent', __( 'Patient Case Learning requires anonymity, valid consent source, evidence reference, and permitted scope.', 'sabri-learning' ) );
			}
		}
		return $data;
	}

	private function store_consent( $lesson_id, $author_id, array $data ) {
		global $wpdb;
		$result = $wpdb->insert(
			$wpdb->prefix . 'slc_consents',
			array(
				'lesson_id'          => $lesson_id,
				'author_id'          => $author_id,
				'policy_version'     => self::CONSENT_VERSION,
				'subject_type'       => 'guardian' === $data['consent_source'] ? 'minor' : 'adult',
				'consent_source'     => $data['consent_source'],
				'scope'              => $data['consent_scope'],
				'evidence_reference' => $data['consent_evidence'],
				'confirmed_at'       => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( false === $result ) {
			throw new RuntimeException( __( 'The patient-consent record could not be stored.', 'sabri-learning' ) );
		}
		update_post_meta( $lesson_id, '_slc_case_anonymized', '1' );
		update_post_meta( $lesson_id, '_slc_case_consent', '1' );
		update_post_meta( $lesson_id, '_slc_consent_version', self::CONSENT_VERSION );
	}

	private function upload_image( $post_id ) {
		if ( empty( $_FILES['image']['name'] ) ) {
			return 0;
		}
		$file = $_FILES['image'];
		if ( ! is_array( $file ) || UPLOAD_ERR_OK !== (int) $file['error'] || ! is_uploaded_file( $file['tmp_name'] ) || (int) $file['size'] < 1024 || (int) $file['size'] > 5 * MB_IN_BYTES ) {
			throw new RuntimeException( __( 'The image must upload successfully and be between 1 KB and 5 MB.', 'sabri-learning' ) );
		}
		$name    = sanitize_file_name( $file['name'] );
		$allowed = array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' );
		$type    = wp_check_filetype_and_ext( $file['tmp_name'], $name, $allowed );
		if ( empty( $type['type'] ) || ! in_array( $type['type'], array_values( $allowed ), true ) ) {
			throw new RuntimeException( __( 'Only verified JPG, PNG, and WebP images are allowed.', 'sabri-learning' ) );
		}
		$info = @getimagesize( $file['tmp_name'] );
		if ( ! is_array( $info ) || $info[0] < 320 || $info[1] < 180 || $info[0] > 8000 || $info[1] > 8000 || ( $info[0] * $info[1] ) > 40000000 ) {
			throw new RuntimeException( __( 'The image dimensions are unsafe or outside the permitted range.', 'sabri-learning' ) );
		}
		$sample = file_get_contents( $file['tmp_name'], false, null, 0, min( (int) $file['size'], 1048576 ) );
		if ( false === $sample || preg_match( '/<\?(?:php|=)|<script\b|eval\s*\(/i', $sample ) ) {
			throw new RuntimeException( __( 'The image failed the content safety scan.', 'sabri-learning' ) );
		}
		$scan = apply_filters( 'slc_image_security_scan', true, $file['tmp_name'], $type['type'], $file );
		if ( is_wp_error( $scan ) || true !== $scan ) {
			throw new RuntimeException( is_wp_error( $scan ) ? $scan->get_error_message() : __( 'The image was rejected by the security scanner.', 'sabri-learning' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$image = media_handle_upload( 'image', $post_id, array(), array( 'test_form' => false, 'mimes' => $allowed ) );
		if ( is_wp_error( $image ) ) {
			throw new RuntimeException( $image->get_error_message() );
		}
		return absint( $image );
	}

	private function quiz( $raw ) {
		$out = array();
		foreach ( array_slice( preg_split( '/\r\n|\r|\n/', sanitize_textarea_field( $raw ) ), 0, 5 ) as $line ) {
			$parts = array_map( 'trim', explode( '|', $line ) );
			if ( count( $parts ) < 6 ) {
				continue;
			}
			$letter = strtoupper( $parts[4] );
			if ( ! in_array( $letter, array( 'A', 'B', 'C' ), true ) ) {
				continue;
			}
			$out[] = array(
				'q' => $this->limit( $parts[0], 300 ),
				'o' => array( $this->limit( $parts[1], 180 ), $this->limit( $parts[2], 180 ), $this->limit( $parts[3], 180 ) ),
				'a' => array_search( $letter, array( 'A', 'B', 'C' ), true ),
				'e' => $this->limit( $parts[5], 500 ),
			);
		}
		return $out;
	}

	private function limit( $value, $length ) {
		return function_exists( 'mb_substr' ) ? mb_substr( (string) $value, 0, $length ) : substr( (string) $value, 0, $length );
	}

	private function fail( $message ) {
		wp_die( esc_html( $message ), esc_html__( 'Lesson not accepted', 'sabri-learning' ), array( 'response' => 400, 'back_link' => true ) );
	}
}
