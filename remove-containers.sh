#!/bin/bash

# Delete all containers
docker rm $(docker ps -a -q) -f

# Delete all images
docker rmi $(docker images -q) -f

# Delete all volumes
docker volume rm $(docker volume ls -q) -f
