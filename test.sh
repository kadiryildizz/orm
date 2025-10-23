#!/bin/bash

PHP_CONTAINER="php"

echo "➡ Entering PHP container..."
docker compose exec $PHP_CONTAINER bash -c "vendor/bin/phpunit"

echo "✅ Tests finished."
