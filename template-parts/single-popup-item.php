<?php
/**
 * Template file for rendering the popup
 *
 * @package nine3popup
 */

// Get content blocks.
$content = get_the_content( null, false, $popup_id );
$blocks  = parse_blocks( $content );

// Get ACF settings.
$position = get_field( 'nine3_popup_position', $popup_id );
$priority = get_field( 'nine3_popup_priority', $popup_id );
$delay    = get_field( 'nine3_popup_delay', $popup_id );

$size  = get_field( 'nine3_popup_size', $popup_id );
$style = 'style=';
if ( isset( $size['width'] ) ) {
	$style .= 'width:' . $size['width'] . 'vw;';
	$style .= 'left:' . ( 100 - $size['width'] ) / 2 . 'vw;';
}
if ( isset( $size['height'] ) ) {
	$style .= 'height:' . $size['height'] . 'vh;';
	$style .= 'top:' . ( 100 - $size['height'] ) / 2 . 'vh;';
}

$animation = get_field( 'nine3_popup_animation', $popup_id );
if ( strpos( $animation, 'slide' ) !== false ) {
	$origin = get_field( 'nine3_popup_animation_origin', $popup_id );
}

$style = '';
if ( ! empty( $priority ) || ! empty( $delay ) ) {
	$style = 'style="';
	if ( ! empty( $priority ) ) {
		$style .= 'z-index:' . esc_attr( $priority * 100000 ) . ';';
	}
	if ( ! empty( $delay ) ) {
		$style .= 'display:none;';
	}
	$style .= '"';
}

?>

<div
class="nine3-popup <?php echo $animation ? esc_attr( $animation ) : ''; ?> <?php echo isset( $origin ) ? esc_attr( $origin ) : ''; ?>"
id="popup-id-<?php echo esc_html( $popup_id ); ?>"
<?php echo ! empty( $delay ) ? 'data-delay="' . esc_attr( intval( $delay ) ) . '"' : ''; ?>
<?php echo esc_attr( $style ); ?>>
	<div
	class="nine3-popup__wrapper <?php echo $position ? esc_attr( $position ) : ''; ?>"
	<?php echo esc_attr( $style ); ?>>
		<div class="nine3-popup__content">
			<button class="nine3-popup__close"><?php esc_html_e( 'Close', 'nine3popup' ); ?></button>
			<?php
			foreach ( $blocks as $block ) {
				echo render_block( $block ); // phpcs:ignore
			}
			?>
		</div>
	</div>
</div>
