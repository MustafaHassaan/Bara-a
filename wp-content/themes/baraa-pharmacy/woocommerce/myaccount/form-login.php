<?php
/**
 * Login form — Baraa Pharmacy
 *
 * A centred single-card sign-in screen. Customer registration lives on its own
 * page now (`[baraa_register]`, see `baraa_pharmacy_register_shortcode()` in
 * functions.php), so this template only renders the login form plus a link
 * across to it.
 *
 * WooCommerce's own classes (`woocommerce-form`, `woocommerce-form-login`,
 * `form-row`, …), field names (`username`, `password`, `login`) and every
 * action hook are kept intact, so the core form handlers keep working.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

defined('ABSPATH') || exit;

$baraa_registration_on = 'yes' === get_option('woocommerce_enable_myaccount_registration');
$baraa_guest_checkout  = 'yes' === get_option('woocommerce_enable_guest_checkout');
$baraa_remembered      = function_exists('baraa_pharmacy_remembered_profile') ? baraa_pharmacy_remembered_profile() : array();

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

$baraa_shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');

$redirect_target = '';
if (!empty($_REQUEST['redirect_to'])) {
    $redirect_target = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
} elseif (!empty($_REQUEST['redirect'])) {
    $redirect_target = esc_url_raw(wp_unslash($_REQUEST['redirect']));
} elseif (function_exists('WC') && WC()->cart && !WC()->cart->is_empty()) {
    $redirect_target = wc_get_cart_url();
}

$baraa_register_page = function_exists('baraa_pharmacy_register_page_id') ? baraa_pharmacy_register_page_id() : 0;
$baraa_register_raw  = $baraa_register_page ? get_permalink($baraa_register_page) : home_url('/register/');
$baraa_register_url  = !empty($redirect_target) ? add_query_arg('redirect_to', urlencode($redirect_target), $baraa_register_raw) : $baraa_register_raw;

do_action('woocommerce_before_customer_login_form');
?>

<div class="baraa-auth">

    <header class="baraa-auth__head">
        <span class="baraa-auth__glyph" aria-hidden="true">
            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">local_pharmacy</span>
        </span>
        <h1 class="baraa-auth__title"><?php echo esc_html(baraa_pharmacy_text('حسابي', 'My account')); ?></h1>
        <p class="baraa-auth__sub">
            <?php echo esc_html(baraa_pharmacy_text(
                'سجّل الدخول لمتابعة طلباتك، أو أنشئ حساب جديد عشان بياناتك تتخزّن وتتعبّى تلقائيًا في كل طلب.',
                'Sign in to track your orders, or create an account so your details are saved and filled in automatically.'
            )); ?>
        </p>
    </header>

    <div class="baraa-auth__grid">

        <!-- ═══ تسجيل الدخول ═══════════════════════════════ -->
        <section class="baraa-auth__card" aria-labelledby="baraa-login-title">
            <h2 class="baraa-auth__card-title" id="baraa-login-title">
                <span class="material-symbols-outlined" aria-hidden="true">login</span>
                <?php esc_html_e('Login', 'woocommerce'); ?>
            </h2>
            <p class="baraa-auth__card-hint">
                <?php echo esc_html(baraa_pharmacy_text('لديك حساب بالفعل؟ أدخل بياناتك لمتابعة طلباتك.', 'Already have an account? Sign in to track your orders.')); ?>
            </p>

            <form class="woocommerce-form woocommerce-form-login login" method="post" novalidate>

                <?php do_action('woocommerce_login_form_start'); ?>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="username"><?php esc_html_e('Username or email address', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo esc_attr($baraa_value('username', 'email')); ?>" required aria-required="true" />
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="password"><?php esc_html_e('Password', 'woocommerce'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('Required', 'woocommerce'); ?></span></label>
                    <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
                </p>

                <div class="clear"></div>

                <?php if (!empty($redirect_target)) : ?>
                    <input type="hidden" name="redirect" value="<?php echo esc_attr($redirect_target); ?>" />
                <?php endif; ?>

                <?php do_action('woocommerce_login_form'); ?>

                <p class="form-row baraa-auth__actions">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" /> <span><?php esc_html_e('Remember me', 'woocommerce'); ?></span>
                    </label>
                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                    <button type="submit" class="woocommerce-button button woocommerce-form-login__submit<?php echo esc_attr(wc_wp_theme_get_element_class_name('button') ? ' ' . wc_wp_theme_get_element_class_name('button') : ''); ?>" name="login" value="<?php esc_attr_e('Log in', 'woocommerce'); ?>"><?php esc_html_e('Log in', 'woocommerce'); ?></button>
                </p>

                <p class="woocommerce-LostPassword lost_password">
                    <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Lost your password?', 'woocommerce'); ?></a>
                </p>

                <?php do_action('woocommerce_login_form_end'); ?>

            </form>
        </section>

    </div>

    <?php if ($baraa_registration_on) : ?>
        <p class="baraa-auth__switch">
            <?php echo esc_html(baraa_pharmacy_text('معندكش حساب؟', "Don't have an account?")); ?>
            <a href="<?php echo esc_url($baraa_register_url); ?>"><?php echo esc_html(baraa_pharmacy_text('أنشئ حساب جديد', 'Create one')); ?></a>
        </p>
    <?php endif; ?>

    <p class="baraa-auth__note">
        <?php if ($baraa_guest_checkout) : ?>
            <span class="material-symbols-outlined" aria-hidden="true">info</span>
            <?php echo esc_html(baraa_pharmacy_text('التسجيل اختياري — تقدر تكمّل طلبك بدون حساب، لكن بياناتك هتتحفظ على المتصفح فقط.', 'Registration is optional — you can order without an account, but your details will only be remembered on this browser.')); ?>
        <?php else : ?>
            <span class="material-symbols-outlined" aria-hidden="true">lock</span>
            <?php echo esc_html(baraa_pharmacy_text('إنشاء الحساب مطلوب لإتمام الطلب وليصل إليك خصم المحفظة.', 'An account is required to place an order and to receive the wallet discount.')); ?>
        <?php endif; ?>
        <a href="<?php echo esc_url($baraa_shop_url); ?>"><?php esc_html_e('Continue shopping', 'woocommerce'); ?></a>
    </p>

</div>

<?php do_action('woocommerce_after_customer_login_form'); ?>
