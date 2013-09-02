#!/bin/bash
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin
export PATH

# install php and nginx

# Check if user is root
if [ $(id -u) != "0" ]; then
	echo "Error: You must be root to run this script, please use root to retry."
	exit 1
fi
cur_dir=$(pwd)
soft_dir=$cur_dir"/soft"
runtime_dir=$cur_dir"/runtime"
mkdir -p soft
mkdir -p runtime
cd $soft_dir
# download software
# for php
if [ ! -s php-5.4.19.tar.gz ]; then
	echo "download PHP and Dependencies......"
	wget -c http://pecl.php.net/get/gmagick-1.1.2RC1.tgz
	wget -c http://downloads.sourceforge.net/project/graphicsmagick/graphicsmagick/1.3.18/GraphicsMagick-1.3.18.tar.gz
	wget -c http://ftp.gnu.org/pub/gnu/libiconv/libiconv-1.14.tar.gz
	wget -c http://sourceforge.net/projects/mcrypt/files/Libmcrypt/2.5.8/libmcrypt-2.5.8.tar.gz/download
	wget -c http://sourceforge.net/projects/mcrypt/files/MCrypt/2.6.8/mcrypt-2.6.8.tar.gz/download
	wget -c http://pecl.php.net/get/memcache-2.2.7.tgz
	wget -c https://launchpad.net/libmemcached/1.0/1.0.16/+download/libmemcached-1.0.16.tar.gz
	wget -c http://pecl.php.net/get/memcached-2.1.0.tgz
	wget -c http://sourceforge.net/projects/mhash/files/mhash/0.9.9.9/mhash-0.9.9.9.tar.gz/download
	wget -c http://pecl.php.net/get/imagick-3.1.0RC2.tgz
	wget -c http://www.php.net/get/php-5.4.19.tar.gz/from/us1.php.net/mirror
fi

# for nginx
if [ ! -s nginx-1.4.2.tar.gz ]; then
	echo "download nginx......"
	wget -c http://sourceforge.net/projects/pcre/files/pcre/8.33/pcre-8.33.tar.gz/download
	wget -c http://nginx.org/download/nginx-1.4.2.tar.gz
fi
cd ../

################### 系统优化 ####################
#yum -y install yum-fastestmirror
#yum -y update

#Disable SeLinux
if [ -s /etc/selinux/config ]; then
sed -i 's/SELINUX=enforcing/SELINUX=disabled/g' /etc/selinux/config
fi

# 时区设置
#Synchronization time
#mv /etc/localtime /etc/localtime.default
#ln -s /usr/share/zoneinfo/Asia/Shanghai /etc/localtime

# 时间同步
#yum install -y ntp
#ntpdate us.pool.ntp.org | logger -t NTP
#date

# 旧软件清理
#yum -y remove mysql*
#yum -y remove php*

#################### 公用包安装 ####################
# public
yum -y install gcc gcc-c++ make
# for php
yum -y install libxml2 libxml2-devel zlib-devel
yum -y install openssl openssl-devel
yum -y install curl curl-devel
yum -y install libjpeg libjpeg-devel libpng libpng-devel freetype freetype-devel
yum -y install openldap openldap-devel cyrus-sasl-devel
yum -y install bzip2 bzip2-devel
yum -y install libxslt libxslt-devel
yum -y install ImageMagick ImageMagick-devel
yum -y install net-snmp-devel

# for mysql
#yum -y install ncurses ncurses-devel

# for memcached
yum -y install autoconf libevent libevent-devel

################### 安装软件 #######################
# Install PHP
cd $runtime_dir
tar -zxvf $soft_dir"/libiconv-1.14.tar.gz"
cd libiconv-1.14
./configure
make && make install
cd ..

cd $runtime_dir
tar -zxvf $soft_dir"/libmcrypt-2.5.8.tar.gz"
cd libmcrypt-2.5.8
./configure
make && make install
/sbin/ldconfig
cd libltdl
./configure --enable-ltdl-install
make
make install
cd ../../

cd $runtime_dir
tar -zxvf $soft_dir"/mhash-0.9.9.9.tar.gz"
cd mhash-0.9.9.9/
./configure
make
make install
cd ..

