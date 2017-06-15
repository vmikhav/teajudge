Read this article in other languages : [Українська](README.uk_UA.md), [Русский](README.ru_RU.md)

**TeaJudge** - self-hosted web environment for programming skills assessment for universities, colleges and programming courses. It comes with these features:
- Self-hosted
- Answer template with non-editable blocks
- Marking task conditions with LaTeX and Markdown
- Grouping tasks in courses
- Specify the time of opening and closing of the course and duration of reports conservation
- Limit time and memory
- Masurements of time and memory
- Import users from csv
- Collect and export of statistics
- Auto-generated user pictogram
- Playback of answer input (coming soon)

Supported programming language:
- C/C++
- Python3

Supported interface language : English, Українська, Русский

### Prerequirement
- Debian / Ubuntu
- http-server (apache2, nginx, lighttpd, etc.)
- mysql-server
- PHP 5.6 or higher with extension : mysql, mbstring
- build-essential
- python3-dev

### Installation and Usage
1. Clone repo to \<your_site_directory\>/teajudge (for example */var/www/html/teajudge*)
1. Exec `sudo bash ./install.sh`
1. Log in as admin : admin
1. Import accounts of students
1. Create your own course
1. Enjoy)

### Dependencies
See [here](DEPENDENCIES.md)

### License
TeaJudge is freely distributable under the terms of the [MIT license](LICENSE).