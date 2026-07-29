<?php
/** Public catalog rendered inside the File 20 application shell. */

defined( 'ABSPATH' ) || exit;

final class SLC_Catalog {
	public function hooks() {
		add_shortcode( 'slc_learning_home', array( $this, 'home' ) );
		add_shortcode( 'sabri_learning', array( $this, 'home' ) );
		add_filter( 'the_content', array( $this, 'replace_foundation' ), 8 );
		add_filter( 'the_content', array( $this, 'lesson_author' ), 19 );
		add_filter( 'the_content', array( $this, 'single_content' ), 20 );
		add_filter( 'the_content', array( $this, 'lesson_navigation' ), 21 );
		add_filter( 'posts_clauses', array( $this, 'popular_clauses' ), 10, 2 );
		add_filter( 'sabri_shell_layout_mode', array( $this, 'shell_layout' ), 10, 2 );
		add_action( 'template_redirect', array( $this, 'guard' ), 1 );
	}

	public function shell_layout( $mode, $settings ) {
		unset( $settings );
		if ( is_singular( array( SLC_Content::BOOK, SLC_Content::LESSON ) ) || $this->is_learning_page() ) {
			return 'two';
		}
		return $mode;
	}

	private function is_learning_page() {
		$foundation = (array) get_option( 'spf_page_map', array() );
		$managed    = (array) get_option( 'slc_page_map', array() );
		$current    = get_queried_object_id();
		$ids        = array_map( 'absint', array_merge( array( isset( $foundation['learn'] ) ? $foundation['learn'] : 0 ), $managed ) );
		return $current && in_array( absint( $current ), $ids, true );
	}

	public function replace_foundation( $content ) {
		if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$pages = (array) get_option( 'spf_page_map', array() );
		if ( ! empty( $pages['learn'] ) && (int) $pages['learn'] === (int) get_queried_object_id() && ( has_shortcode( $content, 'sabri_platform_module' ) || has_shortcode( $content, 'sabri_learning' ) || '' === trim( wp_strip_all_tags( $content ) ) ) ) {
			return '[sabri_learning]';
		}
		return $content;
	}

