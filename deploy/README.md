# OrangeHRM by Maestrano
This version of OrangeHRM is customized to provide Single Sing-On and Connec!™ data sharing. By default, these options are not enabled so an instance of the application can be launched in a Docker container and be run as-is.
More information on [Maestrano SSO](https://maestrano.com) and [Connec!™ data sharing](https://maestrano.com/connec)

## Build Docker container with default OrangeHRM installation
`sudo docker build .`

## Activate Maestrano customisation on start (SSO and Connec!™ data sharing)
This is achieved by specifying Maestrano environment variables

```bash
docker run -it \
  -e "MNO_SSO_ENABLED=true" \
  -e "MNO_CONNEC_ENABLED=true" \
  -e "MNO_MAESTRANO_ENVIRONMENT=local" \
  -e "MNO_SERVER_HOSTNAME=orangehrm.app.dev.maestrano.io" \
  -e "MNO_API_KEY=e876260b50146136ec393b662edc6d91e453a0dbae1facad335b33fb763ead99" \
  -e "MNO_API_SECRET=9309cffc-2cb2-4423-92ea-e1ff64894241" \
  -e "MNO_APPLICATION_VERSION=mno-develop" \
  -e "MNO_POWER_UNITS=4" \
  --add-host application.maestrano.io:172.17.42.1 \
  --add-host connec.maestrano.io:172.17.42.1 \
  maestrano/orangehrm:latest
 ```

## Docker Hub
The image can be pulled down from [Docker Hub](https://registry.hub.docker.com/u/maestrano/orangehrm/)
**maestrano/orangehrm:stable**: Production version

**maestrano/orangehrm:latest**: Develomment version
