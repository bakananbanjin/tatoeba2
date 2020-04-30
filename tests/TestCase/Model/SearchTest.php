<?php
namespace App\Test\TestCase\Model;

include(APP.'Lib/SphinxClient.php'); // needed to get the constants
use App\Model\Search;
use Cake\TestSuite\TestCase;

class SearchTest extends TestCase
{
    public $fixtures = [
        'app.users',
    ];

    public function setUp()
    {
        parent::setUp();
        $this->Search = new Search();
    }

    public function tearDown()
    {
        parent::tearDown();
        unset($this->Search);
    }

    public function testWithoutFilters() {
        $expected = ['index' => ['und_index']];

        $result = $this->Search->asSphinx();

        $this->assertEquals($expected, $result);
    }

    public function testfilterByQuery() {
        $expected = ['index' => ['und_index'], 'query' => 'comme ci comme ça'];
        $this->Search->filterByQuery('comme ci comme ça');

        $result = $this->Search->asSphinx();

        $this->assertEquals($expected, $result);
    }

    public function testfilterByQuery_empty() {
        $expected = ['index' => ['und_index'], 'query' => ''];
        $this->Search->filterByQuery('');

        $result = $this->Search->asSphinx();

        $this->assertEquals($expected, $result);
    }

    public function testfilterByLanguage_validLang() {
        $expected = ['index' => ['por_main_index', 'por_delta_index']];
        $this->Search->filterByLanguage('por');

        $result = $this->Search->asSphinx();

        $this->assertEquals($expected, $result);
    }

    public function testfilterByLanguage_und() {
        $expected = ['index' => ['und_index']];
        $this->Search->filterByLanguage('und');

        $result = $this->Search->asSphinx();

        $this->assertEquals($expected, $result);
    }

    public function testfilterByLanguage_invalidLang() {
        $expected = ['index' => ['und_index']];
        $this->Search->filterByLanguage('1234567890');

        $result = $this->Search->asSphinx();

        $this->assertEquals($expected, $result);
    }

    public function testfilterByOwnerName_invalid() {
        $result = $this->Search->filterByOwnerName('userdoesnotexists');
        $this->assertFalse($result);
    }

