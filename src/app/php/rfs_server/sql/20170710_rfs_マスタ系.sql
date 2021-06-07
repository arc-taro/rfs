-- Project Name : ロードヒーティング
-- Date/Time    : 2017/07/10 14:54:38
-- Author       : haramoto
-- RDBMS Type   : PostgreSQL
-- Application  : A5:SQL Mk-2

-- rfs_m_rh_電力契約種別
create table rfs_m_rh_denryoku_keiyaku_syubetsu (
  denryoku_keiyaku_syubetsu_cd int not null
  , denryoku_keiyaku_syubetsu_nm text not null
  , sort_no int not null
  , constraint rfs_m_rh_denryoku_keiyaku_syubetsu_PKC primary key (denryoku_keiyaku_syubetsu_cd)
) ;

-- rfs_m_rh_放熱
create table rfs_m_rh_hounetsu (
  hounetsu_cd int not null
  , hounetsu_nm text not null
  , sort_no int not null
  , constraint rfs_m_rh_hounetsu_PKC primary key (hounetsu_cd)
) ;

-- rfs_m_rh_集熱
create table rfs_m_rh_syuunetsu (
  syuunetsu_cd int not null
  , syuunetsu_nm text not null
  , sort_no int not null
  , constraint rfs_m_rh_syuunetsu_PKC primary key (syuunetsu_cd)
) ;

-- rfs_m_rh_endou_chiiki
create table rfs_m_rh_endou_chiiki (
  endou_chiiki_cd int not null
  , endou_chiiki_nm text
  , sort_no int
  , constraint rfs_m_rh_endou_chiiki_PKC primary key (endou_chiiki_cd)
) ;

-- rfs_m_rh_endou_kuiki
create table rfs_m_rh_endou_kuiki (
  endou_kuiki_cd int not null
  , endou_kuiki_nm text
  , sort_no int
  , constraint rfs_m_rh_endou_kuiki_PKC primary key (endou_kuiki_cd)
) ;

-- rfs_m_rh_did_syubetsu
create table rfs_m_rh_did_syubetsu (
  did_syubetsu_cd int not null
  , did_syubetsu_nm text not null
  , sort_no int not null
  , constraint rfs_m_rh_did_syubetsu_PKC primary key (did_syubetsu_cd)
) ;

-- rfs_m_rh_endou_joukyou
create table rfs_m_rh_endou_joukyou (
  endou_joukyou_cd int not null
  , endou_joukyou_nm text
  , sort_no int
  , constraint rfs_m_rh_endou_joukyou_PKC primary key (endou_joukyou_cd)
) ;

-- 有無チェック
create table rfs_m_rh_umu (
  umu_cd int not null
  , umu_nm text not null
  , sort_no integer not null
  , constraint rfs_m_rh_umu_PKC primary key (umu_cd)
) ;

