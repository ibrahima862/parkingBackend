FROM php:8.2-fpm

# Installer les dépendances système et l'extension MySQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers du projet
WORKDIR /var/www
COPY . /var/www

# Installer les dépendances de Laravel
RUN composer install --no-dev --optimize-autoloader

# Donner les bonnes permissions aux dossiers de stockage
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Exposer le port et lancer le serveur intégré de Laravel
EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
