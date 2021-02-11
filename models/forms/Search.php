<?php

namespace app\models\forms;

use Yii;
use yii\elasticsearch\Query;
use yii\elasticsearch\ActiveRecord;
use yii\elasticsearch\ActiveDataProvider;

/**
 * This is the form model class for search queries.
 *
 * @inheritdoc
 *
 */
class Search extends \yii\elasticsearch\ActiveRecord
{

    /**
     * @var string the search query.
     */
    public $q;

    /**
     * @var array the indexes to search, defaults to empty, which means all.
     */
    public $index;

    /**
     * @var array all indexes and their definitions
     */
    public $indexes = [
        'user' => '\app\models\indexes\UserIndex',
        'exam' => '\app\models\indexes\ExamIndex',
        'ticket' => '\app\models\indexes\TicketIndex',
        'backup' => '\app\models\indexes\BackupIndex',
        'restore' => '\app\models\indexes\RestoreIndex',
        'howto' => '\app\models\indexes\HowtoIndex',
        'log' => '\app\models\indexes\LogIndex',
        'file'   => '\app\models\indexes\FileIndex',
        'meta'   => '\app\models\indexes\MetaIndex',
    ];

    /**
     * @var array list of reserved word regexes, when the query string matches one of these
     * then a query_string query is performed
     */
    public $query_string_regexes = [
        '/^(?<!\\\)\+/', '/\s(?<!\\\)\+/', # +foo, but not foo+bar
        '/^(?<!\\\)\-/', '/\s(?<!\\\)\-/', '/^(?<!\\\)\!/', '/\s(?<!\\\)\!/', # -foo, but not foo-bar
        '/(?<!\\\)\=/', # =, but not \=
        '/\sAND\s/', '/\s\&\&\s/', # AND, &&, but not \&\&
        '/\sOR\s/', '/\s\|\|\s/', # OR, ||, but not \|\|
        '/\</', '/\>/', # <,>
        '/(?<!\\\)\(/', '/(?<!\\\)\)/', # (,), but not \(, \)
        '/(?<!\\\)\{/', '/(?<!\\\)\}/', # {,}, but not \{, \}
        '/(?<!\\\)\[/', '/(?<!\\\)\]/', # [,], but not \[, \]
        '/(?<!\\\)\^/', # ^, but not \^
        '/(?<!\\\)\"/', # ", but not \"
        '/(?<!\\\)\*/', # *, but not \*
        '/(?<!\\\)\?/', # ?, but not \?
        '/(?<!\\\)\:/', # :, but not \:
        '/(?<!\\\)\~/', # ~, but not \~
        '/(?<!\\\)\//', # /, but not \/
    ];

    /**
     * @var array list of pattern and replacements to rewrite the query before [[query_string_rewritings]] and [[query_string_rewritings]]
     */
    public $forced_rewritings_pre = [
        '/^\\\qs[\s]*/' => '', # remove the \qs marker
        '/[\s]*\\\qs/' => '', # remove the \qs marker
        '/^\\\mm[\s]*/' => '', # remove the \mm marker
        '/[\s]*\\\mm/' => '', # remove the \mm marker
    ];

    /**
     * @var array list of pattern and replacements to rewrite the query in case of a query_string query
     */
    public $query_string_rewritings = [
        # make the queries fuzzy:
        # field:foo -> field:foo~, but not field:"foo"
        # foo AND bar -> foo~ AND bar~
        # "foo" AND bar -> "foo" AND bar~
        # field:foo*bar -> field:foo*bar~ but the tilda has no meaning together with the wildcard *
        # "foo bar baz" AND blubb -> "foo bar baz" AND blubb~
        # _exists_:field should stay as it is
        '/((?<!^)(?<!AND)(?<!TO)(?<!\&\&)(?<!OR)(?<!\|\|)(?<!\s)(?<!\*)(?<!\/)(?<!\~)(?<!\^)(?<!\])(?<!\})(?<!\))(?<!\>)(?<!\<)(?<!\")(?<!\&))(\s|\)|$)(?!TO)/' => '$1~$2',
        # field:* and field: -> _exists_:field
        '/([^\s\:]+)\:[\*\~]?([\s]|$)/' => '_exists_:$1$2',
        # field:foo -> (field:foo OR field.\*:foo)
        '/([^\s\:]+)(\:)([\"][^\"]+[\"]|[\(][^\(]+[\)]|[\{][^\{]+[\}]|[\[][^\[]+[\]]|[^\s\"]+)/' => '($1:$3 OR $1.\\*:$3)',
    ];