    public function testfilterByOwnerName_valid() {
        $result = $this->Search->filterByOwnerName('contributor');
        $this->assertTrue($result);

        $expected = ['index' => ['und_index'], 'filter' => [['user_id', 4]]];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByOwnerName_empty() {
        $expected = ['index' => ['und_index']];
        $result = $this->Search->filterByOwnerName('');
        $this->assertTrue($result);

        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByOwnership_yes() {
        $this->Search->filterByOwnership('yes');

        $expected = ['index' => ['und_index'], 'filter' => [['user_id', 0, false]]];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByOwnership_no() {
        $this->Search->filterByOwnership('no');

        $expected = ['index' => ['und_index'], 'filter' => [['user_id', 0, true]]];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByOwnership_invalid() {
        $this->Search->filterByOwnership('invalid value');

        $expected = ['index' => ['und_index']];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByOwnership_empty() {
        $this->Search->filterByOwnership('');

        $expected = ['index' => ['und_index']];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByCorrectness_yes() {
        $this->Search->filterByCorrectness('yes');

        $expected = ['index' => ['und_index'], 'filter' => [['ucorrectness', 127, false]]];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByCorrectness_no() {
        $this->Search->filterByCorrectness('no');

        $expected = ['index' => ['und_index'], 'filter' => [['ucorrectness', 127, true]]];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByCorrectness_invalid() {
        $this->Search->filterByCorrectness('invalid value');

        $expected = ['index' => ['und_index']];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testfilterByCorrectness_empty() {
        $this->Search->filterByCorrectness('');

        $expected = ['index' => ['und_index']];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    private function assertSortByRank($sort, $rank) {
        $expected = [
            'index' => ['und_index'],
            'query' => 'comme ci comme ça',
            'matchMode' => SPH_MATCH_EXTENDED2,
            'sortMode' => [SPH_SORT_EXTENDED => $sort],
            'rankingMode' => [SPH_RANK_EXPR => $rank],
        ];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    private function assertSortBy($sort) {
        $expected = [
            'index' => ['und_index'],
            'query' => '',
            'matchMode' => SPH_MATCH_EXTENDED2,
            'sortMode' => [SPH_SORT_EXTENDED => $sort],
        ];
        $result = $this->Search->asSphinx();
        $this->assertEquals($expected, $result);
    }

    public function testSortByRelevance_withNonEmptyQuery() {
        $this->Search->filterByQuery('comme ci comme ça');
        $this->Search->sort('relevance');
        $this->assertSortByRank('@rank DESC', '-text_len+top(lcs+exact_order*100)*100');
    }

    public function testSortByRelevance_withNonEmptyQuery_reversed() {
        $this->Search->filterByQuery('comme ci comme ça');
        $this->Search->sort('relevance');
        $this->Search->reverseSort(true);
        $this->assertSortByRank('@rank ASC', '-text_len+top(lcs+exact_order*100)*100');
    }

    public function testSortByRelevance_withEmptyQuery() {
        $this->Search->filterByQuery('');
        $this->Search->sort('relevance');
        $this->assertSortBy('text_len ASC');
    }

    public function testSortByRelevance_withEmptyQuery_reversed() {
        $this->Search->filterByQuery('');
        $this->Search->sort('relevance');
        $this->Search->reverseSort(true);
        $this->assertSortBy('text_len DESC');
    }

    public function testSortByWords_withNonEmptyQuery() {
        $this->Search->filterByQuery('comme ci comme ça');
        $this->Search->sort('words');
        $this->assertSortByRank('@rank DESC', '-text_len');
    }

    public function testSortByWords_withNonEmptyQuery_reversed() {
        $this->Search->filterByQuery('comme ci comme ça');
        $this->Search->sort('words');
        $this->Search->reverseSort(true);
        $this->assertSortByRank('@rank ASC', '-text_len');
    }

    public function testSortByWords_withEmptyQuery() {
        $this->Search->filterByQuery('');
        $this->Search->sort('words');
        $this->assertSortBy('text_len ASC');
    }

    public function testSortByWords_withEmptyQuery_reversed() {
        $this->Search->filterByQuery('');
        $this->Search->sort('words');
        $this->Search->reverseSort(true);
        $this->assertSortBy('text_len DESC');
    }

    public function testSortByCreated_withEmptyQuery() {
        $this->Search->filterByQuery('');
        $this->Search->sort('created');
        $this->assertSortBy('created DESC');
    }

    public function testSortByCreated_withEmptyQuery_reversed() {
        $this->Search->filterByQuery('');
        $this->Search->sort('created');
        $this->Search->reverseSort(true);
        $this->assertSortBy('created ASC');
    }

    public function testSortByCreated_withNonEmptyQuery() {
        $this->Search->filterByQuery('comme ci comme ça');
        $this->Search->sort('created');
        $this->assertSortByRank('@rank DESC', 'created');
    }

    public function testSortByCreated_withNonEmptyQuery_reversed() {
        $this->Search->filterByQuery('comme ci comme ça');
        $this->Search->sort('created');
        $this->Search->reverseSort(true);
        $this->assertSortByRank('@rank ASC', 'created');
    }

    public function testSortByModified_withEmptyQuery() {
        $this->Search->filterByQuery('');
        $this->Search->sort('modified');
        $this->assertSortBy('modified DESC');
    }

    public function testSortByModified_withEmptyQuery_reversed() {
        $this->Search->filterByQuery('');
        $this->Search->sort('modified');
        $this->Search->reverseSort(true);
        $this->assertSortBy('modified ASC');
    }

    public function testSortByModified_withNonEmptyQuery() {
        $this->Search->filterByQuery('comme ci comme ça');
        $this->Search->sort('modified');
        $this->assertSortByRank('@rank DESC', 'modified');
    }

    public function testSortByModified_withNonEmptyQuery_reversed() {
        $this->Search->filterByQuery('comme ci comme ça');
        $this->Search->sort('modified');
        $this->Search->reverseSort(true);
        $this->assertSortByRank('@rank ASC', 'modified');
    }

    public function testSortByRandom() {
        $this->Search->filterByQuery('');
        $this->Search->sort('random');
        $this->assertSortBy('@random');
    }
}