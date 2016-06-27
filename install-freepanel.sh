#!/usr/bin/env bash
set -u
PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:~/bin

CURRENT_DIR=$(pwd)
cd $(dirname "$0")
SCRIPT_DIR=$(pwd)

# FreePanel Automated Installation Script
FREEPANEL_VERSION="1.0"

DOWNLOAD_DIR="/tmp/freepanel"
MIRROR_URL="http://libs.arthas.info/freepanel"
MYSQL_FILENAME="mysql-5.7.12"
MYSQL_FILEHASH="af17ba16f1b21538c9de092651529f7c"
PHP_FILENAME="php-5.6.21"
PHP_FILEHASH="89047bc5219b95dcb47c045774893c4f"
NGINX_FILENAME="nginx-1.10.0"
NGINX_FILEHASH="c184c873d2798c5ba92be95ed1209c02"
HTTPD_FILENAME="httpd-2.4.20"
HTTPD_FILEHASH="e725c268624737a163dc844e28f720d1"
PUREFTPD_FILENAME="pure-ftpd-1.0.42"
PUREFTPD_FILEHASH="4195af8f0e5ee2a798b1014071dae3a3"

function CheckEnv()
{
	# first check if the user is "root" before allowing installation to commence
	if [ $UID -ne 0 ]; then
	    echo "Install failed! To install you must be logged in as 'root', please try again."
		exit 1
	fi
	
	BITS=$(uname -m | sed 's/x86_//;s/i[3-6]86/32/')
#BITS="32" && [ `getconf WORD_BIT` == "32" ] && [ `getconf LONG_BIT` == "64" ] && BITS="64"
	DOMAIN=`ifconfig | grep 'inet addr:' | egrep -v ":192.168|:127." | cut -d: -f2 | awk '{ print $1 }'`;
	if [ -f /etc/issue ]; then
	    OS=$(cat /etc/issue | cut -d" " -f1)
	    OS_VER=$(cat /etc/issue | cut -d" " -f3)
	elif [ -f /etc/lsb-release ]; then
	    OS=$(cat /etc/lsb-release | grep DISTRIB_ID | sed 's/^.*=//')
	    OS_VER=$(cat /etc/lsb-release | grep DISTRIB_RELEASE | sed 's/^.*=//')
	fi
	echo "Detected: $OS $OS_VER ${BITS}-bit"
	echo "Server $DOMAIN"
	if [ "$OS" = "Debian" ] || [ "$OS" = "Ubuntu" ]; then
		echo "Ok."
	else
		echo "Sorry, this installer not supports the installation of FreePanel on $OS."
		exit 1
	fi
}


function RunSelection()
{
    echo "Install/Uninstall FreePanel? Please select: (1~2)"
    select selected in "Install FreePanel" "Uninstall FreePanel"; do break; done;
    if [ "$selected" == "Install FreePanel" ]; then
        Install
    elif [ "$selected" == "Uninstall FreePanel" ]; then
        Uninstall
    else
        RunSelection
        return
    fi;
}


function Install()
{
    GeneratePassword
    echo "Install dependent packages"
    if [ "$OS" == "Debian" ] || [ "$OS" == "Ubuntu" ]; then
        apt-get update && apt-get -y upgrade
        apt-get -y install build-essential cmake libzip-dev libc6-dev bison libpcre3 libpcre3-dev libssl-dev openssl libxml2 libxml2-dev zlib1g zlib1g-dev libbz2-1.0 libglib2.0-0 libglib2.0-dev libcurl4-openssl-dev gettext openssl libncurses5-dev libgd-dev libmcrypt-dev libtool-bin
    fi
    InstallMysql
    InstallNginx
    InstallApache
    InstallPHP
    InstallPureFTPd
    InstallFreePanel
    PrintSuccess "Mysql Password: \033[36m${MYSQL_PASSWORD}\033[0m";
    PrintSuccess "Panel Password: \033[36m${FREEPANEL_PASSWORD}\033[0m";
}


