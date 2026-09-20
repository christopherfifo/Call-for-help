FROM php:8.2-apache

# Instalar extensões necessárias (pdo_mysql)
RUN docker-php-ext-install pdo pdo_mysql

# Ativar mod_rewrite do Apache se for usar rotas amigáveis (opcional)
RUN a2enmod rewrite

# Mudar o DocumentRoot do Apache para o diretório atual do projeto
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copiar os arquivos do projeto
COPY . /var/www/html/

# Dar permissão apropriada
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
