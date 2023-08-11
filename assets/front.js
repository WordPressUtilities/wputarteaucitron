(function() {

    'use strict';

    /* ----------------------------------------------------------
      Settings
    ---------------------------------------------------------- */

    var _settings = {
        "privacyUrl": wputarteaucitron_settings.privacy_page ? wputarteaucitron_settings.privacy_page : '',
        "orientation": wputarteaucitron_settings.banner_orientation ? wputarteaucitron_settings.banner_orientation : 'bottom',
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

    /* GTM */
    if (wputarteaucitron_settings.gtm_id) {
        tarteaucitron.user.googletagmanagerId = wputarteaucitron_settings.gtm_id;
        tarteaucitron.job.push('googletagmanager');
    }

    /* GA 4 */
    if (wputarteaucitron_settings.ga4_id) {
        tarteaucitron.user.gtagUa = wputarteaucitron_settings.ga4_id;
        tarteaucitron.job.push('gtag');
    }

    /* Facebook Pixel */
    if (wputarteaucitron_settings.fbpix_id) {
        tarteaucitron.user.facebookpixelId = wputarteaucitron_settings.fbpix_id;
        tarteaucitron.job.push('facebookpixel');
    }
}());