function InstallMysql()
{
    MYSQL_INSTALL_DIR=/usr/local/mysql
    if [ "${1:-install}" == "uninstall" ]; then
        echo "Uninstalling MySQL"
        /etc/init.d/mysql stop
        pkill mysqld
        rm -rf ${MYSQL_INSTALL_DIR}
        rm -rf /etc/mysql
        rm /etc/my.cnf
        rm -rf /usr/share/mysql
        userdel mysql
        groupdel mysql
        return 0
    fi

    # /usr/local/mysql
    echo -e "\033[34m[+] Installing MySQL\033[0m"
    if [ ! -f ${MYSQL_INSTALL_DIR}/bin/mysql ]; then
        if [ ! -d $CURRENT_DIR/$MYSQL_FILENAME ]; then
            Download "$MYSQL_FILENAME" $MYSQL_FILEHASH
            ExtractFile "$DOWNLOAD_DIR/$MYSQL_FILENAME.tar.gz" $CURRENT_DIR
        fi

        if [ ! -d $CURRENT_DIR/boost_1_59_0 ]; then
            Download "http://libs.arthas.info/boost/1.59.0/boost_1_59_0.tar.gz" ""
            ExtractFile "$DOWNLOAD_DIR/boost_1_59_0.tar.gz" $CURRENT_DIR
        fi
        cd $CURRENT_DIR/mysql*
        cmake -DCMAKE_INSTALL_PREFIX=${MYSQL_INSTALL_DIR} -DDEFAULT_CHARSET=utf8 -DDEFAULT_COLLATION=utf8_general_ci -DWITH_EXTRA_CHARSETS=complex -DWITH_READLINE=1 -DENABLED_LOCAL_INFILE=1 -DWITH_BOOST=$CURRENT_DIR/boost_1_59_0
        make && make install || { echo 'Failed to install MySQL'; exit 1; }
        groupadd mysql
        useradd -r -g mysql -s /bin/false mysql
        cd ${MYSQL_INSTALL_DIR}
        chown -R mysql.mysql ${MYSQL_INSTALL_DIR}
        mkdir /etc/mysql
        cp ${MYSQL_INSTALL_DIR}/support-files/my-default.cnf /etc/mysql/my.cnf
        sed -i "s/skip-locking/skip-external-locking/g" /etc/mysql/my.cnf
        sed -i "s:#innodb:innodb:g" /etc/mysql/my.cnf
        mkdir ${MYSQL_INSTALL_DIR}/data
        ${MYSQL_INSTALL_DIR}/bin/mysqld --initialize-insecure --user=mysql --datadir=${MYSQL_INSTALL_DIR}/data
        chown -R root .
        chown -R mysql data
        ./bin/mysqld_safe --user=mysql &
        cp ${MYSQL_INSTALL_DIR}/support-files/mysql.server /etc/init.d/mysql
        cat > /etc/ld.so.conf.d/mysql.conf<<EOF
        ${MYSQL_INSTALL_DIR}/lib/mysql
        /usr/local/lib
EOF
        ldconfig
        ln -s ${MYSQL_INSTALL_DIR}/lib/mysql /usr/lib/mysql
        ln -sf ${MYSQL_INSTALL_DIR}/include/mysql /usr/include/mysql
        ln -sf ${MYSQL_INSTALL_DIR}/bin/mysql /usr/bin/mysql
        ln -sf ${MYSQL_INSTALL_DIR}/bin/mysqldump /usr/bin/mysqldump
        ln -sf ${MYSQL_INSTALL_DIR}/bin/myisamchk /usr/bin/myisamchk
        ln -sf ${MYSQL_INSTALL_DIR}/mysqld_safe /usr/bin/mysqld_safe
        /etc/init.d/mysql start
        ${MYSQL_INSTALL_DIR}/bin/mysqladmin -u root password "$MYSQL_PASSWORD"
        rm -rf ${MYSQL_INSTALL_DIR}/data/test
        mysql -hlocalhost -uroot -p$MYSQL_PASSWORD <<EOF
        USE mysql;
        DELETE FROM user WHERE User != 'root' OR (User = 'root' AND Host != 'localhost');
        DROP USER ''@'%';
        FLUSH PRIVILEGES;
EOF
        systemctl daemon-reload
        PrintSuccess "MySQL install completed."
    else
        PrintSuccess "MySQL installed."
    fi
}


