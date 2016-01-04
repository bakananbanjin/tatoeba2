<?php
/**
 * Tatoeba Project, free collaborative creation of languages corpuses project
 * Copyright (C) 2009  HO Ngoc Phuong Trang (tranglich@gmail.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  Tatoeba
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link *   http://tatoeba.org
 */

/**
 * Transcription/transliteration tools.
 *
 * To install these tools: https://github.com/Tatoeba/admin
 * Some script can also be found in /docs/daemons.
 *
 * @category PHP
 * @package  Tatoeba
 * @author   HO Ngoc Phuong Trang <tranglich@gmail.com>
 * @license  Affero General Public License
 * @link *   http://tatoeba.org
 */

class Autotranscription
{
    // Still don't know what to do with these
    public function kat($text)
    {
        return $this->_getGeorgianRomanization($text);
    }

    public function wuu($text)
    {
        return $this->_getShanghaineseRomanization($text);
    }

    /**
     * Return IPA of a shanghainese text
     *
     * @param string $shanghaineseText text in shanghainese
     *
     * @return string
     */
    private function _getShanghaineseRomanization($shanghaineseText)
    {
        $ipaFile = fopen(
            "http://static.tatoeba.org/data/shanghainese2IPA2.txt",
            "r"
        );

        $ipaArray = array();
        $sinogramsArray = array();

        // the file is tab separated value
        // we create two array one with characters, the other
        // with the IPA
        while ($line = fgets($ipaFile)) {
            $arrayLine = explode("\t", $line);
            // there's some blank line in this file so mustn't
            // handle them
            if (count($arrayLine) > 1) {
                array_push($ipaArray, str_replace("\n", ". ", $arrayLine[1]));
                array_push($sinogramsArray, $arrayLine[0]);
            }
        }

        $ipaSentence = str_replace($sinogramsArray, $ipaArray, $shanghaineseText);
        return $ipaSentence;

    }


    /**
     * Return IPA of a Georgian text
     *
     * @param string $text text in Georgian
     *
     * @return string
     */
    private function _getGeorgianRomanization($text) {
        //a - b - g - d - e - v - z - t - i - k - l - m - n -
        // o - p - dj - r - s - t - u - p - q - gh - kh - sh -
        //ch - ts - dz - ts - tch- x - j - h -

        $ipaArray = array(
            'a', 'b', 'g', 'd', 'e',
            'v', 'z', 'tʰ', 'i', 'k’',
            'l', 'm', 'n', 'o', 'p’',
            'ʒ' , 'r', 's', 't’', 'u',
            'pʰ', 'q', 'gh', 'kh', 'ʃ',
            'ch', 'ʦ', 'dz', 'ts', 'tch',
            'x', 'ʤ', 'h',
        );

        $alphabetArray = array(
            'ა', 'ბ', 'გ', 'დ', 'ე',
            'ვ', 'ზ', 'თ', 'ი', 'კ',
            'ლ', 'მ', 'ნ', 'ო', 'პ',
            'ჟ', 'რ', 'ს', 'ტ', 'უ',
            'ფ', 'ქ', 'ღ', 'ყ', 'შ',
            'ჩ', 'ც', 'ძ', 'წ', 'ჭ',
            'ხ', 'ჯ', 'ჰ',
        );

        $ipaSentence = str_replace($alphabetArray, $ipaArray, $text);
        return $ipaSentence;
    }

    public function formatFurigana($text, $furigana) {
        /* Try to fix Mecab untokenized tokens like 男の子 or 飼い犬,
           mainly to have tokenized syntax already here and only need
           to fix the furiganas. */
        $furiRegex = preg_replace('/([^\p{Han}]+)/u', '($1)', $text);
        $furiRegex = str_replace(
            '物',
            '(もの|.+?)',
            $furiRegex,
            $kanjis
        );
        $furiRegex = preg_replace('/[\p{Han}]+/u', '(.+?)', $furiRegex,
                                  -1, $kanjis2);
        $kanjis += $kanjis2;

        if ($kanjis > 0) {
            preg_match("/^${furiRegex}\$/u", $furigana, $furiMatches);
            preg_match("/^${furiRegex}\$/u", $text,     $textMatches);
            if (count($furiMatches) == count($textMatches)) {
                $result = '';
                for ($i = 1 ; $i < count($furiMatches); $i++) {
                    $kanji = $textMatches[$i];
                    $furi  = $furiMatches[$i];
                    $result .= $kanji == $furi ? $furi : "[$kanji|$furi]";
                }
                return $result;
            }
        }

        /* Remove furigana on numbers since they are almost always wrong.
           Mecab parses them individually, e.g. 10 reads いちぜろ. */
        if (preg_match('/^[0-9０-９]$/u', $text)) {
            return $text;
        }

        return "[$text|$furigana]";
    }

