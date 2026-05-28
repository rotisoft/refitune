<?php
/**
 * Fájlszerkesztő letiltása a WordPress admin felületéről.
 *
 * A DISALLOW_FILE_EDIT WordPress konstans hatására a rendszer
 * eltávolítja a Megjelenés > Sablonszerkesztő és a Bővítmények >
 * Szerkesztő menüpontokat, és a közvetlen URL-en sem érhető el az
 * oldal a jogosultsággal rendelkező felhasználók számára sem.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}
