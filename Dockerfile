FROM php:8.2-apache

# Install system dependencies needed for pgsql
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql

# Enable Apache rewrite (optional but common for APIs)
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

EXPOSE 80
