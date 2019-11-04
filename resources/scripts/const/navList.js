export const NAVLIST = [
    {
        name: "素材管理",
        key: "/material",
        path: "/myUpload",
        children: [
            {
                name: "我的上传",
                key: "/material/myUpload",
                path: "/material/myUpload",
                children: null
            },
            {
                name: "我的下载",
                key: "/material/myDownloads",
                path: "/material/myDownloads",
                children: null
            },
            {
                name: "我的收藏",
                key: "/material/myCollection",
                path: "/material/myCollection",
                children: null
            },
            {
                name: "我的足迹",
                key: "/material/myFootprint",
                path: "/material/myFootprint",
                children: null
            }
        ]
    },
    {
        name: "我的授权书",
        key: "/material/myFile",
        path: "/material/myFile",
        role: ["1"],
        children: null
    },
    {
        name: "消息中心",
        key: "/material/message",
        path: "/material/message",
        children: null
    },
    {
        name: "管理员中心",
        key: "/material/administrator",
        path: "/material/administrator",
        role: ["admin", "super_admin"],
        children: [
            {
                name: "素材审核",
                key: "/material/imgAudit",
                path: "/material/imgAudit",
                children: null
            },
            {
                name: "权限管理",
                key: "/material/authManage",
                path: "/material/authManage",
                children: null
            },            {
                name: "活动任务管理",
                key: "/material/activeManage",
                path: "/material/activeManage",
                children: null
            },
            {
                name: "商城管理",
                key: "/material/storeManage",
                path: "/material/storeManage",
                role: ["super_admin"],
                children: null
            },
            {
                name: "数据统计",
                key: "/material/statistics",
                path: "/material/statistics",
                role: ["super_admin"],
                children: null
            }
        ]
    }
    // {
    //     name: "设置",
    //     key: "/material/set",
    //     path: "/material/set",
    //     children: null
    // }
];

export const HELPLIST = [
    {
        name: "加入协议",
        key: "/help/copyright",
        path: "/help/copyright",
        children: null
    },
    {
        name: "图片审核规范",
        key: "/help/auditRule",
        path: "/help/auditRule",
        children: null
    }
];