function InstallPHP()
{
    PHP_INSTALL_DIR=/usr/local/php
    if [ "${1:-install}" == "uninstall" ]; then
        echo "Uninstalling PHP"
        rm -rf ${PHP_INSTALL_DIR}
        return 0
    fi

    echo -e "\033[34m[+] Installing $PHP_FILENAME\033[0m"
    if [ ! -d $PHP_INSTALL_DIR ]; then
        if [ ! -d $CURRENT_DIR/$PHP_FILENAME ]; then
	        Download "$PHP_FILENAME" $PHP_FILEHASH
	        ExtractFile "$DOWNLOAD_DIR/$PHP_FILENAME.tar.gz" $CURRENT_DIR
        fi;

        cd $CURRENT_DIR/$PHP_FILENAME
        ./buildconf --force
        ./configure --prefix=${PHP_INSTALL_DIR} --with-apxs2=${HTTPD_INSTALL_DIR}/bin/apxs --with-config-file-path=${PHP_INSTALL_DIR}/etc --with-mysql=/usr/local/mysql --with-mysqli=/usr/local/mysql/bin/mysql_config --with-pdo-mysql=/usr/local/mysql --with-iconv-dir --with-freetype-dir=/usr/local/freetype --with-jpeg-dir --with-png-dir --with-zlib --with-libxml-dir=/usr --enable-xml --enable-discard-path --enable-magic-quotes --enable-safe-mode --enable-bcmath --enable-shmop --enable-sysvsem --enable-inline-optimization --with-curl=/usr/local/curl --enable-mbregex --enable-fastcgi --enable-fpm --enable-force-cgi-redirect --enable-mbstring --with-mcrypt --enable-ftp --with-gd --enable-gd-native-ttf --with-openssl --with-mhash --enable-pcntl --enable-sockets --with-xmlrpc --enable-zip --enable-soap --with-gettext --with-mime-magic
        make ZEND_EXTRA_LIBS='-liconv' && make install || { echo 'Failed to install PHP'; exit 1; }
        libtool --finish $CURRENT_DIR/$PHP_FILENAME/libs
        cp php.ini-production ${PHP_INSTALL_DIR}/etc/php.ini
        # php extensions
        sed -i 's#extension_dir = "./"#extension_dir = "$PHP_INSTALL_DIR/lib/php/extensions/no-debug-non-zts-20131226/"\n#' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's#output_buffering = Off#output_buffering = On#' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's/post_max_size = 8M/post_max_size = 50M/g' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/g' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's/;date.timezone =/date.timezone = PRC/g' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's/short_open_tag = Off/short_open_tag = On/g' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's/; cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/g' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's/; cgi.fix_pathinfo=0/cgi.fix_pathinfo=0/g' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's/max_execution_time = 30/max_execution_time = 300/g' ${PHP_INSTALL_DIR}/etc/php.ini
        sed -i 's/disable_functions =.*/disable_functions = passthru,exec,system,chroot,scandir,chgrp,chown,shell_exec,proc_open,proc_get_status,ini_alter,ini_restore,dl,openlog,syslog,readlink,symlink,popepassthru,stream_socket_server,fsocket/g' ${PHP_INSTALL_DIR}/etc/php.ini
	    PrintSuccess "PHP install completed."
    else
	    PrintSuccess "PHP installed."
    fi
}


function InstallNginx()
{
    echo "Downloading Nginx"
    Download "$NGINX_FILENAME" $NGINX_FILEHASH
    echo "Installing Nginx"
}


function InstallPureFTPd()
{
    echo "Downloading Pure-FTPd"
    Download "$PUREFTPD_FILENAME" $PUREFTPD_FILEHASH
    echo "Installing Pure-FTPd"
}


function InstallApache()
{
    HTTPD_INSTALL_DIR=/usr/local/apache2
    if [ "${1:-install}" == "uninstall" ]; then
        echo "Uninstalling Apache"
        ${HTTPD_INSTALL_DIR}/bin/apachectl stop
        pkill httpd
        rm -rf ${HTTPD_INSTALL_DIR}
        rm /etc/init.d/apache
        userdel www
        groupdel www
        return 0
    fi

    echo -e "\033[34m[+] Installing Apache\033[0m"
    if [ ! -d ${HTTPD_INSTALL_DIR} ]; then
        if [ ! -d $CURRENT_DIR/$HTTPD_FILENAME ]; then
            Download "$HTTPD_FILENAME" $HTTPD_FILEHASH
	        Download "$HTTPD_FILENAME-deps" "336ea2ab3009708648b542c0cacdef8f"
	        ExtractFile "$DOWNLOAD_DIR/$HTTPD_FILENAME.tar.gz" $CURRENT_DIR
	        ExtractFile "$DOWNLOAD_DIR/$HTTPD_FILENAME-deps.tar.gz" $CURRENT_DIR
        fi

	    cd $CURRENT_DIR/$HTTPD_FILENAME
	    ./configure --prefix=${HTTPD_INSTALL_DIR} --enable-mods-shared=most --enable-headers --enable-mime-magic --enable-proxy --enable-so --enable-rewrite --with-ssl --enable-ssl --enable-deflate --with-pcre --with-included-apr --with-apr-util --enable-mpms-shared=all --with-mpm=prefork --enable-remoteip
	    make && make install || { echo 'Failed to install Apache'; exit 1; }
        libtool --finish ${HTTPD_INSTALL_DIR}/lib
        cp $CURRENT_DIR/etc/httpd.conf.in ${HTTPD_INSTALL_DIR}/conf/httpd.conf
        sed -i 's#@HTTPD_INSTALL_DIR@#'$HTTPD_INSTALL_DIR'#g' ${HTTPD_INSTALL_DIR}/conf/httpd.conf
        mkdir ${HTTPD_INSTALL_DIR}/conf/vhosts
        ln -s ${HTTPD_INSTALL_DIR}/bin/apachectl /etc/init.d/apache
        groupadd www
        useradd -r -g www -s /bin/false www
        mkdir -p /var/www/default
        mkdir /var/www/logs
        chown www:www -R /var/www
    else
	    PrintSuccess "Apache Installed."
    fi

}


