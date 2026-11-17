<?php
/**
 * Customer registration screen — Baraa Pharmacy
 *
 * Rendered by the `[baraa_register]` shortcode on the stand-alone registration
 * page. It keeps every WooCommerce field name, class and action hook, so the
 * core handler (`WC_Form_Handler::process_registration()`) runs untouched.
 *
 * The details collected here are stored on the new account and re-used at
 * checkout — see `baraa_pharmacy_created_customer()` in functions.php.
 *
 * @package WooCommerce\Templates
 */

defined('ABSPATH') || exit;

$baraa_remembered = function_exists('baraa_pharmacy_remembered_profile') ? baraa_pharmacy_remembered_profile() : array();

/**
 * Value shown in a field: what was just posted beats what we remembered in the
 * cookie, so a failed submission never loses what the customer typed.
 */
$baraa_value = static function ($post_key, $profile_key = '') use ($baraa_remembered) {
    if (isset($_POST[$post_key]) && is_string($_POST[$post_key])) {
        return wp_unslash($_POST[$post_key]);
    }

    return ('' !== $profile_key && isset($baraa_remembered[$profile_key])) ? (string) $baraa_remembered[$profile_key] : '';
};

$redirect_target = '';
if (!empty($_REQUEST['redirect_to'])) {
    $redirect_target = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
} elseif (!empty($_REQUEST['redirect'])) {
    $redirect_target = esc_url_raw(wp_unslash($_REQUEST['redirect']));
} elseif (function_exists('WC') && WC()->cart && !WC()->cart->is_empty()) {
    $redirect_target = wc_get_cart_url();
}

$baraa_login_raw = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
$baraa_login_url = !empty($redirect_target) ? add_query_arg('redirect_to', urlencode($redirect_target), $baraa_login_raw) : $baraa_login_raw;
?>

<div class="baraa-auth">

    <header class="baraa-auth__head">
        <span class="baraa-auth__glyph" aria-hidden="true">
            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">person_add</span>
        </span>
        <h1 class="baraa-auth__title"><?php echo esc_html(baraa_pharmacy_text('إنشاء حساب جديد', 'Create account')); ?></h1>
        <p class="baraa-auth__sub">
            <?php echo esc_html(baraa_pharmacy_text(
                'سجّل بياناتك مرة واحدة، وهنحفظها لك عشان نعبّيها تلقائيًا في كل طلب جاي.',
                'Save your details once and we will fill them in automatically on every future order.'
            )); ?>
        </p>
    </header>

    <div class="baraa-auth__grid">

        <section class="baraa-auth__card baraa-auth__card--accent" aria-labelledby="baraa-register-title">
            <h2 class="baraa-auth__card-title" id="baraa-register-title">
                <span class="material-symbols-outlined" aria-hidden="true">person_add</span>
                <?php esc_html_e('Register', 'woocommerce'); ?>
            </h2>
            <p class="baraa-auth__card-hint">
                <?php echo esc_html(baraa_pharmacy_text('حساب جديد = بيانات محفوظة + خصم المحفظة تلقائيًا على كل طلب.', 'A new account keeps your details safe and applies the wallet discount to every order.')); ?>
            </p>

            <form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action('woocommerce_register_form_tag'); ?>>

                <?php do_action('woocommerce_register_form_start'); ?>

                <input type="hidden" name="baraa_account_form" value="1" />
                <?php if (!empty($redirect_target)) : ?>
                    <input type="hidden" name="redirect" value="<?php echo esc_attr($redirect_target); ?>" />
                <?php endif; ?>

                <div class="baraa-auth__row">
                    <p class="woocommerce-form-row form-row form-row-first">
                        <label for="baraa_first_name"><?php esc_html_e('First name', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="baraa_first_name" id="baraa_first_name" autocomplete="given-name" value="<?php echo esc_attr($baraa_value('baraa_first_name', 'first_name')); ?>" required aria-required="true" />
                    </p>
                    <p class="woocommerce-form-row form-row form-row-last">
                        <label for="baraa_last_name"><?php esc_html_e('Last name', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="baraa_last_name" id="baraa_last_name" autocomplete="family-name" value="<?php echo esc_attr($baraa_value('baraa_last_name', 'last_name')); ?>" required aria-required="true" />
                    </p>
                </div>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="baraa_phone"><?php esc_html_e('Phone', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
                    <input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" name="baraa_phone" id="baraa_phone" autocomplete="tel" inputmode="tel" value="<?php echo esc_attr($baraa_value('baraa_phone', 'phone')); ?>" required aria-required="true" />
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="reg_email"><?php esc_html_e('Email address', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
                    <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo esc_attr($baraa_value('email', 'email')); ?>" required aria-required="true" />
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="reg_password"><?php esc_html_e('Password', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
                    <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="baraa_address"><?php esc_html_e('Street address', 'woocommerce'); ?>&nbsp;<span class="baraa-auth__optional"><?php esc_html_e('Optional', 'woocommerce'); ?></span></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="baraa_address" id="baraa_address" autocomplete="street-address" value="<?php echo esc_attr($baraa_value('baraa_address', 'address')); ?>" />
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="baraa_city"><?php esc_html_e('Town / City', 'woocommerce'); ?>&nbsp;<span class="baraa-auth__optional"><?php esc_html_e('Optional', 'woocommerce'); ?></span></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="baraa_city" id="baraa_city" autocomplete="address-level2" value="<?php echo esc_attr($baraa_value('baraa_city', 'city')); ?>" />
                </p>

                <div class="clear"></div>

                <?php do_action('woocommerce_register_form'); ?>

                <p class="woocommerce-form-row form-row baraa-auth__actions">
                    <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                    <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="register" value="<?php esc_attr_e('Register', 'woocommerce'); ?>"><?php esc_html_e('Register', 'woocommerce'); ?></button>
                </p>

                <?php do_action('woocommerce_register_form_end'); ?>

            </form>
        </section>

    </div>

    <p class="baraa-auth__switch">
        <?php echo esc_html(baraa_pharmacy_text('عندك حساب بالفعل؟', 'Already have an account?')); ?>
        <a href="<?php echo esc_url($baraa_login_url); ?>"><?php echo esc_html(baraa_pharmacy_text('سجّل الدخول', 'Log in')); ?></a>
    </p>

</div>
