FROM ubuntu:18.04
ARG DEBIAN_FRONTEND=newt
RUN apt-get update && apt-get -y install git unzip php7.2-cli php7.2-curl php7.2-bcmath php7.2-mbstring php7.2-json
COPY . /usr/src/app
WORKDIR /usr/src/app
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN php composer.phar install
CMD [ "php", "./worker-redis.php" ]
