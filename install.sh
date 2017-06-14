#!/bin/bash

printf "\nChecking if some web server is running...\n"
NGINX_IS_RUNNING=$(sudo netstat -ntlp | grep /nginx)
APACHE_IS_RUNNING="$(sudo netstat -ntlp | grep /apache2)"
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

printf "Cheking if mysql-server is installed...\n"
MYSQL_IS_INSTALLED=$(dpkg-query --list | grep "ii[[:space:]]\{2\}mysql-server")

if [ ${#MYSQL_IS_INSTALLED} -eq 0 ]; then
  dialog --yesno "mysql-server isn't install in your machine. Are you want to install it?" 7 50
  USER_ANSWER=$?
  echo $USER_ANSWER
  if [ $USER_ANSWER == "0" ]; then
    dialog --passwordbox "Enter root password:" 8 40 2>answer
    MYSQL_PASSWORD=$(<answer)
    echo "mysql-server mysql-server/root_password password ${MYSQL_PASSWORD}" | sudo debconf-set-selections
    echo "mysql-server mysql-server/root_password_again password ${MYSQL_PASSWORD}" | sudo debconf-set-selections
    if apt-get -y install mysql-server; then
      printf "\nmysql-server successfully installed\n\n"
      MYSQL_HOST="localhost"
      MYSQL_ADMIN="root"
    else
      printf "\nError installing mysql-server\n\n"
      rm answer
      exit
    fi
  else
    dialog --inputbox "Enter mysql host:" 8 40 2>answer
    MYSQL_HOST=$(<answer)
    dialog --inputbox "Enter mysql admin login:" 8 40 2>answer
    MYSQL_ADMIN=$(<answer)
    dialog --passwordbox "Enter mysql admin password:" 8 40 2>answer
    MYSQL_PASSWORD=$(<answer)
  fi
else
  MYSQL_HOST="localhost"
  dialog --inputbox "Enter mysql admin login:" 8 40 2>answer
  MYSQL_ADMIN=$(<answer)
  dialog --passwordbox "Enter mysql admin password:" 8 40 2>answer
  MYSQL_PASSWORD=$(<answer)
fi

#printf "MYSQL_HOST = ${MYSQL_HOST}\n"
#printf "MYSQL_ADMIN = ${MYSQL_ADMIN}\n"
#printf "MYSQL_PASSWORD = ${MYSQL_PASSWORD}\n"

dialog --menu "Select prefered language:" 10 30 3 en_US English uk_UA Ukrainian ru_RU Russian 2>answer
LANG=$(<answer)
#printf "LANG=${LANG}\n"

printf "Importing teajudge database...\n"
mysql -h "${MYSQL_HOST}" -u "${MYSQL_ADMIN}" "-p${MYSQL_PASSWORD}" < "teajudge.sql"
printf "OK\n\n"

printf "Creating teajudge user...\n"
TG_PASSWORD=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
#printf "TG_PASSWORD = ${TG_PASSWORD}\n"
echo "CREATE USER 'teajudge'@'localhost' IDENTIFIED BY '${TG_PASSWORD}';" > answer
echo "GRANT SELECT, INSERT, UPDATE, DELETE ON teajudge.* TO 'teajudge'@'localhost';" >> answer
echo "FLUSH PRIVILEGES;" >> answer
mysql -h "${MYSQL_HOST}" -u "${MYSQL_ADMIN}" "-p${MYSQL_PASSWORD}" < "answer"
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
