# VM Setup Instructions

* Start the VM:

``vagrant up``

* Connect to the VM:

``vagrant ssh``

* Install various requirements:

```
sudo apt-get update
sudo apt-get upgrade
sudo apt install unzip
sudo apt install php7.0-cli
sudo apt install php7.0-pgsql
sudo apt install php7.0-gd
sudo apt install msttcorefonts
```

* Install Postgres/PostGIS:

Follow steps at: http://trac.osgeo.org/postgis/wiki/UsersWikiPostGIS22UbuntuPGSQL95Apt with 'gisdb' database per their example; in "Create new PGSQL user" section create user 'maps' password 'spam' including steps to get the shp2pgsql-gui utility (we only use shp2pgsql though)

* Download/transfer State and Territory boundaries files to VM:

From http://www.abs.gov.au/AUSSTATS/abs@.nsf/DetailsPage/1270.0.55.001July%202011?OpenDocument download *State (S/T) ASGS Ed 2011 Digital Boundaries in ESRI Shapefile Format* (1270055001_ste_2011_aust_shape.zip)

* Download/transfer Australian federal electoral boundaries to VM:

From https://data.gov.au/dataset/psma-administrative-boundaries download *Commonwealth Electoral Boundaries MAY 2016* (Commonwealth-Electoral-Boundaries-MAY-2016.zip)

* Download/transfer Town Points to VM:

From https://data.gov.au/dataset/psma-administrative-boundaries download *Town Points AUGUST 2013* (townpointsaugust2013.zip)

* Load data into Postgres:

Transfer all github repository files to VM then run these commands:

```
chmod u+x configure_db
./configure_db
```