-- T施設
create table rfs_t_daichou_rh (
  sno integer not null
  , GNO text not null
  , endou_joukyou_cd int
  , did_syubetsu_cd int
  , endou_kuiki_cd int
  , endou_chiiki_cd int
  , bus_rosen int
  , tsuugaku_ro int
  , yukimichi int
  , josetsu_kbn int
  , genkyou_num1 text
  , genkyou_num2 text
  , haba_syadou text
  , haba_hodou text
  , koubai_syadou text
  , koubai_hodou text
  , hankei_r text
  , chuusin_enchou_syadou float
  , nobe_enchyou_syadou float
  , fukuin_syadou text
  , menseki_syadou float
  , hosou_syubetsu_syadou text
  , chuusin_enchou_hodou float
  , nobe_enchyou_hodou float
  , fukuin_hodou text
  , menseki_hodou float
  , hosou_syubetsu_hodou text
  , koutsuuryou_syadou float
  , koutsuuryou_hodou float
  , morido boolean
  , kirido boolean
  , kyuu_curve boolean
  , under_syadou boolean
  , under_hodou boolean
  , kyuukoubai_syadou boolean
  , kyuukoubai_hodou boolean
  , fumikiri_syadou boolean
  , fumikiri_houdou boolean
  , kousaten_syadou boolean
  , kousaten_hodou boolean
  , hodoukyou boolean
  , tunnel_syadou boolean
  , tunnel_hodou boolean
  , heimen_syadou boolean
  , heimen_hodou boolean
  , kyouryou_syadou boolean
  , kyouryou_hodou boolean
  , kosen_syadou boolean
  , kosen_hodou boolean
  , etc_syadou boolean
  , etc_hodou boolean
  , etc_comment_syadou text
  , etc_comment_hodou text
  , netsugen_etc text
  , douryoku_etc text
  , syuunetsu_cd int
  , syuunetsu_etc text
  , hounetsu_cd int
  , hounetsu_etc text
  , dennryoku_keiyaku_syubetsu_cd int
  , seibi_keii_syadou text
  , sentei_riyuu_syadou text
  , seibi_keii_hodou text
  , sentei_riyuu_hodou text
  , haishi_jikou text
  , unit_shiyou text
  , unit_ichi text
  , sencor_shiyou text
  , sensor_ichi text
  , seigyoban_shiyou text
  , seigyoban_ichi text
  , haisen_shiyou text
  , haisen_ichi text
  , hokuden_ichi text
  , CHECK1 boolean
  , CHECK2 boolean
  , old_id text
  , comment text
  , HS_check text
  , comment_douroka text
  , comment_dogen text
  , DHS text
  , DCTR int
  , bundenban int
  , boira int
  , update_dt timestamp with time zone
  , update_account text
  , constraint rfs_t_daichou_rh_PKC primary key (sno)
) ;

comment on table rfs_m_rh_denryoku_keiyaku_syubetsu is 'rfs_m_rh_電力契約種別';
comment on column rfs_m_rh_denryoku_keiyaku_syubetsu.denryoku_keiyaku_syubetsu_cd is '電力契約種別cd';
comment on column rfs_m_rh_denryoku_keiyaku_syubetsu.denryoku_keiyaku_syubetsu_nm is '電力契約種別nm';
comment on column rfs_m_rh_denryoku_keiyaku_syubetsu.sort_no is 'sort_no';

comment on table rfs_m_rh_hounetsu is 'rfs_m_rh_放熱';
comment on column rfs_m_rh_hounetsu.hounetsu_cd is '放熱cd';
comment on column rfs_m_rh_hounetsu.hounetsu_nm is '放熱nm';
comment on column rfs_m_rh_hounetsu.sort_no is 'sort_no';

comment on table rfs_m_rh_syuunetsu is 'rfs_m_rh_集熱';
comment on column rfs_m_rh_syuunetsu.syuunetsu_cd is '集熱cd';
comment on column rfs_m_rh_syuunetsu.syuunetsu_nm is '集熱nm';
comment on column rfs_m_rh_syuunetsu.sort_no is 'sort_no';

comment on table rfs_m_rh_endou_chiiki is 'rfs_m_rh_endou_chiiki';
comment on column rfs_m_rh_endou_chiiki.endou_chiiki_cd is 'endou_chiiki';
comment on column rfs_m_rh_endou_chiiki.endou_chiiki_nm is '沿道地域nm';
comment on column rfs_m_rh_endou_chiiki.sort_no is 'sort_no';

comment on table rfs_m_rh_endou_kuiki is 'rfs_m_rh_endou_kuiki';
comment on column rfs_m_rh_endou_kuiki.endou_kuiki_cd is '沿道区域cd';
comment on column rfs_m_rh_endou_kuiki.endou_kuiki_nm is '沿道区域nm';
comment on column rfs_m_rh_endou_kuiki.sort_no is 'sort_no';

comment on table rfs_m_rh_did_syubetsu is 'rfs_m_rh_did_syubetsu';
comment on column rfs_m_rh_did_syubetsu.did_syubetsu_cd is 'DID種別cd';
comment on column rfs_m_rh_did_syubetsu.did_syubetsu_nm is 'DID種別nm';
comment on column rfs_m_rh_did_syubetsu.sort_no is 'sort_no';

