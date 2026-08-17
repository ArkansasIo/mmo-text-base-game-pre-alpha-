#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
for file in modules/2train.php modules/armory.php modules/armoryold.php modules/mil_rank.php modules/personnel.php modules/untrain.php; do
  if ! grep -Fq "empty(\$_SESSION['userid'])" "$file"; then
    perl -0pi -e 's/\$s = new Game\(\);\n/\$s = new Game();\nif (!\$s->loggedIn || empty(\$_SESSION[\x27userid\x27])) { header("Location: \/index.php"); exit; }\n/' "$file"
  fi
done
