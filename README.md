# Australian electoral mapping

* Utilities to generate Australian federal, state and local maps

## Running from a Pre-Built Virtual Machine
The quickest way to get running is to use the pre-built virtual machine with map data already loaded into Postgres and scripts ready to run. 

The image is hosted at https://atlas.hashicorp.com/barrylb/boxes/austmapping/ and is 2.56GB in size.

* Get Vagrant from https://www.vagrantup.com/
* Run these commands:

```
vagrant init barrylb/austmapping
vagrant up --provider virtualbox
vagrant ssh
```

Then skip to the *Generating maps* section below.

## Setting up the VM from scratch
* Get Vagrant from https://www.vagrantup.com/
* Get the Vagrantfile from this repository
* Follow the steps in [VM_Setup.md](VM_Setup.md)

## Generating maps
* To generate Federal division maps run these commands:

```
./gen_act
./gen_nsw
./gen_nt
./gen_qld
./gen_sa
./gen_tas
./gen_vic
./gen_wa
./gen_by_party
```

* To generate Queensland state maps run this command:

```
./gen_qld_state
```