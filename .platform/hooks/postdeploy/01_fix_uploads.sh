#!/bin/bash
set -e

# Ensure uploads directory exists and is writable by the web user
if [ -d "/var/app/current/uploads" ]; then
  chown -R webapp:webapp /var/app/current/uploads || true
  chmod -R 775 /var/app/current/uploads || true
fi

exit 0
