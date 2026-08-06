<?php
/** Public and private learning experience inside the File 20 shell. */
defined( 'ABSPATH' ) || exit;

final class LSCH_Frontend {
	public function hooks() {
		add_shortcode( 'lsch_learning_home', array( $this, 'home' ) );
		add_shortcode( 'sabri_learning', array( $this, 'home' ) );
		add_shortcode( 'lsch_learning_dashboard', array( $this, 'dashboard' ) );
		add_shortcode( 'lsch_course', array( $this, 'course_shortcode' ) );
		add_filter( 'the_content', array( $this, 'replace_foundation_page' ), 8 );
		add_filter( 'the_content', array( $this, 'single_content' ), 20 );
		add_filter( 'sabri_shell_layout_mode', array( $this, 'shell_layout' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'template_redirect', array( $this, 'guard_private_surfaces' ), 1 );
		add_action( 'send_headers', array( $this, 'send_private_headers' ), 1 );
		add_action( 'wp_head', array( $this, 'privacy_headers' ), 1 );
	}

	public function replace_foundation_page( $content ) {
		if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) { return $content; }
		$foundation = (array) get_option( 'spf_page_map', array() );
		if ( ! empty( $foundation['learn'] ) && absint( $foundation['learn'] ) === absint( get_queried_object_id() ) && ( has_shortcode( $content, 'sabri_platform_module' ) || has_shortcode( $content, 'sabri_learning' ) || has_shortcode( $content, 'lsch_learning_home' ) || '' === trim( wp_strip_all_tags( $content ) ) ) ) {
			return $this->home();
		}
		return $content;
	}

	public function assets() {
		if ( ! $this->is_learning_context() ) { return; }
		wp_enqueue_style( 'lsch-learning', LSCH_URL . 'assets/css/learning.css', array(), LSCH_VERSION );
		wp_enqueue_script( 'lsch-learning', LSCH_URL . 'assets/js/learning.js', array(), LSCH_VERSION, true );
		wp_localize_script( 'lsch-learning', 'LSCH_APP', array(
			'root'      => esc_url_raw( rest_url( LSCH_REST::NS . '/' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'loggedIn'  => is_user_logged_in(),
			'loginUrl'  => wp_login_url( home_url( '/learn/' ) ),
			'strings'   => array(
				'working'   => __( 'Working…', 'learn-sabri-classical-homeopathy' ),
				'saved'     => __( 'Saved', 'learn-sabri-classical-homeopathy' ),
				'submitted' => __( 'Submitted', 'learn-sabri-classical-homeopathy' ),
				'error'     => __( 'The action could not be completed. Try again with the support reference shown.', 'learn-sabri-classical-homeopathy' ),
			),
		) );
	}

	private function is_learning_context() {
		if ( is_singular( array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON, LSCH_Content::ASSESSMENT, LSCH_Content::ASSIGNMENT, LSCH_Content::COHORT ) ) || is_post_type_archive( array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON ) ) ) { return true; }
		$pages = (array) get_option( 'lsch_page_map', array() );
		return is_page( array_values( array_filter( array_map( 'absint', $pages ) ) ) );
	}

	public function shell_layout( $mode, $settings ) { unset( $settings ); return $this->is_learning_context() ? 'two' : $mode; }

