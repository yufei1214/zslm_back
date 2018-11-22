<?php

namespace App\Models;
use DB;
use App\Models\system_setup as SystemSetup;
use Illuminate\Http\Request;

class zslm_information
{
    public static $sTableName = 'zslm_information';

    public static function getInformIdToData($id = 0) {
        $data = DB::table(self::$sTableName)
        ->leftJoin('dict_information_type', self::$sTableName . '.z_type', '=', 'dict_information_type.id')
        ->where(self::$sTableName . '.id', $id)
        ->select(
            'dict_information_type.id', 
            self::$sTableName.'.weight', 
            self::$sTableName.'.zx_name', 
            'dict_information_type.name',
            self::$sTableName.'.create_time'
        )->first();

        return !empty($data) ? $data : [];
    }



    public static function setInformWeight($informId = 0, $weight = 0) {
        return DB::table(self::$sTableName)->where('id', $informId)->update([
            'weight'      => $weight,
            'update_time' => time()
        ]);
    }


    public static function getInformPageData(array $dataArr = []) {
        $handel = DB::table(self::$sTableName)->where([
            ['is_delete', '=', 0],
            ['is_show', '=', 0]
        ]);
        if(!empty($dataArr['inform_type_id']))
            $handel = $handel->where('z_type', $dataArr['inform_type_id']);

        $handel = $handel->where('zx_name', 'like', '%' . $dataArr['title_keyword'] . '%');

        if($dataArr['sort_type'])
            $handel = $handel->orderBy('create_time', 'desc');
        else
            $handel = $handel->orderBy('create_time', 'asc');
        
        $count = $handel->count();

        $handel = $handel
            ->offset($dataArr['page_count'] * $dataArr['page_number'])
            ->limit($dataArr['page_count'])
            ->select('id', 'zx_name', 'z_type', 'create_time')
            ->get()
            ->toArray();

        return ['count'=> $count, 'data' => $handel];
    }


    private static function judgeScreenState($screenState, $title, &$handle) {
        switch($screenState) {
            case 0:
                $handle = $handle->where($title, '=', 0);
                break;
            case 1:
                $handle = ($title == 'father_id') ? $handle->where($title, '<>', 0) : $handle->where($title, '=', 1);
                break;
            default :
                break;
        }
    }

    private static function judgeInfoType($infoType, $title, &$handle) {
        switch($infoType) 
        {
            case 0:
                break;
            default :
                $handle->where($title, $infoType);
                break;
        }
    }

    public static function getInformationPageMsg(array $selectMsg = []) {

        $handle = DB::table(self::$sTableName)->where('is_delete', 0);

        $sort_type_arr = [['weight', 'desc'], ['weight', 'asc'], ['create_time', 'desc']];

        switch($selectMsg['screenType'])
        {
            case 0:
                $handle = $handle->where('is_show', 0);
                break;
            case 1:
                $handle = $handle->where('is_show', 1);
                break;
            default :
                break;
        }

        switch($selectMsg['screenState'])
        {
            case 0:
                $handle = $handle->where('is_recommend', 1);
                break;
            case 1:
                $handle = $handle->where('is_show', 0);
                break;
            default :
                break;
        }

        switch($selectMsg['infoType']) 
        {
            case 0:
                break;
            default :
                $handle->where('z_type', $selectMsg['infoType']);
                break;
        }

        $handle = ($selectMsg['infoNameKeyword'] ?? '') ? $handle->where('zx_name', 'like', '%' . $selectMsg['infoNameKeyword'] . '%') : $handle;
        $total = $handle->count();

        $data = $handle->orderBy(...$sort_type_arr[$selectMsg['sortType']])
        ->offset($selectMsg['pageCount'] * ($selectMsg['pageNumber'] - 1))->limit($selectMsg['pageCount'])
        ->select('id', 'zx_name', 'weight', 'is_show', 'is_recommend', 'z_type', 'z_from', 'create_time')->get()->toArray();

        return ['total' => $total, 'data' => $data];
        
    }


    public static function getInfoAppiCount(array $conditionArr = []) {

        return DB::table(self::$sTableName)->where($conditionArr)->count();
    }

    public static function setAppiInfoState(array $updateMsg = []) {
        $handle = DB::table(self::$sTableName)->where('id', $updateMsg['info_id']);
        switch($updateMsg['type'])
        {
            case 0:
                return $handle->update(['weight' => $updateMsg['state'], 'update_time' => time()]);
                break;
            case 1:
                return $handle->update(['is_show' => $updateMsg['state'], 'update_time' => time()]);
                break;
            case 2:
                return $handle->update(['is_recommend' => $updateMsg['state'], 'update_time' => time()]);
                break;
        }
    }


    public static function getAppointInfoMsg($infoId = 0, $msgName = '') {
        if(empty($msgName))
            return DB::table(self::$sTableName)->where('id', $infoId)->first();
        else
            return DB::table(self::$sTableName)->where('id', $infoId)->select(...$msgName)->first();
    }