	public function home() {
		$books = get_posts( array( 'post_type' => SLC_Content::BOOK, 'post_status' => 'publish', 'posts_per_page' => 12, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true ) );
		ob_start();
		?>
		<div class="slc-shell" data-slc-module="learning-home">
			<header class="slc-hero"><div><span><?php esc_html_e( 'Learn Sabri Classical Homeopathy', 'sabri-learning' ); ?></span><h1><?php esc_html_e( 'Books, Lessons, and Structured Classical Study', 'sabri-learning' ); ?></h1><p><?php esc_html_e( 'Read public learning material freely. Log in to bookmark lessons, record progress, and take knowledge checks.', 'sabri-learning' ); ?></p></div><form method="get" role="search"><label class="screen-reader-text" for="slc-search"><?php esc_html_e( 'Search lessons', 'sabri-learning' ); ?></label><input id="slc-search" name="lesson_search" type="search" value="<?php echo esc_attr( isset( $_GET['lesson_search'] ) ? sanitize_text_field( wp_unslash( $_GET['lesson_search'] ) ) : '' ); ?>" placeholder="<?php esc_attr_e( 'Search lessons', 'sabri-learning' ); ?>"><button type="submit"><?php esc_html_e( 'Search', 'sabri-learning' ); ?></button></form></header>
			<section><div class="slc-section-head"><div><span><?php esc_html_e( 'Structured Path', 'sabri-learning' ); ?></span><h2><?php esc_html_e( 'Choose Your Learning Level', 'sabri-learning' ); ?></h2></div><?php $pages = (array) get_option( 'slc_page_map', array() ); if ( is_user_logged_in() && ! empty( $pages['progress'] ) ) : ?><a class="slc-button slc-button-light" href="<?php echo esc_url( get_permalink( $pages['progress'] ) ); ?>"><?php esc_html_e( 'My Learning', 'sabri-learning' ); ?></a><?php endif; ?></div><div class="slc-levels"><?php foreach ( SLC_Content::levels() as $slug => $name ) : ?><a href="<?php echo esc_url( add_query_arg( array( 'lesson_level' => $slug, 'lesson_page' => false ) ) ); ?>"><strong><?php echo esc_html( $name ); ?></strong><span><?php echo esc_html( $this->level_description( $slug ) ); ?></span></a><?php endforeach; ?></div></section>
			<section><div class="slc-section-head"><div><span><?php esc_html_e( 'Founder Publications', 'sabri-learning' ); ?></span><h2><?php esc_html_e( 'Learning Bookshelf', 'sabri-learning' ); ?></h2></div></div><div class="slc-book-grid"><?php foreach ( $books as $book ) : ?><article class="slc-book"><a class="slc-book-cover" href="<?php echo esc_url( get_permalink( $book ) ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Read about %s', 'sabri-learning' ), $book->post_title ) ); ?>"><span>SH</span></a><div><h3><a href="<?php echo esc_url( get_permalink( $book ) ); ?>"><?php echo esc_html( $book->post_title ); ?></a></h3><p><?php echo esc_html( $book->post_excerpt ); ?></p><a href="<?php echo esc_url( get_permalink( $book ) ); ?>"><?php esc_html_e( 'Read Online', 'sabri-learning' ); ?></a></div></article><?php endforeach; ?></div></section>
			<?php echo $this->lessons(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}

	private function level_description( $slug ) {
		$map = array(
			'beginner'               => __( 'Start with essential principles and historical foundations.', 'sabri-learning' ),
			'intermediate'           => __( 'Build structured case and remedy study.', 'sabri-learning' ),
			'advanced'               => __( 'Develop deeper analytical understanding.', 'sabri-learning' ),
			'professional-reference' => __( 'Use focused material as a professional study reference.', 'sabri-learning' ),
		);
		return isset( $map[ $slug ] ) ? $map[ $slug ] : '';
	}

	private function lessons() {
		$search = isset( $_GET['lesson_search'] ) ? sanitize_text_field( wp_unslash( $_GET['lesson_search'] ) ) : '';
		$topic  = isset( $_GET['lesson_topic'] ) ? sanitize_title( wp_unslash( $_GET['lesson_topic'] ) ) : '';
		$level  = isset( $_GET['lesson_level'] ) ? sanitize_title( wp_unslash( $_GET['lesson_level'] ) ) : '';
		$book   = isset( $_GET['lesson_book'] ) ? absint( $_GET['lesson_book'] ) : 0;
		$sort   = isset( $_GET['lesson_sort'] ) ? sanitize_key( wp_unslash( $_GET['lesson_sort'] ) ) : 'latest';
		$paged  = isset( $_GET['lesson_page'] ) ? max( 1, absint( $_GET['lesson_page'] ) ) : 1;
		if ( ! in_array( $sort, array( 'latest', 'popular' ), true ) ) {
			$sort = 'latest';
		}
		$tax = array( 'relation' => 'AND' );
		if ( $topic && SLC_Content::allowed( $topic, SLC_Content::TOPIC ) ) {
			$tax[] = array( 'taxonomy' => SLC_Content::TOPIC, 'field' => 'slug', 'terms' => array( $topic ) );
		}
		if ( $level && SLC_Content::allowed( $level, SLC_Content::LEVEL ) ) {
			$tax[] = array( 'taxonomy' => SLC_Content::LEVEL, 'field' => 'slug', 'terms' => array( $level ) );
		}
		$args = array( 'post_type' => SLC_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => 12, 'paged' => $paged, 's' => $search, 'orderby' => 'date', 'order' => 'DESC' );
		if ( count( $tax ) > 1 ) {
			$args['tax_query'] = $tax;
		}
		if ( $book && SLC_Content::BOOK === get_post_type( $book ) && 'publish' === get_post_status( $book ) ) {
			$args['meta_query'] = array( array( 'key' => '_slc_book_id', 'value' => $book, 'type' => 'NUMERIC' ) );
		}
		if ( 'popular' === $sort ) {
			$args['slc_popular'] = 1;
		}
		$query = new WP_Query( $args );
		$books = get_posts( array( 'post_type' => SLC_Content::BOOK, 'post_status' => 'publish', 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) );
		ob_start();
		?>
		<section class="slc-lessons" id="learning-lessons"><div class="slc-section-head"><div><span><?php esc_html_e( 'Public Learning', 'sabri-learning' ); ?></span><h2><?php esc_html_e( 'Lessons', 'sabri-learning' ); ?></h2></div><?php $pages = (array) get_option( 'slc_page_map', array() ); if ( SLC_Permissions::can_submit() && ! empty( $pages['submit'] ) ) : ?><a class="slc-button" href="<?php echo esc_url( get_permalink( $pages['submit'] ) ); ?>"><?php esc_html_e( 'Submit Lesson', 'sabri-learning' ); ?></a><?php endif; ?></div>
		<form class="slc-filters" method="get"><label><?php esc_html_e( 'Search', 'sabri-learning' ); ?><input type="search" name="lesson_search" value="<?php echo esc_attr( $search ); ?>"></label><label><?php esc_html_e( 'Topic', 'sabri-learning' ); ?><select name="lesson_topic"><option value=""><?php esc_html_e( 'All topics', 'sabri-learning' ); ?></option><?php foreach ( SLC_Content::topics() as $slug => $name ) : ?><option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $topic, $slug ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Level', 'sabri-learning' ); ?><select name="lesson_level"><option value=""><?php esc_html_e( 'All levels', 'sabri-learning' ); ?></option><?php foreach ( SLC_Content::levels() as $slug => $name ) : ?><option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $level, $slug ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Book', 'sabri-learning' ); ?><select name="lesson_book"><option value="0"><?php esc_html_e( 'All books', 'sabri-learning' ); ?></option><?php foreach ( $books as $item ) : ?><option value="<?php echo absint( $item->ID ); ?>" <?php selected( $book, $item->ID ); ?>><?php echo esc_html( $item->post_title ); ?></option><?php endforeach; ?></select></label><label><?php esc_html_e( 'Order', 'sabri-learning' ); ?><select name="lesson_sort"><option value="latest" <?php selected( $sort, 'latest' ); ?>><?php esc_html_e( 'Latest', 'sabri-learning' ); ?></option><option value="popular" <?php selected( $sort, 'popular' ); ?>><?php esc_html_e( 'Popular', 'sabri-learning' ); ?></option></select></label><button class="slc-button" type="submit"><?php esc_html_e( 'Apply', 'sabri-learning' ); ?></button></form>
		<div class="slc-lesson-grid"><?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); if ( SLC_Database::lesson_publicly_available( get_the_ID() ) ) { echo $this->card( get_post() ); } endwhile; else : ?><div class="slc-empty"><h3><?php esc_html_e( 'No lessons found', 'sabri-learning' ); ?></h3><p><?php esc_html_e( 'Try another topic, level, or search phrase.', 'sabri-learning' ); ?></p></div><?php endif; ?></div>
		<?php echo $this->pagination( $query, $paged ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}

