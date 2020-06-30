FROM ubuntu:20.04
ARG DEBIAN_FRONTEND=newt
RUN apt-get update && apt-get -y install git unzip php7.4-cli php7.4-curl php7.4-bcmath php7.4-mbstring php7.4-json
COPY . /usr/src/app
WORKDIR /usr/src/app
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'e0012edf3e80b6978849f5eff0d4b4e4c79ff1609dd1e613307e16318854d24ae64f26d17af3ef0bf7cfb710ca74755a') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN php composer.phar install
CMD [ "php", "./worker-redis.php" ]
