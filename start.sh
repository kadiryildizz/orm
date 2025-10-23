#!/bin/bash

echo "Starting Docker containers..."
docker-compose up -d
echo "Docker started."

echo "You can now run ./install.sh to install dependencies and set up the database."
