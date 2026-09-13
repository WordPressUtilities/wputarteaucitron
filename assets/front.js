(function() {

    'use strict';

    var _services = wputarteaucitron_settings.services;

    /* ----------------------------------------------------------
      Settings
    ---------------------------------------------------------- */

    var _settings = {
        "privacyUrl": wputarteaucitron_settings.privacy_page ? wputarteaucitron_settings.privacy_page : '',
        "orientation": wputarteaucitron_settings.banner_orientation,
        "hashtag": wputarteaucitron_settings.hashtag,
        "cookieName": wputarteaucitron_settings.cookie_name,
        "iconPosition": wputarteaucitron_settings.icon_position,
        "bodyPosition": "bottom",
        "groupServices": false,
        "serviceDefaultState": "wait",
        "showAlertSmall": false,
        "cookieslist": false,
        "showIcon": wputarteaucitron_settings.show_icon ? true : false,
        "adblocker": false,
        /* A blocking overlay without an equally easy refusal is a consent wall: force the Deny CTA */
        "DenyAllCta": (wputarteaucitron_settings.deny_all_cta || wputarteaucitron_settings.blocking_overlay) ? true : false,
        "AcceptAllCta": wputarteaucitron_settings.accept_all_cta ? true : false,
        "highPrivacy": true,
        "googleConsentMode": wputarteaucitron_settings.disable_google_consent_mode ? false : true,
        "handleBrowserDNTRequest": false,
        "removeCredit": false,
        "moreInfoLink": true,
        "useExternalCss": false,
        "useExternalJs": false,
        "readmoreLink": "",
        "mandatory": true,
    };

    window.tarteaucitronUseMin = true;

    /* Custom Icon */
    if (wputarteaucitron_settings.custom_icon) {
        _settings.iconSrc = wputarteaucitron_settings.custom_icon;
    }

    /* Custom message */
    if (wputarteaucitron_settings.banner_message) {
        window.tarteaucitronCustomText = {
            "alertBigPrivacy": wputarteaucitron_settings.banner_message
        };
    }

    /* ----------------------------------------------------------
      Init script
    ---------------------------------------------------------- */

    tarteaucitron.init(_settings);

    /* ----------------------------------------------------------
      Trackers
    ---------------------------------------------------------- */

    tarteaucitron.job = tarteaucitron.job || [];

    for (var _service in _services) {
        wputarteaucitron_init_service(_service, _services[_service]);
    }

    /* ----------------------------------------------------------
      Check if banner is visible
    ---------------------------------------------------------- */

    /* Start at 0: the banner is only really visible once tarteaucitron opens the alert */
    document.body.setAttribute('data-wputarteaucitron-banner-visible', '0');
    window.addEventListener('tac.open_alert', function() {
        document.body.setAttribute('data-wputarteaucitron-banner-visible', '1');
    });
    window.addEventListener('tac.close_alert', function() {
        document.body.setAttribute('data-wputarteaucitron-banner-visible', '0');
    });

    /* ----------------------------------------------------------
      Blocking overlay
    ---------------------------------------------------------- */

    if (wputarteaucitron_settings.blocking_overlay) {
        document.body.setAttribute('data-wputarteaucitron-blocking-overlay', '1');
    }

    /* ----------------------------------------------------------
      Watch events
    ---------------------------------------------------------- */

    document.body.addEventListener('click', function(e) {
        var target = e.target,
            _str_open_panel = 'wputarteaucitron-open-panel';

        while (target !== null && target !== document.body) {
            var key_allow = target.getAttribute('data-wputarteaucitron-allow-service');
            if (key_allow) {
                e.preventDefault();
                tarteaucitron.userInterface.respond(document.getElementById(key_allow + 'Allowed'), true);
                break;
            }
            var key_disallow = target.getAttribute('data-wputarteaucitron-disallow-service');
            if (key_disallow) {
                e.preventDefault();
                tarteaucitron.userInterface.respond(document.getElementById(key_disallow + 'Denied'), false);
                break;
            }
            var has_class_open_panel = target.classList.contains(_str_open_panel) || target.closest('.' + _str_open_panel);
            var key_open_panel = target.getAttribute('data-' + _str_open_panel);
            var href_open_panel = target.getAttribute('href') == '#' + _str_open_panel;
            if (key_open_panel || has_class_open_panel || href_open_panel) {
                e.preventDefault();
                tarteaucitron.userInterface.openPanel();
                break;
            }
            var key_close_panel = target.getAttribute('data-wputarteaucitron-close-panel');
            if (key_close_panel) {
                e.preventDefault();
                tarteaucitron.userInterface.closePanel();
                break;
            }

            target = target.parentNode;
        }
    });

}());