    public static function delAppointInfo($infoId = 0) {
        return DB::table(self::$sTableName)->where('id', $infoId)->update(['is_delete' => 1, 'update_time' => time()]);
    }


    public static function createInfo(array $createMsg = []) {

        return DB::table(self::$sTableName)->insertGetId($createMsg);
    }




    public static function getAutoRecommendInfos($recomInfoCount = 8) {
        $handle = DB::table(self::$sTableName)->where([
            ['is_delete', '=', 0],
            ['is_show', '=', 0],
            ['is_recommend', '=', 0]
        ]);
        if($handle->count() < $recomInfoCount) {
            return $handle->orderBy('weight', 'desc')->pluck('id');
        }
        else {
            return $handle->orderBy('weight', 'desc')->skip($recomInfoCount)->pluck('id');
        }
        // return DB::table(self::$sTableName)->where([
        //     ['is_delete', '=', 0],
        //     ['is_show', '=', 0],
        //     ['is_recommend', '=', 0]
        // ])->orderBy('weight', 'desc')->skip($recomInfoCount)->pluck('id');
    }
    



    //手动设置资讯的推荐阅读时使用
    public static function getAllInfo($pageNum = 0, $pageCount = 10) {


        return DB::table(self::$sTableName)->where('is_delete', 0)
        ->select('id', 'zx_name')->orderBy('update_time', 'desc')
        ->offset($pageNum * $pageCount)->limit($pageCount)->get();

    }


    //获取指定资讯的推荐阅读
    public static function getAppointInfoReRead(array $infoIdArr = []) {
        if($infoIdArr == []) return $infoIdArr;
        
        return DB::table(self::$sTableName)->leftJoin('dict_information_type', self::$sTableName . '.z_type', '=', 'dict_information_type.id')
            ->whereIn(self::$sTableName . '.id', $infoIdArr)->where(self::$sTableName . '.is_delete', 0)
            ->select(self::$sTableName . '.id', self::$sTableName . '.weight as show_weight', 'dict_information_type.name as info_type', self::$sTableName . '.zx_name', self::$sTableName . '.create_time')
            ->get()->map(function($item) {
                $item->create_time = date("Y-m-d H:i", $item->create_time);
                return $item;
            });

    }


    public static function createOneInfo($infoMsg = [], $type = 0,$infoId = 0) {

        if($type == 0)
            return DB::table(self::$sTableName)->insertGetId(array_merge($infoMsg, [
                'create_time' => time()
            ]));
        else if($type == 1 && $infoId != 0) {
            return DB::table(self::$sTableName)->where('id', $infoId)->update(array_merge($infoMsg, [
                'update_time' => time()
            ]));
        }
    }


    /**
     * 前台搜索页面的搜索资讯信息
     */
    public static function getSearchConsults($keyword = '', $pageNumber = 0, $pageCount = 8) {
        return DB::table(self::$sTableName)
            ->where('zx_name', 'like', '%' . $keyword . '%')
            ->orWhere('z_text', '%' . $keyword . '%')
            ->offset($pageCount * ($pageNumber - 1))
            ->limit($pageCount)
            ->select('id', 'zx_name', 'create_time', 'z_text', 'z_image')->get();
    }


    /**
     * 页面左侧推荐阅读模块后端接口
     */
    public static function getRecommendReads($pageNumber = 0) {
        $rem_count = SystemSetup::getContent('front_recommend_read');
        return DB::table(self::$sTableName)
            ->where('is_delete', 0)
            ->orderBy('weight', 'desc')
            ->orderBy('create_time', 'desc')
            ->offset($rem_count * $pageNumber)
            ->limit($rem_count)
            ->select('id', 'zx_name', 'create_time', 'z_image')->get();
    }


    /**
     * 获得看资讯列表页首部轮播信息
     */
    public static function getConsultListBroadcasts() {
        $count = SystemSetup::getContent('front_consult_list_broadcast_count');
        $handel = DB::table(self::$sTableName)->where('is_delete', 0)->orderBy('weight', 'desc')->orderBy('create_time', 'desc');
        if($handel->count() > $count) 
            $handel = $handel->skip($count);

        return $handel->select('id', 'zx_name', 'z_image')->get();
    }


    public static function getConsultListInfos($consultTypeId = 0, $pageCount = 9, $pageNumber = 1) {
        $handel = DB::table(self::$sTableName);
        if($consultTypeId != 0) 
            $handel = $handel->where('z_type', $consultTypeId);

        return $handel->where('is_delete', 0)
            ->orderBy('weight', 'desc')
            ->orderBy('create_time', 'desc')
            ->offset($pageCount * ($pageNumber -1))
            ->limit($pageCount)
            ->select('id', 'zx_name', 'brief_introduction', 'create_time', 'z_image')->get();

    }



}