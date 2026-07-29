<?php
/** Deterministic source-level security and architecture invariants. */
$root = realpath( __DIR__ . '/../sabri-learning' );
if ( ! $root ) {
	fwrite( STDERR, "Plugin root missing.\n" );
	exit( 1 );
}
$files = array();
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $iterator as $file ) {
	if ( $file->isFile() ) {
		$files[] = $file->getPathname();
	}
}
sort( $files, SORT_STRING );
$all = '';
foreach ( $files as $file ) {
	$all .= "\n/* " . str_replace( $root . DIRECTORY_SEPARATOR, '', $file ) . " */\n" . file_get_contents( $file );
}

$required = array(
	"Version: 1.0.0" => 'corrected version',
	"class SLC_Dependencies" => 'dependency gate',
	"smc_user_status" => 'File 00 authority',
	"_smc_doctor_verified" => 'File 00 doctor verification',
	"slc_review_lessons" => 'separate reviewer capability',
	"A lesson author cannot review their own submission" => 'self-review prohibition',
	"_slc_row_version" => 'optimistic concurrency',
	"slc_consents" => 'versioned consent persistence',
	"SLC_PURGE_ON_UNINSTALL" => 'guarded uninstall',
	"replaceChildren" => 'safe quiz result rendering',
	"sabri_shell_layout_mode" => 'File 20 shell integration',
	"lesson_page" => 'catalog pagination',
	"ON DUPLICATE KEY UPDATE view_count=view_count+1" => 'atomic view counter',
);
foreach ( $required as $needle => $label ) {
	if ( false === strpos( $all, $needle ) ) {
		fwrite( STDERR, "Missing invariant: {$label}\n" );
		exit( 1 );
	}
}

$forbidden = array(
	"add_filter( 'option_comment_registration'" => 'site-wide comment setting override',
	'SPD_Helpers' => 'File 03 verification fallback',
	"'capability_type' => 'post'" => 'generic post capabilities',
	'.innerHTML' => 'unsafe dynamic HTML assignment',
	'actions/checkout@v4' => 'mutable checkout action reference',
	'shivammathur/setup-php@v2' => 'mutable setup-php action reference',
);
foreach ( $forbidden as $needle => $label ) {
	if ( false !== strpos( $all, $needle ) ) {
		fwrite( STDERR, "Forbidden invariant found: {$label}\n" );
		exit( 1 );
	}
}

$php = array_values( array_filter( $files, static function( $file ) { return '.php' === substr( $file, -4 ); } ) );
if ( 21 !== count( $files ) || 15 !== count( $php ) ) {
	fwrite( STDERR, sprintf( "Unexpected plugin inventory: %d files, %d PHP.\n", count( $files ), count( $php ) ) );
	exit( 1 );
}

echo "Security and architecture invariants passed.\n";
