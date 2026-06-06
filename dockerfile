FROM php:8.2-fpm

# Installer les dépendances système requises
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

# Installer les extensions PHP indispensables pour Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Installer Composer de manière sécurisée
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www
COPY . /var/www

# Installer les dépendances en ignorant temporairement les conflits de version PHP
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Donner les droits d'accès à Laravel pour les fichiers de cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
