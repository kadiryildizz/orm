# install.sh
#!/bin/bash

CONTAINER_NAME=php

echo "Installing PHP dependencies..."
docker-compose exec $CONTAINER_NAME composer install

echo "Dumping autoload..."
docker-compose exec $CONTAINER_NAME composer dump-autoload

echo "Running database installation..."
docker-compose exec $CONTAINER_NAME php install.php

echo "Application setup finished successfully!"
