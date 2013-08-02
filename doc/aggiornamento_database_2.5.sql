-- MAPSERVER 6 --
------ GISCLIENT 2.5 -----------
SET search_path = gisclient_25, pg_catalog;

-- *********** SIMBOLOGIA LINEARE: SOSTITUZIONE DI STYLE CON PATTERN *********************
CREATE TABLE e_pattern
(
  pattern_id serial NOT NULL,
  pattern_name character varying NOT NULL,
  pattern_def character varying NOT NULL,
  pattern_order smallint,
  CONSTRAINT e_pattern_pkey PRIMARY KEY (pattern_id )
);
ALTER TABLE style ADD COLUMN pattern_id integer;

ALTER TABLE style  ADD CONSTRAINT pattern_id_fkey FOREIGN KEY (pattern_id)
      REFERENCES gisclient_25.e_pattern (pattern_id) MATCH SIMPLE
      ON UPDATE CASCADE ON DELETE NO ACTION;
	  
CREATE INDEX fki_pattern_id_fkey ON style USING btree (pattern_id );

CREATE OR REPLACE VIEW seldb_pattern AS 
         SELECT (-1) AS id, 'Seleziona ====>' AS opzione
UNION ALL 
         SELECT pattern_id AS id, pattern_name AS opzione
           FROM e_pattern;

--INSERISCO I PATTERN EREDITATI DAGLI STYLE CHE VENGONO APPLICATI ALLE LINEE NELLA VECCHIA VERSIONE
insert into e_pattern(pattern_name,pattern_def)
select symbol_name,'PATTERN' ||replace(substring(symbol_def from 'STYLE(.+)END'),'\n',' ') || 'END' from symbol where symbol_def like '%STYLE%';

--AGGIORNO IL pattern_id DELLA TABELLA style CON I VALORI DELLE CHIAVI
update style set pattern_id=e_pattern.pattern_id,symbol_name=null from e_pattern where e_pattern.pattern_name=style.symbol_name;

--TOLGO DAI SIMBOLI QUELLI CHE SERVIVANO SOLO PER IL PATTERN
delete from symbol where symbol_def like '%STYLE%';
--ELIMINO LE KEYWORDS NON COMPATIBILI (	CONTROLLARE IL RISULTATO)
update symbol  set symbol_def=regexp_replace(symbol_def, '\nGAP(.+)', '')  where symbol_def like '%GAP%';
delete from symbol where symbol_def like '%CARTOLINE%';

		   
-- *********** SIMBOLOGIA PUNTUALE: CREAZIONE DI SIMBOLI TRUETYPE IN SOSTITUZIONE DEL CARATTERE IN CLASS_TEXT *********************
--INSERISCO I NUOVI SIMBOLI NELLA TABELLA
insert into symbol (symbol_name,symbolcategory_id,icontype,symbol_def)
select  symbol_ttf_name||'@'||font_name,1,0,
	'TYPE TRUETYPE
	FONT "'||font_name||'"
	CHARACTER "&#'||ascii_code||';"
	ANTIALIAS TRUE'
from symbol_ttf;
update symbol set symbol_def=replace(symbol_def,'"""','"''"')where symbol_def like '%"""%';


--AGGIUNGO GLI STILI ALLE CLASSI---
insert into style(style_id,class_id,style_name,symbol_name,color,angle,size,minsize,maxsize)
select class_id+10000,class_id,symbol_ttf_name,symbol_ttf_name||'@'||label_font,label_color,label_angle,label_size,label_minsize,label_maxsize from class where coalesce(symbol_ttf_name,'')<>'' and coalesce(label_font,'')<>'';

--TOLGO I SYMBOLI TTF DA CLASSI
update class set symbol_ttf_name=null,label_font=null where coalesce(symbol_ttf_name,'')<>'' and coalesce(label_font,'')<>'';

--PULIZIA
--DROP TABLE symbol_ttf;
--DROP SEQUENCE gisclient_25.e_pattern_pattern_id_seq;
--ALTER TABLE e_pattern ALTER COLUMN pattern_id TYPE smallint;		   
		   






