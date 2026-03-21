FROM php:8.2-apache

# تثبيت مكتبة cURL الضرورية لاتصال البوت بتليجرام
RUN apt-get update && apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev

# نسخ ملفاتك إلى السيرفر
COPY . /var/www/html/

# إعطاء صلاحيات الكتابة للمجلد لكي يتمكن الكود من إنشاء ملفات txt (مثل makeorder و message)
RUN chown -R www-data:www-data /var/www/html && chmod -R 775 /var/www/html

# تفعيل محرك Apache
EXPOSE 80
