FROM ubuntu:20.04
ARG DEBIAN_FRONTEND=newt
RUN apt-get update && apt-get -y install git unzip php7.4-cli php7.4-curl php7.4-bcmath php7.4-mbstring php7.4-json
COPY . /usr/src/app
WORKDIR /usr/src/app
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'c31c1e292ad7be5f49291169c0ac8f683499edddcfd4e42232982d0fd193004208a58ff6f353fde0012d35fdd72bc394') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN php composer.phar install
CMD [ "php", "./worker-redis.php" ]