/* ----------------------------------------------------------
  Callback AJAX
---------------------------------------------------------- */

var wputarteaucitron_pending_statuses = false;

/* Tarteaucitron fires one event per service : group them into a single decision */
function wputarteaucitron_send_ajax_status(service, status) {
    if (wputarteaucitron_pending_statuses === false) {
        wputarteaucitron_pending_statuses = {};
        setTimeout(wputarteaucitron_flush_ajax_status, 0);
    }
    wputarteaucitron_pending_statuses[service] = status;
}

function wputarteaucitron_flush_ajax_status() {
    var statuses = wputarteaucitron_pending_statuses,
        service;
    wputarteaucitron_pending_statuses = false;
    if (!statuses) {
        return;
    }

    var form_data = new FormData();
    form_data.append('action', 'wputarteaucitron_status');
    form_data.append('_ajax_nonce', wputarteaucitron_settings.nonce);
    for (service in statuses) {
        form_data.append('services[' + service + ']', statuses[service]);
    }

    /* Beacon survives the page reload triggered when a loaded service is denied */
    if (navigator.sendBeacon && navigator.sendBeacon(wputarteaucitron_settings.ajax_url, form_data)) {
        return;
    }

    jQuery.ajax({
        url: wputarteaucitron_settings.ajax_url,
        type: 'post',
        data: form_data,
        processData: false,
        contentType: false
    });
}

/* ----------------------------------------------------------
  Set service
---------------------------------------------------------- */

function wputarteaucitron_init_service__add_user_key(_details) {
    if (_details.user_key) {
        tarteaucitron.user[_details.user_key] = wputarteaucitron_settings[_details.setting_key];
    }
}

function wputarteaucitron_init_service(_id, _details) {
    'use strict';
    if (!wputarteaucitron_settings[_details.setting_key]) {
        return;
    }
    if (_details.extra) {
        Object.keys(_details.extra).forEach(function(key) {
            wputarteaucitron_init_service__add_user_key(_details.extra[key]);
        });
    }
    wputarteaucitron_init_service__add_user_key(_details);
    tarteaucitron.job.push(_id);

    /* When service is enabled */
    function loaded_service() {
        var _iframes = document.querySelectorAll('[data-src][data-wputarteaucitron-service="' + _id + '"]');
        /* Load iframes */
        Array.prototype.forEach.call(_iframes, function(el) {
            el.setAttribute('src', el.getAttribute('data-src'));
        });
        /* Set body attr */
        document.body.setAttribute('data-wputarteaucitron-service-' + _id, '1');
    }
    document.addEventListener(_id + '_loaded', loaded_service, 1);
    document.addEventListener(_id + '_allowed', loaded_service, 1);
    document.addEventListener(_id + '_allowed', function() {
        wputarteaucitron_send_ajax_status(_id, '1');
    }, 1);

    /* When service is not enabled */
    document.addEventListener(_id + '_disallowed', function() {
        wputarteaucitron_send_ajax_status(_id, '0');
        /* Unload iframes */
        var _iframes = document.querySelectorAll('[src][data-wputarteaucitron-service="' + _id + '"]');
        Array.prototype.forEach.call(_iframes, function(el) {
            el.setAttribute('data-src', el.getAttribute('src'));
            el.removeAttribute('src');
        });
        /* Unset body attr */
        document.body.setAttribute('data-wputarteaucitron-service-' + _id, '0');
    }, 1);
}
