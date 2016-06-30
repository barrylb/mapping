alter table state_electoral_boundaries rename to qld_state_electoral;

create table qld_state_electoral_labels as
WITH geoms AS (
    SELECT adminarean, (ST_Dump(geom)).geom AS geom 
    FROM qld_state_electoral
)
SELECT DISTINCT ON (adminarean) adminarean, ST_AsText(ST_Centroid(geom)) AS centroid
FROM geoms
ORDER BY adminarean ASC, ST_Area(geom) DESC;

alter table qld_state_electoral_labels add label_latitude numeric;
alter table qld_state_electoral_labels add label_longitude numeric;
alter table qld_state_electoral_labels add adminarean_displayname character varying(50);
