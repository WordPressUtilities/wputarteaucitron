(function() {

    'use strict';

    var _services = {
        'googletagmanager': {
            'setting_key': 'gtm_id',
            'user_key': 'googletagmanagerId'
        },
        'gtag': {
            'setting_key': 'ga4_id',
            'user_key': 'gtagUa'
        },
        'facebookpixel': {
            'setting_key': 'fbpix_id',
            'user_key': 'facebookpixelId'
        },
        'hubspot': {
            'setting_key': 'hubspot_api_key',
            'user_key': 'hubspotId'
        },
    };

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
        "showIcon": true,
        "adblocker": false,
        "DenyAllCta": wputarteaucitron_settings.deny_all_cta,
        "AcceptAllCta": wputarteaucitron_settings.accept_all_cta,
        "highPrivacy": true,
        "handleBrowserDNTRequest": false,
        "removeCredit": false,
        "moreInfoLink": true,
        "useExternalCss": false,
        "useExternalJs": false,
        "readmoreLink": "",
        "mandatory": true,
    };

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

}());

/* ----------------------------------------------------------
  Set service
---------------------------------------------------------- */

function wputarteaucitron_init_service(_id, _details) {
    'use strict';
    if (wputarteaucitron_settings[_details.setting_key]) {
        if (_details.user_key) {
            tarteaucitron.user[_details.user_key] = wputarteaucitron_settings[_details.setting_key];
        }
        tarteaucitron.job.push(_id);
    }

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

    /* When service is not enabled */
    document.addEventListener(_id + '_disallowed', function() {
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