comment on table rfs_m_rh_endou_joukyou is 'rfs_m_rh_endou_joukyou';
comment on column rfs_m_rh_endou_joukyou.endou_joukyou_cd is '沿道状況cd';
comment on column rfs_m_rh_endou_joukyou.endou_joukyou_nm is '沿道状況nm';
comment on column rfs_m_rh_endou_joukyou.sort_no is 'sort_no';

comment on table rfs_m_rh_umu is '有無チェック';
comment on column rfs_m_rh_umu.umu_cd is 'umu_cd';
comment on column rfs_m_rh_umu.umu_nm is 'umu_nm';
comment on column rfs_m_rh_umu.sort_no is 'sort_no';

comment on table rfs_t_daichou_rh is 'T施設';
comment on column rfs_t_daichou_rh.sno is 'sno';
comment on column rfs_t_daichou_rh.GNO is 'GNO   グループNo　接続したRH箇所で電力種別や熱源が違う場合に　同一グループと解るようにする。';
comment on column rfs_t_daichou_rh.endou_joukyou_cd is '沿道状況cd   [DID]  [その他市街地]　[平地]　[山地]';
comment on column rfs_t_daichou_rh.did_syubetsu_cd is 'DID種別   ※沿道状況がDIDの時のみ　[中心市街地]　[住宅密集地]';
comment on column rfs_t_daichou_rh.endou_kuiki_cd is '沿道区域cd   沿道用途地域　[住居系]　[商業系]　[工業系] [無指定]';
comment on column rfs_t_daichou_rh.endou_chiiki_cd is '沿道地域cd   沿道用途地域　[住居系]　[商業系]　[工業系] [無指定]';
comment on column rfs_t_daichou_rh.bus_rosen is 'バス有無   バス路線の [有] [無]';
comment on column rfs_t_daichou_rh.tsuugaku_ro is '通学路指定   通学路指定の[有] [無]';
comment on column rfs_t_daichou_rh.yukimichi is '雪みち計画   雪道計画の[有] [無]';
comment on column rfs_t_daichou_rh.josetsu_kbn is '車道除雪区分   ○種';
comment on column rfs_t_daichou_rh.genkyou_num1 is '現況番号1   道路現況';
comment on column rfs_t_daichou_rh.genkyou_num2 is '現況番号2   道路現況';
comment on column rfs_t_daichou_rh.haba_syadou is '現車道幅   道路現況　道路幅員';
comment on column rfs_t_daichou_rh.haba_hodou is '現歩道幅   道路現況　歩道幅員';
comment on column rfs_t_daichou_rh.koubai_syadou is '現勾配_車道   道路現況　％';
comment on column rfs_t_daichou_rh.koubai_hodou is '現勾配_歩道   道路現況　％';
comment on column rfs_t_daichou_rh.hankei_r is '現半径R   道路現況　※車道のみ';
comment on column rfs_t_daichou_rh.chuusin_enchou_syadou is '施設車道中心延長   施設現況';
comment on column rfs_t_daichou_rh.nobe_enchyou_syadou is '施設車道延べ延長   施設現況';
comment on column rfs_t_daichou_rh.fukuin_syadou is '施設車道幅員   施設現況';
comment on column rfs_t_daichou_rh.menseki_syadou is '施設車道面積   施設現況';
comment on column rfs_t_daichou_rh.hosou_syubetsu_syadou is '施設車道舗装種別   施設現況';
comment on column rfs_t_daichou_rh.chuusin_enchou_hodou is '施設歩道中心延長   施設現況';
comment on column rfs_t_daichou_rh.nobe_enchyou_hodou is '施設歩道延べ延長   施設現況';
comment on column rfs_t_daichou_rh.fukuin_hodou is '施設歩道幅員   施設現況';
comment on column rfs_t_daichou_rh.menseki_hodou is '施設歩道面積   施設現況';
comment on column rfs_t_daichou_rh.hosou_syubetsu_hodou is '施設歩道舗装種別   施設現況';
comment on column rfs_t_daichou_rh.koutsuuryou_syadou is '車道交通量   自動車交通量　台／１２h';
comment on column rfs_t_daichou_rh.koutsuuryou_hodou is '歩道交通量   歩行者交通量　人／h';
comment on column rfs_t_daichou_rh.morido is '盛土   道路構造　車道のみ';
comment on column rfs_t_daichou_rh.kirido is '切土   道路構造　車道のみ';
comment on column rfs_t_daichou_rh.kyuu_curve is '急カーブ   道路構造　車道のみ';
comment on column rfs_t_daichou_rh.under_syadou is 'アンダ車道   道路構造';
comment on column rfs_t_daichou_rh.under_hodou is 'アンダ歩道   道路構造';
comment on column rfs_t_daichou_rh.kyuukoubai_syadou is '急勾配_車道   道路構造';
comment on column rfs_t_daichou_rh.kyuukoubai_hodou is '急勾配_歩道   道路構造';
comment on column rfs_t_daichou_rh.fumikiri_syadou is '踏切_車道   道路構造';
comment on column rfs_t_daichou_rh.fumikiri_houdou is '踏切_歩道   道路構造';
comment on column rfs_t_daichou_rh.kousaten_syadou is '交差点_車道   道路構造';
comment on column rfs_t_daichou_rh.kousaten_hodou is '交差点_歩道   道路構造';
comment on column rfs_t_daichou_rh.hodoukyou is '歩道橋歩道   道路構造　歩道のみ';
comment on column rfs_t_daichou_rh.tunnel_syadou is 'トンネル_車道   道路構造';
comment on column rfs_t_daichou_rh.tunnel_hodou is 'トンネル_歩道   道路構造';
comment on column rfs_t_daichou_rh.heimen_syadou is '平面部_車道   道路構造';
comment on column rfs_t_daichou_rh.heimen_hodou is '平面部_歩道   道路構造';
comment on column rfs_t_daichou_rh.kyouryou_syadou is '橋梁_車道   道路構造';
comment on column rfs_t_daichou_rh.kyouryou_hodou is '橋梁_歩道   道路構造';
comment on column rfs_t_daichou_rh.kosen_syadou is '跨線橋_車道   道路構造';
comment on column rfs_t_daichou_rh.kosen_hodou is '跨線橋_歩道   道路構造';
comment on column rfs_t_daichou_rh.etc_syadou is 'その他_車道   道路構造';
comment on column rfs_t_daichou_rh.etc_hodou is 'その他_歩道   道路構造';
comment on column rfs_t_daichou_rh.etc_comment_syadou is 'その他コメント_車道   道路構造がその他の場合';
comment on column rfs_t_daichou_rh.etc_comment_hodou is 'その他コメント_歩道   道路構造がその他の場合';
comment on column rfs_t_daichou_rh.netsugen_etc is '熱源その他';
comment on column rfs_t_daichou_rh.douryoku_etc is '動力その他';
comment on column rfs_t_daichou_rh.syuunetsu_cd is '集熱cd   集熱方式：　直接方式　間接方式　その他';
comment on column rfs_t_daichou_rh.syuunetsu_etc is '集熱その他';
comment on column rfs_t_daichou_rh.hounetsu_cd is '放熱cd   放熱方式：　電熱ｹｰﾌﾞﾙ　放熱管　ﾋｰﾄﾊﾟｲﾌﾟ　その他';
comment on column rfs_t_daichou_rh.hounetsu_etc is '放熱その他';
comment on column rfs_t_daichou_rh.dennryoku_keiyaku_syubetsu_cd is '電力契約種別   電力契約種別';
comment on column rfs_t_daichou_rh.seibi_keii_syadou is '整備経緯_車道';
comment on column rfs_t_daichou_rh.sentei_riyuu_syadou is '選定理由_車道';
comment on column rfs_t_daichou_rh.seibi_keii_hodou is '整備経緯_歩道';
comment on column rfs_t_daichou_rh.sentei_riyuu_hodou is '選定理由_歩道';
comment on column rfs_t_daichou_rh.haishi_jikou is '廃止等事項';
comment on column rfs_t_daichou_rh.unit_shiyou is 'ユニット仕様   図面の有無　ユニット仕様図';
comment on column rfs_t_daichou_rh.unit_ichi is 'ユニット位置   図面の有無　ユニット位置図';
comment on column rfs_t_daichou_rh.sencor_shiyou is 'センサ仕様   図面の有無　センサ仕様図';
comment on column rfs_t_daichou_rh.sensor_ichi is 'センサ位置   図面の有無　センサ位置図';
comment on column rfs_t_daichou_rh.seigyoban_shiyou is '制御盤仕様   図面の有無　制御盤仕様図';
comment on column rfs_t_daichou_rh.seigyoban_ichi is '制御盤位置   図面の有無　制御盤位置図';
comment on column rfs_t_daichou_rh.haisen_shiyou is '配管配線仕様   図面の有無　配管配線仕様図';
comment on column rfs_t_daichou_rh.haisen_ichi is '配管配線位置   図面の有無　配管配線仕様図';
comment on column rfs_t_daichou_rh.hokuden_ichi is '北電柱位置   図面の有無　北電柱位置図';
comment on column rfs_t_daichou_rh.CHECK1 is 'CHECK1';
comment on column rfs_t_daichou_rh.CHECK2 is 'CHECK2';
comment on column rfs_t_daichou_rh.old_id is '旧ID';
comment on column rfs_t_daichou_rh.comment is 'コメント';
comment on column rfs_t_daichou_rh.HS_check is 'HSチェック';
comment on column rfs_t_daichou_rh.comment_douroka is 'コメント_整備課';
comment on column rfs_t_daichou_rh.comment_dogen is 'コメント_土現';
comment on column rfs_t_daichou_rh.DHS is 'DＨＳ箇所';
comment on column rfs_t_daichou_rh.DCTR is 'DCTR数';
comment on column rfs_t_daichou_rh.bundenban is 'HS分電盤数';
comment on column rfs_t_daichou_rh.boira is 'Dﾎﾞｲﾗ数';
comment on column rfs_t_daichou_rh.update_dt is 'update_dt';
comment on column rfs_t_daichou_rh.update_account is 'update_account text';

insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (1,'融雪用電力A(高圧)',1);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (2,'融雪用電力A(低圧)',2);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (3,'融雪用電力B(高圧)',3);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (4,'融雪用電力B(低圧)',4);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (5,'融雪用電力C(高圧)',5);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (6,'融雪電力C(低圧)',6);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (7,'融雪電力D(高圧)',7);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (8,'融雪電力D(低圧)',8);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (9,'業務用電力(高圧)',9);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (10,'公衆街路灯B',10);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (11,'高圧電力A',11);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (12,'高圧電力B',12);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (13,'従量電灯A',13);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (14,'従量電灯B',14);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (15,'従量電灯C',15);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (16,'深夜電力A(低圧)',16);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (17,'深夜電力B(高圧)',17);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (18,'深夜電力B(低圧)',18);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (19,'深夜電力C(高圧)',19);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (20,'深夜電力C(低圧)',20);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (21,'深夜電力D(高圧)',21);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (22,'深夜電力D(低圧)',22);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (23,'低圧電力',23);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (24,'定額電灯',24);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (25,'農事用電力(高圧)',25);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (26,'農事用電力(低圧)',26);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (27,'臨時電灯A',27);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (28,'臨時電灯B',28);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (29,'臨時電灯C',29);
insert into public.rfs_m_rh_denryoku_keiyaku_syubetsu(denryoku_keiyaku_syubetsu_cd,denryoku_keiyaku_syubetsu_nm,sort_no) values (30,'臨時電力',30);

