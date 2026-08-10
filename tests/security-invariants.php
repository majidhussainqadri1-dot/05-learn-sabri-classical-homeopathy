<?php
/** Deterministic current-plan security and ownership invariants. */
$root = realpath( __DIR__ . '/../05-learn-sabri-classical-homeopathy' );
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
	"Version: 3.3.0" => 'release version',
	"SMC_Contracts" => 'File 00 public assertion contract',
	"single-free-tier-v2" => 'single free tier',
	"#087A4E" => 'Sabri Green fallback',
	"global_rank_owner" => 'File26 global discovery owner',
	"LSCH_NOTE_MASTER_KEY" => 'independent private-note key',
	"note_write_key_version" => 'rotatable note key generation',
	"const SCHEMA = 3" => 'auxiliary state schema',
	"LearningCorrectionResubmitted.v1" => 'correction resubmission lifecycle',
	"LearningCorrectionWithdrawn.v1" => 'correction withdrawal lifecycle',
	"saved_searches" => 'account-owned saved learning search',
	"corrections" => 'correction governance ledger',
	"value_events" => 'privacy-minimized value telemetry',
	"independent_reviewer_required" => 'independent review gate',
	"کامیاب کیس" => 'successful-case learning tag',
);
foreach ( $required as $needle => $label ) {
	if ( false === strpos( $all, $needle ) ) {
		fwrite( STDERR, "Missing invariant: {$label}\n" );
		exit( 1 );
	}
}

$forbidden = array(
	"_smc_identity_verified" => 'File 00 private identity meta coupling',
	"_smc_doctor_verified" => 'File 00 private doctor meta coupling',
	"_smc_2fa_enabled" => 'retired local MFA storage coupling',
	'$wpdb->usermeta' => 'direct File00/UserMeta implementation query',
	'.innerHTML' => 'unsafe dynamic HTML assignment',
	"global_rank_owner' => 'file05'" => 'File05 claiming global ranking ownership',
	'PKR 400' => 'obsolete education pricing',
	'#167447' => 'obsolete File05 green fallback',
);
foreach ( $forbidden as $needle => $label ) {
	if ( false !== stripos( $all, $needle ) ) {
		fwrite( STDERR, "Forbidden invariant found: {$label}\n" );
		exit( 1 );
	}
}

$policy = file_get_contents( $root . '/includes/class-lsch-policy.php' );
$parts = explode( 'public static function decrypt_note', $policy, 2 );
$new_write = $parts[0];
if ( false !== strpos( $new_write, "AUTH_KEY . SECURE_AUTH_SALT . 'lsch-note-v1'" ) ) {
	fwrite( STDERR, "New note writes still derive from WordPress authentication salts.\n" );
	exit( 1 );
}

echo "PASS: File 05 current-plan security and ownership invariants.\n";
