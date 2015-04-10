#proxyhp
A simple HTTP proxy written in PHP for exotic server configurations.

##Example
Here's the only two files you will need to configure on the server.
For example, if you want your proxy to be in `/localDir`, the configuration will be the following.

###`index.php`
```php
<?php
require('proxy.php');
new Proxy('example.com', 80, '/remoteDir', '/localDir');
?>
```

###`.htaccess`
```
FallbackResource /localDir/index.php
```
Now, every request to `mydomain.com/localDir/*` will redirect to `example.com/remoteDir/*`

##Motivation
My school provide a free subdomain that I wanted to use, but they'll point to some PHP servers, on which you have a write access but not admin rights. As I wanted a NodeJS application that could run on a server inside the school but not accessible from outside, I wanted a PHP proxy to redirect the HTTP traffic to this server. However, all solutions I found required either `cURL`, late PHP versions, or Apache modules that I couldn't install. So I decided to write my own.


##Installation
You will probably only want the `proxy.php` file that is the only thing needed to make it run.
```bash
wget https://raw.githubusercontent.com/GeoffreyFrogeye/phroxyp/master/proxy.php
chmod 755 proxy.php
```  
Then you will need to write an `index.php` file, containing the following:
```php
<?php
require('proxy.php');
new Proxy('example.com', 80, '/remoteDir', '/localDir');
?>
```
Where:

* `example.com` is the target hostname
* `80` is the target port (default: `80`)
* `/remoteDir` is the directory to point on on the target (default: `/`)
	* For example, if set to `/superapplication`, it will redirect to `example.com/superapplication`
* `/localDir` is the directory that contains the file.
	* For example, if you need to type `mydomain.com/superproxy/index.php` to access this file, set this to `/superproxy`

Then, create the `.htaccess` file in order to redirect every request made in `localDir` to `index.php` that will handle it. In most situations, you will onyl need the following line.
```
FallbackResource /localDir/index.php
```
If your server doesn't support this, try replacing it with the following.
```
ErrorDocument 404 /localDir/index.php
```
The HTTP status code will be the one returned by the target, even on fastCGI servers, don't worry.

If you get a 404 error when trying to access `.php` files, add the following.
```
RemoveHandler .php
```

Be warned that the application doesn't change the links that are inside the request. For example, if the `example.com/index.html` has a link to `/style.css`, and you access it from `mydomain.com/toExample/index.html`, the browser will look for `mydomain.com/style.css`, which may not be the wanted result. To prevent this, you will need to either modify the links on the target, or to put the proxy in the root directory of your server.

#Known working configurations

* **Apache versions:** 2.0, 2.2
* **PHP versions:** 5.3, 5.5
* **Content types:** `application/x-www-urlencoded`, `application/json`

If your configuration doesn't work, feel free to post an issue, I'll be happy to help you!
