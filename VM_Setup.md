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

* Download/transfer State and Territory boundaries files to a folder named mapdata in VM:

From http://www.abs.gov.au/AUSSTATS/abs@.nsf/DetailsPage/1270.0.55.001July%202011?OpenDocument download *State (S/T) ASGS Ed 2011 Digital Boundaries in ESRI Shapefile Format* (1270055001_ste_2011_aust_shape.zip)

* Download/transfer Australian federal electoral boundaries to a folder named mapdata in VM:

From https://data.gov.au/dataset/psma-administrative-boundaries download *Commonwealth Electoral Boundaries MAY 2016* (Commonwealth-Electoral-Boundaries-MAY-2016.zip)

* Download/transfer Town Points to a folder named mapdata in VM:

From https://data.gov.au/dataset/psma-administrative-boundaries download *Town Points AUGUST 2013* (townpointsaugust2013.zip)

* Download/transfer Remnant Vegetation Cover of Queensland boundaries to a folder named mapdata in VM:

From http://qldspatial.information.qld.gov.au/catalogue search and download *Remnant Vegetation Cover of Queensland Version 9.0 - April 2015* (DP_RemVegV_DCDB_A.zip)

* Download/transfer State electoral boundaries Queensland boundaries to a folder named mapdata in VM (name the zip file: qld_state_electoral.zip):

From http://qldspatial.information.qld.gov.au/catalogue search and download *State electoral boundaries Queensland* (download as format Shapefile - SHP - .shp / GDA94)

* Load data into Postgres:

To ensure there is enough memory for the loading process, first adjust VM settings so that the virtual machine has 8GB RAM available.

Then transfer all github repository files to VM then run these commands:

```
chmod u+x configure_db
./configure_db
```

* Make generate scripts executable:

```
chmod u+x gen_*
```

* Create directories for map output:

```
mkdir federal
mkdir federal/act
mkdir federal/nsw
mkdir federal/nt
mkdir federal/qld
mkdir federal/sa
mkdir federal/tas
mkdir federal/vic
mkdir federal/wa
mkdir state/qld
```