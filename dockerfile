FROM php:8.2-fpm

# 1. Installer les dépendances système requises
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

# 2. Installer les extensions PHP indispensables pour Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. Installer Composer de manière sécurisée
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Définir le dossier de travail ET COPIER les fichiers (Crucial)
WORKDIR /var/www
COPY . /var/www

# 5. Installer les dépendances du projet
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 6. Vider le cache de configuration (Maintenant le fichier artisan existe !)
RUN php artisan config:clear

# 7. Donner les droits d'accès à Laravel pour les fichiers de cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
