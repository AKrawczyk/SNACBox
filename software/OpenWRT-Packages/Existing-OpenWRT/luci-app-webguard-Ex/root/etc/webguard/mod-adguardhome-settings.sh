#!/bin/sh

set -eu

CONFIG_FILE=${1:-/etc/adguardhome/adguardhome.yaml}
SOURCE_FILE=${2:-/etc/webguard/adguardhome.wbg}

if [ ! -f "$CONFIG_FILE" ]; then
    echo "AdGuardHome configuration not found: $CONFIG_FILE" >&2
    exit 1
fi

if [ ! -f "$SOURCE_FILE" ]; then
    echo "Client configuration source not found: $SOURCE_FILE" >&2
    exit 1
fi

# Check if persistent: exists in the configuration file
if awk '
    /^[[:space:]]*persistent:[[:space:]]*(\[\]|#.*)?$/ { found = 1; exit }
    END { exit !found }
' "$CONFIG_FILE"; then
    CONFIG_HAS_PERSISTENT=1
else
    CONFIG_HAS_PERSISTENT=0
fi

TEMP_FILE=$(mktemp "${CONFIG_FILE}.tmp.XXXXXX")
trap 'rm -f "$TEMP_FILE"' EXIT HUP INT TERM
cp -p "$CONFIG_FILE" "$TEMP_FILE"

if [ "$CONFIG_HAS_PERSISTENT" -eq 1 ]; then
    if ! awk -v source="$SOURCE_FILE" '
        function emit_source(line) {
            while ((getline line < source) > 0)
                print line
            close(source)
        }

        # Match persistent: [] and strip the brackets while preserving indentation
        /^[[:space:]]*persistent:[[:space:]]*\[\]$/ && !inserted {
            sub(/[[:space:]]*\[\]$/, "", $0)
            print $0
            emit_source()
            inserted = 1
            next
        }

        # Match regular persistent: lines
        /^[[:space:]]*persistent:[[:space:]]*(#.*)?$/ && !inserted {
            print $0
            emit_source()
            inserted = 1
            next
        }

        { print }
    ' "$CONFIG_FILE" > "$TEMP_FILE"; then
        echo "Failed to update AdGuardHome persistent configuration." >&2
        exit 1
    fi
    UPDATE_MESSAGE="Updated persistent configuration in $CONFIG_FILE"
fi

mv "$TEMP_FILE" "$CONFIG_FILE"
trap - EXIT HUP INT TERM
echo "$UPDATE_MESSAGE"