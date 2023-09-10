#!/bin/bash

_SOURCEDIR="$( dirname "${BASH_SOURCE[0]}" )/";

# Thanks to https://gist.github.com/lukechilds/a83e1d7127b78fef38c2914c4ececc3c
update_wputarto_get_latest_release() {
  curl --silent "https://api.github.com/repos/$1/releases/latest" |
    grep '"tag_name":' |
    sed -E 's/.*"([^"]+)".*/\1/'
}

# Delete old path
rm -rf "${_SOURCEDIR}/assets/tarteaucitron";

# Clone the new
git clone --depth 1 --branch $(update_wputarto_get_latest_release "AmauriC/tarteaucitron.js") https://github.com/AmauriC/tarteaucitron.js.git "${_SOURCEDIR}/assets/tarteaucitron";
rm -rf "${_SOURCEDIR}/assets/tarteaucitron/.git";

