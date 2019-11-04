
-- ----------------------------
--  活动任务和图片的对应关系表
-- ----------------------------

CREATE TABLE public.activity_img_relation (
  id bigserial PRIMARY KEY,
  activity_id int8,
  img_id int8
)
;
COMMENT ON COLUMN public.activity_img_relation.id IS '自增id';
COMMENT ON COLUMN public.activity_img_relation.activity_id IS '活动id';
COMMENT ON COLUMN public.activity_img_relation.img_id IS '图片id';
COMMENT ON TABLE public.activity_img_relation IS '活动任务和图片的对应关系表';

CREATE INDEX  ON public.activity_img_relation (activity_id);
CREATE INDEX  ON public.activity_img_relation (img_id);

-- ----------------------------
-- 活动任务 activity_tasks
-- ----------------------------

CREATE TABLE public.activity_tasks (
  id bigserial PRIMARY KEY,
  eid int8,
  activity_title varchar(100) ,
  activity_type varchar[] ,
  theme_keywords varchar[] ,
  img_upload_points int4,
  task_points int4,
  need_img_count int4,
  points_cycle varchar(50) ,
  points_time_type varchar(50) ,
  activity_introduction varchar(500) ,
  activity_description varchar(500) ,
  activity_reward varchar(500) ,
  img_requirements varchar(500) ,
  background_img varchar(255) ,
  now_img_counts int4,
  begin_time timestamptz(6),
  end_time timestamptz(6),
  state varchar(50) ,
  create_username varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  update_username varchar(50) ,
  release_time timestamptz(6),
  update_time timestamptz(6) DEFAULT now(),
  city_sights jsonb
)
;
COMMENT ON COLUMN public.activity_tasks.id IS '自增id';
COMMENT ON COLUMN public.activity_tasks.eid IS '加密id';
COMMENT ON COLUMN public.activity_tasks.activity_title IS '活动名称';
COMMENT ON COLUMN public.activity_tasks.activity_type IS '活动类型:daily日常活动、city_sight城市景点、theme主题活动';
COMMENT ON COLUMN public.activity_tasks.theme_keywords IS '主题关键字';
COMMENT ON COLUMN public.activity_tasks.img_upload_points IS '图片上传积分';
COMMENT ON COLUMN public.activity_tasks.task_points IS '任务积分';
COMMENT ON COLUMN public.activity_tasks.need_img_count IS '需要图片基数';
COMMENT ON COLUMN public.activity_tasks.points_cycle IS '任务奖励周期：one一次性奖励、daily每日奖励、weekly每周奖励';
COMMENT ON COLUMN public.activity_tasks.points_time_type IS '积分发放节点：commit提交审核发放、pass审核通过发放';
COMMENT ON COLUMN public.activity_tasks.activity_introduction IS '活动介绍';
COMMENT ON COLUMN public.activity_tasks.activity_description IS '活动说明';
COMMENT ON COLUMN public.activity_tasks.activity_reward IS '活动奖励';
COMMENT ON COLUMN public.activity_tasks.img_requirements IS '图片要求';
COMMENT ON COLUMN public.activity_tasks.background_img IS '背景图展示';
COMMENT ON COLUMN public.activity_tasks.now_img_counts IS '已征集图片数';
COMMENT ON COLUMN public.activity_tasks.begin_time IS '活动开始时间';
COMMENT ON COLUMN public.activity_tasks.end_time IS '活动结束时间';
COMMENT ON COLUMN public.activity_tasks.state IS '活动在线状态：pending未发布、online已发布、offline已下线';
COMMENT ON COLUMN public.activity_tasks.create_username IS '创建用户名';
COMMENT ON COLUMN public.activity_tasks.create_time IS '创建时间';
COMMENT ON COLUMN public.activity_tasks.update_username IS '最近一次修改用户名';
COMMENT ON COLUMN public.activity_tasks.release_time IS '发布时间';
COMMENT ON COLUMN public.activity_tasks.update_time IS '最近一次修改时间';
COMMENT ON COLUMN public.activity_tasks.city_sights IS '城市景点：key是city_id;value是city展名称';
COMMENT ON TABLE public.activity_tasks IS '活动任务';

CREATE INDEX  ON public.activity_tasks (eid);

-- ----------------------------
-- 操作记录 audit_records
-- ----------------------------

CREATE TABLE public.audit_records (
  id bigserial PRIMARY KEY,
  img_id int8,
  username varchar(50) ,
  operate_type varchar(50) ,
  old_data jsonb,
  diff_data jsonb,
  reject_info jsonb,
  create_time timestamptz(6) DEFAULT now(),
  new_data jsonb,
  point_state varchar(50) ,
  update_time timestamptz(6)
)
;
COMMENT ON COLUMN public.audit_records.id IS '自增id';
COMMENT ON COLUMN public.audit_records.img_id IS '图片id';
COMMENT ON COLUMN public.audit_records.username IS '操作用户名';
COMMENT ON COLUMN public.audit_records.operate_type IS '操作类型：submit首次提交审核；reject审核驳回；modify审核后修改；passed审核通过；remove已删除；';
COMMENT ON COLUMN public.audit_records.old_data IS '审核操作用-存img整条记录';
COMMENT ON COLUMN public.audit_records.diff_data IS '用户修改用-修改字段';
COMMENT ON COLUMN public.audit_records.reject_info IS '审核驳回用-驳回信息：理由+描述';
COMMENT ON COLUMN public.audit_records.create_time IS '操作时间';
COMMENT ON COLUMN public.audit_records.new_data IS '当前数据';
COMMENT ON COLUMN public.audit_records.point_state IS '积分状态：pending未积分；done已积分';
COMMENT ON COLUMN public.audit_records.update_time IS '最后更新时间';
COMMENT ON TABLE public.audit_records IS '操作记录';


