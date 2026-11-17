/**
 * Settings screen behavior: unsaved-changes warning, copy-to-clipboard, and confirmation
 * for destructive actions.
 */
(function ($) {
	'use strict';

	var L10N = window.wpaiSettingsL10n || {};

	function t(key, fallback) {
		return typeof L10N[key] === 'string' ? L10N[key] : fallback;
	}

	/* ------------------------------------------------------------------ *
	 * Unsaved-changes guard
	 *
	 * Each form saves with an ordinary button at the end of its panel. This
	 * only warns before leaving the page with edits on it.
	 * ------------------------------------------------------------------ */

	function serialize($form) {
		// Skip the nonce; it is not a user edit.
		return $form
			.find('input, select, textarea')
			.filter(function () {
				return this.name && !/^_wpnonce|^_wp_http_referer$/.test(this.name);
			})
			.map(function () {
				if (this.type === 'checkbox' || this.type === 'radio') {
					return this.name + '=' + (this.checked ? '1' : '0');
				}
				return this.name + '=' + this.value;
			})
			.get()
			.join('&');
	}

	function initUnsavedGuard($form) {
		var $save = $form.find('[data-wpai-save]');

		if (!$save.length) {
			return;
		}

		var clean = serialize($form);
		var submitting = false;

		$form.on('submit', function () {
			submitting = true;
			$save.prop('disabled', true).text(t('saving', 'Saving…'));
		});

		$(window).on('beforeunload', function () {
			if (!submitting && serialize($form) !== clean) {
				return t('leaveWarning', 'You have unsaved changes that will be lost.');
			}
		});
	}

	/* ------------------------------------------------------------------ *
	 * Copy to clipboard
	 * ------------------------------------------------------------------ */

	function copyValue(value) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(value);
		}

		// Plain-HTTP local installs don't get the async clipboard API.
		return new Promise(function (resolve, reject) {
			var previouslyFocused = document.activeElement;
			var scratch = document.createElement('textarea');
			scratch.value = value;
			scratch.setAttribute('readonly', '');
			scratch.style.position = 'fixed';
			scratch.style.opacity = '0';
			document.body.appendChild(scratch);
			scratch.select();
			var ok = false;
			try {
				ok = document.execCommand('copy');
			} catch (e) {
				ok = false;
			}
			document.body.removeChild(scratch);

			// Removing the scratch node drops focus to <body>; the next Tab would restart
			// at "Skip to main content". Put it back where the user left it.
			if (previouslyFocused && previouslyFocused.isConnected && typeof previouslyFocused.focus === 'function') {
				previouslyFocused.focus();
			}

			ok ? resolve() : reject();
		});
	}

	$(document).on('click', '.wpai-set-copy', function () {
		var $btn = $(this);
		var field = document.getElementById($btn.data('target'));
		if (!field) {
			return;
		}

		var $label   = $btn.find('[data-copy-label]');
		var $target  = $label.length ? $label : $btn;
		var original = $btn.data('label') || $target.text();
		$btn.data('label', original);

		var text = ('value' in field && typeof field.value === 'string') ? field.value : field.textContent.trim();

		copyValue(text)
			.then(function () {
				$btn.addClass('is-done');
				$target.text(t('copied', 'Copied'));
			})
			.catch(function () {
				$btn.removeClass('is-done');
				$target.text(t('copyFailed', 'Press Ctrl+C'));
				if (typeof field.select === 'function') {
					field.select();
				} else if (window.getSelection) {
					var range = document.createRange();
					range.selectNodeContents(field);
					window.getSelection().removeAllRanges();
					window.getSelection().addRange(range);
				}
			})
			.finally(function () {
				window.clearTimeout($btn.data('revertTimer'));
				$btn.data('revertTimer', window.setTimeout(function () {
					$btn.removeClass('is-done');
					$target.text(original);
				}, 2000));
			});
	});

	/* ------------------------------------------------------------------ *
	 * Confirmation for destructive actions
	 * ------------------------------------------------------------------ */

	function confirmAction(message, confirmLabel) {
		return new Promise(function (resolve) {
			if (typeof window.HTMLDialogElement === 'undefined') {
				resolve(window.confirm(message));
				return;
			}

			var dialog = document.createElement('dialog');
			dialog.className = 'wpai-set-dialog';
			var uid = 'wpai-dlg-' + Math.floor(performance.now() * 1000).toString(36);
			dialog.innerHTML =
				'<form method="dialog">' +
				'<div class="wpai-set-dialog__body">' +
				'<h2 class="wpai-set-dialog__title" id="' + uid + '-t"></h2>' +
				'<p class="wpai-set-dialog__text" id="' + uid + '-d"></p>' +
				'</div>' +
				'<div class="wpai-set-dialog__actions">' +
				'<button class="button" value="cancel" type="submit"></button>' +
				'<button class="button wpai-set-danger" value="confirm" type="submit"></button>' +
				'</div>' +
				'</form>';

			dialog.setAttribute('aria-labelledby', uid + '-t');
			dialog.setAttribute('aria-describedby', uid + '-d');
			dialog.querySelector('.wpai-set-dialog__title').textContent = confirmLabel || t('confirmTitle', 'Are you sure?');
			dialog.querySelector('.wpai-set-dialog__text').textContent = message;
			dialog.querySelector('[value="cancel"]').textContent = t('cancel', 'Cancel');
			dialog.querySelector('[value="confirm"]').textContent = confirmLabel || t('confirm', 'Confirm');

			// Must live inside .wpai-set or the design tokens don't resolve. showModal()
			// promotes it to the top layer, so no ancestor can clip it.
			(document.querySelector('.wpai-set') || document.body).appendChild(dialog);
			dialog.addEventListener('close', function () {
				var confirmed = dialog.returnValue === 'confirm';
				dialog.remove();
				resolve(confirmed);
			});
			dialog.showModal();
		});
	}

	$(document).on('click', '[data-wpai-confirm]', function (event) {
		var el = this;

		if ($(el).data('wpaiConfirmed')) {
			return;
		}

		event.preventDefault();

		confirmAction($(el).data('wpai-confirm'), $(el).data('wpai-confirm-label')).then(function (ok) {
			if (!ok) {
				return;
			}
			$(el).data('wpaiConfirmed', true);
			if (el.tagName === 'A') {
				window.location.href = el.href;
			} else {
				el.click();
			}
		});
	});

	/* ------------------------------------------------------------------ *
	 * Inline numeric validation
	 * ------------------------------------------------------------------ */

	$(document).on('blur', '.wpai-set-input--number', function () {
		$(this).toggleClass('wpai-set-input--invalid', !this.checkValidity());
	});

	$(function () {
		// A validation error renders at the top of the page while the offending field can
		// be far below it; without this a keyboard user never lands on it.
		var $errors = $('[data-wpai-errors]');
		if ($errors.length) {
			$errors.trigger('focus');
		}

		$('[data-wpai-save]').closest('form').each(function () {
			initUnsavedGuard($(this));
		});
	});
})(jQuery);
