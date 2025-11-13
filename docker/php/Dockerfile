FROM php:8.2-fpm-alpine

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
    autoconf \
    nodejs \
    npm \
    g++ \
    gcc \
    libc-dev \
    make \
    postgresql-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    icu-dev \
    libzip-dev \
    libxml2-dev

RUN apk add git
RUN apk add openssh-server
RUN apk add openssh-client
RUN apk add --no-cache shadow

RUN docker-php-ext-install bcmath \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install intl \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install zip \
    && docker-php-ext-install soap

RUN apk --no-cache add pcre-dev ${PHPIZE_DEPS} \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apk del pcre-dev ${PHPIZE_DEPS} \
  && rm -rf /tmp/pear

RUN apk add --no-cache linux-headers
RUN CFLAGS="$CFLAGS -D_GNU_SOURCE" docker-php-ext-install sockets

# xdebug install
# RUN pecl install xdebug-3.0.1 && docker-php-ext-enable xdebug

#RUN apk --update add --virtual build-dependencies build-base openssl-dev autoconf \
#  && pecl install mongodb \
#  && docker-php-ext-enable mongodb \
#  && apk del build-dependencies build-base openssl-dev autoconf \
#  && rm -rf /var/cache/apk/*

#RUN docker-php-ext-install exif && \
#     docker-php-ext-enable exif

# composer install
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add mc

RUN mkdir /.config
RUN mkdir /.config/psysh
RUN chmod 0777 /.config && chmod 0777 /.config/psysh

RUN groupadd --gid ${GID} origamiv \
  && useradd --uid ${UID} --gid origamiv --shell /bin/bash --create-home origamiv
USER origamiv

EXPOSE 9000

WORKDIR /var/www/html
