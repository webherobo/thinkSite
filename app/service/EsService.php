<?php


namespace app\service;
use Elasticsearch\ClientBuilder;
/**
 * @desc elasticSearch 查询工具类
 * @date 2019年11月11日14:23:29
 * must should 的区别：
 *        must : 对于给定的搜索字符串，在搜索结果中必须包含改搜索字符串中包含的字符，filter条件无效 如：filter搜素有数据 字符串收缩无数据 最终会显示无数据
 *    should : 对于给定的搜索字符串，在搜索结果中不必须包含改搜索字符串中包含的字符 与filter有关 如：filter搜素有数据 字符串收缩无数据 最终会显示filter中的数据
 */
class EsService
{
    const ES_MATCH_OPERATOR_OR = 'or';

    const ES_MATCH_OPERATOR_AND = 'and';

    const ES_MATCH_HIGHLIGHT_TAT_PRE = ['('];

    const ES_MATCH_HIGHLIGHT_TAT_POST = [')'];

    const ES_MATCH_SEARCH_MODEL_MATCH = 'match';

    const ES_MATCH_SEARCH_MODEL_BOOL = 'bool';

    private $_hosts = [];

    protected $_index = '';

    private $_client = null;

    private $_params = [];

    private $_settings = [];

    private $_mappings = [];

    private $_numberOfShards = 5;

    private $_numberOfReplicas = 0;

    private $_indices = null;

    private $_response = [];

    private $_searchField = null;

    private $_searchSort = [];

    private $_highlightTag = ['pre' => self::ES_MATCH_HIGHLIGHT_TAT_PRE, 'post' => self::ES_MATCH_HIGHLIGHT_TAT_POST];

    private $_scrollExp = 30;

    private $_scrollSize = 2;

    private $_openScroll = false;

    private $_scrollId = '';

    private $_operator = self::ES_MATCH_OPERATOR_OR; // 匹配规则 可选 or  and

    private $_queryKeyword = null;

    private $_searchModel = self::ES_MATCH_SEARCH_MODEL_BOOL;

    private $_pageSize = 1;

