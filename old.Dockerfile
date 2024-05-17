FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx wget

RUN mkdir -p /run/nginx

COPY ./docker/nginx.conf /etc/nginx/nginx.conf

RUN mkdir -p /app
COPY . /app
# COPY ./src /app

RUN sh -c "wget http://getcomposer.org/composer.phar && chmod a+x composer.phar && mv composer.phar /usr/local/bin/composer"
# copy .env.example to .env
# RUN cp /app/.env.production /app/.env
# enable mysql extension
# RUN docker-php-ext-install pdo_mysql
RUN cd /app && \
    /usr/local/bin/composer install --no-dev

RUN chown -R www-data: /app
EXPOSE 8080

CMD sh /app/docker/startup.sh
