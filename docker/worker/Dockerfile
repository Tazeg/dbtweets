FROM ubuntu:18.04
ARG DEBIAN_FRONTEND=newt
RUN apt-get update && apt-get -y install git unzip php7.2-cli php7.2-curl php7.2-bcmath php7.2-mbstring php7.2-json
COPY . /usr/src/app
WORKDIR /usr/src/app
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN php composer.phar install
CMD [ "php", "./worker-redis.php" ]