    /**
     * Convert Japanese text into furigana.
     */
    public function jpn_Jpan_to_Hrkt_generate($text)
    {
        $romanization = '';
        // Use DOMDocument since SimpleXML can't handle mixed content
        $xml = DOMDocument::load("http://127.0.0.1:8842/furigana?str=".urlencode($text), LIBXML_NOBLANKS|LIBXML_NOCDATA);
        $parse = $xml->firstChild->firstChild;
        foreach ($parse->childNodes as $token) {
            foreach ($token->childNodes as $reading) {
                $text = $reading->nodeValue;
                if ($reading->hasChildNodes()) {
                    $furigana = $reading->getAttribute('furigana');
                    $romanization .= $this->formatFurigana($text, $furigana);
                } elseif (
                    preg_match("/[^\p{Hiragana}\p{Katakana}ー\p{P}]/u", $text)
                ) {
                    /* Nihongoparserd returns furigana-less tokens for both
                       tokens it was unable to find a reading AND tokens that
                       can’t have readings such as punctuation. Let's insert
                       empty furiganas only on tokens that should have some. */
                    $romanization .= "[$text|]";
                } else {
                    $romanization .= $text;
                }
            }
        }

        return trim($romanization);
    }

    public function jpn_Jpan_to_Hrkt_validate($sentenceText, $transcr, &$errors) {
        $tokenizeFuriRegex = '/\[([^|]+)\|([\p{Hiragana}\p{Katakana}ー]*)\]/u';

        $withoutFuri = preg_replace($tokenizeFuriRegex, '$1', $transcr);
        if ($sentenceText !== $withoutFuri) {
            /* Find the first character that differs */
            $character = mb_substr(
                mb_strcut(
                    $withoutFuri,
                    strspn($withoutFuri ^ $sentenceText, "\0")
                ),
                0,
                1
            );
            if ($character) {
                $errors[] = format(
                    __(
                        'The provided sentence differs from the original one '.
                        'near “{character}”.',
                        true),
                    compact('character')
                );
            } else {
                $errors[] = format(
                    __(
                        'The provided sentence is shorter than the '.
                        'orginial one.',
                        true),
                    compact('character')
                );
            }
        }

        $withFuri = preg_replace('/\[([^|]+)\|\]/u', '$1', $transcr);
        $withFuri = preg_replace($tokenizeFuriRegex, '$2', $withFuri);
        if (preg_match_all("/[^\p{Hiragana}\p{Katakana}ー\p{P}\p{Z}]/u", $withFuri, $matches)) {
            /* @translators: This string is used to create an enumeration by
               joining each item with it. For instance, if you translate this
               string to “/” and the list is A, B, C, then the translated
               enumeration will be A/B/C. */
            $charsEnumeration = implode(__(', ', true), $matches[0]);
            $errors[] = format(
                __n(
                    'The following character lacks furigana: {charsEnumeration}.',
                    'The following characters lack furigana: {charsEnumeration}.',
                    count($matches[0]),
                    true),
                compact('charsEnumeration')
            );
        }

        return count($errors) == 0;
    }

    private function _sino_detectScript($text) {
        $map = array('simplified' => 'Hans', 'traditional' => 'Hant');
        $guessed = $this->_call_sinoparserd('guess_script', $text);
        return isset($map[$guessed]) ? $map[$guessed] : false;
    }

    private function _call_sinoparserd($action, $text) {
        $xml = simplexml_load_file(
            "http://127.0.0.1:8042/$action?str=".urlencode($text)
            ,'SimpleXMLElement', LIBXML_NOCDATA
        );
        foreach($xml as $key => $value) {
            return (string)$value;
        }
        return false;
    }

