# Chatbot

## Levantar el proyecto
El proyecto se puede levantar con Laravel Herd en local

Pero para hacer pruebas con Facebook Developers debemos levantar el proyecto de la siguiente forma:

**Powershell**
```bash
php -S 127.0.0.1:8001 -t public
```

**Crear un ngrok**

Esto nos sirve para poder hacer pruebas con la api desde Facebook Developers
```bash
ngrok http 8001
```

En este enlace podras ver tus pruebas una vez levantado ngrok 
```cmd
Web Interface           http://127.0.0.1:4040
```