	public function home() {
		$programs = get_posts( array( 'post_type' => LSCH_Content::PROGRAM, 'post_status' => 'publish', 'posts_per_page' => 8, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true ) );
		$books    = get_posts( array( 'post_type' => LSCH_Content::BOOK, 'post_status' => 'publish', 'posts_per_page' => 8, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true ) );
		$courses  = get_posts( array( 'post_type' => LSCH_Content::COURSE, 'post_status' => 'publish', 'posts_per_page' => 12, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true ) );
		ob_start();
		?>
		<main class="lsch-shell" data-lsch-context="catalog">
			<section class="lsch-hero" aria-labelledby="lsch-title">
				<div><span class="lsch-kicker"><?php echo self::icon( 'book-open' ); // phpcs:ignore ?> <?php esc_html_e( 'Learn Sabri Classical Homeopathy', 'learn-sabri-classical-homeopathy' ); ?></span><h1 id="lsch-title"><?php esc_html_e( 'Structured learning from foundation to research and clinical mastery', 'learn-sabri-classical-homeopathy' ); ?></h1><p><?php esc_html_e( 'Approved public learning remains readable without an account. Verified members receive free enrollment, progress, private notes, assignments, assessments, and durable completion records.', 'learn-sabri-classical-homeopathy' ); ?></p><div class="lsch-actions"><a class="lsch-button" href="#lsch-courses"><?php echo self::icon( 'graduation-cap' ); // phpcs:ignore ?> <?php esc_html_e( 'Explore courses', 'learn-sabri-classical-homeopathy' ); ?></a><a class="lsch-button lsch-button-secondary" href="<?php echo esc_url( is_user_logged_in() ? $this->dashboard_url() : wp_login_url( $this->dashboard_url() ) ); ?>"><?php echo self::icon( is_user_logged_in() ? 'chart' : 'log-in' ); // phpcs:ignore ?> <?php echo esc_html( is_user_logged_in() ? __( 'My learning', 'learn-sabri-classical-homeopathy' ) : __( 'Log in for progress tools', 'learn-sabri-classical-homeopathy' ) ); ?></a></div></div>
				<form class="lsch-search" method="get" role="search"><label for="lsch-search"><?php esc_html_e( 'Search learning material', 'learn-sabri-classical-homeopathy' ); ?></label><div><input id="lsch-search" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search lessons, courses, books…', 'learn-sabri-classical-homeopathy' ); ?>"><input type="hidden" name="post_type" value="lsch_lesson"><button type="submit" aria-label="<?php esc_attr_e( 'Search', 'learn-sabri-classical-homeopathy' ); ?>"><?php echo self::icon( 'search' ); // phpcs:ignore ?></button></div></form>
			</section>
			<section aria-labelledby="lsch-level-heading"><div class="lsch-section-head"><div><span class="lsch-kicker"><?php esc_html_e( 'Curriculum architecture', 'learn-sabri-classical-homeopathy' ); ?></span><h2 id="lsch-level-heading"><?php esc_html_e( 'Four governed learning levels', 'learn-sabri-classical-homeopathy' ); ?></h2></div></div><div class="lsch-grid lsch-grid-four"><?php foreach ( LSCH_Content::levels() as $slug => $label ) : $url = get_term_link( $slug, LSCH_Content::LEVEL ); if ( is_wp_error( $url ) ) { $url = add_query_arg( 'level', $slug, home_url( '/learn/' ) ); } ?><a class="lsch-level-card" href="<?php echo esc_url( $url ); ?>"><?php echo self::icon( 'layers' ); // phpcs:ignore ?><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( $this->level_description( $slug ) ); ?></span></a><?php endforeach; ?></div></section>
			<?php echo $this->catalog_section( 'programs', __( 'Programs', 'learn-sabri-classical-homeopathy' ), __( 'Guided study programs', 'learn-sabri-classical-homeopathy' ), $programs ); // phpcs:ignore ?>
			<?php echo $this->catalog_section( 'lsch-courses', __( 'Courses', 'learn-sabri-classical-homeopathy' ), __( 'Current courses', 'learn-sabri-classical-homeopathy' ), $courses, true ); // phpcs:ignore ?>
			<?php echo $this->catalog_section( 'books', __( 'Founder library', 'learn-sabri-classical-homeopathy' ), __( 'Approved learning books', 'learn-sabri-classical-homeopathy' ), $books ); // phpcs:ignore ?>
		</main>
		<?php return ob_get_clean();
	}

