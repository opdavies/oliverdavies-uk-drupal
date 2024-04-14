# Do not edit this file. It is automatically generated by https://www.oliverdavies.uk/build-configs.

FROM php:8.1-apache AS base

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN which composer && composer -V

ARG DOCKER_UID=1000
ENV DOCKER_UID="${DOCKER_UID}"

WORKDIR /var/www/html

RUN adduser --disabled-password --uid "${DOCKER_UID}" app \
  && chown app:app -R /var/www/html

USER app

ENV PATH="${PATH}:/var/www/html/bin:/var/www/html/vendor/bin"

COPY --chown=app:app composer.* ./

################################################################################

FROM base AS build

USER root

RUN a2enmod rewrite

RUN apt-get update -yqq \
  && apt-get install -yqq --no-install-recommends \
    git libpng-dev libjpeg-dev libzip-dev mariadb-client unzip \
  && rm -rf /var/lib/apt/lists/* /usr/share/doc /usr/share/man \
  && apt-get clean

RUN docker-php-ext-configure gd --with-jpeg

RUN docker-php-ext-install gd pdo_mysql zip

COPY --chown=app:app phpunit.xml* ./



USER app

RUN composer validate --strict
RUN composer install --quiet --no-progress

COPY --chown=app:app tools/docker/images/php/root /

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-php"]
CMD ["apache2-foreground"]




