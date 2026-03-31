# Apache Notes

COINGROW already ships with Laravel's standard rewrite rules in [`public/.htaccess`](../../public/.htaccess).

To use Apache in production:

1. Enable `mod_rewrite`
2. Point your vhost `DocumentRoot` to `public/`
3. Copy or adapt `deploy/apache/coingrow-vhost.conf`
4. Reload Apache