	private function pagination( $query, $paged ) {
		if ( $query->max_num_pages < 2 ) {
			return '';
		}
		$base = remove_query_arg( 'lesson_page' );
		$links = paginate_links( array( 'base' => add_query_arg( 'lesson_page', '%#%', $base ), 'format' => '', 'current' => $paged, 'total' => (int) $query->max_num_pages, 'type' => 'list', 'prev_text' => __( 'Previous', 'sabri-learning' ), 'next_text' => __( 'Next', 'sabri-learning' ) ) );
		return $links ? '<nav class="slc-pagination" aria-label="' . esc_attr__( 'Lesson pages', 'sabri-learning' ) . '">' . wp_kses_post( $links ) . '</nav>' : '';
	}

	public function popular_clauses( $clauses, $query ) {
		if ( ! $query->get( 'slc_popular' ) ) {
			return $clauses;
		}
		global $wpdb;
		$clauses['join']    .= " LEFT JOIN {$wpdb->prefix}slc_metrics slcm ON slcm.lesson_id={$wpdb->posts}.ID ";
		$clauses['orderby']  = "COALESCE(slcm.view_count,0) DESC, {$wpdb->posts}.post_date DESC";
		$clauses['distinct'] = 'DISTINCT';
		return $clauses;
	}

	private function card( $post ) {
		$id = $post->ID;
		ob_start();
		?><article class="slc-lesson-card" data-lesson-id="<?php echo absint( $id ); ?>"><?php if ( has_post_thumbnail( $id ) ) : ?><a class="slc-lesson-image" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo get_the_post_thumbnail( $id, 'medium_large', array( 'loading' => 'lazy', 'alt' => $post->post_title ) ); ?></a><?php endif; ?><div><div class="slc-chips"><span><?php echo esc_html( SLC_Content::term( $id, SLC_Content::TOPIC ) ); ?></span><span><?php echo esc_html( SLC_Content::term( $id, SLC_Content::LEVEL ) ); ?></span></div><h3><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( $post->post_title ); ?></a></h3><p><?php echo esc_html( wp_trim_words( $post->post_excerpt, 25 ) ); ?></p><small><?php echo esc_html( SLC_Permissions::label( $post->post_author ) ); ?> · <?php echo absint( SLC_Database::views( $id ) ); ?> <?php esc_html_e( 'views', 'sabri-learning' ); ?></small><?php echo SLC_Learning::actions( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></article><?php
		return ob_get_clean();
	}

	public function single_content( $content ) {
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id = get_the_ID();
		if ( is_singular( SLC_Content::LESSON ) ) {
			$objectives = get_post_meta( $id, '_slc_objectives', true );
			$terms      = get_post_meta( $id, '_slc_terms', true );
			$references = get_post_meta( $id, '_slc_references', true );
			$book       = absint( get_post_meta( $id, '_slc_book_id', true ) );
			$chapter    = get_post_meta( $id, '_slc_chapter', true );
			$time       = get_post_meta( $id, '_slc_study_time', true );
			$head       = '<div class="slc-single-meta"><span>' . esc_html( SLC_Content::term( $id, SLC_Content::TOPIC ) ) . '</span><span>' . esc_html( SLC_Content::term( $id, SLC_Content::LEVEL ) ) . '</span>' . ( $time ? '<span>' . esc_html( $time ) . '</span>' : '' ) . '</div>';
			$tail       = $objectives ? '<section class="slc-panel"><h2>' . esc_html__( 'Learning Objectives', 'sabri-learning' ) . '</h2>' . $this->lines( $objectives ) . '</section>' : '';
			$tail      .= $chapter ? '<p><strong>' . esc_html__( 'Chapter:', 'sabri-learning' ) . '</strong> ' . esc_html( $chapter ) . '</p>' : '';
			$tail      .= $book && SLC_Content::BOOK === get_post_type( $book ) && 'publish' === get_post_status( $book ) ? '<p><strong>' . esc_html__( 'Related Book:', 'sabri-learning' ) . '</strong> <a href="' . esc_url( get_permalink( $book ) ) . '">' . esc_html( get_the_title( $book ) ) . '</a></p>' : '';
			$tail      .= $terms ? '<section class="slc-panel"><h2>' . esc_html__( 'Important Terms', 'sabri-learning' ) . '</h2><p>' . esc_html( $terms ) . '</p></section>' : '';
			$tail      .= $references ? '<section class="slc-panel"><h2>' . esc_html__( 'References', 'sabri-learning' ) . '</h2>' . $this->lines( $references ) . '</section>' : '';
			$tail      .= SLC_Learning::quiz_form( $id ) . '<p class="slc-disclaimer">' . esc_html__( 'This lesson is educational. It does not provide a personal prescription or replace emergency and qualified medical care.', 'sabri-learning' ) . '</p>' . SLC_Learning::actions( $id );
			return '<article class="slc-single" data-lesson-id="' . absint( $id ) . '">' . $head . $content . $tail . '</article>';
		}
		if ( is_singular( SLC_Content::BOOK ) ) {
			$query = new WP_Query( array( 'post_type' => SLC_Content::LESSON, 'post_status' => 'publish', 'posts_per_page' => 100, 'meta_query' => array( array( 'key' => '_slc_book_id', 'value' => $id, 'type' => 'NUMERIC' ) ), 'orderby' => 'date', 'order' => 'ASC', 'no_found_rows' => true ) );
			$tail = '<section class="slc-panel"><h2>' . esc_html__( 'Available Chapters and Lessons', 'sabri-learning' ) . '</h2>';
			if ( $query->have_posts() ) {
				$tail .= '<ul class="slc-lesson-list">';
				while ( $query->have_posts() ) {
					$query->the_post();
					if ( SLC_Database::lesson_publicly_available( get_the_ID() ) ) {
						$tail .= '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></li>';
					}
				}
				$tail .= '</ul>';
			} else {
				$tail .= '<p>' . esc_html__( 'Authorized lessons will appear here as they are published.', 'sabri-learning' ) . '</p>';
			}
			wp_reset_postdata();
			return '<article class="slc-single slc-book-single">' . $content . $tail . '</section><p class="slc-disclaimer">' . esc_html__( 'Read-online access is educational. Paid PDF download and sales are not included in this file.', 'sabri-learning' ) . '</p></article>';
		}
		return $content;
	}

	private function lines( $text ) {
		$out = '<ul>';
		foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $line ) {
			if ( trim( $line ) ) {
				$out .= '<li>' . esc_html( trim( $line ) ) . '</li>';
			}
		}
		return $out . '</ul>';
	}