	private function catalog_section( $id, $kicker, $heading, array $posts, $always = false ) {
		if ( ! $posts && ! $always ) { return ''; }
		ob_start(); ?><section id="<?php echo esc_attr( $id ); ?>" aria-labelledby="<?php echo esc_attr( $id . '-heading' ); ?>"><div class="lsch-section-head"><div><span class="lsch-kicker"><?php echo esc_html( $kicker ); ?></span><h2 id="<?php echo esc_attr( $id . '-heading' ); ?>"><?php echo esc_html( $heading ); ?></h2></div></div><div class="lsch-grid"><?php if ( $posts ) { foreach ( $posts as $post ) { echo $this->card( $post ); } } else { ?><div class="lsch-empty"><h3><?php esc_html_e( 'No approved records are public yet', 'learn-sabri-classical-homeopathy' ); ?></h3><p><?php esc_html_e( 'The governed catalog will show records after editorial approval.', 'learn-sabri-classical-homeopathy' ); ?></p></div><?php } ?></div></section><?php return ob_get_clean();
	}

	private function card( $post ) {
		$id = $post->ID; ob_start(); ?>
		<article class="lsch-card"><a class="lsch-card-media" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php if ( has_post_thumbnail( $id ) ) { echo get_the_post_thumbnail( $id, 'medium_large', array( 'loading' => 'lazy', 'alt' => get_the_title( $id ) ) ); } else { echo '<span aria-hidden="true">' . self::icon( 'book-open' ) . '</span>'; } // phpcs:ignore ?></a><div class="lsch-card-body"><div class="lsch-chips"><span><?php echo esc_html( ucfirst( str_replace( 'lsch_', '', $post->post_type ) ) ); ?></span><span><?php esc_html_e( 'Free', 'learn-sabri-classical-homeopathy' ); ?></span></div><h3><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></h3><p><?php echo esc_html( wp_trim_words( get_the_excerpt( $id ), 24 ) ); ?></p><a class="lsch-text-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php esc_html_e( 'Open', 'learn-sabri-classical-homeopathy' ); ?> <?php echo self::icon( 'arrow' ); // phpcs:ignore ?></a></div></article>
		<?php return ob_get_clean();
	}

	public function course_shortcode( $atts ) { $atts = shortcode_atts( array( 'id' => get_the_ID() ), $atts ); return $this->course_content( absint( $atts['id'] ) ); }

	private function course_content( $course_id ) {
		if ( LSCH_Content::COURSE !== get_post_type( $course_id ) || ! LSCH_Policy::can_read_post( $course_id ) ) { return ''; }
		$lessons = get_posts( array( 'post_type' => LSCH_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'menu_order title', 'order' => 'ASC', 'meta_key' => '_lsch_course_id', 'meta_value' => $course_id, 'no_found_rows' => true ) );
		ob_start(); ?><section class="lsch-course-outline"><div class="lsch-callout"><strong><?php esc_html_e( 'Access', 'learn-sabri-classical-homeopathy' ); ?>:</strong> <?php esc_html_e( 'The complete course is free. Enrollment records private progress and completion.', 'learn-sabri-classical-homeopathy' ); ?><?php if ( is_user_logged_in() ) : ?><div class="lsch-inline-actions"><button type="button" class="lsch-button" data-lsch-enroll="<?php echo absint( $course_id ); ?>"><?php echo self::icon( 'plus-circle' ); // phpcs:ignore ?> <?php esc_html_e( 'Enroll', 'learn-sabri-classical-homeopathy' ); ?></button><button type="button" class="lsch-button lsch-button-secondary" data-lsch-reminder="<?php echo absint( $course_id ); ?>" data-enabled="1"><?php echo self::icon( 'bell' ); // phpcs:ignore ?> <?php esc_html_e( 'Weekly reminder', 'learn-sabri-classical-homeopathy' ); ?></button></div><?php endif; ?><p class="lsch-status" data-lsch-status aria-live="polite"></p></div><h2><?php esc_html_e( 'Course outline', 'learn-sabri-classical-homeopathy' ); ?></h2><ol class="lsch-outline"><?php foreach ( $lessons as $index => $lesson ) : if ( ! LSCH_Policy::can_read_post( $lesson->ID ) ) { continue; } ?><li><a href="<?php echo esc_url( get_permalink( $lesson ) ); ?>"><span><?php echo absint( $index + 1 ); ?></span><div><strong><?php echo esc_html( get_the_title( $lesson ) ); ?></strong><small><?php echo esc_html( get_post_meta( $lesson->ID, '_lsch_duration', true ) ); ?></small></div><?php echo self::icon( 'arrow' ); // phpcs:ignore ?></a></li><?php endforeach; ?></ol></section><?php return ob_get_clean();
	}

