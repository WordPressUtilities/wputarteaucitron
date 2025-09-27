#!/bin/bash

# Thanks to https://gist.github.com/lukechilds/a83e1d7127b78fef38c2914c4ececc3c
update_wputarto_get_latest_release() {
    curl --silent "https://api.github.com/repos/$1/releases/latest" |
    grep '"tag_name":' |
    sed -E 's/.*"([^"]+)".*/\1/'
}


function update_wputarto(){
    local _SOURCEDIR="$( dirname "${BASH_SOURCE[0]}" )/../";

    # Getting current version
    local _current_version=$(grep '"version": "' "${_SOURCEDIR}assets/tarteaucitron/package.json" | sed -E 's/.*"([^"]+)".*/\1/');

    # Getting latest release from GitHub API
    local _latest_release=$(update_wputarto_get_latest_release "AmauriC/tarteaucitron.js");

    # If latest release is the same as current, exit
    if [[ "${_latest_release#v}" == "${_current_version}" ]];then
        echo "# Tarteaucitron.js is already at the latest version. (${_latest_release})";
        return 0;
    fi;


    # Delete old path
    mv "${_SOURCEDIR}assets/tarteaucitron" "${_SOURCEDIR}assets/tarteaucitron_old";


    # Clone the new
    git clone --depth 1 --quiet --branch "${_latest_release}" https://github.com/AmauriC/tarteaucitron.js.git "${_SOURCEDIR}assets/tarteaucitron" &> /dev/null;
    rm -rf "${_SOURCEDIR}assets/tarteaucitron/.git";

    if [[ -f "${_SOURCEDIR}assets/tarteaucitron/tarteaucitron.js" ]];then
        rm -rf "${_SOURCEDIR}assets/tarteaucitron_old";
        echo "# Tarteaucitron.js has been updated from version ${_current_version} to ${_latest_release#v}.";
        sed -i '' "s/private \$tarteaucitron_version = '.*';/private \$tarteaucitron_version = '${_latest_release#v}';/" "${_SOURCEDIR}wputarteaucitron.php"

    else
        rm -rf "${_SOURCEDIR}assets/tarteaucitron";
        mv "${_SOURCEDIR}assets/tarteaucitron_old" "${_SOURCEDIR}assets/tarteaucitron";
        echo "# Tarteaucitron.js could not be updated."
    fi;
}

update_wputarto;



