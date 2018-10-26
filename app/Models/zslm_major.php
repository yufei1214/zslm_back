<?php
    /**
     * Created by PhpStorm.
     * User: star
     * Date: 2018/8/22
     * Time: 16:59
     */
    
    namespace App\Models;
    
    
    use Illuminate\Http\Request;
    use DB;
    define('ADDRESS_SEPARATOR',',');
    
    class zslm_major
    {
        private static $sTableName = 'zslm_major';
    
        /**
         * 通过省的id获取院校专业
         * @param Request $request
         */
        public static function getMajorByP($provice){
            return DB::table(self::$sTableName)
                        ->where('province','like',$provice)
                        ->get(['id','z_name']);
        }
        
        public static function getMajorIdByName($name){
            $result = DB::table(self::$sTableName)
                ->where('z_name',$name)
                ->first(['id']);
            return $result;
        }
        
        public static function getMajorName($ids){
            
            $result = DB::table(self::$sTableName)
                ->whereIn('id',$ids)
                ->get(['z_name']);
            return $result;
        }

        
        public static function getMajorId($name){
        
            return  DB::table(self::$sTableName)->where('z_name','like','%'.$name.'%')->get(['id']);
        }
        
        

        public static function getAppointMajorMsg($majorId){
            return DB::table(self::$sTableName)->where('id',$majorId)->get();
        }

        public static function getAllDictMajor() {
            return DB::table(self::$sTableName)->where('is_delete', 0)->select('id', 'z_name', 'magor_logo_name');
        }


        public static function getMajorAppiCount(array $condition = []) {

            return DB::table(self::$stableName)->where('is_delete', 0)->where($condition)->count();
        }

        public static function setAppiMajorState(array $major) {
            $handle = DB::table(self::$sTableName)->where('id', $major['major_id']);
            switch($major['type'])
            {
                case 0:
                    return $handle->update(['weight' => $major['state'], 'update_time' => time()]);
                    break;
                case 1:
                    return $handle->update(['is_show' => $major['state'], 'update_time' => time()]);
                    break;
                case 2:
                    return $handle->update(['if_recommended' => $major['state'], 'update_time' => time()]);
                    break;
            }
        }
        
        public static function delMajor($majorId) {
            return DB::table(self::$sTableName)->where('id', $majorId)->update(['is_delete' => 1, 'update_time'=> time()]);
        }

        public static function updateMajorTime($majorId, $nowTime) {

            return DB::table(self::$sTableName)->where('id', $majorId)->update(['update_time'=> $nowTime]);
        }
        
        // private static function judgeScreenState($screenState, $title, &$handle) {
        //     switch($screenState) {
        //         case 0:
        //             $handle = $handle->where($title, '=', 0);
        //             break;
        //         case 1:
        //             $handle = $handle->where($title, '=', 1);
        //             break;
        //         default :
        //             break;
        //     }
        // }

        public static function getMajorPageMsg(array $val = []) {

            $handle = DB::table(self::$sTableName)->where('is_delete', 0);
            $sort_type = [0=>['weight','desc'], 1=>['weight','asc'], 2=>['update_time','desc']];
            
            if(isset($val['majorNameKeyword'])) $handle = $handle->where('z_name', 'like', '%' . $val['majorNameKeyword'] . '%');

            switch($val['screenType'])
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
            switch($val['screenState'])
            {
                case 0:
                    $handle = $handle->where('if_recommended', 0);
                    break;
                case 1: 
                    $handle = $handle->where('if_recommended', 1);
                    break;
                default:
                    break;
            }

            $handle = $handle->orderBy($sort_type[$val['sortType']][0], $sort_type[$val['sortType']][1]);

            $count = $handle->count();

            $get_page_msg = $handle
                ->offset($val['pageCount'] * $val['pageNumber'])
                ->limit($val['pageCount'])
                ->select(
                    'id',
                    'z_name',
                    'weight',
                    'is_show',
                    'if_recommended',
                    'update_time'
                )
                ->get()
                ->map(function($item) {
                    $item_student_project_count = DB::table('major_recruit_project')->where('major_id', $item->id)->count();
                    $item->student_project_count = $item_student_project_count;
                    
                    return $item;
                })->toArray();

            return $count >= 0 ? ['count'=>$count, 'get_page_msg' => $get_page_msg] : [];
        }
        
        public static function getAutoRecommendMajors($recomMajorCount = 8) {
            return DB::table(self::$sTableName)->where([
                ['is_delete', '=', 0],
                ['is_show', '=', 0],
                ['if_recommended', '=', 0]
            ])->orderBy('weight','desc')->limit($recomMajorCount)->pluck('id');
        }

        public static function getMajorByids(array $id){
            $data =  DB::table(self::$sTableName)->where('is_delete',0)->whereIn('id',$id)->get(['z_name','id','weight','update_time','province']);
            return $data;
        }
    }