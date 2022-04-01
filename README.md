
# TP_WebServices_Auth

Une API Symfony sur l'authentification.


## Run Locally

Clone the project

```bash
  git clone https://github.com/hochdyl/TP_WebServices_Auth.git
```

Go to the project directory

```bash
  cd TP_WebServices_Auth
```

Install dependencies with [Composer](https://getcomposer.org/)

```bash
  composer install
```

Change `.env` file with your database informations like

```bash
  DATABASE_URL="mysql://root:@127.0.0.1:3306/tp_webservices_auth?serverVersion=5.7&charset=utf8mb4"
```

Install the project with the following lines

```bash
  php bin/console d:d:c

  php bin/console d:m:m

  php bin/console d:f:l
```

You will get an admin account credentials printed in your console to start testing the API.

Run the server

```bash
  symfony serve
```
## Usage/Examples

Add `Authorization` in request header with a Bearer token in value.

```bash
  Bearer 9ad3d3450061fd029c1df3257ca80dc5f5efe26f61d1e3974d4b8fa65359392d8ebd618e628d9a5170fd84ac84ec56462de6f88e23541c63df091d4777ba9049
```

## API endpoints

```text
    POST - /api/refresh-token/{refreshToken}/token
    POST - /api/token
    GET  - /api/validate/{accessToken}
    POST - /api/account
    GET  - /api/account/{uid}
    PUT  - /api/account/{uid}
```