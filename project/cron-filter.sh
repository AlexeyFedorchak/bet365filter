while [ true ]
do
	php /var/www/html/artisan filter:odds:live --verbose --no-interaction &
	sleep 15
done