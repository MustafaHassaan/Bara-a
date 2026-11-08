<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Admin Settings page
 *
 * @author Maksym Tsypliakov <maksym.tsypliakov@gmail.com>
 */
class PMXI_Admin_Settings extends PMXI_Controller_Admin {

	public static $path;

	public static $upload_transient;

	/**
	 * `tab` is preserved so save redirects land the user back on the tab they were editing.
	 * @var array
	 */
	public $baseUrlParamNames = array('page', 'tab', 'pagenum', 'order', 'order_by', 'type', 's', 'f');

	/**
	 * The options exactly as they stood when this request began. save_options() measures
	 * against this so a submission only writes the keys it actually changed.
	 * @var array|null
	 */
	private $optionSnapshot = null;

	/**
	 * Tab slug => label. Order is the rendered order; the first one is the landing tab.
	 */
	public function get_tabs() {
		return array(
			'imports'   => __('Imports', 'wp-all-import'),
			'cron'      => __('Cron', 'wp-all-import'),
			'files'     => __('Files', 'wp-all-import'),
			'templates' => __('Templates', 'wp-all-import'),
			'functions' => __('Functions', 'wp-all-import'),
			'support'   => __('Support', 'wp-all-import'),
		);
	}

	protected function get_current_tab() {
		$tabs = $this->get_tabs();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab  = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : '';

		return isset($tabs[$tab]) ? $tab : key($tabs);
	}

	/**
	 * Rows contributed into the settings table via pmxi_settings_advanced. Captured so the
	 * tab that hosts them can tell whether there is anything to show.
	 */
	protected function get_addon_settings($post) {
		ob_start();
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action('pmxi_settings_advanced', $post);

		return $this->bind_contributed_labels(trim(ob_get_clean()));
	}

	/**
	 * Binds a contributed form-table row's label and description to its control.
	 *
	 * Third-party sections arrive as markup we do not own: the row <label> is bound to
	 * nothing and the description is referenced by nothing, so the control announces
	 * without its group name and without its help text.
	 *
	 * @param  string $html
	 * @return string
	 */
	protected function bind_contributed_labels($html) {
		if ('' === $html || ! class_exists('DOMDocument')) {
			return $html;
		}

		// libxml parses <script>/<textarea> bodies as markup and drops tags that appear
		// inside JS strings. Bailing on the whole buffer would let one add-on's inline
		// script silence binding for every other contributor on the same hook, so stash the
		// bodies, parse, then put them back verbatim.
		$stash = array();
		// Unguessable per call: strtr() replaces blindly, so a fixed token appearing
		// literally in contributed content would be overwritten with a script body.
		$nonce = function_exists('wp_generate_password') ? wp_generate_password(12, false) : (string) wp_rand();
		$html = preg_replace_callback(
			// A quoted attribute may legitimately contain '>'. The run is possessive so the
			// alternation can never be re-entered, and the trailing group is the last-resort
			// path for an unbalanced quote: without it the tag does not match at all and the
			// body is left unstashed, which is the failure this stash exists to avoid.
			'%(<(script|textarea)\b(?:"[^"]*"|\'[^\']*\'|[^>"\'])*+(?:["\'][^>]*)?>)(.*?)(</\2>)%is',
			function ($m) use (&$stash, $nonce) {
				$token = 'WPAIBINDSTASH' . $nonce . count($stash) . 'X';
				$stash[$token] = $m[3];

				return $m[1] . $token . $m[4];
			},
			$html
		);

		$doc = new DOMDocument();
		$previous = libxml_use_internal_errors(true);
		$loaded = $doc->loadHTML(
			'<?xml encoding="utf-8" ?><div id="wpai-bind-root">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if ( ! $loaded) {
			return $stash ? strtr($html, $stash) : $html;
		}

		$xpath = new DOMXPath($doc);

		// A contributed section title is an h3, and it is restyled to be pixel-identical
		// to the native h4 group titles, so the page shows one heading level and the
		// accessibility tree reports another. Override the level rather than rewrite the
		// tag: renaming a contributor's element is the corruption this whole method is
		// careful to avoid.
		foreach ($xpath->query('//h3[not(@aria-level)]') as $heading) {
			$heading->setAttribute('role', 'heading');
			$heading->setAttribute('aria-level', '4');
		}

		foreach ($xpath->query('//tr') as $row) {
			$controls = $xpath->query('.//td//input[not(@type="hidden")] | .//td//select | .//td//textarea', $row);

			if ($controls->length !== 1) {
				continue;
			}

			$id = $controls->item(0)->getAttribute('id');

			if ('' === $id) {
				continue;
			}

			foreach ($xpath->query('.//th//label[not(@for)]', $row) as $label) {
				$label->setAttribute('for', $id);
			}

			$descriptions = $xpath->query('.//td//p[contains(concat(" ", normalize-space(@class), " "), " description ")]', $row);
			$tokens = preg_split('%\s+%', trim($controls->item(0)->getAttribute('aria-describedby')), -1, PREG_SPLIT_NO_EMPTY);
			$index = 0;

			foreach ($descriptions as $description) {
				$descId = $description->getAttribute('id');

				if ('' === $descId) {
					$descId = $id . '-desc' . ($index ? '-' . $index : '');
					$description->setAttribute('id', $descId);
				}

				// Append: an add-on may already point at a note of its own.
				if ( ! in_array($descId, $tokens, true)) {
					$tokens[] = $descId;
				}

				$index++;
			}

			if ($tokens) {
				$controls->item(0)->setAttribute('aria-describedby', implode(' ', $tokens));
			}
		}

		$root = $doc->getElementById('wpai-bind-root');

		if ($root === null) {
			return $stash ? strtr($html, $stash) : $html;
		}

		$out = '';
		// An unbalanced close tag in one contributor's markup closes the wrapper early, so
		// everything after it becomes a top-level sibling of the wrapper rather than a
		// child. Emitting only the wrapper's children would delete every later contributor.
		foreach ($doc->childNodes as $top) {
			if ($top->nodeType === XML_PI_NODE) {
				continue;
			}
			if ($top === $root) {
				foreach ($top->childNodes as $child) {
					$out .= $doc->saveHTML($child);
				}
			} else {
				$out .= $doc->saveHTML($top);
			}
		}

		return $stash ? strtr($out, $stash) : $out;
	}

