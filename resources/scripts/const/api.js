export const globalApi = {
    checkLogin: `/api/img_search/user_login_check.php`,
    // logout: `/api/qssoleave`,
    // getVisit: `/api/visit/`
    loginout:`/loginout.php`,
    totalCount: '/api/personal/data_total.php',
    tabsList: '/api/img_search/filter_item.php',
    copyrightAuth: '/api/upload/user_auth.php',
    loginWay: '/login_way.php',
    login: '/login.php',
};

export const listApi = {
    location: `/api/img_search/get_locations.php`,
    filterOption: `/api/img_search/filter_item.php`
}

export const componentApi = {
    upload: `/api/img_upload.php`,
    edit: `/api/img_edit.php`,
    del: `/api/img_search/img_del.php`,
    citySuggest: `https://sgt.package.qunar.com/suggest/sight/sgt?isContain=true&flMore=&type=%E5%8C%BA%E5%8E%BF,%E5%9B%BD%E5%AE%B6,%E5%9C%B0%E5%8C%BA,%E5%9F%8E%E5%B8%82,%E6%99%AF%E5%8C%BA,%E6%99%AF%E7%82%B9,%E7%9C%81%E4%BB%BD&query=`,
    uploadDel: `/api/upload_del.php`,
    edits: `/api/img_edits.php`
}

export const galleryApi = {
    getImgs: `/api/img_search/get_imgs.php`,
    getOtherImgs: '/api/others/get_imgs.php',
    getRecommendImgs: `/api/img_search/recommend_to_you.php`,
    getMyUploadsImgs: `/api/img_search/my_uploads.php`,
    getMyFavoriteImgs: `/api/img_search/my_favorites.php`,
    getMyDownloadImgs: `/api/img_search/my_downloads.php`,
    likeImg: `/api/img_search/do_praise.php`,
    collectImg: `/api/img_search/do_favorite.php`,
    add: `/api/shop_cart/add.php`,
    delCart: `/api/shop_cart/del_eid.php`
}

export const detailApi = {
    getImgDetail: `/api/img_search/img_detail.php`,
    resizeImg: `/api/img_search/get_url_resize.php`,
    downloadImg: `/api/download.php`
}

export const cartApi = {
    list: `/api/shop_cart/list.php`,
    deleteItems: `/api/shop_cart/del.php`,
    cartCount: `/api/shop_cart/count.php`,
    downloadImg: `/api/shop_cart/download.php`
}

export const mySerialsApi = {
    myDownloads: '/api/img_search/my_downloads.php',
    myCollection: '/api/img_search/my_favorites.php',
    myFootprint: '/api/personal/my_browses.php',
    myCollectionDelete: '/api/personal/del_favorite_batch.php',
    myFootprintDelete: '/api/personal/del_browse_batch.php'
}

export const myUploadApi = {
    myUploadsCount: '/api/personal/my_uploads_count.php',
    myUploadsImgs: `/api/img_search/my_uploads.php`,
    changeImgStatus: `/api/img_search/do_edit.php`,
    delUploadImg: `/api/personal/del_imgs_batch.php`
}

export const myMessageApi = {
    getMessageList: '/api/message/list.php',
    setRead: `/api/message/read.php`
}

export const authManageApi = {
    remove_power: '/api/auth/remove_power.php',
    getManageList: '/api/auth/manager.php',
    addManage: '/api/auth/add_power.php',
    searchUser: '/api/auth/employee.php',
    getCompanyDept: '/api/auth/company_dept.php',
    getBaseCompanyDept: '/api/auth/base_company_dept.php'
}

export const adminApi = {
    getAuditCount: '/api/audit/audit_counts.php',
    getAuditList: `/api/audit/audit_list.php`,
    auditPass: '/api/audit/audit_pass.php',
    auditReject: `/api/audit/reject.php`,
    auditBatchReject: `/api/audit/reject_batch.php`,
    getAuditTrace: `/api/audit/operate_trace.php`,
    auditDel: `/api/audit/del_imgs_batch.php`,
    auditRealDel: `/api/personal/img_real_del.php`,
    auditSystemReject: `/api/audit/system_reject_list.php`,
    getActivityList: '/api/activity_manage/list.php',
    activityOffline: '/api/activity_manage/offline.php',
    activityOnline: '/api/activity_manage/online.php',
    createActivity: '/api/activity_manage/create.php',
    editActivity: '/api/activity_manage/edit.php',
    activityDetail: '/api/activity_manage/detail.php',
    rejectReason: '/api/audit/reject_reason.php',
    star: '/api/audit/star.php',
    unstar: '/api/audit/unstar.php',
}

export const creditsApi = {
    dailyMission: '/api/task/completion_info.php',
    creditsBorad: '/api/points/leader_board.php',
    creditsRecord: '/api/points/points_list.php',
    exchangeRecord: '/api/order/exchange_list.php',
    goodsList: '/api/goods/list.php',
    creditsRules: '/api/points/rules_get.php',
    goodsDetail: '/api/goods/info.php',
    exchangeGoods: '/api/order/product_exchange.php'
}

export const creditsManageApi = {
    exchangeDetail: '/api/order_admin/exchange_list.php',
    goodsList: '/api/goods/manage.php',
    goodsOnSale: '/api/goods/onSale.php',
    goodsOffSale: '/api/goods/offSale.php',
    shipGoods:'/api/goods/order/ship.php',
    orderSuggestion: '/api/order_admin/query_suggest.php',
    goodsSuggestion: '/api/goods/search_record.php',
    editRules: '/api/points/rules_save.php',
    uploadImgs: '/api/goods/img_upload.php',
    addNewGoods: '/api/goods/store.php',
    editGoods: '/api/goods/update.php',
    getUserNumber: '/api/order_admin/decrypt_mobile.php'
}

export const rankApi = {
    browseRank: '/api/rank/browse.php',
    favoriteRank: '/api/rank/favorite.php',
    pointsRank: '/api/rank/points.php',
    popularityRank: '/api/rank/popularity.php',
    praiseRank: '/api/rank/praise.php',
    uploadRank: '/api/rank/upload.php',
    downloadRank: '/api/rank/download.php',
}

export const statisticApi = {
    imgStatus: '/api/statistics/img_status.php',
    userList: '/api/statistics/user_list.php',
    placeImgStatistic: '/api/statistics/place_img_statistic.php',
    placeImgNum: '/api/statistics/place_img_num.php',
    points: '/api/statistics/points.php',
    userPointList: '/api/statistics/user_point_list.php',
    userInteraction: '/api/statistics/user_interaction.php',
    auditUserList: '/api/statistics/audit_user_list.php',
    interactionNum: '/api/statistics/interaction_num.php',
    auditStatistics: '/api/statistics/audit_statistics.php',
    userStatistics: '/api/statistics/user_statistics.php',
    userStatisticsNum: '/api/statistics/user_statistics_num.php',
}

export const statisticExportApi = {
    userStatisticsExport: '/api/statistics/user_statistics_export.php',
    imgStatisticsExport: '/api/statistics/img_statistics_export.php',
    placeImgStatisticExport: '/api/statistics/place_img_statistic_export.php',
    userInteractionStatisticsExport: '/api/statistics/user_interaction_statistics_export.php',
    userPointStatisticsExport: '/api/statistics/user_point_statistics_export.php',
    auditStatisticsExport: '/api/statistics/audit_statistics_export.php',
}
