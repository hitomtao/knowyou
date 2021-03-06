<?php
/**
 * Message:
 * User: jzc
 * Date: 2018/10/19
 * Time: 下午6:33
 * Return:
 */

namespace common\dao;

use yii\db\ActiveRecord;

class Banner extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%banner}}';
    }

    public function getListByCondition($condition, $limit = 1000, $offset = 0, $orderBy = 'created_at desc')
    {
        $db = self::find();
        $db = $this->handlerCondition($db, $condition);

        $rs = $db->offset($offset)->limit($limit)->orderBy($orderBy)->asArray()->all();
        return $rs;
    }

    public function handlerCondition($db, $condition)
    {
        if (!empty($condition) && is_array($condition)) {
            foreach ($condition as $k => $v) {
                if ($k == 'valid_date') {
                    $db = $db->andWhere("start_at<='{$v}'");
                    $db = $db->andWhere("end_at>='{$v}'");
                } else {
                    $db = $db->andWhere([$k => $v]);
                }
            }
        }

        return $db;
    }
}