ln -s /usr/local/lib/libmcrypt.la /usr/lib/libmcrypt.la
ln -s /usr/local/lib/libmcrypt.so /usr/lib/libmcrypt.so
ln -s /usr/local/lib/libmcrypt.so.4 /usr/lib/libmcrypt.so.4
ln -s /usr/local/lib/libmcrypt.so.4.4.8 /usr/lib/libmcrypt.so.4.4.8
ln -s /usr/local/lib/libmhash.a /usr/lib/libmhash.a
ln -s /usr/local/lib/libmhash.la /usr/lib/libmhash.la
ln -s /usr/local/lib/libmhash.so /usr/lib/libmhash.so
ln -s /usr/local/lib/libmhash.so.2 /usr/lib/libmhash.so.2
ln -s /usr/local/lib/libmhash.so.2.0.1 /usr/lib/libmhash.so.2.0.1
ln -s /usr/local/bin/libmcrypt-config /usr/bin/libmcrypt-config
/sbin/ldconfig

if [ `getconf WORD_BIT` = '32' ] && [ `getconf LONG_BIT` = '64' ] ; then
    ln -sv /usr/lib64/libldap* /usr/lib/
fi

cd $runtime_dir
tar -zxvf $soft_dir"/php-5.4.19.tar.gz"
cd php-5.4.19

./configure --prefix=/usr/local/php \
--with-config-file-path=/usr/local/php/etc \
--disable-rpath \
--enable-fpm \
--with-libxml-dir \
--with-openssl \
--with-zlib \
--enable-bcmath \
--enable-calendar \
--with-bz2 \
--with-curl \
--with-curlwrappers \
--enable-dba \
--enable-exif \
--enable-ftp \
--with-gd \
--with-freetype-dir \
--with-jpeg-dir \
--with-png-dir \
--enable-gd-native-ttf \
--with-gettext \
--with-mhash \
--with-ldap \
--enable-mbstring \
--with-mcrypt \
--with-mysql=mysqlnd \
--with-mysqli=mysqlnd \
--with-pdo-mysql=mysqlnd \
--enable-shmop \
--with-snmp \
--enable-soap \
--enable-sockets \
--enable-sysvsem \
--with-xmlrpc \
--with-iconv-dir \
--with-xsl \
--enable-zip \
--with-pcre-regex \
--with-pear

make ZEND_EXTRA_LIBS='-liconv'
make install

# 首次安装，备份默认的php.ini文件
if [ ! -s /usr/local/php/etc/php.ini.default ]; then
	cp php.ini-production /usr/local/php/etc/php.ini.default
fi

if [ ! -s /usr/local/php/etc/php.ini ]; then
	cp php.ini-production /usr/local/php/etc/php.ini
fi
cp sapi/fpm/init.d.php-fpm /etc/init.d/php-fpm
chmod +x /etc/init.d/php-fpm

## modify php-fpm.conf
if [ ! -s /usr/local/php/etc/php-fpm.conf ]; then
cp /usr/local/php/etc/php-fpm.conf.default /usr/local/php/etc/php-fpm.conf
fi

# php 的用户
sed -i 's/;pid = run\/php-fpm.pid/pid = run\/php-fpm.pid/g' /usr/local/php/etc/php-fpm.conf
sed -i 's/user = nobody/user = www/g' /usr/local/php/etc/php-fpm.conf
sed -i 's/group = nobody/group = www/g' /usr/local/php/etc/php-fpm.conf
# php的进程配置 16G MEM
sed -i 's/pm.min_spare_servers = 1/pm.min_spare_servers = 16/g' /usr/local/php/etc/php-fpm.conf
sed -i 's/pm.max_spare_servers = 3/pm.max_spare_servers = 48/g' /usr/local/php/etc/php-fpm.conf
sed -i 's/pm.start_servers = 2/pm.start_servers = 16/g' /usr/local/php/etc/php-fpm.conf
sed -i 's/pm.max_children = 5/pm.max_children = 400/g' /usr/local/php/etc/php-fpm.conf
cd ../

cd $runtime_dir
tar -zxvf $soft_dir"/memcache-2.2.7.tgz"
cd memcache-2.2.7/
/usr/local/php/bin/phpize
./configure --with-php-config=/usr/local/php/bin/php-config
make && make install
cd ../

cd $runtime_dir
tar -zxvf $soft_dir"/libmemcached-1.0.16.tar.gz"
cd libmemcached-1.0.16
./configure
make && make install
cd ../

cd $runtime_dir
tar -zxvf $soft_dir"/memcached-2.1.0.tgz"
cd memcached-2.1.0
/usr/local/php/bin/phpize
./configure --with-php-config=/usr/local/php/bin/php-config
make && make install
cd ../

cd $runtime_dir
tar -zxvf $soft_dir"/imagick-3.1.0RC2.tgz"
cd imagick-3.1.0RC2
/usr/local/php/bin/phpize
./configure --with-php-config=/usr/local/php/bin/php-config
make && make install
cd ../

