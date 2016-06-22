CREATE TABLE public.all_town_point
(
  town_pid character varying(15),
  town_class character varying(1),
  town_name character varying(50),
  state_pid character varying(15),
  state character varying(3),
  lat numeric,
  long numeric
)
WITH (
  OIDS=FALSE
);

insert into all_town_point (town_pid, town_class, town_name, state_pid, lat, long, state)
select tas_town_shp.town_pid, town_class, town_name, state_pid, ST_Y(geom) as lat, ST_X(geom) as long, 'TAS'
from tas_town_shp, tas_town_point_shp
where tas_town_shp.town_pid = tas_town_point_shp.town_pid;

insert into all_town_point (town_pid, town_class, town_name, state_pid, lat, long, state)
select vic_town_shp.town_pid, town_class, town_name, state_pid, ST_Y(geom) as lat, ST_X(geom) as long, 'VIC'
from vic_town_shp, vic_town_point_shp
where vic_town_shp.town_pid = vic_town_point_shp.town_pid;

insert into all_town_point (town_pid, town_class, town_name, state_pid, lat, long, state)
select nsw_town_shp.town_pid, town_class, town_name, state_pid, ST_Y(geom) as lat, ST_X(geom) as long, 'NSW'
from nsw_town_shp, nsw_town_point_shp
where nsw_town_shp.town_pid = nsw_town_point_shp.town_pid;

insert into all_town_point (town_pid, town_class, town_name, state_pid, lat, long, state)
select qld_town_shp.town_pid, town_class, town_name, state_pid, ST_Y(geom) as lat, ST_X(geom) as long, 'QLD'
from qld_town_shp, qld_town_point_shp
where qld_town_shp.town_pid = qld_town_point_shp.town_pid;

insert into all_town_point (town_pid, town_class, town_name, state_pid, lat, long, state)
select sa_town_shp.town_pid, town_class, town_name, state_pid, ST_Y(geom) as lat, ST_X(geom) as long, 'SA'
from sa_town_shp, sa_town_point_shp
where sa_town_shp.town_pid = sa_town_point_shp.town_pid;

insert into all_town_point (town_pid, town_class, town_name, state_pid, lat, long, state)
select wa_town_shp.town_pid, town_class, town_name, state_pid, ST_Y(geom) as lat, ST_X(geom) as long, 'WA'
from wa_town_shp, wa_town_point_shp
where wa_town_shp.town_pid = wa_town_point_shp.town_pid;

insert into all_town_point (town_pid, town_class, town_name, state_pid, lat, long, state)
select act_town_shp.town_pid, town_class, town_name, state_pid, ST_Y(geom) as lat, ST_X(geom) as long, 'ACT'
from act_town_shp, act_town_point_shp
where act_town_shp.town_pid = act_town_point_shp.town_pid;

insert into all_town_point (town_pid, town_class, town_name, state_pid, lat, long, state)
select nt_town_shp.town_pid, town_class, town_name, state_pid, ST_Y(geom) as lat, ST_X(geom) as long, 'NT'
from nt_town_shp, nt_town_point_shp
where nt_town_shp.town_pid = nt_town_point_shp.town_pid;
