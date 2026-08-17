#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
for file in modules/armory.php modules/armoryold.php modules/base.php modules/fleetdock.php modules/rank.php modules/train.php modules/user.php; do
  perl -pi -e "s/!\x24_GET\['time'\]/empty(\x24_GET['time'])/g" "$file"
done
perl -pi -e "s/if \(\x24_GET\['id'\] === \x22deposit\x22 \|\| \x24_GET\['id'\] === \x22withdrawl\x22\)/if (in_array((string)(\x24_GET['id'] ?? ''), ['deposit', 'withdrawl'], true))/" modules/bank.php
perl -pi -e "s/if \(\x24_GET\['id'\] && \x24_GET\['atype'\] != \x22Send\x22\)/if (!empty(\x24_GET['id']) && (string)(\x24_GET['atype'] ?? '') != 'Send')/" modules/c_ally.php
perl -pi -e "s/if \(\x24_GET\['id'\] != \x22mainDisplay\x22\)/if ((string)(\x24_GET['id'] ?? '') != 'mainDisplay')/" modules/technology.php
perl -pi -e "s/if \(\x24_GET\['burst'\]\)/if (!empty(\x24_GET['burst']))/" process.php