# ------------- GraphicsMagick ---------------
cd $runtime_dir
tar -zxvf $soft_dir"/GraphicsMagick-1.3.18.tar.gz"
cd GraphicsMagick-1.3.18
CFLAGS="-O3 -fPIC" ./configure --enable-shared --enable-symbol-prefix
make && make install
cd ../


tar -zxvf $soft_dir"/gmagick-1.1.2RC1.tgz"
cd gmagick-1.1.2RC1
/usr/local/php/bin/phpize
./configure --with-php-config=/usr/local/php/bin/php-config --with-gmagick=/usr/local/gmagick/
make
make install
cd ../
# ------------- GraphicsMagick End ---------------

sed -i 's#;extension=php_zip.dll#;extension=php_zip.dll\n\nextension = "memcache.so"\nextension = "memcached.so"\nextension = "imagick.so"\nextension = "gmagick.so"\n#' /usr/local/php/etc/php.ini
sed -i 's/;date.timezone =/date.timezone = "Asia\/Shanghai"/g' /usr/local/php/etc/php.ini
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/g' /usr/local/php/etc/php.ini
# php error log
sed -i 's/;error_log = php_errors.log/error_log = \/usr\/local\/php\/var\/log\/php_errors.log/g' /usr/local/php/etc/php.ini

/sbin/ldconfig
# 添加web帐户
groupadd www
useradd -s /sbin/nologin -g www www

############# install Nginx start ################
cd $runtime_dir
tar -zxvf $soft_dir"/pcre-8.33.tar.gz"
cd pcre-8.33/
./configure
make && make install
cd ../

cd $runtime_dir
tar -zxvf $soft_dir"/nginx-1.4.2.tar.gz"
cd nginx-1.4.2/
./configure --user=www \
--group=www \
--prefix=/usr/local/nginx \
--with-http_stub_status_module \
--with-http_ssl_module \
--with-http_gzip_static_module \
--with-ipv6
make
make install
cd ../

# fix php-fpm bug
sed -i '1 i\if (!-f $request_filename){\n    return 404;\n}\n' /usr/local/nginx/conf/fastcgi.conf
# backup nginx.conf
if [ -s /usr/local/nginx/conf/nginx.conf ]; then
    mv /usr/local/nginx/conf/nginx.conf /usr/local/nginx/conf/nginx.conf.backup
else
	cp $cur_dir"/nginx.conf" /usr/local/nginx/
fi

mkdir -p /usr/local/nginx/conf/vhosts

if [ `getconf WORD_BIT` = '32' ] && [ `getconf LONG_BIT` = '64' ] ; then
    ln -s /usr/local/lib/libpcre.so.1 /lib64/
fi

# nginx and php-fpm and mysql to start
ulimit -s unlimited
cat >>/etc/rc.local<<EOF
ulimit -n 65535
/etc/init.d/php-fpm start
/usr/local/nginx/sbin/nginx
EOF

#chkconfig --level 345 php-fpm on
#chkconfig --level 345 nginx on
#chkconfig --level 345 mysql on

echo "Starting all service"
#chkconfig --level 345 mysql on
/etc/init.d/php-fpm start
/usr/local/nginx/sbin/nginx

clear
echo "===================================== Check install ==================================="
if [ -s /usr/local/nginx ]; then
  echo "/usr/local/nginx [found]"
  else
  echo "Error: /usr/local/nginx not found!!!"
fi

if [ -s /usr/local/php ]; then
  echo "/usr/local/php [found]"
  else
  echo "Error: /usr/local/php not found!!!"
fi

if [ -s /usr/local/mysql ]; then
  echo "/usr/local/mysql [found]"
  else
  echo "Error: /usr/local/mysql not found!!!"
fi

echo "========================================================================="
echo "Install completed!"
echo ""
echo "default mysql root password:$mysqlrootpwd"
echo "phpinfo : http://youdomain.com/phpinfo.php"
echo "phpMyAdmin : http://youdomain.com/tools/phpmyadmin/"
echo ""
echo "The path of some dirs:"
echo "mysql dir:   /usr/local/mysql"
echo "php dir:     /usr/local/php"
echo "nginx dir:   /usr/local/nginx"
echo "web dir :     /home/wwwroot"
echo ""
echo "nginx: /usr/local/nginx/sbin/nginx"
echo "php-fpm: /etc/init.d/php-fpm {start|stop|force-quit|restart|reload}"
echo "mysql: /etc/init.d/mysql {start|stop|restart|reload|force-reload|status}"
echo ""
echo "========================================================================="

