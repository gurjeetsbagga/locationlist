
### Software Version:
- Drupal: 10.3.2
- PHP Version: 8.3.10

### Download, extract and run the Drupal package:
- curl -sS https://ftp.drupal.org/files/projects/drupal-10.3.2.zip --output drupal-10.3.2.zip
- tar xvfz drupal-10.3.2.zip
- cd /path/to/drupal-10.3.2
- cp -R modules /path/to/drupal-10.3.2/
- php core/scripts/drupal quick-start
- open link http://127.0.0.1:8888/location-finder

### Add Input
  - **Country code:** DE
  - **City:** Bonn
  - **Postal code:** 53113

### Below will be the output in yaml format:
  -- even number in street address
  -- open in weekends

```shell
  locationName: 'Postfiliale 502'
  address:
  countryCode: DE
  postalCode: '53113'
  addressLocality: Bonn
  streetAddress: 'Charles-de-Gaulle-Str. 20'
  openingHours:
  Monday: '08:00:00-17:00:00'
  Tuesday: '08:00:00-17:00:00'
  Wednesday: '08:00:00-17:00:00'
  Thursday: '08:00:00-17:00:00'
  Friday: '08:00:00-17:00:00'
  Saturday: '10:00:00-12:00:00'

  ---
locationName: 'Postfiliale 502'
address:
    countryCode: DE
    postalCode: '53113'
    addressLocality: Bonn
    streetAddress: 'Charles-de-Gaulle-Str. 20'
openingHours:
    Monday: '08:00:00-17:00:00'
    Tuesday: '08:00:00-17:00:00'
    Wednesday: '08:00:00-17:00:00'
    Thursday: '08:00:00-17:00:00'
    Friday: '08:00:00-17:00:00'
    Saturday: '10:00:00-12:00:00'

---
locationName: 'Packstation 145'
address:
    countryCode: DE
    postalCode: '53113'
    addressLocality: Bonn
    streetAddress: 'Charles-de-Gaulle-Str. 20'
openingHours:
    Monday: '00:00:00-23:59:00'
    Tuesday: '00:00:00-23:59:00'
    Wednesday: '00:00:00-23:59:00'
    Thursday: '00:00:00-23:59:00'
    Friday: '00:00:00-23:59:00'
    Saturday: '00:00:00-23:59:00'
    Sunday: '00:00:00-23:59:00'

---
locationName: 'Packstation 703'
address:
    countryCode: DE
    postalCode: '53113'
    addressLocality: Bonn
    streetAddress: 'Charles-de-Gaulle-Str. 20'
openingHours:
    Monday: '00:00:00-23:59:00'
    Tuesday: '00:00:00-23:59:00'
    Wednesday: '00:00:00-23:59:00'
    Thursday: '00:00:00-23:59:00'
    Friday: '00:00:00-23:59:00'
    Saturday: '00:00:00-23:59:00'
    Sunday: '00:00:00-23:59:00'
```
### For cleaning cache
```shell
 ./vendor/bin/drush cr
```
