if you want to use with Apache with mod_php/PHP-CGI use this configration :

	<VirtualHost *:80>
	    ServerName crossover.dev
	    ServerAlias www.crossover.dev

	    DocumentRoot /var/www/crossover.dev/web
	    <Directory /var/www/crossover.dev/web>
		AllowOverride None
		Order Allow,Deny
		Allow from All

		<IfModule mod_rewrite.c>
		    Options -MultiViews
		    RewriteEngine On
		    RewriteCond %{REQUEST_FILENAME} !-f
		    RewriteRule ^(.*)$ app.php [QSA,L]
		</IfModule>
	    </Directory>

	    # uncomment the following lines if you install assets as symlinks
	    # or run into problems when compiling LESS/Sass/CoffeeScript assets
	    # <Directory /var/www/crossover.dev>
	    #     Options FollowSymlinks
	    # </Directory>
	-
	    <Directory /var/www/crossover.dev/web/bundles>
		<IfModule mod_rewrite.c>
		    RewriteEngine Off
		</IfModule>
	    </Directory>
	    ErrorLog /var/log/apache2/crossover_access.log
	    CustomLog /var/log/apache2/crossover_error.log combined
	</VirtualHost>

-----------------------------------

Using mod_php/PHP-CGI with Apache 2.4

	<Directory /var/www/crossover.dev/web>
	    Require all granted
	</Directory>

-----------------------------------

Using mod_proxy_fcgi with Apache 2.4

	<VirtualHost *:80>
	    ServerName crossover.dev
	    ServerAlias www.crossover.dev

	    # Uncomment the following line to force Apache to pass the Authorization
	    # header to PHP: required for "basic_auth" under PHP-FPM and FastCGI
	    #
	    # SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1

	    # For Apache 2.4.9 or higher
	    # Using SetHandler avoids issues with using ProxyPassMatch in combination
	    # with mod_rewrite or mod_autoindex
	    <FilesMatch \.php$>
		SetHandler proxy:fcgi://127.0.0.1:9000
		# for Unix sockets, Apache 2.4.10 or higher
		# SetHandler proxy:unix:/path/to/fpm.sock|fcgi://dummy
	    </FilesMatch>

	    # If you use Apache version below 2.4.9 you must consider update or use this instead
	    # ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://127.0.0.1:9000/var/www/crossover.dev/web/$1

	    DocumentRoot /var/www/crossover.dev/web
	    <Directory /var/www/crossover.dev/web>
		AllowOverride All
		Require all granted
	    </Directory>

	    # uncomment the following lines if you install assets as symlinks
	    # or run into problems when compiling LESS/Sass/CoffeeScript assets
	    # <Directory /var/www/crossover.dev>
	    #     Options FollowSymlinks
	    # </Directory>

	    ErrorLog /var/log/apache2/crossover_access.log
	    CustomLog /var/log/apache2/crossover_error.log combined
	</VirtualHost>

-----------------------------------

Using PHP-FPM with Apache 2.2

	<VirtualHost *:80>
	    ServerName crossover.dev
	    ServerAlias www.crossover.dev

	    AddHandler php5-fcgi .php
	    Action php5-fcgi /php5-fcgi
	    Alias /php5-fcgi /usr/lib/cgi-bin/php5-fcgi
	    FastCgiExternalServer /usr/lib/cgi-bin/php5-fcgi -host 127.0.0.1:9000 -pass-header Authorization

	    DocumentRoot /var/www/crossover.dev/web
	    <Directory /var/www/crossover.dev/web>
		AllowOverride All
		Order Allow,Deny
		Allow from all
	    </Directory>

	    # uncomment the following lines if you install assets as symlinks
	    # or run into problems when compiling LESS/Sass/CoffeeScript assets
	    # <Directory /var/www/crossover.dev>
	    #     Options FollowSymlinks
	    # </Directory>

	    ErrorLog /var/log/apache2/crossover_access.log
	    CustomLog /var/log/apache2/crossover_error.log combined
	</VirtualHost>

-----------------------------------

Using Nginx

	server {
	    server_name crossover.dev www.crossover.dev;
	    root /var/www/crossover.dev/web;

	    location / {
		# try to serve file directly, fallback to app.php
		try_files $uri /app.php$is_args$args;
	    }

	    location ~ ^/(app_dev|config)\.php(/|$) {
		fastcgi_pass unix:/var/run/php5-fpm.sock;
		fastcgi_split_path_info ^(.+\.php)(/.*)$;
		include fastcgi_params;
	       
		fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
		fastcgi_param DOCUMENT_ROOT $realpath_root;
	    }
	    # PROD
	    location ~ ^/app\.php(/|$) {
		fastcgi_pass unix:/var/run/php5-fpm.sock;
		fastcgi_split_path_info ^(.+\.php)(/.*)$;
		include fastcgi_params;
		
		fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
		fastcgi_param DOCUMENT_ROOT $realpath_root;

		internal;
	    }

	    location ~ \.php$ {
		return 404;
	    }

	    error_log /var/log/apache2/crossover_error.log
	    access_log /var/log/apache2/crossover_access.log
	}

<-----------------------------------------------------------------------------------------_>

PHP Requitments
	> PHP 5.3.9 or Higher
		> Extensions : php-intl, php-gd, php-curl, php-xml, php-mbstring, php-iconv, php-dom

Copy Source files into correct folder on your web server

Create database and set information to projectdir/app/config/parameters.yml

If database values are correct you can run install.sh inside of project directory. It will automaticly download required files and creates database.

