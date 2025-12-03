FROM php:8.3-cli
RUN apt-get update && apt-get install -y gcc libffi-dev && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install ffi && echo "ffi.enable=true" >> /usr/local/etc/php/conf.d/ffi.ini
COPY runtime/ffi/protect.c /tmp/protect.c
RUN gcc -shared -fPIC -o /usr/local/lib/protect.so /tmp/protect.c
COPY benchmark/mock_seed /ultra/compiler/build_seed
RUN chmod 600 /ultra/compiler/build_seed
COPY benchmark/bench.php /ultra/benchmark/bench.php
COPY benchmark/bench.ultra2 /ultra/benchmark/bench.ultra2
COPY runtime /ultra/runtime
WORKDIR /ultra
CMD ["php", "benchmark/bench.php"]
