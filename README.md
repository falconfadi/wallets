##	Prerequisites
•	PHP 8.2+
•	Composer
•	MySQL 8.0+
•	Git
##	Installation & Setup Steps
To build system, please follow these instructions:
Run these commands:
git clone https://github.com/falconfadi/wallets.git
cd wallets
composer install
Copy .env.example file and name it .env
Edit .env file with your database credentials:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wallet_service
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=array

Then run this commands:
php artisan key:generate

php artisan migrate --seed

php artisan serve
