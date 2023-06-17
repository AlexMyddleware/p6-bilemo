#!/bin/bash

# start.sh

docker-compose -f docker-compose.test.yml up --build -d

docker exec p7-bilemo-php-1 php bin/console doctrine:schema:update --force --complete
docker exec p7-bilemo-php-1 php bin/console --env=test doctrine:schema:update --force --complete
docker exec p7-bilemo-php-1 php bin/console doctrine:fixtures:load --no-interaction
docker exec p7-bilemo-php-1 php bin/console --env=test doctrine:fixtures:load --no-interaction
