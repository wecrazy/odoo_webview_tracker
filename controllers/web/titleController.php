<?php

class titleController {
    public $title;

    public function __construct() {
        $currentFile = basename($_SERVER['PHP_SELF'], '.php');
        $title = ucwords(str_replace('-', ' ', $currentFile));
        $this->title = $title;
    }

    public function fancyText($text) {
        $title = '';
        $charMap = [
            'a' => '𝒶', 'b' => '𝒷', 'c' => '𝒸', 'd' => '𝒹', 'e' => 'ℯ',
            'f' => '𝒻', 'g' => 'ℊ', 'h' => '𝒽', 'i' => '𝒾', 'j' => '𝒿',
            'k' => '𝓀', 'l' => '𝓁', 'm' => '𝓂', 'n' => '𝓃', 'o' => 'ℴ',
            'p' => '𝓅', 'q' => '𝓆', 'r' => '𝓇', 's' => '𝓈', 't' => '𝓉',
            'u' => '𝓊', 'v' => '𝓋', 'w' => '𝓌', 'x' => '𝓍', 'y' => '𝓎',
            'z' => '𝓏',
            'A' => '𝒜', 'B' => 'ℬ', 'C' => '𝒞', 'D' => '𝒟', 'E' => 'ℰ',
            'F' => 'ℱ', 'G' => '𝒢', 'H' => 'ℋ', 'I' => 'ℐ', 'J' => '𝒥',
            'K' => '𝒦', 'L' => 'ℒ', 'M' => 'ℳ', 'N' => '𝒩', 'O' => '𝒪',
            'P' => '𝒫', 'Q' => 'ℚ', 'R' => 'ℛ', 'S' => '𝒮', 'T' => '𝒯',
            'U' => '𝒰', 'V' => '𝒱', 'W' => '𝒲', 'X' => '𝒳', 'Y' => '𝒴', 
            'Z' => '𝒵',
            '0' => '𝟢', '1' => '𝟣', '2' => '𝟤', '3' => '𝟥', '4' => '𝟦',
            '5' => '𝟧', '6' => '𝟨', '7' => '𝟩', '8' => '𝟪', '9' => '𝟫',
        ];

        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];

            if (array_key_exists($char, $charMap)) {
                $title .= $charMap[$char];
            } else {
                $title .= $char;
            }
        }

        return str_replace('_', ' ', $title);
    }

}