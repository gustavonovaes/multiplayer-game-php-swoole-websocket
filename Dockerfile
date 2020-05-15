FROM php:alpine

RUN apk add --no-cache $PHPIZE_DEPS 

RUN pecl install swoole && \
  docker-php-ext-enable swoole

RUN addgroup -S app && adduser -S -G app app 
USER app