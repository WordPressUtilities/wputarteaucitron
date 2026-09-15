# WPU Tarte Au Citron

Simple WordPress plugin to implement [https://github.com/AmauriC/tarteaucitron.js](Tarteaucitron.js).

This plugin is NOT AFFILIATED WITH Tarteaucitron.js.


## How to

### Create a Button to accept a service

```html
<button data-wputarteaucitron-allow-service="pardot" type="button">Allow cookies from Pardot</button>
```

### Show/hide a block based on a service

```css
/* Hide this block if pardot is accepted */
[data-wputarteaucitron-service-pardot="1"] .my-block {
    display: none;
}
```

### Add a new service

```php
add_filter('wputarteaucitron__services', function ($services) {
    /* array key should match tarteaucitron.job.push('array_key'); */
    $services['twitteruwt'] = [
        /* Name */
        'label' => 'Twitter Pixel',
        /* Used to store the value in database */
        'setting_key' => 'twitter_pixel_id',
        /* Matches the tarteaucitron.user.user_key */
        'user_key' => 'twitteruwtId',
        /* A value to help identify the service key */
        'example' => '123456'
    ];
    return $services;
}, 10, 1);
```

### Add a service unknown to tarteaucitron.js

Add a `custom_service` key containing the raw JS definition : it will be loaded before the banner init.

```php
add_filter('wputarteaucitron__services', function ($services) {
    $services['openaiads'] = [
        'label' => 'OpenAI Ads',
        'setting_key' => 'openaiads_id',
        'user_key' => 'openaiadsId',
        'example' => 'mycustompixelid',
        'custom_service' => '{
            "key": "openaiads",
            "type": "ads",
            "name": "OpenAI Ads",
            "uri": "https://openai.com/policies/privacy-policy/",
            "readmoreLink": "https://openai.com/policies/privacy-policy/",
            "needConsent": true,
            "cookies": [],
            "js": function() {
                if (tarteaucitron.user.openaiadsId === undefined || window.oaiq) {
                    return;
                }
                var q = function() {
                    q.q.push(arguments);
                };
                q.q = [];
                window.oaiq = q;
                tarteaucitron.addScript("https://bzrcdn.openai.com/sdk/oaiq.min.js");
                q("init", {pixelId: tarteaucitron.user.openaiadsId});
            }
        }'
    ];
    return $services;
}, 10, 1);
```
