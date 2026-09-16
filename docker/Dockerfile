# Open Enventory development image.
#
# PHP 7.4 is pinned deliberately: INSTALL/INSTALL.html records 7.4.15 as the
# last version tested against this codebase, and the source uses constructs
# that PHP 8 removed (each(), curly-brace string offsets, `readonly` as a bare
# constant). Do not bump this without fixing those first.
FROM php:7.4-apache

# Runtime helpers shelled out to from lib_*.php. The command names come from
# lib_global_settings.php: ghostscript_command, pdftotext_command,
# imagemagick_command and java_command.
RUN apt-get update && apt-get install -y --no-install-recommends \
        ghostscript \
        imagemagick \
        poppler-utils \
        default-jre-headless \
        unzip \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# gd is configured with freetype/jpeg because lib_draw_molecule.php and
# lib_gd_common.php use imagettftext() for structure rendering.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd mysqli mbstring zip

# ImageMagick's default policy on Debian refuses to read PDFs, which breaks the
# PDF->PNG conversion path in lib_analytics.php.
RUN if [ -f /etc/ImageMagick-6/policy.xml ]; then \
        sed -i 's#<policy domain="coder" rights="none" pattern="PDF" />#<policy domain="coder" rights="read|write" pattern="PDF" />#' /etc/ImageMagick-6/policy.xml; \
    fi

# The repo ships an .htaccess whose php_value directives need AllowOverride.
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Mirrors the .htaccess values so CLI and web agree.
RUN { \
        echo 'memory_limit = 512M'; \
        echo 'upload_max_filesize = 64M'; \
        echo 'post_max_size = 64M'; \
        echo 'max_execution_time = 300'; \
        echo 'error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT'; \
        echo 'display_errors = On'; \
    } > /usr/local/etc/php/conf.d/zz-open-enventory.ini

WORKDIR /var/www/html