insert into public.rfs_m_rh_did_syubetsu(did_syubetsu_cd,did_syubetsu_nm,sort_no) values (1,'中心市街地',1);
insert into public.rfs_m_rh_did_syubetsu(did_syubetsu_cd,did_syubetsu_nm,sort_no) values (2,'住宅密集地',2);

insert into public.rfs_m_rh_endou_joukyou(endou_joukyou_cd,endou_joukyou_nm,sort_no) values (1,'DID',1);
insert into public.rfs_m_rh_endou_joukyou(endou_joukyou_cd,endou_joukyou_nm,sort_no) values (2,'その他市街地',2);
insert into public.rfs_m_rh_endou_joukyou(endou_joukyou_cd,endou_joukyou_nm,sort_no) values (3,'平地',3);
insert into public.rfs_m_rh_endou_joukyou(endou_joukyou_cd,endou_joukyou_nm,sort_no) values (4,'山地',4);

--insert into public.rfs_m_rh_endou_joukyou(endou_joukyou_cd,endou_joukyou_nm,sort_no) values (1,'DID',1);
--insert into public.rfs_m_rh_endou_joukyou(endou_joukyou_cd,endou_joukyou_nm,sort_no) values (2,'その他市街地',2);
--insert into public.rfs_m_rh_endou_joukyou(endou_joukyou_cd,endou_joukyou_nm,sort_no) values (3,'平地',3);
--insert into public.rfs_m_rh_endou_joukyou(endou_joukyou_cd,endou_joukyou_nm,sort_no) values (4,'山地',4);

