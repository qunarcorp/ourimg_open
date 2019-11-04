<?php
/**
 * 后台登录权限控制
 */

$value = [
    // 增加权限控制的api
    "super_admin" => [
        // 商品管理接口
        "/api/goods/manage.php",
        "/api/goods/offSale.php",
        "/api/goods/onSale.php",
        "/api/goods/store.php",
        "/api/goods/update.php",
        "/api/goods/search_record.php",
        "/api/goods/update.php",
        "/api/goods/img_upload.php"

        //兑换订单管理员后台
        ,"/api/order_admin/decrypt_mobiel"
        ,"/api/order_admin/exchange_list"
        ,"/api/order_admin/query_suggest"

        //积分规则录入
        ,"/api/points/rules_save",
        // 审核拒绝原因
        "/api/audit/reject_reason.php",
    ],
    "admin" => [
        "/api/auth/manager.php",
        "/api/auth/company_dept.php",
        "/api/auth/employee.php",
        "/api/auth/remove_power.php",
        "/api/auth/add_power.php",
        //审核接口
        "/api/audit/audit_counts.php",
        "/api/audit/audit_list.php",
        "/api/audit/audit_pass.php",
        "/api/audit/del_imgs_batch.php",
        "/api/audit/operate_trace.php",
        "/api/audit/reject.php",
        // 审核拒绝原因
        "/api/audit/reject_reason.php",
    ],
    "design" => [
    ],
];