    /**
     * @var array list of pattern and replacements to rewrite the query in case of a multi_match query
     */
    public $multi_match_rewritings = [];

    /**
     * @var array list of pattern and replacements to rewrite the query after [[query_string_rewritings]] and [[query_string_rewritings]]
     */
    public $forced_rewritings_post = [
        '/^\\\notouch[\s]*/' => '', # remove the \notouch marker
        '/[\s]*\\\notouch/' => '', # remove the \notouch marker
    ];

   /**
     * @inheritdoc
     */
    public function formName()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['q', 'filter', 'skipOnArray' => true, 'filter' => function ($value) {
                return trim($value);
            }],
            ['index', 'default', 'value' => []],
            ['index', 'filter', 'filter' => function($v) { return is_string($v) ? explode(',', $v) : $v; }, 'skipOnEmpty' => false],
            ['index', 'each', 'rule' => ['in', 'range' => array_keys($this->indexes), 'skipOnEmpty' => false]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'q' => \Yii::t('search', 'Search query'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'q' => \Yii::t('search', 'todo'),
            'index' => \Yii::t('search', 'todo'),
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return yii\elasticsearch\ActiveDataProvider
     */
    public function search($params)
    {
        $this->load($params, '');

        $query = new Query;
        $query->from($this->index) # null means all indices are being queried
            ->limit(10);


        /*$query->query(['prefix' => [
            '*' => [
                'value' => $this->q,
            ]
        ]]);*/

        $q = $this->rewrite_query($this->q);

        // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
        // if query syntax is used execute a query_string search
        if ($this->is_query_string()) {
            $query->query(['query_string' => [
                'query' => $q,
                //'quote_field_suffix' => '.keyword',
            ]]);
        } else {
            $this->rewrite_query($this->forced_rewritings_pre);
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
            // if no query syntax is used execute multi_match query
            $query->query(['multi_match' => [
                'query' => $q,
                'fuzziness' => 'AUTO',
                //'type' => 'most_fields',
                //'type' => 'phrase_prefix', // prefix query on multiple fields
                'fields' => ['*']
            ]]);
        }


        // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-simple-query-string-query.html#simple-query-string-syntax
        /*$query->query(['simple_query_string' => [
            'query' => $this->q,
            'quote_field_suffix' => '.keyword',
        ]]);*/
        /*if (strpos($this->q, ':') !== false) {
            $query->query(['query_string' => [
                'query' => $this->q,
            ]]);
        } else {
            $query->query(['multi_match' => [
                'query' => $this->q,
                'fuzziness' => 'AUTO',
                'fields' => ['*']
            ]]);
        }*/

        $query->highlight(['fields' =>  ['*' => [ "pre_tags" => ["<em>"], "post_tags" => ["</em>"] ]]]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [ 'pageSize' => 10, ]
        ]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // @todo only query own elements
        //Yii::$app->user->can('ticket/index/all') ?: $query->own();

        return $dataProvider;
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return array
     */
    public function autocomplete($params)
    {
        $this->load($params, '');

        $query = new Query;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [ 'pageSize' => 10, ]
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }
        if (empty($this->index)) {
            $this->index = array_merge(array_keys($this->indexes));
        } else {
            array_unshift($this->index, 'meta');
        }

        $query->from($this->index)->limit(10);

        /* default fields to search for when autocompleting */
        $fields = [];
        $baseFields = [];

        if (str_contains($this->q, ' ')) {
            #$this->q = substr($this->q, strrpos($this->q, ' ') + 1); // get last element after splitting the string
        }

        if (str_contains($this->q, ':')) {
            list($field, $value) = explode(':', $this->q, 2);
            $this->q = $value;
            $baseFields[] = $field;
            foreach ($this->index as $index) {
                if (array_key_exists($index, $this->indexes)) {
                    $class = $this->indexes[$index];
                    if ($class !== null) {
                        if (array_key_exists($field, $class::$autocomplete)) {
                            $fields = array_merge($fields, $class::$autocomplete[$field]);
                        }
                    }
                }
            }
        } else {
            /**
             * Check the index definitions for autocomplete fields to search in
             * this specific indices
             */
            foreach ($this->index as $index) {
                if (array_key_exists($index, $this->indexes)) {
                    $class = $this->indexes[$index];
                    if ($class !== null) {
                        foreach ($class::$autocomplete as $baseField => $autocompleteFields) {
                            $baseFields[] = $baseField;
                            $fields = array_merge($fields, $autocompleteFields);
                        }
                    }
                }
            }
        }

        // use a search-as-you-type query, a suggest query won't work over multiple indices
        // with multiple fields.
        $query->query(['multi_match' => [
            'query' => $this->q,
            'type' => 'bool_prefix', // search-as-you-type query
            'zero_terms_query' => empty($this->q) ? 'all' : 'none', // if the query q is empty, return all documents
            'fields' => $fields,
        ]]);

        foreach ($fields as $field) {
            $query->addAggregate($field, ['terms' => ['field' => $field.'.keyword']]);
        }

        // @todo only query own elements
        //Yii::$app->user->can('ticket/index/all') ?: $query->own();

        /* build the return array with aggregated results */
        $ret = [];
        foreach ($dataProvider->getAggregations() as $field => $aggregate) {
            foreach ($aggregate['buckets'] as $bucket) {
                $value = $bucket['key'];
                if (count($baseFields) == 1) {
                    $template = str_contains($value, ' ') ? '{field}:"{value}"' : '{field}:{value}';
                    $field = $baseFields[0];
                } else if ($field == 'fieldname') {
                    $template = '{value}:';
                } else {
                    $template = str_contains($value, ' ') ? '"{value}"' : '{value}';
                }

                $ret[] = [
                    'value' => substitute($template, [
                        'value' => $value,
                        'field' => $field
                    ]),
                ];
            }
        }

        return $ret;
    }

    /**
     * Desides whether to call query_string or multi_match
     * if the search string contains one of + - = AND OR && || > < ! ( ) { } [ ] ^ " ~ * ? : \ /
     * then a query_string query should be performed
     *
     * @return bool
     */
    public function is_query_string()
    {
        $m = [];
        if (preg_match('/[\s]*\\\qs[\s]*/', $this->q, $m) === 1) {
            return true;
        }
        if (preg_match('/[\s]*\\\mm[\s]*/', $this->q, $m) === 1) {
            return false;
        }
        foreach ($this->query_string_regexes as $r) {
            if (preg_match($r, $this->q, $m) === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Desides whether the query string needs to be rewritten
     */
    public function is_notouch()
    {
        $m = [];
        return preg_match('/[\s]*\\\notouch[\s]*/', $this->q, $m) === 1;
    }

    /**
     * Rewrite the query string [[q]] in according to the given rewritings.
     * @param $rewritings array
     */
    public function rewrite_query($q)
    {
        $q = $this->rewrite_string($this->q, $this->forced_rewritings_pre);
        if (!$this->is_notouch()) {
            if ($this->is_query_string()) {
                $q = $this->rewrite_string($q, $this->query_string_rewritings);
            } else {
                $q = $this->rewrite_string($q, $this->multi_match_rewritings);
            }
        }
        return $this->rewrite_string($q, $this->forced_rewritings_post);
    }

    /**
     * Rewrite the query string [[q]] in according to the given rewritings.
     * @param $rewritings array
     */
    public function rewrite_string($q, $rewritings)
    {
        foreach ($rewritings as $pattern => $replacement) {
            $q = preg_replace($pattern, $replacement, $q);
        }
        return $q;
    }
}