	public function dashboard() {
		if ( ! is_user_logged_in() ) { return '<div class="lsch-notice"><p>' . esc_html__( 'Log in to view your private learning dashboard.', 'learn-sabri-classical-homeopathy' ) . '</p><a class="lsch-button" href="' . esc_url( wp_login_url( $this->dashboard_url() ) ) . '">' . esc_html__( 'Log in', 'learn-sabri-classical-homeopathy' ) . '</a></div>'; }
		if ( ! LSCH_Policy::can_use_learning_actions() ) { return '<div class="lsch-notice lsch-notice-warning"><h2>' . esc_html__( 'Learning actions are awaiting account approval', 'learn-sabri-classical-homeopathy' ) . '</h2><p>' . esc_html__( 'Public lessons remain readable. Private progress, notes, assessments, and enrollment require verified-entry approval.', 'learn-sabri-classical-homeopathy' ) . '</p></div>'; }
		$data = LSCH_Services::dashboard( get_current_user_id() ); ob_start(); ?>
		<main class="lsch-shell lsch-private" data-lsch-context="dashboard"><header class="lsch-page-head"><span class="lsch-kicker"><?php echo self::icon( 'chart' ); // phpcs:ignore ?> <?php esc_html_e( 'Private learning dashboard', 'learn-sabri-classical-homeopathy' ); ?></span><h1><?php esc_html_e( 'My learning', 'learn-sabri-classical-homeopathy' ); ?></h1><p><?php esc_html_e( 'Progress, private notes, attempts, submissions, reminders, and completion records are private and excluded from public caches.', 'learn-sabri-classical-homeopathy' ); ?></p></header><div class="lsch-grid lsch-dashboard-grid"><section class="lsch-panel"><h2><?php echo self::icon( 'play' ); // phpcs:ignore ?> <?php esc_html_e( 'Continue learning', 'learn-sabri-classical-homeopathy' ); ?></h2><?php echo $this->progress_list( $data['progress'] ); // phpcs:ignore ?></section><section class="lsch-panel"><h2><?php echo self::icon( 'bookmark' ); // phpcs:ignore ?> <?php esc_html_e( 'Bookmarks', 'learn-sabri-classical-homeopathy' ); ?></h2><?php echo $this->bookmark_list( $data['bookmarks'] ); // phpcs:ignore ?></section><section class="lsch-panel"><h2><?php echo self::icon( 'award' ); // phpcs:ignore ?> <?php esc_html_e( 'Completion records', 'learn-sabri-classical-homeopathy' ); ?></h2><?php echo $this->completion_list( $data['completions'] ); // phpcs:ignore ?></section><section class="lsch-panel"><h2><?php echo self::icon( 'check-circle' ); // phpcs:ignore ?> <?php esc_html_e( 'Assessment attempts', 'learn-sabri-classical-homeopathy' ); ?></h2><?php echo $this->attempt_list( $data['attempts'] ); // phpcs:ignore ?></section><section class="lsch-panel"><h2><?php echo self::icon( 'clipboard' ); // phpcs:ignore ?> <?php esc_html_e( 'Assignment submissions', 'learn-sabri-classical-homeopathy' ); ?></h2><?php echo $this->submission_list( $data['submissions'] ); // phpcs:ignore ?></section></div></main>
		<?php return ob_get_clean();
	}

