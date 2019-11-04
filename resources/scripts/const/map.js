import Img from "../page/admin/statistics/userImg";
import Place from "../page/admin/statistics/place";
import Points from "../page/admin/statistics/points";
import Interaction from "../page/admin/statistics/interaction";
import Audit from "../page/admin/statistics/audit";

export const purposeMap = {
    1: '不限',
    2: '可做商业用途',
    3: '此素材仅限编辑传媒类使用，不可用作广告宣传等商业用途'
};

export const auditStatusMap = {
    '草稿箱': 0,
    '审核中': 1,
    '未通过': 3,
    '已通过': 2
};

export const auditLabelMap = {
    0: 'draft_box',
    1: 'under_review',
    3: 'not_pass',
    2: 'passed'
};

export const messageTypeMap = {
    f: '未读',
    t: '已读'
};

export const authTypeMap = {
    'admin': '管理员',
    'design': '设计运营'
};

export const adminAuditTypeMap = {
    '待审核': 1,
    '已通过': 2,
    '未通过': 3,
    '已下架': 4,
    '系统驳回': 5
};
export const adminAuditLabelMap = {
    1: 'pending_count',
    2: 'passed_count',
    3: 'reject_count',
    4: 'remove_count',
    5: 'system_reject_count'
};

export const rejectReasonMap = {
    1: '图片标题问题',
    2: '图片分类有误',
    3: '拍摄地点信息不清晰',
    4: '图片关键词问题',
    5: '图片质量问题',
    6: '图片含敏感/违规元素',
    7: '其他',
    8: '图片残缺无法读取信息'
};

export const blankHintMap = {
    cart: '您的购物车是空哒~',
    search: '木有您需要的素材，再逛逛吧~',
    myUpload: '您还木有上传过的素材～',
    myDownloads: '您还木有下载过的素材～',
    myCollection: '您还木有收藏过的素材～',
    myFootprint: '您还木有浏览过的素材～',
    adminAudit1: '无未审核的素材',
    adminAudit2: '无已通过的素材',
    adminAudit3: '无未通过的素材',
    adminAudit4: '无已下架的素材',
    message: '消息空空',
    loading: '暂未开放，敬请期待！',
    empty: '空空如也~'
};

export const copyrightMap = {
    2: '商业用途：可用于商品、广告、产品包装以及任何其他盈利活动',
    3: '编辑用途：可用于新闻，文章插图等用途，禁止用于一切商业广告、销售类、与商品售卖相关的用途'
};

//  0 草稿箱|1 审核中| 2 已通过| 3 已驳回 | 4 已删除
export const ownerMessageMap = [
    '已在您的草稿箱 >',
    '已在审核中 >',
    '查看详情 >'
];

export const locationMap = ['country', 'province', 'city'];

export const keywordFilterList = ['null', 'NULL', ''];

export const medalIconMap = {
    0: '/img/gold.png',
    1: '/img/silver.png',
    2: '/img/copper.png'
};

export const storeTabMap = {
    PUBLISH: '商品发布',
    MANAGE: '商品管理',
    EXCHANGE: '兑换明细',
    RULE: '规则录入'
};

export const orderStatusMap = {
    exchange_success: '兑换成功',
    exchange_fail: '兑换失败',
    shipped: '已发货'
};

export const creditsTimeMap = {
    all: '全部记录',
    one_month: '一个月内记录',
    three_month: '三个月内记录',
    half_year: '半年内记录',
    one_year: '一年内记录'
};

export const goodsTypeMap = {
    AVALIABLE: '马上兑换',
    LACK_CREDITS: '积分不足',
    LACK_STOCK: '已兑完',
    SOLD_OUT: '已下架'
};

export const goodsManageTypeMap = {
    no_sale: '未上架',
    off_sale: '已下架',
    no_stock: '已兑完',
    can_exchange: '可兑换'
};

export const ignoreSmallType = ['6','7','8','11','13'];

export const rankMap = {
    "upload": "贡献榜",
    "browse": "浏览榜",
    "popularity": "人气榜",
    "download": "下载榜",
    "points": "积分榜",
};

export const statisticTabMap = {
    USER_IMG: '图片统计',
    PLACE: '拍摄地点覆盖',
    POINTS: '积分数据统计',
    INTERACTION: '交互数据统计',
    AUDIT: '审核数据统计',
    USER_STATISTICS: '用户数据统计'
};
