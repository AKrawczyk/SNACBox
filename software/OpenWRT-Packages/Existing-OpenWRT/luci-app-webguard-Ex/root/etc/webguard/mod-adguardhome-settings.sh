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

if awk '
	/^clients:[[:space:]]*(#.*)?$/ { in_clients = 1; next }
	in_clients && /^  persistent:[[:space:]]*(#.*)?$/ { found = 1 }
	in_clients && /^[^[:space:]#][^:]*:/ { in_clients = 0 }
	END { exit !found }
' "$SOURCE_FILE"; then
	SOURCE_HAS_CLIENT_SETTINGS=1
else
	SOURCE_HAS_CLIENT_SETTINGS=0
fi

TEMP_FILE=$(mktemp "${CONFIG_FILE}.tmp.XXXXXX")
trap 'rm -f "$TEMP_FILE"' EXIT HUP INT TERM
cp -p "$CONFIG_FILE" "$TEMP_FILE"

if [ "$SOURCE_HAS_CLIENT_SETTINGS" -eq 0 ]; then
	if ! awk -v source="$SOURCE_FILE" '
		function emit_source(line) {
			while ((getline line < source) > 0)
				print line
			close(source)
		}

		/^log:[[:space:]]*(#.*)?$/ && !inserted {
			emit_source()
			print ""
			inserted = 1
		}
		{ print }

		END {
			if (!inserted) {
				if (NR > 0)
					print ""
				emit_source()
			}
		}
	' "$CONFIG_FILE" > "$TEMP_FILE"; then
		echo "Failed to copy source configuration into AdGuardHome YAML." >&2
		exit 1
	fi
	UPDATE_MESSAGE="Copied source configuration before log in $CONFIG_FILE"
else
	if ! awk -v source="$SOURCE_FILE" '
	function emit_source(line) {
		while ((getline line < source) > 0)
			print line
		close(source)
	}

	/^persistent:[[:space:]]*\[\]$/ {
		print "persistent:"
		emit_source()
		next
	}

	/^persistent:[[:space:]]*(#.*)?$/ {
		print $0
		emit_source()
		next
	}

	{ print }
' "$CONFIG_FILE" > "$TEMP_FILE"; then
		echo "Failed to update AdGuardHome clients configuration." >&2
		exit 1
	fi
	UPDATE_MESSAGE="Updated persistent configuration in $CONFIG_FILE"
fi

mv "$TEMP_FILE" "$CONFIG_FILE"
trap - EXIT HUP INT TERM
echo "$UPDATE_MESSAGE"