	private function progress_list( $rows ) { if ( ! $rows ) { return '<p>' . esc_html__( 'No progress recorded yet.', 'learn-sabri-classical-homeopathy' ) . '</p>'; } $out = '<ul class="lsch-list">'; foreach ( $rows as $row ) { if ( ! LSCH_Policy::can_read_post( $row['lesson_id'], get_current_user_id() ) ) { continue; } $out .= '<li><a href="' . esc_url( get_permalink( $row['lesson_id'] ) ) . '"><strong>' . esc_html( get_the_title( $row['lesson_id'] ) ) . '</strong><span>' . esc_html( $row['state'] ) . ' — ' . absint( $row['percent'] ) . '%</span></a></li>'; } return $out . '</ul>'; }
	private function bookmark_list( $rows ) { if ( ! $rows ) { return '<p>' . esc_html__( 'No bookmarks yet.', 'learn-sabri-classical-homeopathy' ) . '</p>'; } $out = '<ul class="lsch-list">'; foreach ( $rows as $row ) { if ( ! LSCH_Policy::can_read_post( $row['object_id'], get_current_user_id() ) ) { continue; } $out .= '<li><a href="' . esc_url( get_permalink( $row['object_id'] ) ) . '">' . esc_html( get_the_title( $row['object_id'] ) ) . '</a></li>'; } return $out . '</ul>'; }
	private function completion_list( $rows ) { if ( ! $rows ) { return '<p>' . esc_html__( 'No completion record yet.', 'learn-sabri-classical-homeopathy' ) . '</p>'; } $out = '<ul class="lsch-list">'; foreach ( $rows as $row ) { $out .= '<li><strong>' . esc_html( get_the_title( $row['course_id'] ) ) . '</strong><span>' . esc_html( $row['status'] ) . ' — ' . esc_html( $row['earned_at'] ) . '</span></li>'; } return $out . '</ul>'; }
	private function attempt_list( $rows ) { if ( ! $rows ) { return '<p>' . esc_html__( 'No assessment attempt yet.', 'learn-sabri-classical-homeopathy' ) . '</p>'; } $out = '<ul class="lsch-list">'; foreach ( $rows as $row ) { $out .= '<li><strong>' . esc_html( get_the_title( $row['assessment_id'] ) ) . '</strong><span>' . esc_html( $row['score'] ) . '% — ' . esc_html( $row['status'] ) . '</span></li>'; } return $out . '</ul>'; }
	private function submission_list( $rows ) { if ( ! $rows ) { return '<p>' . esc_html__( 'No assignment submission yet.', 'learn-sabri-classical-homeopathy' ) . '</p>'; } $out = '<ul class="lsch-list">'; foreach ( $rows as $row ) { $out .= '<li><strong>' . esc_html( get_the_title( $row['assignment_id'] ) ) . '</strong><span>' . esc_html( $row['status'] ) . ( 'graded' === $row['status'] ? ' — ' . esc_html( $row['score'] ) . '%' : '' ) . '</span></li>'; } return $out . '</ul>'; }

	public function single_content( $content ) {
		if ( ! in_the_loop() || ! is_main_query() ) { return $content; }
		$id = get_the_ID(); $type = get_post_type( $id );
		if ( ! in_array( $type, array( LSCH_Content::PROGRAM, LSCH_Content::COURSE, LSCH_Content::BOOK, LSCH_Content::LESSON, LSCH_Content::ASSESSMENT, LSCH_Content::ASSIGNMENT, LSCH_Content::COHORT ), true ) ) { return $content; }
		if ( ! LSCH_Policy::can_read_post( $id ) ) { return '<div class="lsch-notice lsch-notice-warning"><h2>' . esc_html__( 'Restricted learning record', 'learn-sabri-classical-homeopathy' ) . '</h2><p>' . esc_html__( 'This record is unavailable for your current account state.', 'learn-sabri-classical-homeopathy' ) . '</p></div>'; }
		$meta = '<div class="lsch-single-meta"><span>' . esc_html__( 'Version', 'learn-sabri-classical-homeopathy' ) . ' ' . absint( LSCH_Content::version( $id ) ) . '</span><span>' . esc_html__( 'Free access', 'learn-sabri-classical-homeopathy' ) . '</span></div>';
		if ( LSCH_Content::COURSE === $type ) { return '<div class="lsch-single">' . $meta . $content . $this->course_content( $id ) . '</div>'; }
		if ( LSCH_Content::LESSON === $type ) { return $this->lesson_content( $id, $content, $meta ); }
		if ( LSCH_Content::ASSESSMENT === $type ) { return '<div class="lsch-single">' . $meta . $content . $this->assessment_form( $id ) . '</div>'; }
		if ( LSCH_Content::ASSIGNMENT === $type ) { return '<div class="lsch-single">' . $meta . $content . $this->assignment_form( $id ) . '</div>'; }
		return '<div class="lsch-single">' . $meta . $content . '</div>';
	}