	/**
	 * wp_check_invalid_utf8()'s strip mode routes to iconv() without //IGNORE, which
	 * returns false on PHP 8, so a bad byte erases the whole field rather than the
	 * sequence. Sanitise here instead, so one plugin's header costs one character.
	 */
	protected function utf8_safe($value) {
		$value = (string) $value;

		if ('' === $value || (function_exists('mb_check_encoding') && mb_check_encoding($value, 'UTF-8'))) {
			return $value;
		}

		if (function_exists('iconv')) {
			$stripped = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
			if (false !== $stripped) {
				return $stripped;
			}
		}

		return preg_replace('/[\x80-\xFF]/', '', $value);
	}

	/**
	 * Plain-text environment summary users can paste into a support ticket.
	 */
	protected function get_system_report() {
		global $wp_version;

		if ( ! function_exists('get_plugin_data')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$theme  = wp_get_theme();
		$active = array();
		foreach ((array) get_option('active_plugins', array()) as $plugin) {
			$data     = @get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, false, false);
			$name     = $this->utf8_safe((string) (empty($data['Name']) ? $plugin : $data['Name']));
			$version  = $this->utf8_safe((string) (empty($data['Version']) ? '' : $data['Version']));
			$active[] = '' === $version ? $name : $name . ' ' . $version;
		}
		sort($active);

		$server_limit  = $this->get_server_time_limit();
		$wpai_override = (int) PMXI_Plugin::getInstance()->getOption('max_execution_time');

		$lines = array(
			'== WP All Import ==',
			'WP All Import:      ' . PMXI_VERSION . (defined('PMXI_EDITION') ? ' (' . PMXI_EDITION . ')' : ''),
			'PHP in mappings:    ' . (PMXI_Plugin::$is_php_allowed ? 'allowed' : 'blocked'),
			'Secure mode:        ' . (PMXI_Plugin::getInstance()->getOption('secure') ? 'on' : 'off'),
			'Chunk size:         ' . PMXI_Plugin::getInstance()->getOption('large_feed_limit'),
			'',
			'== Environment ==',
			'WordPress:          ' . $wp_version . (is_multisite() ? ' (multisite)' : ''),
			'Theme:              ' . $this->utf8_safe((string) $theme->get('Name')) . ' ' . $this->utf8_safe((string) $theme->get('Version')),
			'PHP:                ' . PHP_VERSION,
			'Memory limit:       ' . ini_get('memory_limit'),
			'Max execution (server): ' . ($server_limit ? $server_limit . 's' : 'unlimited'),
			'Max execution (WPAI):   ' . ($wpai_override ? $wpai_override . 's' : 'unlimited (WP All Import overrides the server on its own admin pages)'),
			'Max upload size:    ' . size_format(wp_max_upload_size()),
			// The two raw limits behind it: the computed figure never says which one a failed upload hit.
			'Upload max filesize: ' . ini_get('upload_max_filesize'),
			'Post max size:      ' . ini_get('post_max_size'),
			// Truncates a large field mapping without reporting anything.
			'Max input vars:     ' . ini_get('max_input_vars'),
			'libxml:             ' . (defined('LIBXML_DOTTED_VERSION') ? LIBXML_DOTTED_VERSION : 'unknown'),
			'Server:             ' . (empty($_SERVER['SERVER_SOFTWARE']) ? 'unknown' : $this->utf8_safe(sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])))),
			'MySQL:              ' . $this->utf8_safe((string) $this->get_db_version()),
			'WP_DEBUG:           ' . (defined('WP_DEBUG') && WP_DEBUG ? 'on' : 'off'),
			'',
			'== Active plugins (' . count($active) . ') ==',
		);

		// esc_textarea() is htmlspecialchars() without ENT_SUBSTITUTE, so a single invalid
		// byte anywhere in here returns '' for the entire report, with a Copy button that
		// then copies nothing.
		return $this->utf8_safe(implode("\n", array_merge($lines, $active)));
	}

	/**
	 * Normalises a request-derived option array before it is stored.
	 *
	 * @param  array $post
	 * @return array
	 */
	protected function normalise_saved_options($post) {
		$stored = PMXI_Plugin::getInstance()->getOption();

		// A crafted POST can turn a scalar option into an array (port[]=…), which then
		// persists and fatals on every later read. Options that are legitimately arrays are
		// left alone.
		foreach ($post as $key => $value) {
			if (is_scalar($value) || ! isset($stored[$key]) || is_array($stored[$key])) {
				continue;
			}

			$post[$key] = $stored[$key];
		}

		// $_POST is all strings, so an untouched int option comes back as "5" against a
		// stored 5. save_options() compares with !==, so each one would count as changed and
		// every numeric option would be rewritten as a string on the first save.
		foreach ($post as $key => $value) {
			if (is_int($stored[$key] ?? null) && is_string($value) && is_numeric($value)
				&& (string) (int) $value === $value) {
				$post[$key] = (int) $value;
			}
		}

		// No free form edits cron options, and Pro reads the same stored values.
		foreach ($post as $key => $value) {
			if (0 === strpos((string) $key, 'cron_') && array_key_exists($key, $stored)) {
				$post[$key] = $stored[$key];
			}
		}

		return $post;
	}

	/**
	 * Persist the options this request actually changed, and nothing else.
	 *
	 * A form submission is a statement about the fields it carries; $post is $_POST laid
	 * over every stored option, so writing it whole re-asserts values the request only
	 * observed, including ones another request (a dismissal AJAX, say) wrote in between.
	 *
	 * @param  array $post
	 * @return array the keys written
	 */
	protected function save_options($post) {
		$snapshot = is_null($this->optionSnapshot)
			? PMXI_Plugin::getInstance()->getOption()
			: $this->optionSnapshot;

		$changed = array();

		foreach ((array) $post as $key => $value) {
			if ( ! array_key_exists($key, $snapshot) or $snapshot[$key] !== $value) {
				$changed[$key] = $value;
			}
		}

		if ($changed) {
			PMXI_Plugin::getInstance()->updateOption($changed);
		}

		return $changed;
	}

	/**
	 * The server's real execution ceiling.
	 *
	 * ini_get() is useless here: plugin.php ini_set()s max_execution_time from its own
	 * `max_execution_time` option (default 0 = unlimited) before any WPAI admin controller
	 * runs, so ini_get reports our override rather than the server. get_cfg_var() reads
	 * php.ini and is unaffected by ini_set. This matters because the override only applies
	 * to WPAI admin pages. A real cron request comes in through wp-load.php and gets the
	 * server value below.
	 */
	protected function get_server_time_limit() {
		$cfg = get_cfg_var('max_execution_time');

		return $cfg === false || $cfg === '' ? (int) ini_get('max_execution_time') : (int) $cfg;
	}

	protected function get_db_version() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var('SELECT VERSION()');
	}

	public function __construct(){

		parent::__construct();

		self::$upload_transient = 'pmxi_uploads_path';

		$uploads = wp_upload_dir();

		$is_secure_import = PMXI_Plugin::getInstance()->getOption('secure');

		if ( ! $is_secure_import ){

			self::$path = wp_all_import_secure_file($uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::UPLOADS_DIRECTORY );

		}
		else {

			self::$path = get_transient( self::$upload_transient );

			if ( empty(self::$path) ) {
				self::$path = wp_all_import_secure_file($uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::UPLOADS_DIRECTORY );
				set_transient( self::$upload_transient, self::$path);
			}
		}

		$sleep = apply_filters( 'wp_all_import_shard_delay', 0 );
		usleep($sleep);

		$this->enqueue_settings_assets();
	}

	protected function enqueue_settings_assets() {

		wp_enqueue_style('pmxi-settings-style', WP_ALL_IMPORT_ROOT_URL . '/static/css/settings.css', array('pmxi-admin-style'), PMXI_VERSION);
		wp_enqueue_script('pmxi-settings-script', WP_ALL_IMPORT_ROOT_URL . '/static/js/settings.js', array('jquery'), PMXI_VERSION, true);

		wp_localize_script('pmxi-settings-script', 'wpaiSettingsL10n', array(
			'saving'            => __('Saving…', 'wp-all-import'),
			'leaveWarning'      => __('You have unsaved changes that will be lost.', 'wp-all-import'),
			'copied'            => __('Copied', 'wp-all-import'),
			'copyFailed'        => __('Press Ctrl+C', 'wp-all-import'),
			'confirm'           => __('Confirm', 'wp-all-import'),
			'confirmTitle'      => __('Are you sure?', 'wp-all-import'),
			'cancel'            => __('Cancel', 'wp-all-import'),
		));
	}

	public function index() {

		$this->optionSnapshot = PMXI_Plugin::getInstance()->getOption();
		$this->data['post'] = $post = $this->input->post($this->optionSnapshot);

		if ($this->input->post('is_settings_submitted')) { // save settings form
			check_admin_referer('edit-settings', '_wpnonce_edit-settings');

			if (empty($post['html_entities'])) $post['html_entities'] = 0;
			if (empty($post['utf8_decode'])) $post['utf8_decode'] = 0;

			// min/max on the inputs is a convenience, not a guarantee. The same bounds
			// have to hold for anything that posts directly.
			$numeric_fields = array(
				'large_feed_limit'           => array(__('Chunk size', 'wp-all-import'), 1, 100000),
				'log_storage'                => array(__('Logs kept', 'wp-all-import'), 0, 1000),
				'history_file_count'         => array(__('Import files kept', 'wp-all-import'), 0, 100000),
				'history_file_age'           => array(__('Delete files older than', 'wp-all-import'), 0, 3650),
				'port'                       => array(__('Custom port', 'wp-all-import'), 1, 65535),
			);

			foreach ($numeric_fields as $field => $spec) {
				list($label, $min, $max) = $spec;

				// Only the submitted tab's fields are in $_POST; the rest come from stored
				// options. Validating those too means a value that was legal before these
				// ranges existed blocks saving every other tab, naming a field that isn't
				// on the page.
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! isset($_POST[$field]) || ! is_scalar($post[$field])) {
					continue;
				}

				$value = trim((string) $post[$field]);

				if ('' === $value) {
					continue;
				}

				if ( ! preg_match('%^\d+$%', $value)) {
					/* translators: %s: name of the settings field */
					$this->errors->add('form-validation', sprintf(__('%s must be a whole number, or left blank.', 'wp-all-import'), $label));
					continue;
				}

				if ((int) $value < $min || (int) $value > $max) {
					/* translators: 1: name of the settings field, 2: minimum value, 3: maximum value */
					$this->errors->add('form-validation', sprintf(__('%1$s must be between %2$d and %3$d.', 'wp-all-import'), $label, $min, $max));
				}
			}

			if ( ! $this->errors->get_error_codes()) { // no validation errors detected

				$previous_port = (string) PMXI_Plugin::getInstance()->getOption('port');

				$this->save_options($this->normalise_saved_options($post));

				// The base controller built baseUrl from the pre-save port, so a port change
				// would redirect to the old address.
				$new_port = isset($post['port']) ? (string) $post['port'] : '';
				if ($new_port !== $previous_port) {
					$parts = wp_parse_url($this->baseUrl);

					if ( ! empty($parts['host'])) {
						$site = wp_parse_url(site_url());
						$fallback = ( ! empty($site['port']) && ! in_array((int) $site['port'], array(80, 443), true))
							? ':' . (int) $site['port']
							: '';

						$this->baseUrl = $parts['scheme'] . '://' . $parts['host']
							. ('' !== $new_port && is_numeric($new_port) ? ':' . (int) $new_port : $fallback)
							. (isset($parts['path']) ? $parts['path'] : '')
							. (isset($parts['query']) ? '?' . $parts['query'] : '');
					}
				}

				$files = new PMXI_File_List(); $files->sweepHistory(); // adjust file history to new settings specified

				// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
				wp_redirect(esc_url_raw(add_query_arg('pmxi_nt', urlencode(__('Settings saved', 'wp-all-import')), $this->baseUrl))); die();
			}
		}

		if ($this->input->post('is_templates_submitted')) { // delete templates form

			check_admin_referer('delete-templates', '_wpnonce_delete-templates');

			if ($this->input->post('import_templates')){

				if (!empty($_FILES)){
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$file_name = sanitize_file_name($_FILES['template_file']['name'] ?? '');
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
					$file_size = intval($_FILES['template_file']['size'] ?? 0);
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$tmp_name  = realpath($_FILES['template_file']['tmp_name'] ?? '');
					
					if(isset($file_name)) 
					{				
						
						$filename  = stripslashes($file_name);
						$extension = strtolower(pmxi_getExtension($filename));
										
						if (($extension != "txt")) 
						{							
							$this->errors->add('form-validation', __('Unknown File extension. Only txt files are permitted', 'wp-all-import'));
						}
						else {
							$import_data = @file_get_contents($tmp_name);
							if (!empty($import_data)){
								$import_data = str_replace("\xEF\xBB\xBF", '', $import_data);
								$templates_data = json_decode($import_data, true);
								
								if ( ! empty($templates_data) ){
									if ( ! empty($templates_data[0]['options']) && is_array($templates_data[0]['options'])){
										$templateOptions = $templates_data[0]['options'];
									}
									else{
										$templateOptions = empty($templates_data[0]['options']) ? false : \pmxi_maybe_unserialize($templates_data[0]['options']);
									}
									if ( empty($templateOptions) ){
										$this->errors->add('form-validation', __('The template is invalid. Options are missing.', 'wp-all-import'));
									}
									else{
										if (isset($templateOptions['is_user_export'])){
											$this->errors->add('form-validation', __('The template you\'ve uploaded is intended to be used with WP All Export plugin.', 'wp-all-import'));
										}
										else{
											$template = new PMXI_Template_Record();
											foreach ($templates_data as $template_data) {
												unset($template_data['id']);
												$template->clear()->set($template_data)->insert();
											}
											/* translators: see placeholders in the string below */
											wp_redirect(esc_url_raw(add_query_arg('pmxi_nt', urlencode(sprintf(_n('%d template imported', '%d templates imported', count($templates_data), 'wp-all-import'), count($templates_data))), $this->baseUrl))); die(); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
										}
									}
								}
								else $this->errors->add('form-validation', __('Wrong imported data format', 'wp-all-import'));							
							}
							else $this->errors->add('form-validation', __('File is empty or doesn\'t exests', 'wp-all-import'));
						}
					}
					else $this->errors->add('form-validation', __('Undefined entry!', 'wp-all-import'));
				}
				else $this->errors->add('form-validation', __('Please select file.', 'wp-all-import'));

			}
			else{
				$templates_ids = $this->input->post('templates', array());
				if (empty($templates_ids)) {
					$this->errors->add('form-validation', __('Templates must be selected', 'wp-all-import'));
				}
				
				if ( ! $this->errors->get_error_codes()) { // no validation errors detected
					if ($this->input->post('delete_templates')){
						$template = new PMXI_Template_Record();
						foreach ($templates_ids as $template_id) {
							$template->clear()->set('id', $template_id)->delete();
						}
						/* translators: see placeholders in the string below */
						wp_redirect(esc_url_raw(add_query_arg('pmxi_nt', urlencode(sprintf(_n('%d template deleted', '%d templates deleted', count($templates_ids), 'wp-all-import'), count($templates_ids))), $this->baseUrl))); die(); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					}
					if ($this->input->post('export_templates')){
						$export_data = array();
						$template = new PMXI_Template_Record();
						foreach ($templates_ids as $template_id) {
							$export_data[] = $template->clear()->getBy('id', $template_id)->toArray(TRUE);
						}	
						
						$uploads = wp_upload_dir();
						$targetDir = $uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::TEMP_DIRECTORY;
						$export_file_name = "templates_".uniqid().".txt";
						file_put_contents($targetDir . DIRECTORY_SEPARATOR . $export_file_name, json_encode($export_data));
						
						PMXI_download::csv($targetDir . DIRECTORY_SEPARATOR . $export_file_name);
						
					}				
				}
			}
		}

		$this->data['tabs']        = $this->get_tabs();
		$this->data['current_tab'] = $this->get_current_tab();
		$this->data['tab_url']     = array();
		foreach ($this->data['tabs'] as $slug => $label) {
			$this->data['tab_url'][$slug] = add_query_arg('tab', $slug, admin_url('admin.php?page=pmxi-admin-settings'));
		}

		$this->data['post'] = $post;

		$uploads = wp_upload_dir();
		$this->data['functions_file'] = apply_filters(
			'import_functions_file_path',
			$uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_IMPORT_UPLOADS_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'functions.php'
		);
		$this->data['uploads_basedir'] = $uploads['basedir'] . DIRECTORY_SEPARATOR . WP_ALL_IMPORT_UPLOADS_BASE_DIRECTORY;

		$this->data['addon_settings']    = $this->get_addon_settings($post);
		// get_plugin_data() reads and parses every active plugin's header file. Only the
		// Support tab shows the result, so the other five must not pay for it.
		$this->data['system_report'] = 'support' === $this->data['current_tab']
			? $this->get_system_report()
			: '';

		$this->render();
	}

	/*
	*
	* Activate licenses for main plugin and all premium addons
	*
	*/
	protected function activate_licenses() {

		// listen for our activate button to be clicked
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if( isset( $_POST['pmxi_license_activate'] ) ) {

			// retrieve the license from the database
			$options = PMXI_Plugin::getInstance()->getOption();

			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			foreach ($_POST['pmxi_license_activate'] as $class => $val) {						

				if (!empty($options['licenses'][$class])){

					$product_name = (method_exists($class, 'getEddName')) ? call_user_func(array($class, 'getEddName')) : false;

					if ( $product_name !== false ){
						// data to send in our API request
						$api_params = array( 
							'edd_action'=> 'activate_license', 
							'license' 	=> $options['licenses'][$class], 
							'item_name' => urlencode( $product_name ) // the name of our product in EDD
						);								
						
						// Call the custom API.
						$response = wp_remote_get( esc_url_raw(add_query_arg( $api_params, $options['info_api_url'] ), array( 'timeout' => 15, 'sslverify' => false ) ));

						// make sure the response came back okay
						if ( is_wp_error( $response ) )
							continue;

						// decode the license data
						$license_data = json_decode( wp_remote_retrieve_body( $response ) );
						
						// $license_data->license will be either "active" or "inactive"

						$options['statuses'][$class] = $license_data->license;
						
						PMXI_Plugin::getInstance()->updateOption($options);	
					}
				}

			}				

		}
	}	

	/*
	*
	* Check plugin's license
	*
	*/
	public static function check_license($class) {

		global $wp_version;

		$options = PMXI_Plugin::getInstance()->getOption();	

		if (!empty($options['licenses'][$class])){

			$product_name = (method_exists($class, 'getEddName')) ? call_user_func(array($class, 'getEddName')) : false;

			if ( $product_name !== false ){

				$api_params = array( 
					'edd_action' => 'check_license', 
					'license' => $options['licenses'][$class], 
					'item_name' => urlencode( $product_name ) 
				);

				// Call the custom API.
				$response = wp_remote_get( esc_url_raw(add_query_arg( $api_params, $options['info_api_url'] ), array( 'timeout' => 15, 'sslverify' => false ) ));

				if ( is_wp_error( $response ) )
					return false;

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				return $license_data->license;
				
			}
		}

		return false;

	}
	
	public function cleanup(){

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = (!empty($_REQUEST['_wpnonce'])) ? sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])) : '';
        if ( ! wp_verify_nonce( $nonce, '_wpnonce-cleanup_logs' ) ) {
            die( esc_html__('Security check', 'wp-all-import') );
        }

        $removedFiles = 0;

		$wp_uploads = wp_upload_dir();

		$dir = $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::TEMP_DIRECTORY;

		$cacheDir = PMXI_Plugin::ROOT_DIR . '/libraries/cache';

		$files = array_diff(@scandir($dir), array('.','..'));

		$cacheFiles = @scandir($cacheDir);
		$cacheFiles = is_array($cacheFiles) ? @array_diff($cacheFiles, array('.','..')) : [];

		$msg = __('Files not found', 'wp-all-import');

		if ( count($files) or count($cacheFiles)){

			wp_all_import_clear_directory( $dir );

			wp_all_import_clear_directory( $cacheDir );		

			$msg = __('Clean Up has been successfully completed.', 'wp-all-import');
		}

		// clean logs files
		$table = PMXI_Plugin::getInstance()->getTablePrefix() . 'history';
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$histories = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

		if ( ! empty($histories) )
		{
			$importRecord = new PMXI_Import_Record();
			$importRecord->clear();
			foreach ($histories as $history) {
				$importRecord->getById($history['import_id']);
				if ( $importRecord->isEmpty() )
				{
					$historyRecord = new PMXI_History_Record();
					$historyRecord->getById($history['id']);
					if ( ! $historyRecord->isEmpty() ) {
						$historyRecord->delete();
					}
				}
				$importRecord->clear();
			}
		}

		// clean uploads folder
		$table = PMXI_Plugin::getInstance()->getTablePrefix() . 'files';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
		$files = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

		$required_dirs = array();

		if ( ! empty($files) )
		{
			$importRecord = new PMXI_Import_Record();
			$importRecord->clear();
			foreach ($files as $file) {
				$importRecord->getById($file['import_id']);				
				if ( $importRecord->isEmpty()){
					$fileRecord = new PMXI_File_Record();
					$fileRecord->getById($file['id']);					
					if ( ! $fileRecord->isEmpty() ) {						
						$fileRecord->delete();
					}
				}
				else
				{
					$path_parts = pathinfo(wp_all_import_get_absolute_path($file['path']));
					if ( ! empty($path_parts['dirname'])){
			            $path_all_parts = explode('/', $path_parts['dirname']);
			            $dirname = array_pop($path_all_parts);
			            if ( wp_all_import_isValidMd5($dirname)){    
			            	$required_dirs[] = $path_parts['dirname'];
			            }
			        }					
				}
				$importRecord->clear();
			}			
		}

		$uploads_dir = $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::UPLOADS_DIRECTORY;

		if (($dir = @opendir($uploads_dir . DIRECTORY_SEPARATOR)) !== false or ($dir = @opendir($uploads_dir)) !== false) {				
			while(($file = @readdir($dir)) !== false) {
				$filePath = $uploads_dir . DIRECTORY_SEPARATOR . $file;									
				
				if ( is_dir($filePath) and ! in_array($filePath, $required_dirs) and ( ! in_array($file, array('.', '..'))))
				{
					wp_all_import_rmdir($filePath);								
				}						
			}
		}

		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		wp_redirect(esc_url_raw(add_query_arg('pmxi_nt', urlencode($msg), $this->baseUrl))); die();
	}

	public function dismiss(){

		if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false )){
			exit( esc_html__('Security check', 'wp-all-import'));
		}

		PMXI_Plugin::getInstance()->updateOption("dismiss", 1);

		exit('OK');
	}

	public function dismiss_speed_up(){

		if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false )){
			exit( esc_html__('Security check', 'wp-all-import'));
		}

		PMXI_Plugin::getInstance()->updateOption("dismiss_speed_up", 1);

		exit('OK');
	}

	public function dismiss_manage_top(){

		if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false )){
			exit( json_encode(array('result' => array(), 'failed_msgs' => array(__('Security check', 'wp-all-import')))));
		}

		PMXI_Plugin::getInstance()->updateOption("dismiss_manage_top", 1);

		exit( json_encode(array('result' => 'OK')) );
	}

	public function dismiss_manage_bottom(){

		if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false )){
			exit( json_encode(array('result' => array(), 'failed_msgs' => array(__('Security check', 'wp-all-import')))));
		}

		PMXI_Plugin::getInstance()->updateOption("dismiss_manage_bottom", 1);

		exit( json_encode(array('result' => 'OK')) );
	}
	
	public function meta_values(){

		if ( ! PMXI_Plugin::getInstance()->getAdminCurrentScreen()->is_ajax) { // call is only valid when send with ajax
			exit('nice try!');
		}				

		if ( ! check_ajax_referer( 'wp_all_import_secure', 'security', false ) ){
			exit( json_encode(array('meta_values' => array())) );
		}

		global $wpdb;

		$meta_key = sanitize_text_field( wp_unslash( $_POST['key'] ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$r = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT postmeta.meta_value FROM {$wpdb->postmeta} as postmeta WHERE postmeta.meta_key = %s LIMIT 0,10", $meta_key), ARRAY_A);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$meta_values = array();
		
		if ( ! empty($r) ){
			foreach ($r as $key => $value) { if (empty($value['meta_value'])) continue;
				$meta_values[] = esc_html($value['meta_value']);
			}
		}

		exit( json_encode(array('meta_values' => $meta_values)) );
	}

	/**
	 * upload.php
	 *
	 * Copyright 2009, Moxiecode Systems AB
	 * Released under GPL License.
	 *
	 * License: http://www.plupload.com/license
	 * Contributing: http://www.plupload.com/contributing
	 */
	public function upload(){			

		if ( ! check_ajax_referer( 'wp_all_import_secure', '_wpnonce', false )){
			exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 100, "message" => __('Security check', 'wp-all-import')), "id" => "id")));
		}
		
		// HTTP headers for no cache etc
		// header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		// header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		// header("Cache-Control: no-store, no-cache, must-revalidate");
		// header("Cache-Control: post-check=0, pre-check=0", false);
		// header("Pragma: no-cache");

		// Settings
		//$targetDir = ini_get("upload_tmp_dir") . DIRECTORY_SEPARATOR . "plupload";
		//$uploads = wp_upload_dir();	

		$targetDir = self::$path;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		if (! is_dir($targetDir) || ! is_writable($targetDir)){
			delete_transient( self::$upload_transient );
			exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 100, "message" => __("Uploads folder is not writable.", "wp-all-import")), "id" => "id")));
		}

		$cleanupTargetDir = true; // Remove old files
		$maxFileAge = 5 * 3600; // Temp file age in seconds

		// 5 minutes execution time
		// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		@set_time_limit(5 * 60);	

		// Uncomment this one to fake upload time
		// usleep(5000);

		// Get parameters
		$chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
		$chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
		$fileName = isset($_REQUEST["name"]) ? sanitize_file_name(wp_unslash($_REQUEST["name"])) : '';

		// Clean the fileName for security reasons
		$fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

		if ( ! preg_match('%\W(xml|gzip|zip|csv|tsv|gz|json|txt|dat|psv|sql|xls|xlsx)$%i', trim(basename($fileName)))) {
			exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 100, "message" => __("Uploaded file must be XML, CSV, ZIP, GZIP, GZ, JSON, SQL, TXT, DAT or PSV", "wp-all-import")), "id" => "id")));
		}

		// Make sure the fileName is unique but only if chunking is disabled
		if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
			$ext = strrpos($fileName, '.');
			$fileName_a = substr($fileName, 0, $ext);
			$fileName_b = substr($fileName, $ext);

			$count = 1;
			while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
				$count++;

			$fileName = $fileName_a . '_' . $count . $fileName_b;
		}

		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

		// Create target dir
		if (!file_exists($targetDir))
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			@mkdir($targetDir);

		// Remove old temp files	
		if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
			while (($file = readdir($dir)) !== false) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

				// Remove temp file if it is older than the max age and is not the current file
				if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
					wp_delete_file($tmpfilePath);
				}
			}

			closedir($dir);
		} else{
			delete_transient( self::$upload_transient );
			exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 100, "message" => __("Failed to open temp directory.", "wp-all-import")), "id" => "id")));
		}
			

		// Look for the content type header
		$contentType = '';
		if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
			$contentType = sanitize_text_field(wp_unslash($_SERVER["HTTP_CONTENT_TYPE"]));

		if (isset($_SERVER["CONTENT_TYPE"]))
			$contentType = sanitize_text_field(wp_unslash($_SERVER["CONTENT_TYPE"]));

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
		if (strpos($contentType, "multipart") !== false) {
			if (isset($_FILES['async-upload']['tmp_name']) && is_uploaded_file(realpath($_FILES['async-upload']['tmp_name']))) {
				// Open temp file
				$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
				if ($out) {
					// Read binary input stream and append it to temp file
					$in = fopen(realpath($_FILES['async-upload']['tmp_name']), "rb");

					if ($in) {
						while ($buff = fread($in, 4096))
							fwrite($out, $buff);
					} else{
						delete_transient( self::$upload_transient );
						exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 101, "message" => __("Failed to open input stream.", "wp-all-import")), "id" => "id")));
					}
					fclose($in);
					fclose($out);
					wp_delete_file(realpath($_FILES['async-upload']['tmp_name']));
				} else{
					delete_transient( self::$upload_transient );
					exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 102, "message" => __("Failed to open output stream.", "wp-all-import")), "id" => "id")));
				}
			} else{
				delete_transient( self::$upload_transient );
				exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 103, "message" => __("Failed to move uploaded file.", "wp-all-import")), "id" => "id")));
			}
		} else {
			// Open temp file
			$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
			if ($out) {
				// Read binary input stream and append it to temp file
				$in = fopen("php://input", "rb");

				if ($in) {
					while ($buff = fread($in, 4096))
						fwrite($out, $buff);
				} else{
					delete_transient( self::$upload_transient );
					exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 101, "message" => __("Failed to open input stream.", "wp-all-import")), "id" => "id")));
				}

				fclose($in);
				fclose($out);
			} else{
				delete_transient( self::$upload_transient );
				exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 102, "message" => __("Failed to open output stream.", "wp-all-import")), "id" => "id")));
			}
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		
		$post_type = false;		

		$notice = false;

		$warning = false;

		// Check if file has been uploaded
		if (!$chunks || $chunk == $chunks - 1) {
			// Strip the temp .part suffix off
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
			$res = rename("{$filePath}.part", $filePath);
			if (!$res){
				@copy("{$filePath}.part", $filePath);
				wp_delete_file("{$filePath}.part");
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			chmod($filePath, 0755);
			delete_transient( self::$upload_transient );

			$errors = new WP_Error;

			// Check if alternative Excel processing is requested
			if (!empty($_POST['use_alternative_excel']) && $_POST['use_alternative_excel'] === '1') {
				global $wp_all_import_force_alternative_excel;
				$wp_all_import_force_alternative_excel = true;

				// Store in session for later use when import is created
				if (!empty(PMXI_Plugin::$session)) {
					PMXI_Plugin::$session->set('use_alternative_excel_processing', true);
					PMXI_Plugin::$session->save_data();
				}
			}

			$parse_options = PMXI_ParseOptions::for_import_id($this->input->get('id', 0));
			$uploader = new PMXI_Upload($filePath, $errors, rtrim(str_replace(basename($filePath), '', $filePath), '/'), null === $parse_options ? PMXI_ParseOptions::for_new_import() : $parse_options);

			$upload_result = $uploader->upload();
			
			if ($upload_result instanceof WP_Error){
				$errors = $upload_result;

				$msgs = $errors->get_error_messages();
				ob_start();
				?>
				<?php foreach ($msgs as $msg): ?>
					<p><?php echo wp_kses_post($msg); ?></p>
				<?php endforeach ?>
				<?php
				$response = ob_get_clean();

				exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 102, "message" => $response), "id" => "id")));
			}
			else 
			{				
				if ( ! empty($upload_result['post_type'])) 
				{
					$post_type = $upload_result['post_type'];

					if ( ! empty($upload_result['template']) )
					{																	

						$template = json_decode($upload_result['template'], true);

						if ( ! empty($template[0]['options']))
						{
							$is_show_cf_notice = ( ! empty($template[0]['options']['custom_name'])) ? true : false;

							$is_show_images_notice = false;

							if ( $post_type != 'product' && (
								isset($template[0]['options']['download_featured_image']) && $template[0]['options']['download_featured_image'] != '' || 
								isset($template[0]['options']['gallery_featured_image']) && $template[0]['options']['gallery_featured_image'] != '' || 
								isset($template[0]['options']['featured_image']) && $template[0]['options']['featured_image'] != ''))
							{
								$is_show_images_notice = true;
							}

							if ( $is_show_cf_notice && $is_show_images_notice ){
								$warning = __('<a class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=images">Upgrade to the Pro edition of WP All Import to Import Images and Custom Fields</a> <p>If you already own it, remove the free edition and install the Pro edition.</p>', 'wp-all-import');
							}
							else if ( $is_show_cf_notice ){
								$warning = __('<a class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=custom-fields">Upgrade to the Pro edition of WP All Import to Import Custom Fields</a> <p>If you already own it, remove the free edition and install the Pro edition.</p>', 'wp-all-import');
							}
							else if ( $is_show_images_notice ) {		
								$warning = __('<a class="upgrade_link" target="_blank" href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839966&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-99&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=images">Upgrade to the Pro edition of WP All Import to Import Images</a> <p>If you already own it, remove the free edition and install the Pro edition.</p>', 'wp-all-import');
							}
						}						
					}					

					switch ( $post_type ) {
						
						case 'shop_order':
							
							if ( ! class_exists('WooCommerce') ) {
								$notice = __('<p class="wpallimport-bundle-notice">The import bundle you are using requires WooCommerce.</p><a class="upgrade_link" href="https://wordpress.org/plugins/woocommerce/" target="_blank">Get WooCommerce</a>', 'wp-all-import');							
							}
							else {

								if ( ! defined('PMWI_EDITION') ) {

									$notice = __('<p class="wpallimport-bundle-notice">The import bundle you are using requires the Pro version of the WooCommerce Add-On.</p><a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839961&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-169&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=import-wooco-bundle" class="upgrade_link" target="_blank">Purchase the WooCommerce Add-On</a>', 'wp-all-import');

								}
								elseif ( PMWI_EDITION != 'paid' ) {

									$notice = __('<p class="wpallimport-bundle-notice">The import bundle you are using requires the Pro version of the WooCommerce Add-On, but you have the free version installed.</p><a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839961&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-169&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=import-wooco-bundle" target="_blank" class="upgrade_link">Purchase the WooCommerce Add-On</a>', 'wp-all-import');

								}							
							}

							break;

						case 'import_users':

							if ( ! class_exists('PMUI_Plugin') ) {
								$notice = __('<p class="wpallimport-bundle-notice">The import bundle you are using requires the User Add-On.</p><a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839963&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-169&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=import-users" target="_blank" class="upgrade_link">Purchase the User Add-On</a>.', 'wp-all-import');
							}

							break;


						case 'shop_customer':

							if ( ! class_exists('WooCommerce') ) {
								$notice = __('<p class="wpallimport-bundle-notice">The import bundle you are using requires WooCommerce.</p><a class="upgrade_link" href="https://wordpress.org/plugins/woocommerce/" target="_blank">Get WooCommerce</a>.', 'wp-all-import');
							}
							elseif ( ! post_type_exists('shop_customer') ) {
								$notice = __('<p class="wpallimport-bundle-notice">User Import Add-On Pro is required to import WooCommerce Customers.</p><p class="wpallimport-upgrade-links-container"><a href="https://www.wpallimport.com/checkout/?edd_action=add_to_cart&download_id=5839963&edd_options%5Bprice_id%5D=1&discount=welcome-upgrade-169&utm_source=import-plugin-free&utm_medium=upgrade-notice&utm_campaign=import-customers" target="_blank" class="upgrade_link">Purchase User Import Add-On Pro</a></p>', 'wp-all-import');
							}

							break;
						
						default:
							# code...
							break;
					}					
				}				

				if ( ! empty($upload_result['is_empty_bundle_file']))
				{										
					// Return JSON-RPC response
					exit(json_encode(array("jsonrpc" => "2.0", "error" => null, "result" => null, "id" => "id", "name" => $upload_result['filePath'], "post_type" => $post_type, "notice" => $notice, "template" => $upload_result['template'], "url_bundle" => true)));
				}
				else
				{
					// $root_element = wp_all_import_get_reader_engine( array($upload_result['filePath']), array('root_element' => $upload_result['root_element']) );	

					// if ( ! empty($root_element) and empty($upload_result['root_element']))
					// {
					// 	$upload_result['root_element'] = $root_element;
					// }
					
					// validate XML
					$file = new PMXI_Chunk($upload_result['filePath'], array('element' => $upload_result['root_element']));										    					    					   												

					$is_valid = true;

					if ( ! empty($file->options['element']) ) 						
						$defaultXpath = "/". $file->options['element'];																			    		  
					else
						$is_valid = false;

					if ( $is_valid ){

						while ($xml = $file->read()) {

					    	if ( ! empty($xml) ) { 

					      		//PMXI_Import_Record::preprocessXml($xml);
					      		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . "\n" . $xml;
					    	
						      	$dom = new DOMDocument( '1.0', 'UTF-8' );
								$old = libxml_use_internal_errors(true);
								$dom->loadXML($xml);
								libxml_use_internal_errors($old);
								$xpath = new DOMXPath($dom);									
								if (($elements = $xpath->query($defaultXpath)) and $elements->length){
									break;
								}												
						    }
						    /*else {
						    	$is_valid = false;
						    	break;
						    }*/

						}

						if ( empty($xml) ) $is_valid = false;
					}

					unset($file);

					if ( ! preg_match('%\W(xml)$%i', trim($upload_result['source']['path']))) wp_delete_file($upload_result['filePath']);
					
					if ( ! $is_valid )
					{
						ob_start();					
						
						?>
						
						<div class="error inline"><p><?php echo wp_kses( __('Please confirm you are importing a valid feed.<br/> Often, feed providers distribute feeds with invalid data, improperly wrapped HTML, line breaks where they should not be, faulty character encodings, syntax errors in the XML, and other issues.<br/><br/>WP All Import has checks in place to automatically fix some of the most common problems, but we can’t catch every single one.<br/><br/>It is also possible that there is a bug in WP All Import, and the problem is not with the feed.<br/><br/>If you need assistance, please contact support – <a href="mailto:support@wpallimport.com">support@wpallimport.com</a> – with your XML/CSV file. We will identify the problem and release a bug fix if necessary.', 'wp-all-import'), array('br' => array(), 'a' => array('href' => array())) ); ?></p></div>
						
						<?php

						$response = ob_get_clean();

						$file_type = strtoupper(pmxi_getExtension($upload_result['source']['path']));

						/* translators: see placeholders in the string below */
						$error_message = sprintf(__("Please verify that the file you uploading is a valid %s file.", "wp-all-import"), esc_attr($file_type));

						exit(json_encode(array("jsonrpc" => "2.0", "error" => array("code" => 102, "message" => $error_message), "is_valid" => false, "id" => "id")));
					
					}
					else {
					    $copyFileAllowed = apply_filters('wp_all_import_copy_uploaded_file_into_files_folder', true);
					    if ($copyFileAllowed) {
						$wp_uploads = wp_upload_dir();
						$uploads    = $wp_uploads['basedir'] . DIRECTORY_SEPARATOR . PMXI_Plugin::FILES_DIRECTORY . DIRECTORY_SEPARATOR;
                            if ( ! file_exists($uploads . basename($filePath))) {
                                @copy($filePath, $uploads . basename($filePath));
                            }
					}
				}
			}		
			}
		}			

		// Return JSON-RPC response
		exit(json_encode(array("jsonrpc" => "2.0", "error" => null, "result" => null, "id" => "id", "name" => $filePath, "post_type" => $post_type, "notice" => $notice, "warning" => $warning)));

	}		

}