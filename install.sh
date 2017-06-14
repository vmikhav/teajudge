#!/bin/bash

printf "\nCheking if net-tools is installed...\n"
NETTOOLS_IS_INSTALLED=$(dpkg-query --list | grep "ii[[:space:]]\{2\}net-tools")

if [ ${#NETTOOLS_IS_INSTALLED} -eq 0 ]; then
  printf "net-tools isn't installed\ninstalling...\n\n"

  if sudo apt-get install -y net-tools; then
    printf "\nnet-tools successfully installed\n\n"
  else
    printf "\nError installing net-tools\n\n"
    exit
  fi
else
  printf "OK\n\n"
fi

printf "Checking LAMP stack...\n\n"
printf "Checking if some web server is running...\n"
NGINX_IS_RUNNING=$(sudo netstat -ntlp | grep /nginx)
APACHE_IS_RUNNING=$(sudo netstat -ntlp | grep /apache2)
LIGHTTP_IS_RUNNING=$(sudo netstat -ntlp | grep /lighttpd)

if [ ${#NGINX_IS_RUNNING} -eq 0  ] && [ ${#APACHE_IS_RUNNING} -eq 0 ] && [ ${#LIGHTTP_IS_RUNNING} -eq 0 ]; then
  printf "Seems none web servers aren't running on your machine\n"
  printf "Install Nginx, Apache or Lighttpd, install and configure PHP and try again\n\n"
  exit
fi
printf "OK\n\n";

printf "Cheking if php is installed...\n"
PHP_IS_INSTALLED=$(dpkg-query --list | grep php)

if [ ${#PHP_IS_INSTALLED} -eq 0 ]; then
  printf "Seems PHP isn't installed on your machine\n"
  printf "Install PHP, configure it with your web server and try again\n\n"
  exit
fi
printf "OK\n\n";

printf "Checking if required php packages are installed...\n\n"

for php_package in "json" "mysql"
do
  printf "Cheking if php-${php_package} is installed...\n"
  PACKAGE_IS_INSTALLED=$(dpkg-query --list | grep "ii[[:space:]]\{2\}php..${php_package}")

  if [ ${#PACKAGE_IS_INSTALLED} -eq 0 ]; then
    printf "Seems php-${php_package} isn't installed on your machine\n"
    printf "Install it and try again\n\n"
    exit
  else
    printf "OK\n\n"
  fi
done

printf "Cheking if mysql-server is installed...\n"
MYSQL_IS_INSTALLED=$(dpkg-query --list | grep "ii[[:space:]]\{2\}mysql-server")

if [ ${#MYSQL_IS_INSTALLED} -eq 0 ]; then
  dialog --yesno "mysql-server isn't install in your machine. Are you want to user remote host?" 7 50
  USER_ANSWER=$?
  echo $USER_ANSWER
  if [ $USER_ANSWER == "0" ]; then
    dialog --inputbox "Enter mysql host:" 8 40 2>answer
    MYSQL_HOST=$(<answer)
    dialog --inputbox "Enter mysql login:" 8 40 2>answer
    MYSQL_ADMIN=$(<answer)
    dialog --passwordbox "Enter mysql password:" 8 40 2>answer
    MYSQL_PASSWORD=$(<answer)
  else
    printf "MySQL database required for TeaJudge\n"
    printf "Install it and try again\n\n"
    exit
  fi
else
  printf "OK\n\n"
  MYSQL_HOST="localhost"
  MYSQL_ADMIN="root"
  dialog --passwordbox "Enter mysql root password:" 8 40 2>answer
  MYSQL_PASSWORD=$(<answer)
fi

printf "MYSQL_HOST = ${MYSQL_HOST}\n"
printf "MYSQL_ADMIN = ${MYSQL_ADMIN}\n"
printf "MYSQL_PASSWORD = ${MYSQL_PASSWORD}\n"

printf "LAMP stack successfully checked\n\n"

for package in "build-essential" "python3-dev" "dialog"
do
  printf "Cheking if ${package} is installed...\n"
  IS_INSTALLED=$(dpkg-query --list | grep "ii[[:space:]]\{2\}${package}")

  if [ ${#IS_INSTALLED} -eq 0 ]; then
    printf "${package} isn't installed\ninstalling...\n\n"

    if sudo apt-get install -y ${package}; then
      printf "\n${package} successfully installed\n\n"
    else
      printf "\nError installing ${package}\n\n"
      exit
    fi
  else
    printf "OK\n\n"
  fi
done

dialog --menu "Select prefered language:" 10 30 3 en_US English uk_UA Ukrainian ru_RU Russian 2>answer
LANG=$(<answer)
#printf "LANG=${LANG}\n"

printf "Importing teajudge database...\n"
sudo mysql -h "${MYSQL_HOST}" -u "${MYSQL_ADMIN}" "-p${MYSQL_PASSWORD}" < "teajudge.sql"
printf "OK\n\n"

printf "Creating teajudge user...\n"
TG_PASSWORD=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
#printf "TG_PASSWORD = ${TG_PASSWORD}\n"
echo "CREATE USER 'teajudge'@'localhost' IDENTIFIED BY '${TG_PASSWORD}';" > answer
echo "GRANT SELECT, INSERT, UPDATE, DELETE ON teajudge.* TO 'teajudge'@'localhost';" >> answer
echo "FLUSH PRIVILEGES;" >> answer
sudo mysql -h "${MYSQL_HOST}" -u "${MYSQL_ADMIN}" "-p${MYSQL_PASSWORD}" < "answer"
rm answer
printf "OK\n\n"

printf "Saving credentials...\n"
TG_SECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
sed -i "s/define('TJSECRET', '.*')/define('TJSECRET', '${TG_SECRET}')/" ./php/sensetive_data.php
sed -i "s/define('DBHOST', '.*')/define('DBHOST', '${MYSQL_HOST}')/" ./php/sensetive_data.php
sed -i "s/define('DBUSER', '.*')/define('DBUSER', 'teajudge')/" ./php/sensetive_data.php
sed -i "s/define('DBPASS', '.*')/define('DBPASS', '${TG_PASSWORD}')/" ./php/sensetive_data.php
sed -i "s/define('LANG', '.*')/define('LANG', '${LANG}')/" ./php/sensetive_data.php
printf "OK\n\n"

printf "Building sandbox...\n"
cd ./sandbox/libsandbox
sudo chmod +x configure
./configure
sudo make install prefix=/usr libdir=/usr/lib
cd ../pysandbox
python3 setup.py build
sudo python3 setup.py install
printf "OK\n\n"

printf "TeaJudge successfully installed\n\n"
