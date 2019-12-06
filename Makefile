.PHONY: tests

setup:
	@docker-compose pull --ignore-pull-failures
	@docker-compose run --rm -T composer install

tests:
	@docker-compose run --rm -T php vendor/bin/codecept run --coverage --coverage-html --phpunit-xml test-results.xml --coverage-xml coverage.xml --steps

run:
	@docker-compose run --rm -T php ./php-version-audit
