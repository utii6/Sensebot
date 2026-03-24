FROM php:8.2-apache

# تثبيت المكتبات الضرورية (cURL و PostgreSQL)
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    libpq-dev \
    && docker-php-ext-install curl pgsql

# نسخ ملفاتك إلى السيرفر
COPY . /var/www/html/

# إعطاء الصلاحيات (رغم أننا نستخدم قاعدة بيانات، يفضل إبقاؤها)
RUN chown -R www-data:www-data /var/www/html && chmod -R 775 /var/www/html

# تفعيل محرك Apache
EXPOSE 80
