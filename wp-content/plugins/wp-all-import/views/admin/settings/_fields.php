<?php
if ( ! defined( 'ABSPATH' ) ) exit; // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Field renderers for the Settings screen.
 *
 * These exist so every row is guaranteed a label bound to its control by id. The previous
 * hand-rolled table markup left every label unassociated.
 */

if ( ! function_exists('wpai_settings_field_id') ):
/**
 * Derives a DOM id from an option name.
 */
function wpai_settings_field_id($name) {
	return 'wpai-set-' . trim(preg_replace('%[^a-z0-9]+%i', '-', $name), '-');
}
endif;

if ( ! function_exists('wpai_settings_row') ):
/**
 * @param array $args label, for, control (html), help (html), class, help_id
 */
function wpai_settings_row($args) {
	$args = array_merge(array(
		'label'   => '',
		'for'     => '',
		'control' => '',
		'help'    => '',
		'class'   => '',
		'help_id' => '',
	), $args);

	// The help id must not depend on 'for': toggles and action rows have no single
	// labelable control but still reference the help text by id.
	if ('' === $args['help_id'] && $args['for']) {
		$args['help_id'] = $args['for'] . '-help';
	}

	$classes = 'wpai-set-row' . ($args['class'] ? ' ' . $args['class'] : '');
	?>
	<div class="<?php echo esc_attr($classes); ?>">
		<div class="wpai-set-row__label">
			<?php if ($args['for']): ?>
				<label for="<?php echo esc_attr($args['for']); ?>"><?php echo esc_html($args['label']); ?></label>
			<?php else: ?>
				<span class="wpai-set-row__label-text"><?php echo esc_html($args['label']); ?></span>
			<?php endif; ?>
		</div>
		<div class="wpai-set-row__field">
			<?php echo $args['control']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-built control markup, escaped at the point it was assembled. ?>
			<?php if ($args['help']): ?>
				<p class="wpai-set-help" <?php if ($args['help_id']): ?>id="<?php echo esc_attr($args['help_id']); ?>"<?php endif; ?>><?php echo wp_kses_post($args['help']); ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
endif;

if ( ! function_exists('wpai_settings_number') ):
/**
 * Numeric option. `unit` renders as a suffix so the field states what it counts.
 */
function wpai_settings_number($name, $value, $args = array()) {
	$args = array_merge(array(
		'label'       => '',
		'help'        => '',
		'unit'        => '',
		'min'         => 0,
		'max'         => '',
		'step'        => 1,
		'placeholder' => '',
		'note'        => '',
	), $args);

	$id = wpai_settings_field_id($name);

	ob_start();
	?>
	<span class="wpai-set-numeric">
		<input type="number" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>"
			class="wpai-set-input wpai-set-input--number"
			value="<?php echo esc_attr($value); ?>"
			min="<?php echo esc_attr($args['min']); ?>" step="<?php echo esc_attr($args['step']); ?>"
			<?php if ($args['max'] !== ''): ?>max="<?php echo esc_attr($args['max']); ?>"<?php endif; ?>
			<?php if ($args['placeholder'] !== ''): ?>placeholder="<?php echo esc_attr($args['placeholder']); ?>"<?php endif; ?>
			<?php if ($args['help']): ?>aria-describedby="<?php echo esc_attr($id); ?>-help"<?php endif; ?> />
		<?php if ($args['unit']): ?><span class="wpai-set-unit"><?php echo esc_html($args['unit']); ?></span><?php endif; ?>
	</span>
	<?php if ($args['note']): ?><p class="wpai-set-note"><?php echo esc_html($args['note']); ?></p><?php endif; ?>
	<?php
	$control = ob_get_clean();

	wpai_settings_row(array(
		'label'   => $args['label'],
		'for'     => $id,
		'control' => $control,
		'help'    => $args['help'],
	));
}
endif;

if ( ! function_exists('wpai_settings_toggle') ):
/**
 * Checkbox option. The paired hidden input is what makes an unchecked box save as 0.
 */
function wpai_settings_toggle($name, $checked, $args = array()) {
	$args = array_merge(array(
		'label'    => '',
		'checkbox' => '',
		'help'     => '',
	), $args);

	$id = wpai_settings_field_id($name);

	ob_start();
	?>
	<input type="hidden" name="<?php echo esc_attr($name); ?>" value="0" />
	<label class="wpai-set-check" for="<?php echo esc_attr($id); ?>">
		<input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="1"
			<?php checked($checked); ?>
			<?php if ($args['help']): ?>aria-describedby="<?php echo esc_attr($id); ?>-help"<?php endif; ?> />
		<span><?php echo esc_html($args['checkbox']); ?></span>
	</label>
	<?php
	$control = ob_get_clean();

	wpai_settings_row(array(
		'label'   => $args['label'],
		'for'     => $id,
		'control' => $control,
		'help'    => $args['help'],
		'help_id' => $id . '-help',
	));
}
endif;

if ( ! function_exists('wpai_settings_group') ):
function wpai_settings_group($title, $description = '') {
	?>
	<div class="wpai-set-group">
		<h4 class="wpai-set-group__title"><?php echo esc_html($title); ?></h4>
		<?php if ($description): ?><p class="wpai-set-group__desc"><?php echo wp_kses_post($description); ?></p><?php endif; ?>
	</div>
	<?php
}
endif;
