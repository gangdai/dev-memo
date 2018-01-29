1. Apache 2.4.29

Download Apache 2.4.29 x64: https://www.apachehaus.com/cgi-bin/download.plx#APACHE24VC14

Download and install the Microsoft Visual C++ Redistributable for Visual Studio 2017(VC15) OR Visual Studio C++ 2015 (VC14)

Unzip Apache binaries to the C:\Apache24 folder.

install Apache 2.4 as a Windows Service:

[code lang="css"]  httpd.exe -k install -n "Apache 2.4" [/code]


2. php 7.1.13

download the thread safe binaries: php-7.2.1-Win32-VC15-x64.zip, unzip the PHP binaries to the C:\php7 folder.


3. Config Apache

httpd.conf -> change line

[code lang="css"]

...

ServerRoot "c:/Apache24"

...

DocumentRoot "c:/Apache24/htdocs"
<Directory "c:/Apache24/htdocs">

...

#add this at the top

AddHandler application/x-httpd-php .php
AddType application/x-httpd-php .php .html
LoadModule php7_module "c:/php7/php7apache2_4.dll"
PHPIniDir "c:/php7"

[/code]


4. Config php

Change C:\php7\php.ini-development to C:\php7\php.ini and update

[code lang="css"]

...

extension_dir = "c:/php7/ext/"

#create log dir

error_log = "c:/php7/logs/error.log"

... #uncomment:

extension=php_mbstring.dll

extension=php_gd2.dll

extension=php_mysqli.dll

extension=php_pdo_mysql.dll

[/code]


5. Mysql 5.7

Installation: mysql 5.7 doc


6. Testing

[code lang="css"]

#start httpd service:

httpd.exe -k start -n "Apache2.4"

#or

net start "Apache 2.4"

#Test the configuration
httpd.exe -t

[/code]


Create the following text file C:\Apache24\htdocs\phpinfo.php:
<?php
phpinfo();
?>


Download

apache2.4.29 X64: https://www.apachehaus.com/cgi-bin/download.plx?dli=O1mR0F1UNRTTUp1KZBjT2AlVOpkVFVFdhpmUSNWQ

php7.1.13: http://php.net/downloads.php

Mysql5.7.21: https://dev.mysql.com/downloads/windows/installer/5.7.html