	private function lesson_content( $id, $content, $meta ) {
		$tools = '<div class="lsch-learning-tools"><button type="button" data-lsch-bookmark="' . absint( $id ) . '" data-lsch-type="lsch_lesson">' . self::icon( 'bookmark' ) . ' ' . esc_html__( 'Bookmark', 'learn-sabri-classical-homeopathy' ) . '</button><button type="button" data-lsch-progress="' . absint( $id ) . '">' . self::icon( 'check-circle' ) . ' ' . esc_html__( 'Complete reading component', 'learn-sabri-classical-homeopathy' ) . '</button></div><div class="lsch-private-note"><label for="lsch-note-' . absint( $id ) . '">' . self::icon( 'lock' ) . ' ' . esc_html__( 'Private note', 'learn-sabri-classical-homeopathy' ) . '</label><textarea id="lsch-note-' . absint( $id ) . '" data-lsch-note="' . absint( $id ) . '" rows="5"></textarea><button type="button" class="lsch-button" data-lsch-save-note="' . absint( $id ) . '">' . esc_html__( 'Save private note', 'learn-sabri-classical-homeopathy' ) . '</button><p class="lsch-status" data-lsch-status aria-live="polite"></p></div>';
		$sources = (string) get_post_meta( $id, '_lsch_sources', true ); $objectives = (string) get_post_meta( $id, '_lsch_objectives', true ); $safety = (string) get_post_meta( $id, '_lsch_safety', true );
		return '<article class="lsch-single">' . $meta . ( $objectives ? '<section class="lsch-panel"><h2>' . esc_html__( 'Learning objectives', 'learn-sabri-classical-homeopathy' ) . '</h2>' . wpautop( esc_html( $objectives ) ) . '</section>' : '' ) . $content . ( $sources ? '<section class="lsch-panel"><h2>' . esc_html__( 'Sources and references', 'learn-sabri-classical-homeopathy' ) . '</h2>' . wpautop( esc_html( $sources ) ) . '</section>' : '' ) . ( $safety ? '<aside class="lsch-notice"><strong>' . esc_html__( 'Educational and medical safety', 'learn-sabri-classical-homeopathy' ) . '</strong>' . wpautop( esc_html( $safety ) ) . '</aside>' : '' ) . $tools . '</article>';
	}

	private function assessment_form( $assessment_id ) {
		$questions = json_decode( (string) get_post_meta( $assessment_id, '_lsch_questions', true ), true );
		if ( ! is_array( $questions ) || ! $questions ) { return '<div class="lsch-notice lsch-notice-warning">' . esc_html__( 'This assessment has no approved question blueprint.', 'learn-sabri-classical-homeopathy' ) . '</div>'; }
		ob_start(); ?><form class="lsch-assessment-form lsch-panel" data-lsch-assessment="<?php echo absint( $assessment_id ); ?>"><h2><?php esc_html_e( 'Knowledge check', 'learn-sabri-classical-homeopathy' ); ?></h2><?php foreach ( $questions as $index => $question ) : if ( empty( $question['question'] ) || empty( $question['options'] ) || ! is_array( $question['options'] ) ) { continue; } ?><fieldset><legend><?php echo esc_html( ( $index + 1 ) . '. ' . $question['question'] ); ?></legend><?php foreach ( $question['options'] as $key => $option ) : ?><label><input type="radio" name="q<?php echo absint( $index ); ?>" value="<?php echo esc_attr( (string) $key ); ?>" required> <?php echo esc_html( $option ); ?></label><?php endforeach; ?></fieldset><?php endforeach; ?><button type="submit" class="lsch-button"><?php esc_html_e( 'Submit assessment', 'learn-sabri-classical-homeopathy' ); ?></button><div class="lsch-result" data-lsch-status aria-live="polite"></div></form><?php return ob_get_clean();
	}

