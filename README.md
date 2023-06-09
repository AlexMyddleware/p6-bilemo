# Bilemo

## Introduction

Bilemo is a PHP API built with Symfony that provides an interface to manage Clients, Customers and Phones data. It provides Docker support, database fixtures and various utility scripts for easy setup and teardown, as well as automated tests.

## Features

RESTful API endpoints for Clients, Customers and Phones.
JWT Authentication.
Data fixtures for easy testing.
Dockerized setup for isolated development environment.
Integrated with Doctrine ORM for efficient database operations.
Self-discoverable API endpoints and documentation with NelmioApiDocBundle.

## Installation

clone the repository

`git clone https://github.com/user/bilemo.git`

Then navigate into the project directory:

bash

`cd bilemo`

Next, install the PHP dependencies using Composer:

`composer install`

## Configuration

Copy .env to .env.local and update your database credentials.

## Note on the .ps1 scripts and .sh scripts
You will need to update the scripts to match your local setup. The scripts are provided as an example and may not work out of the box.

### For PostgreSQL with Docker
DATABASE_URL="postgresql://app:!ChangeMe!@database:5432/app?serverVersion=15&charset=utf8"
### For MySQL with Laragon or local setup
DATABASE_URL="mysql://root:root@127.0.0.1:3306/bilemo?serverVersion=5.7"

Update the same for the test environment in .env.test:

env

### For PostgreSQL with Docker
DATABASE_URL="postgresql://app:!ChangeMe!@database_test:5432/test_db"
### For MySQL with Laragon or local setup
DATABASE_URL="mysql://root:root@127.0.0.1:3306/bilemo?serverVersion=5.7"

## Docker Usage

Bilemo is Docker ready. To launch the Docker environment, you can use provided script files:

### On Windows (PowerShell):


`./start.ps1`

### On Linux/MacOS:


`./start.sh`

You can also manage the Docker environment manually:

To build and run the Docker image:

docker-compose up -d

To stop and remove containers, networks and volumes:


docker-compose down

## Tests
Bilemo is built with testing in mind. You can run tests using the following command:

### local

`php ./vendor/bin/phpunit --colors --testdox`

### Docker

`docker-compose -f docker-compose.test.yml exec php ./vendor/bin/phpunit --colors --testdox`
For the tests, change your doctrine configuration based on your environment in config/packages/doctrine.yaml:

### Running a single test

#### Local
php ./vendor/bin/phpunit --colors --testdox --filter '/::testGetAllCustomersOfTheAdminClient$/'


#### Docker
docker-compose -f docker-compose.test.yml exec php ./vendor/bin/phpunit --colors --testdox --filter '/::testGetAllCustomersOfTheAdminClient$/'


### Changing doctrine.yaml for Docker setup
when@test:
    doctrine:
        dbal:
            dbname_suffix: '_test%env(default::TEST_TOKEN)%'

### For local setup
when@test:
    doctrine:
        dbal:
        url: '%env(resolve:DATABASE_URL)%' # Use a separate test database

## Postman suite

You can import the Postman collection and environment from the postman directory to test the API endpoints.
Go to your postman, click on import and select the postman json directory at the root of the project. You will see the collection and environment imported.

![GitHub top language](https://img.shields.io/github/languages/top/AlexMyddleware/p6-bilemo)
[![GitHub issues](https://img.shields.io/github/issues/AlexMyddleware/p6-bilemo)](https://github.com/AlexMyddleware/p6-bilemo/issues)
[![GitHub closed issues](https://img.shields.io/github/issues-closed/AlexMyddleware/p6-bilemo)](https://github.com/AlexMyddleware/p6-bilemo/issues?q=is%3Aissue+is%3Aclosed)