	public function lesson_author( $content ) {
		if ( ! is_singular( SLC_Content::LESSON ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id     = get_the_ID();
		$author = absint( get_post_field( 'post_author', $id ) );
		return '<div class="slc-author"><strong><a href="' . esc_url( SLC_Permissions::profile_url( $author ) ) . '">' . esc_html( get_the_author_meta( 'display_name', $author ) ) . '</a></strong><span>' . esc_html( SLC_Permissions::label( $author ) ) . ' · ' . esc_html__( 'Updated', 'sabri-learning' ) . ' ' . esc_html( get_the_modified_date( '', $id ) ) . '</span></div>' . $content;
	}

	public function lesson_navigation( $content ) {
		if ( ! is_singular( SLC_Content::LESSON ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$previous = get_previous_post();
		$next     = get_next_post();
		if ( $previous && ! SLC_Database::lesson_publicly_available( $previous->ID ) ) { $previous = null; }
		if ( $next && ! SLC_Database::lesson_publicly_available( $next->ID ) ) { $next = null; }
		if ( ! $previous && ! $next ) {
			return $content;
		}
		$nav  = '<nav class="slc-lesson-nav" aria-label="' . esc_attr__( 'Lesson navigation', 'sabri-learning' ) . '">';
		$nav .= $previous ? '<a href="' . esc_url( get_permalink( $previous ) ) . '">← ' . esc_html__( 'Previous Lesson', 'sabri-learning' ) . '<br><strong>' . esc_html( get_the_title( $previous ) ) . '</strong></a>' : '<span></span>';
		$nav .= $next ? '<a href="' . esc_url( get_permalink( $next ) ) . '">' . esc_html__( 'Next Lesson', 'sabri-learning' ) . ' →<br><strong>' . esc_html( get_the_title( $next ) ) . '</strong></a>' : '<span></span>';
		return $content . $nav . '</nav>';
	}

	public function guard() {
		if ( is_singular( SLC_Content::LESSON ) ) {
			$id = get_queried_object_id();
			if ( ! SLC_Database::lesson_publicly_available( $id ) ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				nocache_headers();
				$template = get_404_template();
				if ( $template ) {
					include $template;
				}
				exit;
			}
		}
	}
}
