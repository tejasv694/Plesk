// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

if (window.location.pathname.indexOf('/repair') === 0 && document.getElementById('repair-kit-text')) {
    document.getElementById('repair-kit-text').innerHTML = 'Repair Kit is unavailable. To restore it, log in to the server via SSH, run the command \'systemctl restart plesk-repaird\', and then refresh this page.';
}