    public function cmn_detectScript($text) {
        return $this->_sino_detectScript($text);
    }

    public function cmn_Hant_to_Hans_generate($text) {
        return $this->_call_sinoparserd('simp', $text);
    }

    public function cmn_Hans_to_Hant_generate($text) {
        return $this->_call_sinoparserd('trad', $text);
    }

    private function _basic_pinyin_cleanup($text) {
        $text = preg_replace('/\s+([!?:;.,])/', '$1', $text);
        $text = preg_replace('/"\s*([^"]+)\s*"/', '"$1"', $text);
        $text = ucfirst($text);
        return $text;
    }

    public function cmn_Hant_to_Latn_generate($text) {
        $pinyin = $this->_call_sinoparserd('pinyin', $text);
        $pinyin = $this->_basic_pinyin_cleanup($pinyin);
        return $pinyin;
    }

    public function cmn_Hans_to_Latn_generate($text) {
        $pinyin = $this->_call_sinoparserd('pinyin', $text);
        $pinyin = $this->_basic_pinyin_cleanup($pinyin);
        return $pinyin;
    }

    public function cmn_Hans_to_Latn_validate($sentenceText, $transcr, &$errors) {
        return $this->cmn_Latn_validate($sentenceText, $transcr, $errors);
    }

    public function cmn_Hant_to_Latn_validate($sentenceText, $transcr, &$errors) {
        return $this->cmn_Latn_validate($sentenceText, $transcr, $errors);
    }

    private function cmn_Latn_validate($sentenceText, $transcr, &$errors) {
        if (preg_match_all('/[\p{Han}]/u', $transcr, $matches)) {
            $charsEnumeration = implode(__(', ', true), $matches[0]);
            $errors[] = format(
                __n(
                    'The following character is not transcribed: {charsEnumeration}.',
                    'The following characters are not transcribed: {charsEnumeration}.',
                    count($matches[0]),
                    true),
                compact('charsEnumeration')
            );
            return false;
        }
        return true;
    }

    private function yue_jyutping($text) {
        return $this->_call_sinoparserd('jyutping', $text);
    }

    public function yue_detectScript($text) {
        return $this->_sino_detectScript($text);
    }

    public function yue_Hant_to_Latn_generate($text) {
        return $this->yue_jyutping($text);
    }

    public function yue_Hans_to_Latn_generate($text) {
        return $this->yue_jyutping($text);
    }

    /**
     * Uzbek script-switching functions
     * © 2010, Dmitry Kushnariov. Distributed under the BSD license
     *
     * Finds a script of Uzbek text
     */
    public function uzb_detectScript($text) {
        if (empty($text)) {
            return false;
        }

        $needles = array(
            '‘', '’', '.', ',', ';',
            ':', '1', '2', '3', '4',
            '5', '6', '7', '8', '9',
            '0', ' ', '-', '«', '»',
            '—'
        );
        $replacements = array(
            "'", "'", '', '', '',
            '', '', '', '', '',
            '', '', '', '', '',
            '',  '',  '', '', '',
            ''
        );
        $sentence = str_replace($needles, $replacements, $text);

        $cyr = 0;
        $lat = 0;
        for ($i = 0; $i < strlen($sentence); $i++) {
            if (ord($sentence[$i]) < 128) {
                $lat += 2;
            } else {
                $cyr += 1;
            }
        }
        return ($cyr >= $lat) ? 'Cyrl' : 'Latn';
    }

