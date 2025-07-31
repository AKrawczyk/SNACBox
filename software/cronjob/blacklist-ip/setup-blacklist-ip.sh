#!/bin/sh

# Define source and destination directories
DEST_DIR="/etc/blacklist-ip"

# Create the destination directory if it doesn't exist
if [ ! -d "$DEST_DIR" ]; then
    mkdir -p "$DEST_DIR"
    echo "Created directory $DEST_DIR"
else
    echo "Directory $DEST_DIR already exists."
fi

# Copy the contents of the blacklist-ip folder to /etc/blacklist-ip/
cp -r *.php "$DEST_DIR/"
echo "Copied contents to $DEST_DIR"

# Create a cron job to run the PHP script daily at 6 AM
CRON_JOB="0 6 * * * php8-cli $DEST_DIR/blacklist-ip.php"

# Check if the cron job already exists
if ! crontab -l | grep -q "$CRON_JOB"; then
    (crontab -l; echo "$CRON_JOB") | crontab -
    echo "Cron job added: $CRON_JOB"
else
    echo "Cron job already exists: $CRON_JOB"
fi

echo "Setup completed."