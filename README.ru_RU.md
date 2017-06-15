Читайте эту статью на других языках : [English](README.md), [Українська](README.uk_UA.md)

**TeaJudge** - self-hosted веб-среда для проверки навыков программирования для университетов и курсов с программирования. Она обладает следующими возможностями:
- Шаблоны ответов с нередактируемыми участками
- Разметка условий с использованием LaTeX и Markdown
- Объединение задач в курсы
- Указание времени открытия и закрытия курсов
- Ограничения времени и памяти
- Импорт пользователей с csv
- Сбор и экспорт статистики
- Воспроизведение ввода отвера (в разработке)
- Поддерживаемые языки программирования : C/C++, Python3
- Поддерживаемые языки интерфейса : English, Українська, Русский

### Prerequirement
- Debian / Ubuntu
- http-server (apache2, nginx, lighttpd, etc.)
- mysql-server

### Установка и Использование
1. Скачайте [teajudge_0.9-1_all.deb](https://packagecloud.io/sungmaster/teajudge/packages/debian/jessie/teajudge_0.9-1_all.deb)
1. Выполните
  ```bash
  sudo dpkg -i teajudge_0.9-1_all.deb
  sudo apt-get -f install
  ```
или
1. Клонируйте репозиторий в \<your_site_directory\>/teajudge (например */var/www/html/teajudge*)
1. Выполните `sudo bash ./install.sh`

затем
- Зайдите как admin : admin
- Импортируйте пользователей
- Создайте собственный курс
- Наслаждайтесь)

### Зависимости
Смотрите [здесь](DEPENDENCIES.md)

### Лицензия
TeaJudge распространяется под условиями [MIT license](LICENSE).