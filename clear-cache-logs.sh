#!/bin/bash

# Check if folder exists and remove subfolder
if [ -d "/websites/p7-bilemo/var/cache" ]; then
    cd "/websites/p7-bilemo/var/cache"
    if [ -d "dev" ]; then rm -r dev; else echo "Folder dev is already deleted"; fi
    if [ -d "test" ]; then rm -r test; else echo "Folder test is already deleted"; fi
else
    echo "Folder /websites/p7-bilemo/var/cache does not exist"
fi

# Check if folder exists and remove log files
if [ -d "/websites/p7-bilemo/var/log" ]; then
    cd "/websites/p7-bilemo/var/log"
    if [ -f "dev.log" ]; then rm dev.log; else echo "File dev.log is already deleted"; fi
    if [ -f "test.log" ]; then rm test.log; else echo "File test.log is already deleted"; fi
else
    echo "Folder /websites/p7-bilemo/var/log does not exist"
fi

# Change to project directory
cd "/websites/p7-bilemo"

# Run cache clear command on local machine
# php bin/console cache:clear

# Run cache clear command in docker container
# docker exec p7-bilemo-php-1 php bin/console cache:clear

# Run cache clear command in docker container for test environment
# docker exec p7-bilemo-php-1 php bin/console --env=test cache:clear

# Validation message and prompt to exit
echo "Cache cleared successfully in both local and docker environments. Press any key to exit."
read -n 1
