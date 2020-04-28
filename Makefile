.PHONY: tests

setup:
	@docker-compose pull --ignore-pull-failures
	@docker-compose run --rm composer install

tests:
	@docker-compose run --rm php vendor/bin/codecept run --coverage --coverage-html --phpunit-xml test-results.xml --coverage-xml coverage.xml --steps

run:
	@docker-compose run --rm php ./php-version-audit

phpstan:
	@docker-compose run --rm phpstan

psalm:
	@docker-compose run --rm php ./vendor/bin/psalm

rector-dry:
	@docker run --rm -v $(PWD):/project rector/rector:latest process /project/src --config /project/rector.yml --autoload-file /project/vendor/autoload.php --dry-run

rector:
	@docker run --rm -v $(PWD):/project rector/rector:latest process /project/src --config /project/rector.yml --autoload-file /project/vendor/autoload.php
