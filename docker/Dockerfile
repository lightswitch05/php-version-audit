FROM composer:1.9 as composer-build
WORKDIR /opt/php-version-audit
COPY ./src ./src
COPY ./docs ./docs
COPY php-version-audit .
COPY ./composer.json .
COPY ./composer.lock .
RUN composer install --prefer-dist --no-progress --no-suggest

FROM php:7.4-cli-alpine
WORKDIR /opt/php-version-audit
RUN addgroup -S appgroup && adduser -S appuser -G appgroup
COPY --from=composer-build /opt/php-version-audit /opt/php-version-audit
ENTRYPOINT ["/opt/php-version-audit/php-version-audit"]