insert into public.rfs_m_rh_endou_chiiki(endou_chiiki_cd,endou_chiiki_nm,sort_no) values (1,'住居系',1);
insert into public.rfs_m_rh_endou_chiiki(endou_chiiki_cd,endou_chiiki_nm,sort_no) values (2,'商業系',2);
insert into public.rfs_m_rh_endou_chiiki(endou_chiiki_cd,endou_chiiki_nm,sort_no) values (3,'工業系',3);
insert into public.rfs_m_rh_endou_chiiki(endou_chiiki_cd,endou_chiiki_nm,sort_no) values (4,'無指定',4);

insert into public.rfs_m_rh_endou_kuiki(endou_kuiki_cd,endou_kuiki_nm,sort_no) values (1,'都市計画区域内',1);
insert into public.rfs_m_rh_endou_kuiki(endou_kuiki_cd,endou_kuiki_nm,sort_no) values (2,'都市計画区域外',2);

insert into public.rfs_m_rh_hounetsu(hounetsu_cd,hounetsu_nm,sort_no) values (1,'電熱ケーブル',1);
insert into public.rfs_m_rh_hounetsu(hounetsu_cd,hounetsu_nm,sort_no) values (2,'放熱管',2);
insert into public.rfs_m_rh_hounetsu(hounetsu_cd,hounetsu_nm,sort_no) values (3,'ヒートパイプ',3);
insert into public.rfs_m_rh_hounetsu(hounetsu_cd,hounetsu_nm,sort_no) values (4,'その他',4);

insert into public.rfs_m_rh_syuunetsu(syuunetsu_cd,syuunetsu_nm,sort_no) values (1,'直接',1);
insert into public.rfs_m_rh_syuunetsu(syuunetsu_cd,syuunetsu_nm,sort_no) values (2,'間接(熱交換機・ﾋｰﾄﾎﾟﾝﾌﾟ・ﾋｰﾄﾊﾟｲﾌﾟ)',2);
insert into public.rfs_m_rh_syuunetsu(syuunetsu_cd,syuunetsu_nm,sort_no) values (3,'その他',3);

insert into public.rfs_m_rh_umu(umu_cd,umu_nm,sort_no) values (0,'不明',3);
insert into public.rfs_m_rh_umu(umu_cd,umu_nm,sort_no) values (1,'有',1);
insert into public.rfs_m_rh_umu(umu_cd,umu_nm,sort_no) values (2,'無',2);

insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,1,1,'熱源','電熱',1);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,1,2,'熱源','地下水',2);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,1,3,'熱源','温泉',3);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,1,4,'熱源','工場等温泉排水',4);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,1,5,'熱源','加熱温水',5);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,1,6,'熱源','地中熱',6);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,1,7,'熱源','その他',7);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,2,1,'動力','電気',1);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,2,2,'動力','ガス',2);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,2,3,'動力','灯油',3);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,2,4,'動力','重油',4);
insert into public.rfs_m_keishiki_kubun(shisetsu_kbn,syubetsu,keishiki_kubun_cd,syubetsu_title,keishiki_kubun,sort_no) values (21,2,5,'動力','その他',5);

--insert into public.rfs_m_shisetsu_kbn(shisetsu_kbn,shisetsu_kbn_nm,sort_no,daityou_tbl) values (21,'ロードヒーティング',21,'rfs_t_daichou_rh');
