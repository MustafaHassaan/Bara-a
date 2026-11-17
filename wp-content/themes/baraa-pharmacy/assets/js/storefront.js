/* Baraa Pharmacy — storefront interactions */
(function () {
  "use strict";

  /* ── Light mode enforced (No dark mode) ─────────────────────── */
  var html = document.documentElement;
  html.classList.remove("dark");
  if (document.cookie.indexOf("baraa_theme=dark") !== -1) {
    document.cookie =
      "baraa_theme=light; path=/; max-age=31536000; SameSite=Lax";
  }

  /* ── Cart quantity stepper (used on product page) ────────── */
  document.addEventListener("click", function (e) {
    var btn =
      e.target && e.target.closest
        ? e.target.closest("[data-quantity-step]")
        : null;

    if (!btn || btn.disabled) return;

    var container = btn.closest(".baraa-qty-stepper, .cart, form.cart");
    var input = container
      ? container.querySelector('input.qty, input[name*="quantity"]')
      : null;
    if (!input) return;

    var step = Number(input.step) || 1;
    var current = Number(input.value) || 1;
    var min = Number(input.min) || 1;
    var max = Number(input.max);
    var delta = Number(btn.getAttribute("data-quantity-step")) * step;
    var next = Math.max(min, current + delta);
    if (max > 0) next = Math.min(max, next);
    if (next === current) return;

    input.value = String(next);
    input.dispatchEvent(new Event("change", { bubbles: true }));
  });

  /* ── «الشحن إلى عنوان مختلف؟» — empty the delivery address ── */
  var shipElsewhere = document.getElementById(
    "baraa-ship-to-different-address-checkbox",
  );

  if (shipElsewhere) {
    shipElsewhere.addEventListener("change", function () {
      if (!shipElsewhere.checked) return;

      [
        "routew_address_details",
        "routew_landmark",
        "routew_delivery_instructions",
      ].forEach(function (id) {
        var field = document.getElementById(id);
        if (field) field.value = "";
      });
    });
  }
})();
