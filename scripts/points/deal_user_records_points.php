<?php

/**
 * 处理用户积分--从操作记录中获取数据
 * 每分钟执行
 */

require_once __DIR__."/../../htdocs/app_api.php";

//保证当前只有一个任务在执行
crontab_run_one("crontab","user_records_points_mark");

$log_prefer = 'crontab';
$log_action = 'user_records_points';

//记录日志，开始执行
QLog::info($log_prefer,$log_action,"开始计算用户积分");

//查询需要处理积分的记录
$record_sql = " SELECT * FROM ".QImgSearch::$auditRecordsTableName." WHERE point_state = 'pending' ORDER BY id ASC LIMIT 1 ";

while( TRUE ){
    $record_rs = DB::GetQueryResult($record_sql);
    //没有需要处理的记录，退出循环
    if( empty($record_rs) || !is_array($record_rs) ){
        QLog::info($log_prefer, $log_action,"没有查询到需要处理积分的记录");
        break;
    }
    
    //有记录，开始计算
    QLog::info($log_prefer, $log_action,"audit_records表记录id：".$record_rs['id']."；详细信息：". var_export($record_rs, true));
    
    //获取图片信息
    $img_params = [
        'id' => $record_rs['img_id'],
    ];
    $img_info = QImgSearch::getOneImg($img_params);
    if( !$img_info || !is_array($img_info) ){
        QLog::info($log_prefer, $log_action,"图片id=".$record_rs['img_id']."的图片记录没有查询到");
        QActivity_TaskPoint::errorUpdate(['id'=>$record_rs['id'],'point_state'=>'img_id_error']);
        continue;
    }
    
    /*
     * 判断类型
     * 1、提交且为首次提交：任务分
     * 2、审核通过：图片分
     */
    if( $record_rs['operate_type'] == 'submit' ){
        //判断是否是第一次提交审核--不是第一次 不计算积分;只是首次提交审核--只任务分
        $is_first = QActivity_TaskPoint::isFirstSubmit(['id'=>$record_rs['id'], 'img_id'=>$record_rs['img_id']]);
        if( !$is_first ){
            QLog::info($log_prefer,$log_action,"不是首次提交，不计算任务积分");
            $update_rs = QActivity_TaskPoint::errorUpdate(['id'=>$record_rs['id'],'point_state'=>'not_first_submit']);
            QLog::info($log_prefer,$log_action,"状态更新：".$update_rs ? "成功" : "失败");
            continue;
        }
        
        //开始处理
        DB::TransBegin();
        
        //基础处理--原有积分任务处理【上传10张图片奖励5积分】
        $old_task_params = [
            'username' => $record_rs['username'],
            'operate_username' => $record_rs['username'],
            'img_id' => $record_rs['img_id'],
        ];
        $old_task_rs = QImgTask::uploadTasks($old_task_params);
        if( !$old_task_rs ){
            //失败退出
            DB::TransRollback();
            QLog::info($log_prefer,$log_action,"原有的长期任务积分计算失败，退出：". var_export($old_task_params, true));
            continue;
        }
        
        //查询当前有效期内、在线状态、符合城市和主题的任务活动
        $img_current_info = json_decode($record_rs['new_data'], true);
        $activity_params = [
            'city_id' => $img_current_info['city_id'],
            'theme_keywords' => $img_current_info['keyword_arr'],
        ];
        $activity_list = QActivity_TaskPoint::getValidActivities($activity_params);
        if( !$activity_list ){
            QLog::info($log_prefer,$log_action,"没有满足条件的活动，不计算任务积分");
            $update_rs = QActivity_TaskPoint::errorUpdate(['id'=>$record_rs['id'],'point_state'=>'no_activity']);
            if( !$update_rs ){
                //失败退出
                DB::TransRollback();
                QLog::info($log_prefer,$log_action,"状态更新失败，退出");
                continue;
            }
            DB::TransCommit();
            QLog::info($log_prefer,$log_action,"积分处理完成");
            continue;
        
        }
        
        QLog::info($log_prefer,$log_action,"计算任务积分：". var_export($activity_list, true));
        //计算任务分--循环查看当前任务
        foreach( $activity_list as $k => $v ){
            //查询记录
            $activity_record_rs = QActivity_TaskPoint::getCurrentActivities($v,$record_rs['username']);

            //根据记录判断并处理
            if( !$activity_record_rs || $activity_record_rs['complete_state'] != 'done' ){
                //不存在或未完成才处理，如果已经完成，不需要处理
                if( !$activity_record_rs ){
                    //当前没有记录，insert
                    $complete_state = QActivity_TaskPoint::addTask(['activity_info'=>$v, 'audit_record'=>$record_rs]);
                }else{
                    //有记录 但是没完成，要更新
                    $points_deal_params = [
                        'activity_record_rs' => $activity_record_rs,
                        'activity_info' => $v,
                        'audit_record' => $record_rs,
                    ];
                    $complete_state = QActivity_TaskPoint::updateTask($points_deal_params);
                }
                
                //判断处理
                if( !$complete_state ){
                    DB::TransRollback();
                    QLog::info($log_prefer,$log_action,"任务表更新失败，退出");
                    continue;
                }elseif( $complete_state == 'done' ){
                    //已完成任务，需要增加任务积分
                    $points_params = [
                        'complete_state' => $complete_state,
                        'audit_record' => $record_rs,
                        'activity_info' => $v,
                    ];
                    $activity_points = QActivity_TaskPoint::pointChange($points_params);
                    if( !$activity_points ){
                        DB::TransRollback();
                        QLog::info($log_prefer,$log_action,"任务积分更新，退出");
                        continue;
                    }
                }
            }

            //更新活动表中图片数量
            $activity_update_rs = QActivity_TaskPoint::addImgCount($v);
            if( !$activity_update_rs ){
                DB::TransRollback();
                QLog::info($log_prefer,$log_action,"活动表中图片数量更新失败，退出");
                continue;
            }

            //插入活动和图片对应表
            $relation_info = [
                'activity_id' => $v['id'],
                'img_id' => $record_rs['img_id'],
            ];
            $relation_rs = QActivity_TaskPoint::insertRelation($relation_info);
            if( !$relation_rs ){
                DB::TransRollback();
                QLog::info($log_prefer,$log_action,"活动和图片对应关系表插入失败，退出:". var_export($relation_info, true));
                continue;
            }
        }
        
        //更新该条记录积分统计状态
        $update_rs = QActivity_TaskPoint::updateAuditRecord(['record_id'=>$record_rs['id']]);
        if( !$update_rs ){
            //失败退出
            DB::TransRollback();
            QLog::info($log_prefer,$log_action,"状态更新失败，退出");
            continue;
        }
        DB::TransCommit();
        QLog::info($log_prefer,$log_action,"积分处理完成");
        continue;
    
    }elseif( $record_rs['operate_type'] == 'passed' ){
        //开始处理
        DB::TransBegin();
        
        //审核通过
        $is_first = QActivity_TaskPoint::getFirstPassList(['img_id'=>$record_rs['img_id']]);
        QLog::info($log_prefer,$log_action,"开始计算图片分");
        
        //计算图片积分
        if( !$is_first['is_first'] ){
            QLog::info($log_prefer,$log_action,"不是首次审核通过,第一次审核通过图片积分信息：". var_export($is_first['points_list'], true));
            
            //计算图片积分:根据之前已有的图片积分记录
            $change_points_arr = array_column($is_first['points_list'], 'change_points');
            $change_points = array_sum($change_points_arr);
            
            //加精选推荐积分
            if( $img_info['star'] == 't' ){
                $change_points+= QImgPoints::$pointConf['recommend'];
            }
            
            $params_insert = [
                'operate_username' => $record_rs['username'],
                'img_id' => $record_rs['img_id'],
                'operate_source' => QActivity_TaskPoint::getOperateSource(['operate_source'=>$is_first['points_list'][0]['operate_source'],'operate_type'=>'pass']),
                'username' => $img_info['username'],
                'change_points' => $change_points,
                'total_points_change' => 'no',
            ];

            QLog::info($log_prefer,$log_action,"积分信息：". var_export($params_insert, true));
            $insert_rs = QImgPoints::pointInsert($params_insert);
            if( !$insert_rs ){
                DB::TransRollback();
                QLog::info($log_prefer,$log_action,"积分更新失败，退出");
                continue;
            }
            
        }else{
            QLog::info($log_prefer,$log_action,"首次审核通过");
            QLog::info($log_prefer,$log_action,"计算图片基础分");

            //更新积分
            $params_insert = [
                'operate_username' => $record_rs['username'],
                'img_id' => $record_rs['img_id'],
                'operate_source' => 'img_pass',
                'username' => $img_info['username'],
                'is_first_pass' => 't',
                'change_points' => QImgPoints::$pointConf['img_pass'],
            ];

            QLog::info($log_prefer,$log_action,"积分信息：". var_export($params_insert, true));
            $insert_rs = QImgPoints::pointInsert($params_insert);
            if( !$insert_rs ){
                DB::TransRollback();
                QLog::info($log_prefer,$log_action,"图片基础积分更新失败，退出");
                continue;
            }
            
            //查询当前有效期内、在线状态、符合城市和主题的任务活动，计算积分
            $img_current_info = json_decode($record_rs['new_data'], true);
            $activity_params = [
                'city_id' => $img_current_info['city_id'],
                'theme_keywords' => $img_current_info['keyword_arr'],
            ];
            $activity_list = QActivity_TaskPoint::getValidActivitiesImgpoints($activity_params);
            
            //没有满足条件的任务，只计算普通图片积分
            if( $activity_list ){
                QLog::info($log_prefer,$log_action,"有满足条件的活动，计算活动任务图片分". var_export($activity_list, true));
                
                //计算图片分
                foreach( $activity_list as $k => $v ){
                    //更新积分
                    $params_insert = [
                        'operate_username' => $record_rs['username'],
                        'img_id' => $record_rs['img_id'],
                        'activity_id' => $v['id'],
                        'operate_source' => 'activity_img_pass',
                        'username' => $img_info['username'],
                        'is_first_pass' => 't',
                        'change_points' => $v['img_upload_points'],
                    ];

                    QLog::info($log_prefer,$log_action,"积分信息：". var_export($params_insert, true));
                    $insert_rs = QImgPoints::pointInsert($params_insert);
                    if( !$insert_rs ){
                        DB::TransRollback();
                        QLog::info($log_prefer,$log_action,"积分更新失败，退出");
                        continue;
                    }
                }
                
            }
        }
        
        //更新该条记录积分统计状态
        $update_rs = QActivity_TaskPoint::updateAuditRecord(['record_id'=>$record_rs['id']]);
        if( $update_rs ){
            DB::TransCommit();
        }else{
            DB::TransRollback();
            QLog::info($log_prefer,$log_action,"状态更新失败，退出");
        }

        QLog::info($log_prefer,$log_action,"图片分计算完成");
        continue;
        
    }else{
        //类型错误--更新state=no_need
        QLog::info($log_prefer,$log_action,"当前操作类型不需要计算任务积分");
        $update_rs = QActivity_TaskPoint::errorUpdate(['id'=>$record_rs['id'],'point_state'=>'no_need']);
        QLog::info($log_prefer,$log_action,"状态更新：".$update_rs ? "成功" : "失败");
        continue;
    }
}

QLog::info($log_prefer,$log_action,"本次脚本执行完成");
