FROM ubuntu:20.04
ARG DEBIAN_FRONTEND=newt
RUN apt-get update && apt-get -y install git unzip php7.4-cli php7.4-curl php7.4-bcmath php7.4-mbstring php7.4-json
COPY . /usr/src/app
WORKDIR /usr/src/app
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '8a6138e2a05a8c28539c9f0fb361159823655d7ad2deecb371b04a83966c61223adc522b0189079e3e9e277cd72b8897') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN php composer.phar install
CMD [ "php", "./worker-redis.php" ]
