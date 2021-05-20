#!/usr/bin/env bash
cd $(dirname $0)
./vendor/phpunit/phpunit/phpunit.php tests
