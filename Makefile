.PHONY: tests

setup:
	@docker-compose pull --ignore-pull-failures
	@docker-compose run --rm composer install

tests:
	@docker-compose run --rm composer validate --strict
	@docker-compose run --rm --entrypoint=php php vendor/bin/codecept run --coverage --coverage-html --phpunit-xml test-results.xml --coverage-xml coverage.xml --steps

run:
	@docker-compose run --rm php ./php-version-audit

phpstan:
	@docker-compose run --rm phpstan

psalm:
	@docker-compose run --rm --entrypoint=./vendor/bin/psalm php

rector-dry:
	@docker-compose run --rm --entrypoint vendor/bin/rector php process src --dry-run

rector:
	@docker-compose run --rm --entrypoint vendor/bin/rector php process src
