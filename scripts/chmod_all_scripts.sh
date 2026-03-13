#!/bin/bash
# Check if directory exists to avoid errors
if [ -d "./scripts" ]; then
    chmod +x ./scripts/*.sh
    echo "✅ All .sh files in ./scripts are now executable."
else
    echo "❌ Directory ./scripts not found."
    exit 1
fi