    public function uzb_Latn_to_Cyrl_generate($text) {
        $needles = array(
            '‘', '’', "s'h", "S'h", "S'H",
            "O'", "o'", "G'", "g'", 'SH',
            'Sh', 'sh', 'CH', 'Ch', 'ch',
            'YO', 'Yo', 'yo', ' E', ' e',
            '-E', '-e', 'Ye', 'YE', 'ye',
            'e', 'E', 'YA', 'Ya', 'ya',
            'YU', 'Yu', 'yu', 'A', 'a',
            'B', 'b', 'D', 'd', 'F',
            'f', 'G', 'g', 'H', 'h',
            'I', 'i', 'J', 'j', 'K',
            'k', 'L', 'l', 'M', 'm',
            'N', 'n', 'O', 'o', 'P',
            'p', 'Q', 'q', 'R', 'r',
            'S', 's', 'T', 't', 'U',
            'u', 'V', 'v', 'X', 'x',
            'Y', 'y', 'Z', 'z', "'",
            'ТС', 'тс', 'Тс', 'циз', 'ЦИЗ',
            'сирк', 'Сирк'
        );
        $replacements = array(
            "'", "'", 'сҳ',  'Сҳ',  "СҲ",
            'Ў',  'ў',  'Ғ',  'ғ',  'Ш',
            'Ш',  'ш',  'Ч',  'Ч',  'ч',
            'Ё',  'Ё',  'ё',  ' Э', ' э',
            '-Э', '-э', 'Е',  'Е',  'е',
            'е', 'Е', 'Я',  'Я',  'я',
            'Ю',  'Ю',  'ю',  'А', 'а',
            'Б', 'б', 'Д', 'д', 'Ф',
            'ф', 'Г', 'г', 'Ҳ', 'ҳ',
            'И', 'и', 'Ж', 'ж', 'К',
            'к', 'Л', 'л', 'М', 'м',
            'Н', 'н', 'О', 'о', 'П',
            'п', 'Қ', 'қ', 'Р', 'р',
            'С', 'с', 'Т', 'т', 'У',
            'у', 'В', 'в', 'Х', 'х',
            'Й', 'й', 'З', 'з', 'ъ',
            'Ц',  'ц',  'ц',  'тсиз', 'ТСИЗ',
            'цирк', 'Цирк'
        );
        return str_replace($needles, $replacements, $text);
    }

    public function uzb_Cyrl_to_Latn_generate($text) {
        $needles =  array(
            'ац',  'ец',  'иц',  'оц',  'уц',
            'ўц',   'эц',  'АЦ',  'ЕЦ',  'ИЦ',
            'ОЦ',  'УЦ',  'ЎЦ',   'Ац',  'Ец',
            'Ии',  'Оц',  'Уц',  'Ўц',   'ц',
            'Ц', ' Е',  ' е',  '-Е', '-е',
            'е', 'А', 'а', 'Б', 'б',
            'В', 'в', 'Г', 'г', 'Ғ',
            'ғ', 'Д', 'д', 'Ё', 'ё',
            'Ж', 'ж', 'З', 'з', 'И',
            'и', 'Й', 'й', 'К', 'к',
            'Қ', 'қ', 'Л', 'л', 'М',
            'м', 'Н', 'н', 'О', 'о',
            'П', 'п', 'Р', 'р', 'С',
            'с', 'Т', 'т', 'У', 'у',
            'Ў', 'ў', 'Ф', 'ф', 'Х',
            'х', 'Ҳ', 'ҳ', 'Ч', 'ч',
            'Ш', 'ш', 'Ъ', 'ъ', 'Ы',
            'ы', 'Ь', 'ь', 'Э', 'э',
            'Ю',  'ю',  'Я',  'я'
        );
        $replacements = array(
            'ats', 'еts', 'its', 'ots', 'uts',
            'o‘ts', 'эts', 'ATS', 'ЕTS', 'ITS',
            'OTS', 'UTS', 'O‘TS', 'Ats', 'Еts',
            'Its', 'Ots', 'Uts', 'O‘ts', 's',
            'S', ' Ye', ' ye', '-Ye', '-ye',
            'e', 'A', 'a', 'B', 'b',
            'V', 'v', 'G', 'g', 'G‘',
            'g‘', 'D', 'd', 'Yo', 'yo',
            'J', 'j', 'Z', 'z', 'I',
            'i', 'Y', 'y', 'K', 'k',
            'Q', 'q', 'L', 'l', 'M',
            'm', 'N', 'n', 'O', 'o',
            'P', 'p', 'R', 'r', 'S',
            's', 'T', 't', 'U', 'u',
            'O‘','o‘', 'F', 'f', 'X',
            'x', 'H', 'h', 'Ch', 'ch',
            'Sh', 'sh', '’', '’', 'I',
            'i', '', '', 'E', 'e',
            'Yu', 'yu', 'Ya', 'ya'
        );
        return str_replace($needles, $replacements, $text);
    }
}
