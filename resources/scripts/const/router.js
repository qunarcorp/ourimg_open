import Home from 'PAGE/home/index';
import List from 'PAGE/list/index';
import Detail from 'PAGE/detail/index';
import User from 'PAGE/user/index';
import Edit from 'PAGE/edit/index';
import bulkEdit from 'PAGE/edit/bulkEdit';
import Upload from 'PAGE/edit/upload';
import Cart from 'PAGE/cart/index';
import Material from 'PAGE/material/index';
import Credits from 'PAGE/credits/index';
import CreditsStore from 'PAGE/credits/creditsStore/index';
import GoodsDetail from 'PAGE/credits/goodsDetail/index';
import MyCredits from 'PAGE/credits/myCredits/index';
import CreditsRule from 'PAGE/credits/creditsRule/index';
import MyUpload from 'PAGE/myUpload/index';
import MySerials from 'PAGE/mySerials/index';
import MyMessage from 'PAGE/myMessage/index';
import MyFile from 'PAGE/myFile/index';
import AuthManage from 'PAGE/admin/authManage';
import ImgAudit from 'PAGE/admin/imgAudit';
import StoreManage from 'PAGE/admin/storeManage/index';
import ActiveManage from 'PAGE/admin/activeManage/index';
import Statistics from 'PAGE/admin/statistics/index';
import Help from 'PAGE/help/index';
import Copyright from 'PAGE/help/copyright';
import AuditRule from 'PAGE/help/auditRule';
import RankComponent from 'PAGE/rank/index';

export const ROUTER_MAP = {
    home: {
        label: '首页',
        path: '/',
        component: Home,
        exact: true,
        needLogin: false
    },
    list: {
        label: '搜索页',
        path: '/list',
        component: List,
        exact: true,
        needLogin: false
    },
    detail: {
        label: '详情页',
        path: '/detail',
        component: Detail,
        exact: false,
        needLogin: false
    },
    user: {
        label: '我的主页',
        path: '/user',
        component: User,
        exact: true,
        needLogin: false
    },
    cart: {
        label: '购物车',
        path: '/cart',
        component: Cart,
        exact: true,
        needLogin: true
    },
    edit: {
        label: '编辑页',
        path: '/edit',
        component: Edit,
        exact: true,
        needLogin: true
    },
    bulkEdit: {
        label: '编辑页',
        path: '/bulkedit',
        component: bulkEdit,
        exact: true,
        needLogin: true
    },
    upload: {
        label: '上传图片',
        path: '/upload',
        component: Upload,
        exact: true,
        needLogin: true
    },
    material: {
        label: '个人中心',
        path: '/material',
        component: Material,
        exact: false,
        needLogin: false
    },
    help: {
        label: '帮助',
        path: '/help',
        component: Help,
        exact: false,
        needLogin: false
    },
    credits: {
        label: '积分中心',
        path: '/credits',
        component: Credits,
        exact: false,
        needLogin: false
    },
    rank: {
        label: '排行榜',
        path: '/rank',
        component: RankComponent,
        exact: true,
        needLogin: false
    }
}

export const MATERIAL_MAP = {
    myUpload: {
        label: '我的上传',
        path: '/material/myUpload',
        component: MyUpload,
        exact: true,
        needLogin: true
    },
    myDownloads: {
        label: '我的下载',
        path: '/material/myDownloads',
        component: MySerials,
        exact: true,
        needLogin: true
    },
    myCollection: {
        label: '我的收藏',
        path: '/material/myCollection',
        component: MySerials,
        exact: true,
        needLogin: true
    },
    myFootprint: {
        label: '我的足迹',
        path: '/material/myFootprint',
        component: MySerials,
        exact: true,
        needLogin: true
    },
    myFile: {
        label: '我的授权书',
        path: '/material/myFile',
        component: MyFile,
        exact: true,
        needLogin: true
    },
    message: {
        label: '消息中心',
        path: '/material/message',
        component: MyMessage,
        exact: true,
        needLogin: true
    },
    authManage: {
        label: '权限管理',
        path: '/material/authManage',
        component: AuthManage,
        exact: true,
        needLogin: true,
        role: 'admin'
    },
    imgAudit: {
        label: '素材审核',
        path: '/material/imgAudit',
        component: ImgAudit,
        exact: true,
        needLogin: true,
        role: 'admin'
    },
    storeManage: {
        label: '商城管理',
        path: '/material/storeManage',
        component: StoreManage,
        exact: true,
        needLogin: true,
        role: 'admin'
    },
    activeManage: {
        label: '活动任务管理管理',
        path: '/material/activeManage',
        component: ActiveManage,
    },
    statistics: {
        label: '数据统计',
        path: '/material/statistics',
        component: Statistics,
        exact: true,
        needLogin: true,
        role: 'admin'
    }
}

export const HELP_MAP = {
    copyright: {
        label: '加入协议',
        path: '/help/copyright',
        component: Copyright,
        exact: true,
        needLogin: true
    },
    auditRule: {
        label: '图片审核规范',
        path: '/help/auditRule',
        component: AuditRule,
        exact: true,
        needLogin: true
    }
}

export const CREDITS_MAP = {
    creditsStore: {
        label: '积分商城',
        path: '/credits/creditsStore',
        component: CreditsStore,
        exact: true,
        needLogin: true
    },
    goodsDetail: {
        label: '商品详情',
        path: '/credits/goodsDetail',
        component: GoodsDetail,
        exact: true,
        needLogin: true
    },
    myCredits: {
        label: '我的积分',
        path: '/credits/myCredits',
        component: MyCredits,
        exact: true,
        needLogin: true
    },
    creditsRule: {
        label: '积分细则',
        path: '/credits/creditsRule',
        component: CreditsRule,
        exact: true,
        needLogin: true
    }
}
