(function() {

    /* ----------------------------------------------------------
      Settings
    ---------------------------------------------------------- */

    var _settings = {
        "privacyUrl": wputarteaucitron_settings.privacy_page ? wputarteaucitron_settings.privacy_page : '',
        "orientation": wputarteaucitron_settings.banner_orientation ? wputarteaucitron_settings.banner_orientation : 'bottom',
        "bodyPosition": "bottom",
        "hashtag": "#tarteaucitron",
        "cookieName": "tarteaucitron",
        "groupServices": false,
        "serviceDefaultState": "wait",
        "showAlertSmall": false,
        "cookieslist": false,
        "showIcon": true,
        "iconPosition": "BottomRight",
        "adblocker": false,
        "DenyAllCta": false,
        "AcceptAllCta": true,
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

    /* ----------------------------------------------------------
      Init script
    ---------------------------------------------------------- */

    tarteaucitron.init(_settings);

    /* ----------------------------------------------------------
      Trackers
    ---------------------------------------------------------- */

    /* GTM */
    if (wputarteaucitron_settings.gtm_id) {
        tarteaucitron.user.googletagmanagerId = wputarteaucitron_settings.gtm_id;
        (tarteaucitron.job = tarteaucitron.job || []).push('googletagmanager');
    }

    /* GA 4 */
    if (wputarteaucitron_settings.ga4_id) {
        tarteaucitron.user.gtagUa = wputarteaucitron_settings.ga4_id;
        (tarteaucitron.job = tarteaucitron.job || []).push('gtag');
    }

    /* Facebook Pixel */
    if (wputarteaucitron_settings.fbpix_id) {
        tarteaucitron.user.facebookpixelId = wputarteaucitron_settings.fbpix_id;
        (tarteaucitron.job = tarteaucitron.job || []).push('facebookpixel');
    }
}());
