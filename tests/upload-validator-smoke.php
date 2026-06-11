<?php
/**
 * Standalone validator smoke tests (no WordPress bootstrap).
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	if ( 'cli' !== php_sapi_name() ) {
		exit;
	}

	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Minimal WordPress shims for isolated testing.
if ( ! function_exists( 'wp_basename' ) ) {
	function wp_basename( $path ) {
		return basename( (string) $path );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;

		public function __construct( $code, $message ) {
			$this->code    = $code;
			$this->message = $message;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		return $text;
	}
}

define( 'REFITUNE_UPLOAD_SCAN_MAX_BYTES', 262144 );

require dirname( __DIR__ ) . '/includes/upload-validator.php';

$tmpdir = sys_get_temp_dir();
$tests  = array(
	'valid_jpg_name' => array(
		'file'   => 'photo.jpg',
		'expect' => true,
		'write'  => "\xFF\xD8\xFF\xE0\x00\x10JFIF",
	),
	'double_ext' => array(
		'file'   => 'shell.php.jpg',
		'expect' => 'dangerous_extension',
	),
	'php_ext' => array(
		'file'   => 'image.jpg.php',
		'expect' => 'dangerous_extension',
	),
	'polyglot_gif' => array(
		'file'   => 'logo.gif',
		'expect' => 'script_marker',
		'write'  => "GIF89a<?php echo 1;",
	),
	'jpeg_as_png_ext' => array(
		'file'   => 'screenshot.png',
		'expect' => true,
		'write'  => "\xFF\xD8\xFF\xE0\x00\x10JFIF",
	),
	'magic_mismatch' => array(
		'file'   => 'fake.jpg',
		'expect' => 'magic_mismatch',
		'write'  => "\x00\x01\x02\x03\x04\x05\x06\x07\x08",
	),
);

$passed = 0;
$failed = 0;

foreach ( $tests as $name => $test ) {
	$path = $tmpdir . DIRECTORY_SEPARATOR . 'refitune_test_' . md5( $name );
	$content = $test['write'] ?? 'test';
	file_put_contents( $path, $content );

	$result = refitune_upload_validate_file( $path, $test['file'] );

	if ( true === $test['expect'] ) {
		$ok = true === $result;
	} else {
		$ok = is_wp_error( $result ) && $test['expect'] === $result->get_error_code();
	}

	if ( $ok ) {
		++$passed;
		echo "PASS: {$name}\n";
	} else {
		++$failed;
		$code = is_wp_error( $result ) ? $result->get_error_code() : 'true';
		echo "FAIL: {$name} expected {$test['expect']} got {$code}\n";
	}

	unlink( $path );
}

echo "Summary: {$passed} passed, {$failed} failed\n";
exit( $failed > 0 ? 1 : 0 );
