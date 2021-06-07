drop MATERIALIZED view rfs_mv_shisetsu_sum;
create MATERIALIZED view rfs_mv_shisetsu_sum as
(
  with time_table as (
    --現在の年月の取得
    select
    EXTRACT(MONTH FROM now()) as month
    , EXTRACT(YEAR FROM now()) as year
  )
  SELECT
  idx
  , str
  , shisetsu_kbn
  , dogen_cd
  , syucchoujo_cd
  , sum(case when kyouyou_kbn = 1 then 1 else 0 end) as cnt_kyouyou
  , sum(case when kyouyou_kbn = 0 then 1 else 0 end) as cnt_kyuushi
  , sum(CASE WHEN kyouyou_kbn IS NULL THEN 1 ELSE 0 END) AS cnt_noinput
  , sum(case when kyouyou_kbn = 2 then 1 else 0 end) as cnt_ichibu
  , COALESCE(count(*), 0) AS cnt_all
  FROM
  (
    select
    t1.*
    , case
    when t1.sub_yyyy >= 20
    then 1
    when (t1.sub_yyyy >= 10 AND t1.sub_yyyy < 20)
    then 2
    when (t1.sub_yyyy >= 5 AND t1.sub_yyyy < 10)
    then 3
    when (t1.sub_yyyy >= 0 AND t1.sub_yyyy < 5)
    then 4
    when (t1.secchi_yyyy is null)
    then 5
    end as idx
    , case
    when t1.sub_yyyy >= 20
    then '20年以上'
    when (t1.sub_yyyy >= 10 AND t1.sub_yyyy < 20)
    then '10年以上20年未満'
    when (t1.sub_yyyy >= 5 AND t1.sub_yyyy < 10)
    then '5年以上10年未満'
    when (t1.sub_yyyy >= 0 AND t1.sub_yyyy < 5)
    then '5年未満'
    when (t1.secchi_yyyy is null)
    then '設置年度不明'
    end as str
    from
    (
      select
      *
      , case                                --年度の処理
      when time_table.month <= 3
      then (time_table.year - secchi_yyyy - 1)
      else (time_table.year - secchi_yyyy)
      end sub_yyyy
      from
      rfs_m_shisetsu
      CROSS JOIN time_table
      where
      (trim(haishi) = '' or haishi is null)
      and haishi_yyyy is null
    ) as t1
  ) t2
  group by
  idx
  , str
  , shisetsu_kbn
  , dogen_cd
  , syucchoujo_cd
  UNION ALL
  SELECT
  6 idx
  , '計' str
  , shisetsu_kbn
  , dogen_cd
  , syucchoujo_cd
  , sum(case when kyouyou_kbn = 1 then 1 else 0 end) as cnt_kyouyou
  , sum(case when kyouyou_kbn = 0 then 1 else 0 end) as cnt_kyuushi
  , sum(CASE WHEN kyouyou_kbn IS NULL THEN 1 ELSE 0 END) AS cnt_noinput
  , sum(case when kyouyou_kbn = 2 then 1 else 0 end) as cnt_ichibu
  , COALESCE(count(*), 0) AS cnt_all
  FROM
  rfs_m_shisetsu
  where
  (
    (trim(haishi) = '' or haishi is null)
    and haishi_yyyy is null
  )
  group by
  shisetsu_kbn
  , dogen_cd
  , syucchoujo_cd
)
