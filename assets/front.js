(function() {
    var _settings = {
        "privacyUrl": wputarteaucitron_settings.privacy_page ? wputarteaucitron_settings.privacy_page : '',
        "bodyPosition": "bottom",
        "hashtag": "#tarteaucitron",
        "cookieName": "tarteaucitron",
        "orientation": "bottom",
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

    /* Init script */
    tarteaucitron.init(_settings);

    /* GTM */
    if (wputarteaucitron_settings.gtm_id) {
        tarteaucitron.user.googletagmanagerId = wputarteaucitron_settings.gtm_id;
        (tarteaucitron.job = tarteaucitron.job || []).push('googletagmanager');
    }
}());