CREATE INDEX  ON public.audit_records (img_id);

-- ----------------------------
-- 浏览记录 browse_trace
-- ----------------------------

CREATE TABLE public.browse_trace (
  id bigserial PRIMARY KEY,
  img_id int8,
  username varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.browse_trace.id IS '自增id';
COMMENT ON COLUMN public.browse_trace.img_id IS '图片id';
COMMENT ON COLUMN public.browse_trace.username IS '用户名';
COMMENT ON COLUMN public.browse_trace.create_time IS '创建时间';
COMMENT ON COLUMN public.browse_trace.update_time IS '最后浏览时间';
COMMENT ON TABLE public.browse_trace IS '浏览记录';

CREATE INDEX  ON public.browse_trace (img_id);
CREATE INDEX  ON public.browse_trace (username);


-- ----------------------------
-- 公司组织架构 company_dept
-- ----------------------------

CREATE TABLE public.company_dept (
  id bigserial PRIMARY KEY,
  dept_name varchar(32) ,
  dept public.ltree,
  parent_id int4 DEFAULT 0,
  employee_num int4 DEFAULT 0,
  last_sync_time timestamptz(6),
  create_time timestamptz(6)
)
;
COMMENT ON COLUMN public.company_dept.dept_name IS '组织架构名称';
COMMENT ON COLUMN public.company_dept.dept IS '组织架构';
COMMENT ON COLUMN public.company_dept.parent_id IS '父级id';
COMMENT ON COLUMN public.company_dept.employee_num IS '该节点员工数量';
COMMENT ON COLUMN public.company_dept.last_sync_time IS '最后更新时间';
COMMENT ON COLUMN public.company_dept.create_time IS '创建时间';
COMMENT ON TABLE public.company_dept IS '公司组织架构';

-- ----------------------------
-- 每日任务完成情况列表 daily_task_list
-- ----------------------------

CREATE TABLE public.daily_task_list (
  id bigserial PRIMARY KEY,
  username varchar(50) ,
  task_name varchar(100) ,
  city_name varchar(100) ,
  complete_num int4,
  complete_state varchar(50) ,
  img_id int4[],
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now(),
  activity_id int8
)
;
COMMENT ON COLUMN public.daily_task_list.id IS '自增id';
COMMENT ON COLUMN public.daily_task_list.username IS '用户名';
COMMENT ON COLUMN public.daily_task_list.task_name IS '任务名：upload上传任务；city城市任务';
COMMENT ON COLUMN public.daily_task_list.city_name IS '城市名称';
COMMENT ON COLUMN public.daily_task_list.complete_num IS '当前完成数量';
COMMENT ON COLUMN public.daily_task_list.complete_state IS '当前完成状态：doing进行中；done已完成';
COMMENT ON COLUMN public.daily_task_list.img_id IS '图片id';
COMMENT ON COLUMN public.daily_task_list.create_time IS '创建时间';
COMMENT ON COLUMN public.daily_task_list.update_time IS '最后修改时间';
COMMENT ON COLUMN public.daily_task_list.activity_id IS '活动id';
COMMENT ON TABLE public.daily_task_list IS '每日任务完成情况列表';

CREATE INDEX  ON public.daily_task_list (img_id);
CREATE INDEX  ON public.daily_task_list (username);


-- ----------------------------
-- 下载表 download
-- ----------------------------

CREATE TABLE public.download (
  id bigserial PRIMARY KEY,
  img_ids varchar(512) ,
  username varchar(50) ,
  filename varchar(255) ,
  file_expire_date timestamptz(6),
  is_del bool DEFAULT false,
  del_time timestamptz(6),
  create_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.download.img_ids IS '下载图片的id串，id需要排序，以备后用，以逗号分隔';
COMMENT ON COLUMN public.download.filename IS '下载的文件名';
COMMENT ON COLUMN public.download.file_expire_date IS '下载过期时间';
COMMENT ON COLUMN public.download.is_del IS '是否删除';
COMMENT ON COLUMN public.download.del_time IS '删除时间';
COMMENT ON TABLE public.download IS '下载表';

CREATE INDEX  ON public.download (username);



-- ----------------------------
-- 下载历史表,同一图同一用户只保留一份 download_history
-- ----------------------------

CREATE TABLE public.download_history (
  id bigserial PRIMARY KEY,
  img_id int8,
  username varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.download_history.id IS '自增id';
COMMENT ON COLUMN public.download_history.img_id IS '图片id';
COMMENT ON COLUMN public.download_history.username IS '用户名';
COMMENT ON COLUMN public.download_history.create_time IS '建立时间';
COMMENT ON COLUMN public.download_history.update_time IS '更新时间';
COMMENT ON TABLE public.download_history IS '下载历史表,同一图同一用户只保留一份';

CREATE INDEX  ON public.download_history (img_id);
CREATE INDEX  ON public.download_history (username);


-- ----------------------------
-- 员工表employee
-- ----------------------------

CREATE TABLE public.employee (
  id bigserial PRIMARY KEY,
  userid varchar(32) ,
  name varchar(32) ,
  adname varchar(64) ,
  emplid varchar(10) ,
  avatar varchar(500) ,
  dept public.ltree,
  dept_id int4,
  status bool,
  last_sync_time timestamptz(6),
  create_time timestamptz(6)
)
;
COMMENT ON COLUMN public.employee.id IS '员工id';
COMMENT ON COLUMN public.employee.userid IS '用户id';
COMMENT ON COLUMN public.employee.name IS '员工姓名';
COMMENT ON COLUMN public.employee.adname IS 'ad姓名';
COMMENT ON COLUMN public.employee.emplid IS '工号';
COMMENT ON COLUMN public.employee.avatar IS '员工头像';
COMMENT ON COLUMN public.employee.dept IS '组织架构';
COMMENT ON COLUMN public.employee.dept_id IS '部门id';
COMMENT ON COLUMN public.employee.status IS '在职状态';
COMMENT ON COLUMN public.employee.last_sync_time IS '最后更新时间';
COMMENT ON COLUMN public.employee.create_time IS '创建时间';
COMMENT ON TABLE public.employee IS '员工';



-- ----------------------------
-- 商品兑换订单表 exchange_order
-- ----------------------------

CREATE TABLE public.exchange_order (
  id bigserial PRIMARY KEY,
  order_id varchar(20) ,
  product_id int8,
  product_points int8,
  exchange_count int4,
  exchange_points int8,
  mobile varchar(20) ,
  address varchar(255) ,
  state varchar(25) ,
  username varchar(50) ,
  ship_time timestamptz(6),
  ship_user varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.exchange_order.id IS '自增id';
COMMENT ON COLUMN public.exchange_order.order_id IS '订单id';
COMMENT ON COLUMN public.exchange_order.product_id IS '商品id';
COMMENT ON COLUMN public.exchange_order.product_points IS '单个商品积分';
COMMENT ON COLUMN public.exchange_order.exchange_count IS '兑换数量';
COMMENT ON COLUMN public.exchange_order.exchange_points IS '本次兑换积分';
COMMENT ON COLUMN public.exchange_order.mobile IS '收货人手机号';
COMMENT ON COLUMN public.exchange_order.address IS '收货人地址';
COMMENT ON COLUMN public.exchange_order.state IS '兑换状态：exchange_success兑换成功；exchange_fail兑换失败；shipped已发货';
COMMENT ON COLUMN public.exchange_order.username IS '下单用户名';
COMMENT ON COLUMN public.exchange_order.ship_time IS '发货时间';
COMMENT ON COLUMN public.exchange_order.ship_user IS '发货人';
COMMENT ON COLUMN public.exchange_order.create_time IS '下单时间';
COMMENT ON COLUMN public.exchange_order.update_time IS '最后修改时间';
COMMENT ON TABLE public.exchange_order IS '商品兑换订单表';

CREATE INDEX  ON public.exchange_order (username);
CREATE INDEX  ON public.exchange_order (order_id);


-- ----------------------------
-- 收藏表 favorite
-- ----------------------------

CREATE TABLE public.favorite (
  id bigserial PRIMARY KEY,
  img_id int8,
  username varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  favorite_type varchar(20)  NOT NULL DEFAULT 'img'::character varying
)
;
COMMENT ON COLUMN public.favorite.img_id IS '收藏分类,图片id|专辑id';
COMMENT ON COLUMN public.favorite.favorite_type IS '收藏分类,默认img=图片,album=专辑,font=字体,voide=视频';
COMMENT ON TABLE public.favorite IS '收藏表';

CREATE INDEX  ON public.favorite (username);
CREATE INDEX  ON public.favorite (img_id);



-- ----------------------------
-- 商品图片信息记录 goods_img_record
-- ----------------------------

CREATE TABLE public.goods_img_record (
  id bigserial PRIMARY KEY,
  img_key varchar(255) ,
  file_name varchar(255) ,
  width int4,
  height int4,
  img_size int8,
  username varchar(32) ,
  create_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.goods_img_record.id IS 'id';
COMMENT ON COLUMN public.goods_img_record.img_key IS '图片唯一key';
COMMENT ON COLUMN public.goods_img_record.file_name IS '图片名称';
COMMENT ON COLUMN public.goods_img_record.width IS '图片宽';
COMMENT ON COLUMN public.goods_img_record.height IS '图片高';
COMMENT ON COLUMN public.goods_img_record.img_size IS '图片大小-字节数';
COMMENT ON COLUMN public.goods_img_record.username IS '图片创建者';
COMMENT ON COLUMN public.goods_img_record.create_time IS '创建时间';
COMMENT ON TABLE public.goods_img_record IS '商品图片信息记录';

CREATE INDEX  ON public.goods_img_record (img_key);

-- ----------------------------
-- img
-- ----------------------------

CREATE TABLE public.img (
  id bigserial PRIMARY KEY,
  eid int8,
  domain_id int4 DEFAULT 0,
  username varchar(50) ,
  file_name varchar(255) ,
  ext varchar(20) ,
  title varchar(255) ,
  url varchar(255) ,
  location jsonb,
  city_id int8,
  place varchar(255) ,
  big_type int2 DEFAULT 1,
  small_type jsonb,
  purpose int2 DEFAULT 0,
  filesize int8 DEFAULT 0,
  download int8 DEFAULT 0,
  favorite int8 DEFAULT 0,
  praise int8 DEFAULT 0,
  browse int8 DEFAULT 0,
  width int4 DEFAULT 0,
  height int4 DEFAULT 0,
  size_type int2 DEFAULT 0,
  keyword varchar[] ,
  audit_state int2 DEFAULT 0,
  audit_desc varchar(255) ,
  audit_user varchar(50) ,
  audit_time timestamptz(6),
  user_ip inet,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now(),
  is_del bool NOT NULL DEFAULT false,
  del_time timestamptz(6),
  reject_reason varchar(255)[] ,
  del_user varchar(50) ,
  system_check jsonb,
  logo_url varchar(255) ,
  upload_source varchar(255) ,
  purchase_source varchar(255) ,
  original_author varchar(255) ,
  is_signature bool DEFAULT false,
  signature_url varchar(255) ,
  signature_logo_url varchar(255) ,
  star bool DEFAULT false,
  star_time timestamptz(6),
  video_poster varchar(255) ,
  video_duration varchar(255) ,
  authorization_begin_date timestamptz(6),
  authorization_end_date timestamptz(6)
)
;
COMMENT ON COLUMN public.img.eid IS '加密id';
COMMENT ON COLUMN public.img.domain_id IS '所属网站， 0去哪儿内部,对应关系先写配置文件。以后改成后台管理';
COMMENT ON COLUMN public.img.username IS '上传用户';
COMMENT ON COLUMN public.img.file_name IS '图片原始文件名';
COMMENT ON COLUMN public.img.ext IS '图片扩展';
COMMENT ON COLUMN public.img.title IS '图片标题';
COMMENT ON COLUMN public.img.url IS '存储路径 /bucket/filemd5.ext';
COMMENT ON COLUMN public.img.location IS '存储国家省城市 中国.河北省.保定市.涿州市';
COMMENT ON COLUMN public.img.city_id IS '城市id';
COMMENT ON COLUMN public.img.place IS '图片地点';
COMMENT ON COLUMN public.img.big_type IS '图片大分类 1图片,2矢量图,3--PSD,4--PPT模板 注意分类可以后台管理，后台管理时不能瞎写';
COMMENT ON COLUMN public.img.small_type IS '图片小分类 1=自然风光,2=美食,3=人物,4=城市建筑 ,5=航拍 , 注意分类可以后台管理，后台管理时不能瞎写';
COMMENT ON COLUMN public.img.purpose IS '图片用途';
COMMENT ON COLUMN public.img.filesize IS '存储字节数';
COMMENT ON COLUMN public.img.download IS '下载数';
COMMENT ON COLUMN public.img.favorite IS '收藏数';
COMMENT ON COLUMN public.img.praise IS '点赞数/喜欢数';
COMMENT ON COLUMN public.img.browse IS '浏览量';
COMMENT ON COLUMN public.img.width IS '宽';
COMMENT ON COLUMN public.img.height IS '高';
COMMENT ON COLUMN public.img.size_type IS '文件大小分类 ,1=特大尺寸,2大尺寸,3=中尺寸,4=小尺寸';
COMMENT ON COLUMN public.img.keyword IS '关键词';
COMMENT ON COLUMN public.img.audit_state IS '0--待提交,1 待审核,--2审核通过,3--审核驳回';
COMMENT ON COLUMN public.img.audit_desc IS '审核描述，用于存储驳回原因';
COMMENT ON COLUMN public.img.audit_user IS '审核用户';
COMMENT ON COLUMN public.img.audit_time IS '审核时间';
COMMENT ON COLUMN public.img.user_ip IS '上传用户ip';
COMMENT ON COLUMN public.img.create_time IS '创建时间';
COMMENT ON COLUMN public.img.update_time IS '更新时间';
COMMENT ON COLUMN public.img.is_del IS '是否已删除';
COMMENT ON COLUMN public.img.del_time IS '删除时间';
COMMENT ON COLUMN public.img.reject_reason IS '驳回原因';
COMMENT ON COLUMN public.img.del_user IS '删除用户';
COMMENT ON COLUMN public.img.system_check IS '图片元素审核结果记录: img元素检测；word文字检测（0-未检测；1-验证通过；2-验证未通过；3-验证失败）';
COMMENT ON COLUMN public.img.logo_url IS '加水印后地址';
COMMENT ON COLUMN public.img.upload_source IS '上传来源';
COMMENT ON COLUMN public.img.purchase_source IS '采购来源';
COMMENT ON COLUMN public.img.original_author IS '原始作者';
COMMENT ON COLUMN public.img.is_signature IS '是否署名';
COMMENT ON COLUMN public.img.signature_url IS '签名图片地址';
COMMENT ON COLUMN public.img.signature_logo_url IS '签名logo图片地址';
COMMENT ON COLUMN public.img.star IS '精选推荐';
COMMENT ON COLUMN public.img.star_time IS '精选推荐时间';
COMMENT ON COLUMN public.img.video_poster IS '视频海报';
COMMENT ON COLUMN public.img.video_duration IS '视频时长';
COMMENT ON COLUMN public.img.authorization_begin_date IS '授权开始时间';
COMMENT ON COLUMN public.img.authorization_end_date IS '授权结束时间';
COMMENT ON TABLE public.img IS '图片表';


CREATE INDEX  ON public.img (audit_state, is_del);
CREATE INDEX  ON public.img (domain_id, big_type, audit_state);
CREATE INDEX  ON public.img (domain_id, username);
CREATE UNIQUE INDEX  ON public.img (eid);
CREATE UNIQUE INDEX  ON public.img (logo_url);
CREATE UNIQUE INDEX  ON public.img (url);


-- ----------------------------
-- 专辑表 img_album
-- ----------------------------

CREATE TABLE public.img_album (
  id bigserial PRIMARY KEY,
  eid int8 DEFAULT 0,
  album_name varchar(50) ,
  album_define jsonb,
  album_favorite int8 DEFAULT 0,
  create_user varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.img_album.id IS '自增id';
COMMENT ON COLUMN public.img_album.eid IS '专辑加密id';
COMMENT ON COLUMN public.img_album.album_name IS '专辑名称';
COMMENT ON COLUMN public.img_album.album_define IS '专辑定义条件';
COMMENT ON COLUMN public.img_album.album_favorite IS '收藏数';
COMMENT ON COLUMN public.img_album.create_user IS '创建用户';
COMMENT ON COLUMN public.img_album.create_time IS '建立时间';
COMMENT ON COLUMN public.img_album.update_time IS '更新时间';
COMMENT ON TABLE public.img_album IS '专辑表';

CREATE UNIQUE INDEX  ON public.img_album (eid);


-- ----------------------------
-- img_album_map
-- ----------------------------

CREATE TABLE public.img_album_map (
  id bigserial PRIMARY KEY,
  album_id int8 DEFAULT 0,
  img_id int8 DEFAULT 0,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.img_album_map.id IS '自增id';
COMMENT ON COLUMN public.img_album_map.album_id IS '专辑id';
COMMENT ON COLUMN public.img_album_map.img_id IS '图片id';
COMMENT ON COLUMN public.img_album_map.create_time IS '建立时间';
COMMENT ON COLUMN public.img_album_map.update_time IS '更新时间';
COMMENT ON TABLE public.img_album_map IS '专辑与图片映射表';

CREATE INDEX  ON public.img_album_map (album_id, img_id);
CREATE INDEX  ON public.img_album_map (img_id);


-- ----------------------------
-- 图片删除记录表img_del_record
-- ----------------------------

CREATE TABLE public.img_del_record (
  id bigserial PRIMARY KEY,
  url varchar(255) ,
  ceph_del bool DEFAULT false,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.img_del_record.id IS '自增id';
COMMENT ON COLUMN public.img_del_record.url IS '图片路径';
COMMENT ON COLUMN public.img_del_record.ceph_del IS 'ceph存储是否已删除';
COMMENT ON COLUMN public.img_del_record.create_time IS '建立时间';
COMMENT ON COLUMN public.img_del_record.update_time IS '更新时间';
COMMENT ON TABLE public.img_del_record IS '图片删除记录表，删除了需要判断表中这个文件是否还有未删除的，如果全部删除了将ceph文件备份至delete原
文件删除';


CREATE INDEX  ON public.img_del_record (url);
CREATE INDEX  ON public.img_del_record (id)  WHERE ceph_del = false;

-- ----------------------------
-- 图片元素记录 img_elements
-- ----------------------------

CREATE TABLE public.img_elements (
  id bigserial PRIMARY KEY,
  eid int8,
  elements jsonb,
  last_sync_time timestamptz(6),
  create_time timestamptz(6)
)
;
COMMENT ON COLUMN public.img_elements.id IS '图片元素记录id';
COMMENT ON COLUMN public.img_elements.eid IS '图片加密id';
COMMENT ON COLUMN public.img_elements.elements IS '图片元素';
COMMENT ON COLUMN public.img_elements.last_sync_time IS '最后更新时间';
COMMENT ON COLUMN public.img_elements.create_time IS '创建时间';
COMMENT ON TABLE public.img_elements IS '图片元素记录';

CREATE INDEX  ON public.img_elements (eid);

-- ----------------------------
-- 图片扩展表 img_ext
-- ----------------------------

CREATE TABLE public.img_ext (
  id bigserial PRIMARY KEY,
  img_id int8 NOT NULL,
  extend jsonb
)
;
COMMENT ON COLUMN public.img_ext.extend IS '存储一些图片其他信息，不常更新的不需要搜索的。例如exif city_info 存储sight 信息';
COMMENT ON TABLE public.img_ext IS '图片扩展表';

CREATE UNIQUE INDEX  ON public.img_ext (eid);

-- ----------------------------
-- 图片关键词 img_keyword
-- ----------------------------

CREATE TABLE public.img_keyword (
  id bigserial PRIMARY KEY,
  keyword varchar(255) ,
  create_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.img_keyword.id IS '图片关键词id';
COMMENT ON COLUMN public.img_keyword.keyword IS '关键词';
COMMENT ON COLUMN public.img_keyword.create_time IS '创建时间';
COMMENT ON TABLE public.img_keyword IS '图片关键词';

CREATE UNIQUE INDEX  ON public.img_keyword (keyword);


-- ----------------------------
-- 图片地区 img_location
-- ----------------------------

CREATE TABLE public.img_location (
  id bigserial PRIMARY KEY,
  location_name varchar(32) ,
  location_level varchar(32) ,
  location jsonb,
  parent_id int4 DEFAULT 0,
  img_num int4 DEFAULT 0,
  last_sync_time timestamptz(6),
  create_time timestamptz(6)
)
;
COMMENT ON COLUMN public.img_location.id IS '图片地区id';
COMMENT ON COLUMN public.img_location.location_name IS '地区名称';
COMMENT ON COLUMN public.img_location.location_level IS '地区级别';
COMMENT ON COLUMN public.img_location.location IS '地区';
COMMENT ON COLUMN public.img_location.parent_id IS '父级id';
COMMENT ON COLUMN public.img_location.img_num IS '图片数量';
COMMENT ON COLUMN public.img_location.last_sync_time IS '最后更新时间';
COMMENT ON COLUMN public.img_location.create_time IS '创建时间';
COMMENT ON TABLE public.img_location IS '图片地区';

CREATE UNIQUE INDEX  ON public.img_location (location_name);


-- ----------------------------
-- 图片库队列 img_queue
-- ----------------------------

CREATE TABLE public.img_queue (
  id bigserial PRIMARY KEY,
  job_name varchar(255) ,
  parameters jsonb,
  plan_consume_time timestamptz(6),
  consume_time timestamptz(6),
  tries int4 DEFAULT 1,
  failures int4 DEFAULT 0,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6)
)
;
COMMENT ON COLUMN public.img_queue.id IS '队列id';
COMMENT ON COLUMN public.img_queue.job_name IS 'job名称即class name';
COMMENT ON COLUMN public.img_queue.parameters IS '队列参数';
COMMENT ON COLUMN public.img_queue.plan_consume_time IS '队列记录消费时间';
COMMENT ON COLUMN public.img_queue.consume_time IS '队列消费时间';
COMMENT ON COLUMN public.img_queue.tries IS '队列最大尝试次数';
COMMENT ON COLUMN public.img_queue.failures IS '队列执行失败次数';
COMMENT ON COLUMN public.img_queue.create_time IS '队列创建时间';
COMMENT ON COLUMN public.img_queue.update_time IS '更新时间';
COMMENT ON TABLE public.img_queue IS '图片库队列';

CREATE INDEX  ON public.img_queue (plan_consume_time);

-- ----------------------------
-- 消息通知 message
-- ----------------------------

CREATE TABLE public.message (
  id bigserial PRIMARY KEY,
  username varchar(50) ,
  message jsonb,
  is_read bool NOT NULL DEFAULT false,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.message.id IS '自增id';
COMMENT ON COLUMN public.message.username IS '用户';
COMMENT ON COLUMN public.message.message IS '消息体,content是消息内容，type是消息分类，以后可随意扩展';
COMMENT ON COLUMN public.message.is_read IS '是否已读';
COMMENT ON COLUMN public.message.create_time IS '建立时间';
COMMENT ON COLUMN public.message.update_time IS '更新时间';
COMMENT ON TABLE public.message IS '消息通知';

CREATE INDEX  ON public.message (username);

-- ----------------------------
-- 管理员操作日志表 operate_log
-- ----------------------------

CREATE TABLE public.operate_log (
  id bigserial PRIMARY KEY,
  log_type varchar(50) ,
  log_id int8,
  content jsonb,
  operate_user varchar(50)  NOT NULL DEFAULT 'system'::character varying,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.operate_log.id IS '自增id';
COMMENT ON COLUMN public.operate_log.log_type IS '分类';
COMMENT ON COLUMN public.operate_log.content IS '内容json串';
COMMENT ON COLUMN public.operate_log.operate_user IS '操作用户,默认system';
COMMENT ON COLUMN public.operate_log.create_time IS '创建时间';
COMMENT ON COLUMN public.operate_log.update_time IS '更新时间';
COMMENT ON TABLE public.operate_log IS '管理员操作日志表';
CREATE INDEX  ON public.operate_log (operate_user);
CREATE INDEX  ON public.operate_log (log_type);


-- ----------------------------
-- 积分规则说明 point_rules
-- ----------------------------

CREATE TABLE public.point_rules (
  id bigserial PRIMARY KEY,
  point_obtain_rule varchar(150) ,
  point_related_instructions varchar(150) ,
  point_questions jsonb,
  username varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.point_rules.id IS '自增id';
COMMENT ON COLUMN public.point_rules.point_obtain_rule IS '积分获取规则';
COMMENT ON COLUMN public.point_rules.point_related_instructions IS '积分相关说明-文字';
COMMENT ON COLUMN public.point_rules.point_questions IS '积分相关说明-问答';
COMMENT ON COLUMN public.point_rules.username IS '修改用户名';
COMMENT ON COLUMN public.point_rules.create_time IS '创建时间';
COMMENT ON COLUMN public.point_rules.update_time IS '最后修改时间';
COMMENT ON TABLE public.point_rules IS '积分规则说明';



-- ----------------------------
--积分流水  points_trace
-- ----------------------------

CREATE TABLE public.points_trace (
  id bigserial PRIMARY KEY,
  username varchar(50) ,
  old_points int8,
  change_points int4,
  operate_source varchar(50) ,
  operate_username varchar(50) ,
  operate_info jsonb,
  create_time timestamptz(6) DEFAULT now(),
  is_first_pass bool,
  activity_id int8
)
;
COMMENT ON COLUMN public.points_trace.id IS '自增id';
COMMENT ON COLUMN public.points_trace.username IS '积分用户名';
COMMENT ON COLUMN public.points_trace.old_points IS '本次操作前已有积分数';
COMMENT ON COLUMN public.points_trace.change_points IS '本次积分变更：正数是增加、负数是减少';
COMMENT ON COLUMN public.points_trace.operate_source IS '积分来源：img_pass上传被审核通过praise点赞favorite收藏download下载city_first_pass城市首图task任务奖励';
COMMENT ON COLUMN public.points_trace.operate_username IS '操作人的用户名';
COMMENT ON COLUMN public.points_trace.operate_info IS '积分来源信息img_id\city_name\task_name';
COMMENT ON COLUMN public.points_trace.create_time IS '记录创建时间';
COMMENT ON COLUMN public.points_trace.is_first_pass IS '是否是第一次审核通过积分：t是；f不是';
COMMENT ON COLUMN public.points_trace.activity_id IS '活动任务id';
COMMENT ON TABLE public.points_trace IS '积分流水';

CREATE INDEX  ON public.points_trace (username);

-- ----------------------------
-- 点赞记录 praise
-- ----------------------------

CREATE TABLE public.praise (
  id bigserial PRIMARY KEY,
  img_id int8,
  username varchar(50) ,
  create_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON TABLE public.praise IS '点赞记录';
CREATE INDEX  ON public.praise (username,img_id);



-- ----------------------------
-- 商品表 product_info
-- ----------------------------

CREATE TABLE public.product_info (
  id bigserial PRIMARY KEY,
  eid int8,
  title varchar(255)  NOT NULL,
  description varchar(500)  NOT NULL,
  img_url jsonb NOT NULL,
  browse int8 DEFAULT 0,
  exchange_count int8 DEFAULT 0,
  on_sale bool,
  exchange_begin_time timestamptz(6),
  exchange_end_time timestamptz(6),
  exchange_description varchar(500)  NOT NULL,
  price numeric(10,2) NOT NULL,
  points int4 NOT NULL,
  stock int4 NOT NULL,
  remain_stock int4 NOT NULL,
  detail varchar(500)  NOT NULL,
  detail_title varchar(500)  NOT NULL,
  detail_img jsonb NOT NULL,
  create_username varchar(50)  NOT NULL,
  last_update_username varchar(50)  NOT NULL,
  sale_time timestamptz(6),
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.product_info.id IS '自增id';
COMMENT ON COLUMN public.product_info.eid IS '商品加密id';
COMMENT ON COLUMN public.product_info.title IS '商品名称';
COMMENT ON COLUMN public.product_info.description IS '商品描述';
COMMENT ON COLUMN public.product_info.img_url IS '商品图片';
COMMENT ON COLUMN public.product_info.browse IS '商品浏览量';
COMMENT ON COLUMN public.product_info.exchange_count IS '商品兑换量';
COMMENT ON COLUMN public.product_info.on_sale IS '商品上架状态：t上架；f下架';
COMMENT ON COLUMN public.product_info.exchange_begin_time IS '可兑换开始时间';
COMMENT ON COLUMN public.product_info.exchange_end_time IS '可兑换结束时间';
COMMENT ON COLUMN public.product_info.exchange_description IS '兑换说明';
COMMENT ON COLUMN public.product_info.price IS '商品价格（元）';
COMMENT ON COLUMN public.product_info.points IS '商品可兑换积分';
COMMENT ON COLUMN public.product_info.stock IS '商品总数量';
COMMENT ON COLUMN public.product_info.remain_stock IS '商品当前剩余可兑换库存';
COMMENT ON COLUMN public.product_info.detail IS '商品详情';
COMMENT ON COLUMN public.product_info.detail_title IS '商品详情标题';
COMMENT ON COLUMN public.product_info.detail_img IS '商品详情图片';
COMMENT ON COLUMN public.product_info.create_username IS '创建用户名';
COMMENT ON COLUMN public.product_info.last_update_username IS '最后修改用户名';
COMMENT ON COLUMN public.product_info.sale_time IS '上下架时间';
COMMENT ON COLUMN public.product_info.create_time IS '创建时间';
COMMENT ON COLUMN public.product_info.update_time IS '最后修改时间';
COMMENT ON TABLE public.product_info IS '商品表';

CREATE INDEX  ON public.product_info (eid);
CREATE INDEX  ON public.product_info (on_sale, points, remain_stock);

-- ----------------------------
-- 搜索日志表 search
-- ----------------------------

CREATE TABLE public.search (
  id bigserial PRIMARY KEY,
  extend jsonb,
  username varchar(50) ,
  create_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON TABLE public.search IS '搜索日志表';
CREATE INDEX  ON public.search (username);

-- ----------------------------
-- 搜索记录表 search_record
-- ----------------------------

CREATE TABLE public.search_record (
  id bigserial PRIMARY KEY,
  query varchar(20) ,
  username varchar(50) ,
  source varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  field_name varchar(255) 
)
;
COMMENT ON COLUMN public.search_record.id IS '自增id';
COMMENT ON COLUMN public.search_record.query IS '搜索词';
COMMENT ON COLUMN public.search_record.username IS '用户名';
COMMENT ON COLUMN public.search_record.source IS '来源：product商品、order兑换订单';
COMMENT ON COLUMN public.search_record.create_time IS '浏览时间';
COMMENT ON COLUMN public.search_record.field_name IS '字段名称';
COMMENT ON TABLE public.search_record IS '搜索记录表';

CREATE INDEX  ON public.search_record (username);

-- ----------------------------
-- 购物车 shop_cart
-- ----------------------------

CREATE TABLE public.shop_cart (
  id bigserial PRIMARY KEY,
  eid int8 DEFAULT 0,
  username varchar(50) ,
  img_id int8,
  is_del bool DEFAULT false,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.shop_cart.id IS '自增id';
COMMENT ON COLUMN public.shop_cart.eid IS '购物车加密id';
COMMENT ON COLUMN public.shop_cart.username IS '用户名';
COMMENT ON COLUMN public.shop_cart.img_id IS '图片id';
COMMENT ON COLUMN public.shop_cart.is_del IS '是否已删除';
COMMENT ON COLUMN public.shop_cart.create_time IS '建立时间';
COMMENT ON COLUMN public.shop_cart.update_time IS '更新时间';
COMMENT ON TABLE public.shop_cart IS '购物车';


CREATE INDEX  ON public.search_record (username);

-- ----------------------------
-- 系统用户表 system_user
-- ----------------------------

CREATE TABLE public.system_user (
  id bigserial PRIMARY KEY,
  domain_id int4 DEFAULT 0,
  role jsonb,
  username varchar(50) ,
  name varchar(50) ,
  dept jsonb,
  img varchar(500) ,
  extend jsonb,
  create_user varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now(),
  auth_time timestamptz(6),
  auth_state int2 DEFAULT 0,
  total_points int8 DEFAULT 0,
  current_points int8 DEFAULT 0,
  last_date_points int8 DEFAULT 0,
  last_point_date date,
  password char(32) 
)
;
COMMENT ON COLUMN public.system_user.domain_id IS '所属网站， 0去哪儿内部,对应关系先写配置文件。以后改成后台管理';
COMMENT ON COLUMN public.system_user.role IS '角色 admin/design/normal';
COMMENT ON COLUMN public.system_user.username IS '用户名';
COMMENT ON COLUMN public.system_user.name IS '姓名';
COMMENT ON COLUMN public.system_user.dept IS '部门';
COMMENT ON COLUMN public.system_user.img IS '头像';
COMMENT ON COLUMN public.system_user.extend IS 'extend';
COMMENT ON COLUMN public.system_user.create_user IS '创建用户';
COMMENT ON COLUMN public.system_user.create_time IS '创建时间';
COMMENT ON COLUMN public.system_user.update_time IS '更新时间';
COMMENT ON COLUMN public.system_user.auth_time IS '用户授权时间';
COMMENT ON COLUMN public.system_user.auth_state IS '用户授权状态：0未授权；1已授权';
COMMENT ON COLUMN public.system_user.total_points IS '用户总积分-只包含增加的';
COMMENT ON COLUMN public.system_user.current_points IS '用户当前积分-包含增加和减少的';
COMMENT ON COLUMN public.system_user.last_date_points IS '用户当日积分';
COMMENT ON COLUMN public.system_user.last_point_date IS '最后积分日期';
COMMENT ON COLUMN public.system_user.password IS '特殊用户密码';
COMMENT ON TABLE public.system_user IS '系统用户表';

CREATE INDEX  ON public.search_record (domain_id, username);


-- ----------------------------
-- 每日城市任务配置表 task_city_list
-- ----------------------------

CREATE TABLE public.task_city_list (
  id bigserial PRIMARY KEY,
  city_name varchar(100) ,
  begin_time timestamptz(6),
  end_time timestamptz(6),
  first_img_state varchar(20) ,
  first_img_time timestamptz(6),
  first_img_username varchar(50) ,
  create_time timestamptz(6) DEFAULT now(),
  update_time timestamptz(6) DEFAULT now()
)
;
COMMENT ON COLUMN public.task_city_list.id IS '自增id';
COMMENT ON COLUMN public.task_city_list.city_name IS '城市名称';
COMMENT ON COLUMN public.task_city_list.begin_time IS '任务开始时间';
COMMENT ON COLUMN public.task_city_list.end_time IS '任务结束时间';
COMMENT ON COLUMN public.task_city_list.first_img_state IS '首图上传状态：done已完成';
COMMENT ON COLUMN public.task_city_list.first_img_time IS '首图上传时间';
COMMENT ON COLUMN public.task_city_list.first_img_username IS '首图上传用户名';
COMMENT ON COLUMN public.task_city_list.create_time IS '创建时间';
COMMENT ON COLUMN public.task_city_list.update_time IS '更新时间';
COMMENT ON TABLE public.task_city_list IS '每日城市任务配置表';
