# ---- Stage 1: frontend build artifacts ----
FROM owntracks/frontend:latest AS frontend

# ---- Stage 2: nginx + php-fpm ----
FROM nginx:1.27-alpine

LABEL org.opencontainers.image.title="otweb"
LABEL org.opencontainers.image.description="OwnTracks web tier (nginx + php-fpm)"
LABEL maintainer="Ian Kluhsman"

# ------------------------------------------------------------
# Install PHP-FPM + extensions + supervisor
# ------------------------------------------------------------
RUN apk add --no-cache \
    php82 \
    php82-fpm \
    php82-json \
    php82-mbstring \
    php82-curl \
    php82-opcache \
    php82-session \
    php82-phar \
    php82-iconv \
    php82-fileinfo \
    supervisor

# ------------------------------------------------------------
# Directory layout (quicksetup-like)
# ------------------------------------------------------------
RUN mkdir -p \
    /usr/share/nginx/html/owntracks \
    /usr/local/owntracks/userdata \
    /run/php \
    /var/log/php82 \
    /var/log/supervisor

# ------------------------------------------------------------
# Frontend SPA
# ------------------------------------------------------------
COPY --from=frontend /usr/share/nginx/html/ /usr/share/nginx/html/owntracks/
COPY logo-owntracks-grayscale-96x96.jpg /usr/share/nginx/html/owntracks/

# ------------------------------------------------------------
# PHP entrypoints
# ------------------------------------------------------------
COPY index.php /usr/share/nginx/html/index.php
COPY otrc.php  /usr/share/nginx/html/owntracks/otrc.php

# Frontend runtime config
COPY config.js /usr/share/nginx/html/owntracks/config/config.js

# ------------------------------------------------------------
# nginx configuration
# ------------------------------------------------------------
COPY nginx.conf /etc/nginx/nginx.conf
COPY owntracks.conf /etc/nginx/conf.d/owntracks.conf
RUN rm -f /etc/nginx/conf.d/default.conf
RUN rm -f /usr/share/nginx/html/index.html
RUN rm -f /usr/share/nginx/html/50x.html

# ------------------------------------------------------------
# PHP-FPM configuration
# ------------------------------------------------------------
COPY php-fpm.conf /etc/php82/php-fpm.conf
COPY www.conf     /etc/php82/php-fpm.d/www.conf

# ------------------------------------------------------------
# Supervisor
# ------------------------------------------------------------
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# ------------------------------------------------------------
# Permissions
# ------------------------------------------------------------
RUN chown -R nginx:nginx \
    /usr/share/nginx/html \
    /usr/local/owntracks \
    /run/php \
    /var/log/php82

# ------------------------------------------------------------
# Runtime
# ------------------------------------------------------------
EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

