## Searching

* `foo` searches for `foo` and will also find words similar to `foo`
* `foo bar` searches for `foo` or `bar`, but results with both will have a higher score
* `"foo bar"` exact phrase, searches for `foo` follwed by `bar`
* `"foo"` searches for `foo` and will not find words similar to `foo`

### Advanced Search Options:

#### By fields

* `user:1234` search by user id
* `test_taker:steve` search by student name
* `username:"John Smith"` exact phrase in field

#### Grouping

`(quick OR brown) AND fox` logic and grouping

#### Boolean operators

`quick brown +fox -news` translates to `((quick AND fox) OR (brown AND fox) OR fox) AND NOT news`

The search result **must** contain `fox` and **must not** contain `news`, `quick` and `brown` are optional and will increase the score if present.

#### Range search

* `date:[2012-01-01 TO 2012-12-31]` date range
* `date:{* TO 2012-01-01}` dates before 2012

#### Wildcard search

* `foo*` strings starting with foo
* `*foo` strings ending with foo
* `*foo*` strings containing with foo

* `field:foo*` field has strings starting with foo
* `field:*foo` field has strings ending with foo
* `field:*foo*` field has strings containing with foo

#### Regular expressions

`name:/joh?n(ath[oa]n)/`, see [Regular expression syntax](https://www.elastic.co/guide/en/elasticsearch/reference/current/regexp-syntax.html) for details.

#### Reserved characters

The reserved characters are: `+ - = && || > < ! ( ) { } [ ] ^ " ~ * ? : \ /`

### Modifiers:

* `\notouch`: does not rewrite the search query internally, search exactly the provided string
* `\qs`: force a [query_string](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html) query
* `\mm`: force a [multi_match](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html) query