    private function init(array $config = [])
    {
        if (isset($config['host'])) {
            $this->_hosts = is_array($config['host']) ? $config['host'] : [$config['host']];
        } else {
            $this->_hosts = ['192.168.1.2:9200'];
        }

        if (isset($config['index']) && is_string($config['index']) && !empty($config['index'])) $this->_index = $config['index'];
        else {
            throw new \Exception("索引名称不能为空", 90003);
        }

        $this->_params['index'] = $this->_index;

        $this->_settings = [
            'number_of_shards' => $this->_numberOfShards,
            'number_of_replicas' => $this->_numberOfReplicas
        ];

        $this->_client = ClientBuilder::create()->setHosts($this->_hosts)->build();
        $this->_indices = $this->_client->indices();
    }
    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
        //
        $this->app->bind('elasticSearch', EsService::class);
    }


    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        $config=config("elasticSearch");
        $this->init($config);
    }
    /**
     * @desc 设置映射
     */
    public function setMappings(array $mappings)
    {
        if (!$mappings) {
            throw new \Exception("映射不能为空", 90003);
        }

        $this->_mappings = $mappings;
        return $this;
    }

    /**
     * @desc 创建索引
     */
    public function createIndex(string $index = '')
    {
        if ($index) $this->_params['index'] = $this->_index = $index;

        $this->_response[__FUNCTION__] = $this->_createIndex();

        return $this->_response[__FUNCTION__];
    }

    /**
     * @desc 创建索引
     */
    private function _createIndex()
    {
        if (!$this->_mappings || !is_array($this->_mappings)) {
            throw new \Exception("未指定mapping参数", 90001);
        }

        if (!$this->_index) {
            throw new \Exception("未指定索引名称", 90001);
        }

        if (!$this->isIndexExists()) {
            $this->_params['body'] = [
                'settings' => $this->_settings,
                'mappings' => ['properties' => $this->_mappings]
            ];

            $response = $this->_indices->create($this->_params);

            $this->_response[__FUNCTION__] = $response;
            //print_r($respone);
            if (!$response['acknowledged']) {
                return false;
            }
        } else {
            throw new \Exception("索引名称已存在", 900011);
        }

        return true;
    }

    /**
     * @desc 检查索引是否存在
     */
    public function isIndexExists()
    {
        return $this->_indices->exists(['index' => $this->_params['index']]);
    }

    /**
     * @desc 获取参数
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * @desc 参数设置
     */
    public function setParams($params = [])
    {
        if ($params) {
            $this->_params = array_merge($this->_params, $params);
        }

        return $this;
    }

    /**
     * @desc 单个索引文档
     * @param array $data 文档数据 如 ['field1'=>'value1', 'field2'=>'value2']
     * @param string $idKey 创建id值 不传es自动创建 如：field1
     */
    public function singleDoc(array $data, $idKey = '')
    {
        if (!$data) return false;

        if (isset($data[$idKey])) {
            $this->_params['id'] = $data[$idKey];
            unset($data[$idKey]);
        }

        $this->_params['body'] = $data;
        $this->_response[__FUNCTION__] = $this->_client->index($this->_params);
        return $this->_response[__FUNCTION__];
    }

    /**
     * @desc 批量索引文档
     * @param array $data 文档数据 如 [['field1'=>'value1', 'field2'=>'value2'], ['field1'=>'value3', 'field2'=>'value4']]
     * @param string $idKey 创建id值 不传es自动创建
     */
    public function bulkDoc(array $data = [], $idKey = '')
    {
        if (!$data) return false;

        $params = ['body' => []];
        $responses = null;

        foreach ($data as $key => $value) {
            $params['body'][] = [
                'index' => [
                    '_index' => $this->_index,
                    '_id' => isset($value[$idKey]) ? $value[$idKey] : null
                ]
            ];

            //if (isset($value[$idKey])) unset($value[$idKey]);
            $params['body'][] = $value;

            if ($key % 1000 == 0) {
                $responses = $this->_client->bulk($params);

                $params = ['body' => []];

                $this->_response[__FUNCTION__][] = $responses;
                unset($responses);
            }
        }

        if (!empty($params['body'])) {
            $responses = $this->_client->bulk($params);
            $this->_response[__FUNCTION__][] = $responses;
        }

        return $this->_response[__FUNCTION__];
    }

    /**
     * @desc 删除索引
     */
    public function deleteIndex()
    {
        $this->_response[__FUNCTION__] = $this->_indices->delete($this->_params);
        return $this->_response[__FUNCTION__];
    }

    /**
     * @desc 获取索引设置
     */
    public function getSettings()
    {
        $this->_response[__FUNCTION__] = $this->_indices->getSettings($this->_params);
        return $this->_response[__FUNCTION__];
    }

    /**
     * @desc 获取索引设置
     */
    public function getMappings()
    {
        $this->_response[__FUNCTION__] = $this->_indices->getMapping($this->_params);
        return $this->_response[__FUNCTION__];
    }

    /**
     * @desc 获取文档
     */
    public function getDoc($id)
    {
        if (!$id) {
            return false;
        }

        try {
            $this->_params['id'] = $id;
            $this->_response[__FUNCTION__] = $this->_client->get($this->_params);
        } catch (\Exception $e) {
            //echo $e->getmessage();
            return false;
        }

        return $this->_response[__FUNCTION__]['_source'];
    }

    /**
     * @desc 删除文档
     */
    public function deleteDoc($id)
    {
        if (!$id) {
            return false;
        }

        try {
            $this->_params['id'] = $id;
            $this->_response[__FUNCTION__] = $this->_client->delete($this->_params);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @desc 设置搜索字段 如 field [$field1, $field2] [field=>or] [field1=>or, field2=>and]
     */
    public function setSearchField($fields)
    {
        if (!$fields) {
            throw new \Exception("搜索字段不能为空", 90002);
        }

        if (!is_string($fields) && !is_array($fields)) {
            throw new \Exception("搜索字段必须是字符串或者数组", 90002);
        }

        $operstorList = [self::ES_MATCH_OPERATOR_OR, self::ES_MATCH_OPERATOR_AND];
        if (is_array($fields)) {
            foreach ($fields as $key => $field) {
                if (!in_array($field, $operstorList) && is_numeric($key)) {
                    $fields[$field] = $this->_operator;
                    unset($fields[$key]);
                } else if (!in_array($field, $operstorList) && is_string($key)) {
                    $fields[$key] = $this->_operator;
                }
            }

            $this->_searchField = $fields;
        } else {
            $this->_searchField[$fields] = $this->_operator;
        }

        return $this;
    }

    /**
     * @desc 设置排序字段倒叙
     */
    public function setSortDesc(string $sortField)
    {
        if ($sortField) {
            $this->_searchSort[$sortField] = 'desc';
        }

        return $this;
    }

    /**
     * @desc 设置排序字段正序
     */
    public function setSortAsc(string $sortField)
    {
        if ($sortField) {
            $this->_searchSort[$sortField] = 'asc';
        }

        return $this;
    }

    /**
     * @desc 设置高亮前缀标签
     */
    public function setPreHighlinhtTag(string $tag)
    {
        if ($tag) $this->_highlightTag['pre'][] = $tag;

        return $this;
    }

    /**
     * @desc 设置高亮后缀标签
     */
    public function setPostHighlinhtTag(string $tag)
    {
        if ($tag) $this->_highlightTag['post'][] = $tag;

        return $this;
    }

    /**
     * @desc 如果开启了scroll 设置scroll过期时间 单位 秒
     */
    public function setScrollExp(int $time)
    {
        if ($time) $this->_scrollExp = $time;

        return $this;
    }

    /**
     * @desc 如果开启了scroll 设置scroll数量 单位 秒
     * @param int $size 每页数量
     * @return object
     */
    public function setScrollSize(int $size)
    {
        if ($size) $this->_scrollSize = $size;

        return $this;
    }

    /**
     * @desc 如果开启了match查询 设置字段的查询条件
     */
    public function setOperatorAnd(string $field = '')
    {
        $this->_operator = self::ES_MATCH_OPERATOR_AND;
        foreach ($this->_searchField as $field => $operator) {
            $this->_searchField[$field] = $this->_operator;
        }

        return $this;
    }

    /**
     * @desc 开启scroll 查询
     */
    public function openScroll(string $scroll_id = '')
    {
        $this->_openScroll = true;
        $this->_scrollId = $scroll_id;

        return $this;
    }

    /**
     * @desc 设置搜索模式为 match
     */
    public function setSearchModelMatch()
    {
        $this->_searchModel = self::ES_MATCH_SEARCH_MODEL_MATCH;

        return $this;
    }

    /**
     * @desc 全文搜索
     * @param string $keywords 搜索内容
     */
    public function query(string $keywords = '')
    {
        if (!$keywords && $this->_searchModel == self::ES_MATCH_SEARCH_MODEL_MATCH) {
            throw new \Exception("搜索内容不能为空", 90008);
        }

        if (!$this->_searchField && $this->_searchModel == self::ES_MATCH_SEARCH_MODEL_MATCH) {
            throw new \Exception("搜索字段没有设置", 90009);
        }

        $this->_queryKeyword = $keywords;

        if ($this->_openScroll) {
            $this->_params['scroll'] = $this->_scrollExp . 's';
        } else {
            $this->_params['from'] = ($this->_pageSize - 1) * $this->_scrollSize;
        }
        $this->_params['size'] = $this->_scrollSize;

        switch ($this->_searchModel) {
            case self::ES_MATCH_SEARCH_MODEL_BOOL:
                $this->_setBoolQuery();
                break;

            default:
                $this->_setMatchQuery();
                break;
        }

        $this->_setHighlight();
        $this->_setSort();

        $return = ['total' => 0, 'data' => [], 'scroll_id' => ''];
        try {
            if ($this->_openScroll && $this->_scrollId) {
                $this->_response[__FUNCTION__] = $this->_client->scroll(['scroll_id' => $this->_scrollId, 'scroll' => $this->_params['scroll']]);
            } else {
                $this->_response[__FUNCTION__] = $this->_client->search($this->_params);
            }
        } catch (\Exception $e) {
            //echo $e->getmessage();
            return false;
        }

        if (isset($this->_response[__FUNCTION__]['hits']['hits']) && count($this->_response[__FUNCTION__]['hits']['hits']) > 0) {
            $return['total'] = $this->_response[__FUNCTION__]['hits']['total']['value'];
            if (isset($this->_response[__FUNCTION__]['_scroll_id'])) {
                $return['scroll_id'] = $this->_response[__FUNCTION__]['_scroll_id'];
            }

            foreach ($this->_response[__FUNCTION__]['hits']['hits'] as $key => $value) {
                $return['data'][] = $value['_source'];
            }
        }

        return $return;
    }

    public function setPageSize(int $pageSize)
    {
        if ($pageSize <= 1) $pageSize = 1;

        $this->_pageSize = $pageSize;

        return $this;
    }


    private function _setMatchQuery()
    {
        foreach ($this->_searchField as $field => $operator) {
            $this->_params['body']['query']['match'][$field] = ['query' => $this->_queryKeyword, 'operator' => $operator];
        }


        return $this;
    }

    /**
     * @desc 设置must term查询
     *
     */
    public function setMustTerm(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->_searchField['bool']['must'][]['term'][$field] = $value;
            $this->_searchField[$field] = $value;
        }

        return $this;
    }

    //结果中必须包含至少一个分词
    public function setMustMatch(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->_searchField['bool']['must'][]['match'][$field] = $value;
            $this->_searchField[$field] = $value;
        }

        return $this;
    }

    //结果中必须包含所有分词
    public function setMustAllMatch(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->_searchField['bool']['must'][]['match'][$field] = ['query' => $value, 'operator' => self::ES_MATCH_OPERATOR_AND];
            $this->_searchField[$field] = self::ES_MATCH_OPERATOR_AND;
        }

        return $this;
    }

    public function setFilterTerm(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->_searchField['bool']['filter'][]['term'][$field] = $value;
            $this->_searchField[$field] = $value;
        }

        return $this;
    }

    public function setFilterRange(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->_searchField['bool']['filter'][]['range'][$field] = $value;
            $this->_searchField[$field] = $value;
        }

        return $this;
    }

    public function setFilterMatch(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->_searchField['bool']['filter'][]['match'][$field] = $value;
            $this->_searchField[$field] = $value;
        }

        return $this;
    }

    public function setShouldMatch(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->_searchField['bool']['should'][]['match'][$field] = $value;
            $this->_searchField[$field] = $value;
        }

        return $this;
    }

    public function setShouldTerm(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->_searchField['bool']['should'][]['term'][$field] = $value;
            $this->_searchField[$field] = $value;
        }

        return $this;
    }

    private function _setBoolQuery()
    {
        if (!$this->_searchField['bool']) {
            throw new \Exception("搜索字段没有设置", 900010);
        }

        $this->_params['body']['query']['bool'] = $this->_searchField['bool'];
        return $this;
    }

    private function _setHighlight()
    {
        $this->_params['body']['highlight']['pre_tags'] = $this->_highlightTag['pre'] ?? [];
        $this->_params['body']['highlight']['post_tags'] = $this->_highlightTag['post'] ?? [];
        foreach ($this->_searchField as $field => $operator) {
            if ($field == 'bool') continue;

            $this->_params['body']['highlight']['fields'][$field] = new \stdClass;
        }


        return $this;
    }

    private function _setSort()
    {
        $this->_params['body']['sort'] = $this->_searchSort;
    }

    public function response()
    {
        if (count($this->_response) == 1) {
            $this->_response = array_shift($this->_response);
        }

        print_r($this->_response);
    }

}