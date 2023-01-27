FROM ubuntu:bionic
RUN apt update
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN apt install tzdata -y
RUN apt install software-properties-common -y
RUN add-apt-repository ppa:ondrej/php -y
RUN apt update
RUN apt install php8.1-cli php8.1-dev php8.1-bz2 php8.1-amqp -y && \
    apt install php8.1-zip php8.1-mbstring -y && \
    apt install php8.1-pdo-mysql php8.1-sqlite php8.1-redis -y
RUN apt install php8.1-swoole -y
RUN ln -sf /usr/bin/php8.1 /usr/bin/php
RUN printf "Show PHP Version...\r\n" && php -v
RUN printf "Show PHP Modules...\r\n" && php -m
RUN mkdir -p /opt/app/
COPY . /opt/app/
RUN printf "chmod 777...\r\n" && \
    chmod 777 /opt/app/ -R
EXPOSE 9502
ENTRYPOINT /opt/app/entrypoint.sh
