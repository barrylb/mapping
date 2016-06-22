CREATE TABLE public.comm_electoral
(
  ce_ply_pid character varying(15),
  dt_create date,
  dt_retire date,
  ce_pid character varying(15),
  elect_div character varying(50),
  state_pid character varying(15),
  geom geometry(MultiPolygon)
)
WITH (
  OIDS=FALSE
);

CREATE INDEX comm_electoral_geom_idx
  ON public.comm_electoral
  USING gist
  (geom);

insert into public.comm_electoral (ce_ply_pid, dt_create, dt_retire, ce_pid, geom)
select ce_ply_pid, dt_create, dt_retire, ce_pid, geom 
from act_comm_electoral_polygon_shp;

insert into public.comm_electoral (ce_ply_pid, dt_create, dt_retire, ce_pid, geom)
select ce_ply_pid, dt_create, dt_retire, ce_pid, geom 
from nsw_comm_electoral_polygon_shp;

insert into public.comm_electoral (ce_ply_pid, dt_create, dt_retire, ce_pid, geom)
select ce_ply_pid, dt_create, dt_retire, ce_pid, geom 
from nt_comm_electoral_polygon_shp;

insert into public.comm_electoral (ce_ply_pid, dt_create, dt_retire, ce_pid, geom)
select ce_ply_pid, dt_create, dt_retire, ce_pid, geom 
from qld_comm_electoral_polygon_shp;

insert into public.comm_electoral (ce_ply_pid, dt_create, dt_retire, ce_pid, geom)
select ce_ply_pid, dt_create, dt_retire, ce_pid, geom 
from sa_comm_electoral_polygon_shp;

insert into public.comm_electoral (ce_ply_pid, dt_create, dt_retire, ce_pid, geom)
select ce_ply_pid, dt_create, dt_retire, ce_pid, geom 
from tas_comm_electoral_polygon_shp;

insert into public.comm_electoral (ce_ply_pid, dt_create, dt_retire, ce_pid, geom)
select ce_ply_pid, dt_create, dt_retire, ce_pid, geom 
from vic_comm_electoral_polygon_shp;

insert into public.comm_electoral (ce_ply_pid, dt_create, dt_retire, ce_pid, geom)
select ce_ply_pid, dt_create, dt_retire, ce_pid, geom 
from wa_comm_electoral_polygon_shp;

UPDATE comm_electoral
SET elect_div = sub1.name, state_pid = sub1.state_pid
FROM (select ce_pid, name, state_pid
FROM act_comm_electoral_shp
union
select ce_pid, name, state_pid
FROM nsw_comm_electoral_shp
union
select ce_pid, name, state_pid
FROM nt_comm_electoral_shp
union
select ce_pid, name, state_pid
FROM qld_comm_electoral_shp
union
select ce_pid, name, state_pid
FROM sa_comm_electoral_shp
union
select ce_pid, name, state_pid
FROM tas_comm_electoral_shp
union
select ce_pid, name, state_pid
FROM vic_comm_electoral_shp
union
select ce_pid, name, state_pid
FROM wa_comm_electoral_shp) as sub1
WHERE comm_electoral.ce_pid = sub1.ce_pid;

create table comm_electoral_labels as
SELECT ST_AsText(ST_Centroid(ST_Collect(geom))) as centroid, elect_div FROM comm_electoral
group by elect_div

alter table comm_electoral_labels add label_latitude numeric;
alter table comm_electoral_labels add label_longitude numeric;
alter table comm_electoral_labels add elect_div_displayname character varying(50);
