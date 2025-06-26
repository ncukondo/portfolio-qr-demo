FROM php:8.2-apache

# Update package lists and install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    bash \
    ca-certificates \
    wget \
    gnupg \
    lsb-release \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    postgresql-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set bash as default shell and ensure it's properly configured
RUN ln -sf /bin/bash /bin/sh && \
    echo 'export SHELL=/bin/bash' >> /etc/environment && \
    echo 'export BASH_ENV=/etc/bash.bashrc' >> /etc/environment

# Verify curl and bash are available
RUN which curl && which bash && curl --version && bash --version

# Install Node.js directly using NodeSource repository
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g yarn pnpm

# Verify Node.js installation
RUN node --version && npm --version

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /workspace

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY .devcontainer/apache.conf /etc/apache2/sites-available/000-default.conf

# Create www-data user home directory and set up shell
RUN mkdir -p /var/www/.bashrc.d && \
    chown -R www-data:www-data /var/www && \
    usermod -s /bin/bash www-data && \
    usermod -d /var/www www-data && \
    echo 'export PATH=/usr/bin:$PATH' >> /var/www/.bashrc

# Install sudo for development convenience
RUN apt-get update && apt-get install -y sudo \
    && echo 'www-data ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Ensure Node.js is accessible to all users
RUN echo 'export PATH=/usr/bin:$PATH' >> /etc/environment

USER www-data