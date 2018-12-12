<?php
/**
 * Message:
 * User: jzc
 * Date: 2018/10/22
 * Time: 3:31 PM
 * Return:
 */

namespace common\models;

use yii\base\Model;
use common\dao\Article;
use Yii;

class ArticleModel extends Model
{
    const ARTICLE_STATUS_NORMAL = 1;//正常
    const ARTICLE_STATUS_FORBIDDEN = 2;//封禁，无法查看
    const ARTICLE_STATUS_UNCOMMENT = 3;//无法评论
    const ARTICLE_STATUS_DELETED = 4;//删除

    const REDIS_ARTICLE_READ_NUMBER = 'know_you_article_read_number_';//文章阅读数 hash 有效期三天
    const REDIS_EXPIRE_TIME = 259200;//三天
    const BASE_ARTICLE_ID_KEY = 'BASE_ARTICLE_ID';//id = base_id * partition + uid % partition

    const ARTICLE_COVER_DEFAULT = '/img/knowyou_article_img/default_article_cover.jpg';//文章默认封面
    const TABLE_PARTITION = Article::TABLE_PARTITION;

    public function rules()
    {
        return parent::rules(); // TODO: Change the autogenerated stub
    }

    /**
     * 获取or初始化阅读数
     * @param $id
     * @param $add
     * @return int
     */
    public function getReadNumber($id, $add = true)
    {
        $redis = Yii::$app->redis;
        $nowDate = date('Ymd');
        //新一日，切换新的缓存hash
        if (!$redis->exists(self::REDIS_ARTICLE_READ_NUMBER . $nowDate)) {
            $redis->hset(self::REDIS_ARTICLE_READ_NUMBER . $nowDate, 'base_id', 0);
            $redis->expire(self::REDIS_ARTICLE_READ_NUMBER . $nowDate, self::REDIS_EXPIRE_TIME);
            Yii::warning("msg:set redis read number hash;date:{$nowDate};", CATEGORIES_WARN);
        }

        //获取今日的缓存，若没有则去取昨日缓存（说明这个时间段没人访问这个ID）
        if (!$redis->hexists(self::REDIS_ARTICLE_READ_NUMBER . date('Ymd'), $id)) {
            $readNumber = $redis->hget(self::REDIS_ARTICLE_READ_NUMBER . date('Ymd', strtotime('yesterday')), $id);
            if (!$readNumber) {
                //缓存丢失，穿透DB
                $articleInfo = $this->getOneByCondition($id);
                $readNumber = $articleInfo['read_number'];
                Yii::warning("msg:get db read number;article_id:{$id};", CATEGORIES_WARN);
            }

            if ($add) {
                $readNumber = $readNumber + 1;
            }
            //重设缓存
            $redis->hset(self::REDIS_ARTICLE_READ_NUMBER . date('Ymd'), $id, $readNumber);
        } else {
            $readNumber = $redis->hget(self::REDIS_ARTICLE_READ_NUMBER . date('Ymd'), $id);
            if ($add) {
                $readNumber = $readNumber + 1;
            }
            $redis->hset(self::REDIS_ARTICLE_READ_NUMBER . date('Ymd'), $id, $readNumber);
        }

        return $readNumber;
    }

    /**
     * 获取总表记录数
     * @param null $condition
     * @return int
     */
    public function getCountByCondition($condition = null)
    {
        $count = Article::TABLE_PARTITION;
        $result = 0;

        while ($count - Article::TABLE_PARTITION < Article::TABLE_PARTITION) {
            $article  = new Article($count);

            $result = $result + $article->getCountByCondition($condition);
            $count++;
        }

        return $result;
    }

    /**
     * 从所有表中获取文章记录
     * 需要当无指定条件时，每次返回数据不同
     * @param null $condition
     * @param int $limit //每个分表里取的数量
     * @param $offset
     * @return array
     */
    public function getListByCondition($condition = null, $limit = 10, $offset = 0)
    {
        $count = Article::TABLE_PARTITION;
        $result = array();

        while ($count - Article::TABLE_PARTITION < Article::TABLE_PARTITION) {
            $article = new Article($count);

            $result = array_merge($result, $article->getListByCondition($condition, $limit, $offset));
            $count++;
        }

        return $result;
    }

    /**
     * 获取符合条件的所有数据，受limit限制
     * @param $key
     * @param array $condition
     * @param int $limit
     * @return mixed
     */
    public function getAllList($key, $condition = array(), $limit = 1000)
    {
        $rs = (new Article($key))->getAllList($condition, $limit);
        return $rs;
    }

    /**
     * 查询单条文章记录，注意ID可传入UID或者article_id
     * @param $id
     * @param null $condition
     * @return mixed
     */
    public function getOneByCondition($id, $condition = null)
    {
        $index = intval($id) % Article::TABLE_PARTITION;
        $article = new Article($index);
        return $article->getOneByCondition($condition);
    }

    /**
     * 插入文章并返回文章ID
     * @param array $data
     * @return int
     */
    public function insert(array $data)
    {
        if (empty($data) || empty($data['uid'])) {
            return false;
        }

        if (!Yii::$app->redis->exists(self::BASE_ARTICLE_ID_KEY)) {
            Yii::$app->redis->set(self::BASE_ARTICLE_ID_KEY, 0);
        }

        $articleID = Yii::$app->redis->incr(self::BASE_ARTICLE_ID_KEY) * self::TABLE_PARTITION + $data['uid'] % self::TABLE_PARTITION;
        $data['id'] = $articleID;

        $transaction = Yii::$app->db->beginTransaction();

        //插入文章数据
        if (!(new Article($data['uid']))->insertData($data)) {
            $transaction->rollBack();
            return 0;
        }

        //插入索引数据
        if (!(new ArticleIndexModel())->insert($articleID)) {
            $transaction->rollBack();
            return 0;
        }

        $transaction->commit();

        return $articleID;
    }

    /**
     * 批量更新
     * index只能指定一个条件！
     * @param $key
     * @param $data
     * @param $index
     * @return int
     */
    public function updateBatch($key, $data, $index)
    {
        return (new Article($key))->updateBatch($data, $index);
    }

    /**
     * 文章点赞
     * @param $id
     * @param int $change
     * @return bool
     */
    public function praiseArticle($id, $change = 1)
    {
        return (new Article($id))->praiseArticle($id, $change);
    }
}