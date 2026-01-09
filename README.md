# Owntracks Frontend + Nginx + PHP

This is a custom docker image that does a lot of the work that Owntracks quicksetup project does, leaving out the Let's Encrypt and Mosquitto broker that is created by Owntracks' quicksetup.

The intention is for this to be a companion Docker image for the Owntracks Recorder, where you have your own separate Mosquitto message broker, and an external Reverse Proxy that terminates SSL for connections to your recorder instance so you don't need Let's Encrypt certificates.

Therefore the Mosquitto and Let's Encrypt configurations are intentionally left out and the Frontend here is hosted on standard HTTP port 80 or wherever you'd like.

## What to expect

    - Owntracks Frontend SPA built and installed at /usr/share/nginx/html/owntracks
    - index.php and otrc.php installed in webroot /usr/share/nginx/html
    - Nginx instance hosting the php front-end for otrc files and links to the map, and recorder pages
    - NO MQTT Broker, NO Let's encrypt. Frontend intended to listen on port 80 but that can be changed in env vars.

## File Descriptions

These are the files needed to build the image, make sure to modify the necessary files with your values.

### Files to Modify

- **config.js** - this is the configuration for the frontend site. The baseUrl must be a FQDN as this is where the CORS origin is set. Update the baseUrl with your own domain name.
- **nginx.conf** - The default nginx.conf. Modify with your cookie key you generate using `openssl rand -hex 24`. Ensure the key is used in owntracks.conf.
- **owntracks.conf** - The Nginx site configuration for Owntracks Frontend. Update the cookie otauth with the same key created for **nginx.conf**. Change the recorder URL to fit your Owntracks's Recorder http URL.

### Other Files Needed for Build

- **index.php** - The landing page for the Frontend, allows download of otrc files and provides links to the Frontend and legacy recorder pages.
- **Dockerfile** - build the Owntracks Frontend image using this file.
- **logo-owntracks-grayscale-96x96.jpg** - Logo for the frontend php landing page.
- **otrc.php** - The php downloader for user otrc device files.
- **php-fpm.conf** - PHP daemon configuration.
- **supervisord.conf** - The daemon configuration running php and ngnix.
- **www.conf** - PHP/nginx configuration.

## Directories

The directories used in this image mirror the defaults from the quicksetup ansible automated configuration:

- otdir = /usr/local/owntracks
- userdata = /usr/local/owntracks/userdata
- webroot = /usr/share/nginx/html/
- otweb = /usr/share/nginx/html/owntracks
- nginxdir = /etc/nginx

## Docker Build

1. Clone the repository.
2. Generate a cookie value using `openssl rand -hex 12` or something similar. Keep it small or nginx complains.
3. Use the cookie string in changing the required files (see [File Descriptions](#File%20Descriptions)).
4. Build the image `docker build -t otweb:latest .` from your local repository folder.
5. Create persistent storage folder for userdata.
6. Create htpasswd and user.pass files and store them in persistent userdata storage (userdata folder goes in root of build)
    - user.pass files can be created with `docker run --rm python:3-alpine python -c 'import secrets; print(secrets.token_urlsafe(24))'`
    - See your MQTT documentation for creating mosquitto users.
    - htpasswd file can be created using htpasswd in apache2-utils
7. Put all these files in the root of your build with the Dockerfile and the rest of the repository.
8. Build the image using `docker -t otweb:latest .`
9. Pull and run the image using your preferred method.
10. Check for the front-end on http://owntracks.example.com
11. Proceed to use your external reverse proxy to terminate SSL, and upgrade the Websocket connection if you'd like. You can use HTTP method for connecting from your devices, but Owntracks recommends that if you're running in a container that it makes mangaging friends more difficult. However I don't find that to be the case if you're used to managing the files in the userdata folder. I'll update this step when I get used to managing friends and trying HTTPS/Websockets to publish locations.

## Docker Compose

Here is an example docker compose file to run the frontend.

``` yaml
services:
  otrecorder:
    container_name: otrecorder
    image: owntracks/recorder:latest
    restart: always
    ports:
      - 172.30.1.2:8083:8083
    env_file:
      - stack.env
    extra_hosts:
      - "mqtt.example.com" # private network to mqtt/tls tcp/8883, change env vars for ws or unencrypted
    volumes:
      - type: volume
        source: data
        target: /store
        volume:
          subpath: store
      - type: volume
        source: data
        target: /certs
        volume:
          subpath: certs
      - type: volume
        source: data
        target: /config
        volume:
          subpath: config
  otfrontend:
    image: otweb:latest
    container_name: otfrontend
    depends_on:
      - otrecorder
    ports:
      - 172.30.1.2:80:80
    volumes:
      - type: volume
        source: data
        target: /usr/local/owntracks/userdata
        volume:
          subpath: userdata
    env_file:
      - stack.env
    restart: always
volumes:
  data:
```

And the stack.env vars example, or use environment in each container. OTR_ variable prefixes are for the recorder, the rest are for the frontend.

```
OTR_STORAGEDIR=/store
OTR_HOST=mqtt.example.com
OTR_PORT=8883
OTR_USER=owntracks
OTR_PASS=owntracks_mqtt_password
OTR_QOS=2
OTR_CLIENTID=otrecorder
OTR_GEOKEY=opencage:opencage-secret-api-key
OTR_PRECISION=7
OTR_TOPICS=owntracks/#
OTR_CAFILE=/certs/ca.pem
OTR_SERVERLABEL=My Owntracks
OTR_HTTPPORT=8083
OTR_HTTPLOGDIR=/var/log/owntracks
OTR_HTTPPREFIX=https://owntracks.example.com/owntracks
SERVER_HOST=otrecorder
SERVER_PORT=8083
OT_BASIC_AUTH_USERNAME=user
OT_BASIC_AUTH_PASSWORD=passwd_from_htpasswd
LISTEN_PORT=80
```

