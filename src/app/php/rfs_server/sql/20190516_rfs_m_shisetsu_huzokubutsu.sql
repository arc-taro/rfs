-- Table: public.rfs_m_shisetsu_huzokubutsu

-- DROP TABLE public.rfs_m_shisetsu_huzokubutsu;

CREATE TABLE public.rfs_m_shisetsu_huzokubutsu
(
  shisetsu_kbn integer NOT NULL, -- 施設区分コード
  CONSTRAINT rfs_m_shisetsu_huzokubutsu_pkey PRIMARY KEY (shisetsu_kbn)
);
COMMENT ON COLUMN public.rfs_m_shisetsu_huzokubutsu.shisetsu_kbn IS '施設区分コード';

INSERT INTO rfs_m_shisetsu_huzokubutsu(shisetsu_kbn) VALUES (1);
INSERT INTO rfs_m_shisetsu_huzokubutsu(shisetsu_kbn) VALUES (2);
INSERT INTO rfs_m_shisetsu_huzokubutsu(shisetsu_kbn) VALUES (3);
INSERT INTO rfs_m_shisetsu_huzokubutsu(shisetsu_kbn) VALUES (4);
INSERT INTO rfs_m_shisetsu_huzokubutsu(shisetsu_kbn) VALUES (5);
INSERT INTO rfs_m_shisetsu_huzokubutsu(shisetsu_kbn) VALUES (22);
INSERT INTO rfs_m_shisetsu_huzokubutsu(shisetsu_kbn) VALUES (23);
