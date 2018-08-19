# timesheet-manager
Add projects/tasks and their working times and generate reports from it

The main goal of this project is to create a simple but effective and useful web application to record working time. It can use any freelancers for their time and material based projects. It could be a multi user system with more complex functions but I currently focus on a simple single use case. The backend is Slim PHP Framework + mysql database and PHP Unit Tests the frontend is VUE js framework. See more on the wiki pages. 


How to use:

I tested with PHP 7.0 and MySql 5.7.12

1. setup database, create database and schema, see db folder
2. install the first test user, see db folder first_user.sql (password: test)
3. install project dependencies using composer install

With docker:

- setup your environment: see docker-compose.yml and docker/nginx/nginx.conf file
- run from the project root the following command: docker-compose up -d

Without docker:

- cd public folder and run the following command: DB_HOST="127.0.0.1" DB_USER="<your db user>" DB_PASS="<your db password>" DB_NAME="timesheet_manager" php -d variables_order=EGPCS -S localhost:8000
- open browser and type http://localhost:8000/

If you modify the frontend code, install dpendencies using npm and after build with npm run build command

Releases

0.9 Base
- user login (single worker role)
- ability to 
  - add project
  - add working time or delete today working time
  - ability to list recorded times
  - ability to attach invoices
  - ability to export PDF reports in 3 languages (English, German, Hungarian)
