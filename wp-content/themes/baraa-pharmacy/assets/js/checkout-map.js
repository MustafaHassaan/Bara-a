/**
 * Baraa Pharmacy — Checkout delivery map picker (Leaflet + OpenStreetMap).
 * Lets the customer pin the exact delivery location on a map, search for an
 * area, or use the device's current position. Coordinates are written to the
 * hidden inputs and saved on the order by functions.php. No coordinates are
 * set until the customer actually picks a spot, so checkout validation can
 * ask for a location.
 */
(function () {
  "use strict";

  var NOMINATIM = "https://nominatim.openstreetmap.org";

  function t(key, fallback) {
    var cfg = window.baraaMap || {};
    var strings = cfg.strings || {};
    return strings[key] || fallback;
  }

  function init() {
    var mapEl = document.getElementById("baraa-map");
    if (!mapEl || typeof L === "undefined") {
      return;
    }

    var cfg = window.baraaMap || {};
    var defaultCenter = cfg.defaultCenter || [31.2001, 29.9187];

    var latInput = document.getElementById("baraa_map_lat");
    var lngInput = document.getElementById("baraa_map_lng");
    var addressInput = document.getElementById("baraa_map_address");
    var statusEl = document.getElementById("baraa-map-status");
    var searchInput = document.getElementById("baraa-map-search-input");
    var searchBtn = document.getElementById("baraa-map-search-btn");
    var locateBtn = document.getElementById("baraa-map-locate-btn");

    var hasStart = latInput && latInput.value && lngInput && lngInput.value;
    var start = hasStart
      ? [parseFloat(latInput.value), parseFloat(lngInput.value)]
      : defaultCenter;

    var map = L.map(mapEl, { scrollWheelZoom: false }).setView(
      start,
      hasStart ? 16 : 12,
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap",
    }).addTo(map);

    var marker = null;
    var geocodeTimer = null;

    // Leaflet needs a nudge when its container is laid out after init.
    setTimeout(function () {
      map.invalidateSize();
    }, 300);
    window.addEventListener("resize", function () {
      map.invalidateSize();
    });

    function setStatus(text) {
      if (statusEl) {
        statusEl.textContent = text || "";
      }
    }

    function setCoords(latlng) {
      if (latInput) {
        latInput.value = latlng.lat.toFixed(6);
      }
      if (lngInput) {
        lngInput.value = latlng.lng.toFixed(6);
      }
    }

    function reverseGeocode(latlng) {
      var url =
        NOMINATIM +
        "/reverse?format=jsonv2&accept-language=ar&lat=" +
        encodeURIComponent(latlng.lat) +
        "&lon=" +
        encodeURIComponent(latlng.lng);

      fetch(url, { headers: { Accept: "application/json" } })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (data && data.display_name && addressInput) {
            addressInput.value = data.display_name;
          }
          setStatus("");
        })
        .catch(function () {
          setStatus(t("reverseError", ""));
        });
    }

    function place(latlng) {
      if (!marker) {
        marker = L.marker(latlng, { draggable: true }).addTo(map);
        marker.on("dragend", function () {
          place(marker.getLatLng());
        });
      } else {
        marker.setLatLng(latlng);
      }

      setCoords(latlng);
      clearTimeout(geocodeTimer);
      geocodeTimer = setTimeout(function () {
        reverseGeocode(latlng);
      }, 500);
    }

    // Restore a previously pinned location (e.g. failed checkout attempt).
    if (hasStart) {
      place({ lat: start[0], lng: start[1] });
    }

    map.on("click", function (e) {
      place(e.latlng);
    });

    if (locateBtn) {
      locateBtn.addEventListener("click", function () {
        if (!navigator.geolocation) {
          setStatus(t("geoError", ""));
          return;
        }
        setStatus(t("locating", ""));
        navigator.geolocation.getCurrentPosition(
          function (pos) {
            var latlng = {
              lat: pos.coords.latitude,
              lng: pos.coords.longitude,
            };
            map.setView(latlng, 16);
            place(latlng);
            setStatus(t("located", ""));
          },
          function () {
            setStatus(t("geoError", ""));
          },
          { enableHighAccuracy: true, timeout: 10000 },
        );
      });
    }

    function runSearch() {
      var query = searchInput ? searchInput.value.trim() : "";
      if (!query) {
        setStatus(t("searchEmpty", ""));
        return;
      }

      var url =
        NOMINATIM +
        "/search?format=jsonv2&accept-language=ar&countrycodes=eg&limit=1&q=" +
        encodeURIComponent(query);

      setStatus(t("searching", ""));
      fetch(url, { headers: { Accept: "application/json" } })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (data && data.length) {
            var latlng = {
              lat: parseFloat(data[0].lat),
              lng: parseFloat(data[0].lon),
            };
            map.setView(latlng, 16);
            place(latlng);
            setStatus("");
          } else {
            setStatus(t("searchNone", ""));
          }
        })
        .catch(function () {
          setStatus(t("searchNone", ""));
        });
    }

    if (searchBtn) {
      searchBtn.addEventListener("click", runSearch);
    }
    if (searchInput) {
      searchInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          runSearch();
        }
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
