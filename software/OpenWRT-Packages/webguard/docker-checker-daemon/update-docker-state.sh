#!/bin/sh

# update-docker-state.sh

# Check if jq is installed
if ! command -v jq >/dev/null 2>&1; then
    echo "Installing jq..."
    opkg update
    opkg install jq
fi

# Function to show usage
usage() {
    echo "Usage: $0 <docker_name> <state>"
    echo "  docker_name: The Docker container name (e.g., 'web-rated-g-e2gns')"
    echo "  state: Either 'start' or 'stop'"
    exit 1
}

# Check arguments
if [ $# -ne 2 ]; then
    usage
fi

DOCKER_NAME="$1"
STATE="$2"

# Validate state
case "$STATE" in
    start|stop) ;;
    *)
        echo "Error: State must be either 'start' or 'stop'"
        usage
        ;;
esac

# Path to JSON file
JSON_FILE="/usr/bin/web-rated-docker.json"

# Check if file exists
if [ ! -f "$JSON_FILE" ]; then
    echo "Error: JSON file not found at $JSON_FILE"
    exit 1
fi

# Create temporary file
TMP_FILE=$(mktemp)

# Update the JSON file
jq --arg docker_name "$DOCKER_NAME" --arg state "$STATE" '
    # First find the Name value for the given Docker Name
    def get_name:
        map(select(.["Docker Name"] == $docker_name) | .Name) | first;
    
    # Get the Name value
    ($docker_name | get_name) as $matching_name |
    
    # Update all entries with matching Name
    map(
        if .["Docker Name"] == $docker_name or .Name == $matching_name then
            .["Docker State"] = $state
        else
            .
        end
    )
' "$JSON_FILE" > "$TMP_FILE"

# Check if jq command was successful
if [ $? -eq 0 ]; then
    # Backup original file
    cp "$JSON_FILE" "${JSON_FILE}.bak"
    
    # Move temporary file to original
    mv "$TMP_FILE" "$JSON_FILE"
    
    echo "Successfully updated Docker state to '$STATE' for container '$DOCKER_NAME' and related entries"
else
    rm "$TMP_FILE"
    echo "Error: Failed to update JSON file"
    exit 1
fi
