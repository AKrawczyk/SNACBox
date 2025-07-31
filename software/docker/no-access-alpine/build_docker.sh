# openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout nginx-selfsigned.key -out nginx-selfsigned.crt -subj "/CN=*.web-rated.ie"
# px5g selfsigned -days 365 -newkey rsa:2048 -keyout nginx-selfsigned.key -out nginx-selfsigned.crt -subj "/CN=*.web-rated.ie"
docker build -t webbound:v1.0 .