function InstallFreePanel()
{
    FREEPANEL_INSTALL_DIR=/usr/local/freepanel
    if [ "${1:-install}" == "uninstall" ]; then
        echo "Uninstalling FreePanel"
        rm -rf ${FREEPANEL_INSTALL_DIR}
        return 0
    fi

    mkdir -p $FREEPANEL_INSTALL_DIR
    mkdir -p $FREEPANEL_INSTALL_DIR/web
    mkdir -p $FREEPANEL_INSTALL_DIR/logs
    mkdir -p $FREEPANEL_INSTALL_DIR/etc
    cd $SCRIPT_DIR
    ./configure --prefix=$FREEPANEL_INSTALL_DIR --with-mysql-dir=${MYSQL_INSTALL_DIR} --with-apache-dir=${HTTPD_INSTALL_DIR} --with-php-dir=${PHP_INSTALL_DIR}&& make && make install
    sed 's#@FREEPANEL_INSTALL_DIR@#'$FREEPANEL_INSTALL_DIR'#g' $SCRIPT_DIR/etc/httpd-freepanel.conf.in >$FREEPANEL_INSTALL_DIR/etc/httpd-freepanel.conf
    sed -i \
        -e 's|# INCLUDE_FREEPANEL_CONF|Include '$FREEPANEL_INSTALL_DIR'/etc/httpd-freepanel.conf|g' \
        -e 's|# INCLUDE_FREEPANEL_VHOSTS_CONF|IncludeOptional '$FREEPANEL_INSTALL_DIR'/etc/vhosts/*.conf|g' \
        ${HTTPD_INSTALL_DIR}/conf/httpd.conf
    cp -R $SCRIPT_DIR/web/. $FREEPANEL_INSTALL_DIR/web
    sed -i 's|@MYSQL_PASSWORD@|'${MYSQL_PASSWORD}'|g' ${FREEPANEL_INSTALL_DIR}/web/application/config/database.php
    chown www:www -R $FREEPANEL_INSTALL_DIR/web
}


function Uninstall()
{
    InstallMysql uninstall
    InstallApache uninstall
    InstallPHP uninstall
    InstallFreePanel uninstall
}


function GeneratePassword()
{
    HASH=$(echo -n "$DOMAIN" | md5sum | sed "s/ .*//")
    MYSQL_PASSWORD=$(echo -n "$HASH" | cut -b -8)
    FREEPANEL_PASSWORD=$(echo -n "$HASH" | cut -b 9-16)
    echo -e "Mysql Password: \033[36m${MYSQL_PASSWORD}\033[0m";
    echo -e "Panel Password: \033[36m${FREEPANEL_PASSWORD}\033[0m";
}


# $1 the file name or a url
# $2 file hash, can be null
function Download()
{
    if [[ $1 == http://* ]] || [[ $1 == https://* ]]; then
        url=$(echo ${1%/*})
        filename=$(echo ${1##*/})
    else
        url=$MIRROR_URL
        filename="$1.tar.gz"
    fi
    if [ -d ${DOWNLOAD_DIR} ]; then
        if [ -f "${DOWNLOAD_DIR}/${filename}" ] && [ -n "$2" ]; then
            hash=$(md5sum "${DOWNLOAD_DIR}/${filename}" | sed "s/ .*//")
            if [ $2 == $hash ]; then
                return 0
            else
                rm ${DOWNLOAD_DIR}/${filename}
            fi
        fi
    else
        mkdir -p ${DOWNLOAD_DIR}
    fi

    if wget -c --tries=3 $url/$filename -O"${DOWNLOAD_DIR}/${filename}"; then
        echo "Ok."
    else
        echo -e "\033[31m Could not download file from $1 \033[0m"
    fi

}


function ExtractFile()
{
    if [[ "$1" == *tar.gz ]]; then
        tar -zxf $1 -C $2
    else
        echo -e "\033[45;37mNot supported extract file type: $1\033[0m"
    fi
}


function PrintSuccess()
{
    echo -e "\033[32m[OK] $1\033[0m"
}


clear
echo "+--------------------------------------------------------------+"
echo "|                       FreePanel 1.0                          |"
echo "| Please make sure you provider hadn't pre-installed any       |"
echo "| packages required by FreePanel.                              |"
echo "+--------------------------------------------------------------+"
echo "| If you are installing on a physical machine where the OS     |"
echo "| has been installed by yourself pelase make sure you only     |"
echo "| installed with no extra packages.                            |"
echo "+--------------------------------------------------------------+"

CheckEnv
RunSelection

