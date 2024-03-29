.PHONY: tests

setup:
	@docker compose pull --ignore-pull-failures
	@docker compose run --rm composer install

tests:
	@docker compose run --rm composer validate --strict
	@docker compose run --rm php vendor/bin/codecept run --coverage --coverage-html --phpunit-xml test-results.xml --coverage-xml coverage.xml --steps

run:
	@docker compose run --rm php-version-audit

lint: phpstan psalm rector-dry ecs-dry
lint-fix: phpstan psalm rector ecs

phpstan:
	@docker compose run --rm phpstan

psalm:
	@docker compose run --rm --entrypoint=./vendor/bin/psalm php

rector-dry:
	@docker compose run --rm --entrypoint vendor/bin/rector php process src --dry-run

rector:
	@docker compose run --rm --entrypoint vendor/bin/rector php process src

ecs-dry:
	@docker compose run --rm --entrypoint vendor/bin/ecs php

ecs:
	@docker compose run --rm --entrypoint vendor/bin/ecs php --fix
