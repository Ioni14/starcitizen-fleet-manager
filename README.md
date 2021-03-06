# Star Citizen Fleet Manager #

## Installation for development ##

**Requirements**

- git
- docker
- docker-compose

**Clone repository**

```
git clone https://github.com/Ioni14/starcitizen-fleet-manager.git
cd starcitizen-fleet-manager
```

**Customize environment variables**

```
echo "APP_ENV=dev" > .env.local
```

**Customize docker-compose.override.yml**

    cp docker-compose.override.yml.dist docker-compose.override.yml

Customize the ports according to your needs, configure your dev reverse-proxy, etc.

**Launch the stack (build & up containers)**

```
make install
```

**Launch all tests**
```
make -j8 tests
```
