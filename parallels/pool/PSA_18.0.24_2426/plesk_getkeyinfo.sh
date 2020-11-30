#!/bin/sh
### Copyright 1999-2017. Plesk International GmbH. All rights reserved.

set -e

target_version=$1

psa_prefix=
for t in /opt/psa /usr/local/psa; do
    if [ -x "$t/admin/bin/php" ]; then
        psa_prefix="$t"
        break
    fi
done

if [ -z "$psa_prefix" ]; then
    echo "cannot find Plesk PHP engine"
    exit 1
fi


PATH=$PATH:/usr/bin:
admphp_ini=${psa_prefix}/admin/conf/php.ini

eval "`sw-engine -c "${admphp_ini}" -r '
    foreach (array("plesk-unified", "plesk-unix") as $prod) {
        $key = of_get_key_by_product($prod);
        if ($key !== false) {
            break;
        }
    }

    $url = $key ? of_find_properties($key, SWKEY_CORE_NS, "license-server-url") : null;
    $url = $url ? $url[0] : "https://ka.plesk.com/";

    $key_id = $key ? str_replace(".", "", of_get_id($key)) : "plsk000000000000";

    $key_pwd = "";
    if ($key_id != "plsk000000000000") {
        foreach(array(SWKEY_CORE_NS, "http://swsoft.com/schemas/keys/core/2") as $core_ns) {
            $res = of_find_properties($key, $core_ns, "update-ticket");
            if ($res) {
                $key_pwd = $res[0];
                break;
            }
        }
    }

    echo "prod=$prod\n";
    echo "url=$url\n";
    echo "key_id=$key_id\n";
    echo "key_pwd=$key_pwd\n";
'`"

skip_key_upgrade="check_key_upgrade"

if [ -n "$target_version" -a -n "$prod" ]; then
    skip_key_upgrade="`sw-engine -c "${admphp_ini}" -r '
        $targetVersion="'$target_version'";
        $key = of_get_key_by_product("'$prod'");
        $vers = of_get_versions($key); /* plesk >= 10.0.0 */
        if (!is_array($vers))
            $vers = array($vers);
        $match = false;
        foreach ($vers as $ver) {
            if (!is_array($ver)) {
                $match |= strtok($ver, ".") == strtok($targetVersion, ".");
            } else {
                $match |= ("any" == $ver[0] || version_compare($ver[0], $targetVersion) <= 0) &&
                          ("any" == $ver[1] || version_compare($ver[1], $targetVersion) >= 0);
            }
        }
        echo $match ? "skip_key_upgrade" : "check_key_upgrade";
    '`"
fi

echo "Key Info v1.0"
echo "PleskLogin"
echo "PleskPassword"
echo "$url"
echo "$key_id"
echo "$key_pwd"
echo "$skip_key_upgrade"
