Read this article in other languages : [Українська](README.uk_UA.md), [Русский](README.ru_RU.md)

**TeaJudge** - self-hosted web environment for programming skills assessment for universities, colleges and programming courses. It comes with these features:
- Playback of answer input
- Comparison of answers
- Answer template with non-editable blocks
- Marking task conditions with LaTeX and Markdown
- Grouping tasks in courses
- Specify the time of opening and closing of the course and duration of reports conservation
- Limit time and memory
- Masurements of time and memory
- Import users from csv
- Collect and export of statistics
- Supported programming language : C/C++, Python3
- Supported interface language : English, Українська, Русский

### Prerequirement
- Debian / Ubuntu
- http-server (apache2, nginx, lighttpd, etc.)
- mysql-server

### Installation and Usage
1. Download [teajudge_1.0-1_all.deb](https://packagecloud.io/sungmaster/teajudge/packages/debian/stretch/teajudge_1.0-1_all.deb)
1. Exec
  ```bash
  sudo dpkg -i teajudge_1.0-1_all.deb
  sudo apt-get -f install
  ```
or
1. Clone repo to \<your_site_directory\>/teajudge (for example */var/www/html/teajudge*)
1. Exec `sudo bash ./install.sh`

then
- Log in as admin : admin
- Import accounts of students
- Create your own course
- Enjoy)

### Dependencies
See [here](DEPENDENCIES.md)

### License
TeaJudge is freely distributable under the terms of the [MIT license](LICENSE).