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
