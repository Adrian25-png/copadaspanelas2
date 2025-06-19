# Use a imagem oficial do PHP com servidor embutido e extensões básicas
FROM php:8.2-cli

# Instala extensões necessárias, incluindo pdo_mysql
RUN docker-php-ext-install pdo pdo_mysql

# Defina o diretório de trabalho dentro do container
WORKDIR /app

# Copie todo o conteúdo do seu projeto para o diretório de trabalho
COPY . /app

# Exponha a porta 8080 (Railway usa essa porta para mapear)
EXPOSE 8080

# Comando para iniciar o servidor embutido do PHP, apontando para a pasta app/pages
CMD ["php", "-S", "0.0.0.0:8080", "-t", "app/pages"]