	private function assignment_form( $assignment_id ) {
		ob_start(); ?><form class="lsch-assignment-form lsch-panel" data-lsch-assignment="<?php echo absint( $assignment_id ); ?>"><h2><?php esc_html_e( 'Assignment response', 'learn-sabri-classical-homeopathy' ); ?></h2><label for="lsch-assignment-body-<?php echo absint( $assignment_id ); ?>"><?php esc_html_e( 'Write your response', 'learn-sabri-classical-homeopathy' ); ?></label><textarea id="lsch-assignment-body-<?php echo absint( $assignment_id ); ?>" name="body" rows="12" maxlength="100000" required></textarea><button type="submit" class="lsch-button"><?php esc_html_e( 'Submit assignment', 'learn-sabri-classical-homeopathy' ); ?></button><div class="lsch-result" data-lsch-status aria-live="polite"></div></form><?php return ob_get_clean();
	}

	public function guard_private_surfaces() { if ( is_singular( array( LSCH_Content::ASSESSMENT, LSCH_Content::ASSIGNMENT ) ) && ! is_user_logged_in() ) { auth_redirect(); } }
	private function is_private_learning_surface() { $pages = (array) get_option( 'lsch_page_map', array() ); return is_singular( array( LSCH_Content::ASSESSMENT, LSCH_Content::ASSIGNMENT ) ) || ( ! empty( $pages['dashboard'] ) && is_page( absint( $pages['dashboard'] ) ) ); }
	public function send_private_headers() { if ( $this->is_private_learning_surface() ) { nocache_headers(); header( 'X-Robots-Tag: noindex, nofollow, noarchive', true ); } }
	public function privacy_headers() { if ( $this->is_private_learning_surface() ) { echo "<meta name=\"robots\" content=\"noindex,nofollow,noarchive\">\n"; } }
	private function dashboard_url() { $pages = (array) get_option( 'lsch_page_map', array() ); return ! empty( $pages['dashboard'] ) ? get_permalink( $pages['dashboard'] ) : home_url( '/learn/dashboard/' ); }
	private function level_description( $slug ) { $map = array( 'foundation' => __( 'Essential principles and guided study habits.', 'learn-sabri-classical-homeopathy' ), 'intermediate' => __( 'Structured remedy, case, and repertory understanding.', 'learn-sabri-classical-homeopathy' ), 'advanced' => __( 'Deep analysis, comparison, and governed clinical learning.', 'learn-sabri-classical-homeopathy' ), 'research-clinical-mastery' => __( 'Research methods, critical appraisal, and advanced mastery.', 'learn-sabri-classical-homeopathy' ) ); return isset( $map[ $slug ] ) ? $map[ $slug ] : ''; }

	public static function icon( $name ) {
		$paths = array(
			'book-open' => '<path d="M2 4a2 2 0 0 1 2-2h5a3 3 0 0 1 3 3 3 3 0 0 1 3-3h5a2 2 0 0 1 2 2v15a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3H3a1 1 0 0 1-1-1Z"/><path d="M12 5v18"/>',
			'graduation-cap' => '<path d="m2 10 10-5 10 5-10 5Z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/>',
			'chart' => '<path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/>',
			'log-in' => '<path d="M10 17l5-5-5-5m5 5H3"/><path d="M21 19V5a2 2 0 0 0-2-2h-6"/>',
			'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
			'layers' => '<path d="m12 2 10 5-10 5L2 7Z"/><path d="m2 12 10 5 10-5M2 17l10 5 10-5"/>',
			'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
			'plus-circle' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8m-4-4h8"/>',
			'play' => '<circle cx="12" cy="12" r="9"/><path d="m10 8 6 4-6 4Z"/>',
			'bookmark' => '<path d="M6 3h12v18l-6-4-6 4Z"/>',
			'award' => '<circle cx="12" cy="8" r="5"/><path d="m8 12-2 9 6-3 6 3-2-9"/>',
			'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
			'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
			'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
			'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M9 10h6m-6 4h6"/>',
		);
		return '<svg class="lsch-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['book-open'] ) . '</svg>';
	}
}
