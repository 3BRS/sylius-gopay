#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# project root
cd "$(dirname "$DIR")"

set -x
# error_reporting 24575 = E_ALL^E_DEPRECATED
APP_ENV="test" php -d memory_limit=1G -d error_reporting=24575 vendor/bin/behat "$@"
