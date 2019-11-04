<?php

/**
 * 更新图片驳回原因
 */


require_once __DIR__."/../../htdocs/app_api.php";
set_time_limit(0);

$rejectReason = $dic_img['reject_reason'];

$imgMaxId = DB::GetQueryResult("select max(id) from public.img",true)['max'] + 1;

function getImgSql()
{
    global $imgMaxId;
    return "select id, eid, reject_reason, audit_desc from public.img where id < {$imgMaxId} and reject_reason is not null order by id desc limit 1";
}

while($row = DB::GetQueryResult(getImgSql(),true)) {
    $imgMaxId = $row['id'];
    $row["reject_reason"] = trim(trim($row["reject_reason"], '{"'), '"}');
    $reason = [];
    if ($row["reject_reason"] != 7) {
        array_push($reason, $rejectReason[$row["reject_reason"]]);
    }
    if ($row["audit_desc"]) {
        array_push($reason, $row["audit_desc"]);
    }

    $reason = DB::EscapeString(array2pgarray($reason));
    $updateImgRejectReasonSql = <<<SQL
    update public.img set reject_reason = '$reason', audit_desc = null where id = {$row['id']}
SQL;
    DB::Query($updateImgRejectReasonSql);
}

$imgAuditRecordMaxId = DB::GetQueryResult("select max(id) from public.audit_records",true)['max'] + 1;

function getImgRejectSql()
{
    global $imgAuditRecordMaxId;
    return "select id, img_id, reject_info from public.audit_records where id < {$imgAuditRecordMaxId} and operate_type = 'reject' order by id desc limit 1";
}

while($row = DB::GetQueryResult(getImgRejectSql(),true)) {
    $imgAuditRecordMaxId = $row['id'];
    $rejectInfo = json_decode($row["reject_info"], true);
    $reason = [];
    if ($rejectInfo["reason"] != 7) {
        array_push($reason, $rejectReason[$rejectInfo["reason"]]);
    }
    if ($rejectInfo["desc"]) {
        array_push($reason, $rejectInfo["desc"]);
    }
    $newRejectInfo = DB::EscapeString(json_encode([
        "reason" => $reason,
        "desc" => "",
    ], JSON_UNESCAPED_UNICODE));
    $updateImgAuditRecordSql = <<<SQL
    update public.audit_records set reject_info = '{$newRejectInfo}' where id = {$row['id']}
SQL;
    DB::Query($updateImgAuditRecordSql);
}