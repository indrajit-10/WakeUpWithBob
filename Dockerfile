# Wake up with Bob — container image for Render (works on any Docker host).
# Render has no native PHP runtime, so we ship a small Apache + PHP image.
FROM php:8.3-apache

# pdo_sqlite / sqlite3 are compiled into the official PHP image by default,
# so there are no PHP extensions to install for this SQLite app.

# 1) Serve the app's public/ folder as the web root (never the project root).
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 2) Listen on the port Render provides ($PORT, defaults to 10000).
ENV PORT=10000
RUN sed -ri -e 's!^Listen 80$!Listen ${PORT}!' /etc/apache2/ports.conf \
 && sed -ri -e 's!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!' /etc/apache2/sites-available/000-default.conf

# 3) App code.
COPY . /var/www/html/
WORKDIR /var/www/html

# 4) config.php is git-ignored (so it isn't in the repo) — build one from the
#    example, and make the data + uploads folders writable by the web server.
RUN cp -n app/config.example.php app/config.php \
 && mkdir -p data public/uploads \
 && chown -R www-data:www-data data public/uploads

EXPOSE 10000
# apache2-foreground (the base image's default CMD) starts the server.
