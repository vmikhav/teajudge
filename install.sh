#!/bin/bash

COLOR='\033[0;36m'
NC='\033[0m'

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
  printf "${COLOR}Seems none web servers aren't running on your machine\n"
  printf "Install Nginx, Apache or Lighttpd and try again${NC}\n\n"
  exit 3
fi
printf "OK\n\n";

MYSQL_IS_INSTALLED=$(dpkg-query --list | grep "ii[[:space:]]\{2\}mysql-server")

if [ ${#MYSQL_IS_INSTALLED} -eq 0 ]; then
  printf "\n"
  printf "${COLOR}Please, exec the following commands:\n"
  printf "\tsudo apt install mysql-server\n"
  printf "\tsudo mysql_secure_installation (set root password)\n"
  printf "\nand repeat attempt${NC}\n"
  printf "\n"
  exit 4
fi 

if sudo apt-get install -y git phpmyadmin build-essential python3-dev dialog; then
  printf "\nOK\n\n"
else
  printf "\n${COLOR}Error installing${NC}\n\n"
  exit 5
fi

MYSQL_HOST="localhost"

dialog --backtitle "TeaJudge installation" \
--menu "Select prefered language:" 10 30 3 en_US English uk_UA Ukrainian ru_RU Russian 2>answer
LANG=$(<answer)

dialog --backtitle "TeaJudge installation" \
--inputbox "Enter mysql admin login:" 8 40 root 2>answer
MYSQL_ADMIN=$(<answer)

dialog --backtitle "TeaJudge installation" \
--insecure --passwordbox "Enter mysql admin password:" 8 40 2>answer
MYSQL_PASSWORD=$(<answer)

USER_EXIST=="$(mysql -u ${MYSQL_ADMIN} -p${MYSQL_PASSWORD} -sse "SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = 'tjuser')")"
if [ ${USER_EXIST} = 1 ]; then
  echo "Database already exist"
else
  printf "Importing teajudge database...\n"
  IMPORTRES=$(sudo mysql -u "${MYSQL_ADMIN}" "-p${MYSQL_PASSWORD}" < "./teajudge.sql")
  printf "OK\n\n"

  printf "Creating teajudge user...\n"
  TG_PASSWORD=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 10 | head -n 1)
  #printf "TG_PASSWORD = ${TG_PASSWORD}\n"
  sudo mysql -u "${MYSQL_ADMIN}" "-p${MYSQL_PASSWORD}" -e "CREATE USER tjuser@localhost IDENTIFIED BY '${TG_PASSWORD}';"
  sudo mysql -u "${MYSQL_ADMIN}" "-p${MYSQL_PASSWORD}" -e "GRANT SELECT, UPDATE, INSERT, DELETE ON teajudge.* TO tjuser@localhost;"
  sudo mysql -u "${MYSQL_ADMIN}" "-p${MYSQL_PASSWORD}" -e "FLUSH PRIVILEGES;"
  rm answer

  sed -i "s/define('DBHOST', '.*')/define('DBHOST', '${MYSQL_HOST}')/" ./php/sensetive_data.php
  sed -i "s/define('DBUSER', '.*')/define('DBUSER', 'tjuser')/" ./php/sensetive_data.php
  sed -i "s/define('DBPASS', '.*')/define('DBPASS', '${TG_PASSWORD}')/" ./php/sensetive_data.php
  printf "OK\n\n"
fi

TG_SECRET=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
sed -i "s/define('TJSECRET', '.*')/define('TJSECRET', '${TG_SECRET}')/" ./php/sensetive_data.php
sed -i "s/define('LANG', '.*')/define('LANG', '${LANG}')/" ./php/sensetive_data.php 

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
