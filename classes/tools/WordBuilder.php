<?php

use \PhpOffice\PhpWord\PhpWord;
use \PhpOffice\PhpWord\Element\Header;
use \PhpOffice\PhpWord\Shared\Converter;
use \PhpOffice\PhpWord\Style\Language;
use \PhpOffice\PhpWord\IOFactory;

class WordBuilder {
    
    protected $book;
    protected $layout;
    protected $format;
    protected $config;
    protected $metadata;
    protected $parts;
    protected $document;
    protected $styles = [];
    
    protected static $WIDTH = 'width';
    protected static $HEIGHT = 'height';
    protected static $MARGIN = 'margin';
    protected static $FONT = 'font';
    protected static $PARAGRAPH = 'paragraph';
    
    public function __construct($book, $layout = 'default', $format = 'Word2007', $config = 'word') {
        $this->book = $book;
        $this->layout = $layout;
        $this->format = $format;
        $this->config = Zord::array_merge(Zord::getConfig('word'), Zord::getConfig($config));
    }
    
    public function process() {
        $this->document = new PhpWord();
        $this->loadData();
        $this->loadSettings();
        foreach ($this->parts as $part) {
            if ($this->isSection($part)) {
                $section = $this->document->addSection($this->getSectionStyle($part));
                $this->dressSection($section, $part);
                $text = Zord::firstElementChild($part['dom']->documentElement);
                $footnotes = [];
                foreach (Zord::nextElementSibling($text)->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE
                        && $child->localName === 'div'
                        && $child->hasAttribute('id')) {
                        $footnotes[$child->getAttribute('id')] = Zord::firstElementChild(Zord::nextElementSibling(Zord::firstElementChild($child)));
                    }
                }
                $this->handleNode($section, null, $text, $footnotes, 'text', [], []);
            }
        }
        $writer = IOFactory::createWriter($this->document, $this->format);
        $file = $this->filename();
        $writer->save($file);
        return $file;
    }
    
    protected function loadData() {
        $this->metadata = Library::data($this->book, 'metadata.json', 'array');
        $this->parts = Library::data($this->book, 'parts.json', 'array');
        foreach ($this->parts as &$part) {
            if ($this->isSection($part)) {
                $part['dom'] = Library::data($this->book, $part['name'].'.xhtml', 'document');
            }
        }
    }
    
    protected function loadSettings() {
        $this->document->getSettings()->setThemeFontLang(new Language($this->metadata['language'] ?? 'fr'));
        $this->document->getSettings()->setEvenAndOddHeaders(true);
    }
    
    protected function getSectionStyle($part) {
        return [
            "pageSizeH"    => $this->getPageHeight($part),
            "pageSizeW"    => $this->getPageWidth($part),
            "marginTop"    => $this->getMarginTop($part),
            "marginBottom" => $this->getMarginBottom($part),
            "marginLeft"   => $this->getMarginLeft($part),
            "marginRight"  => $this->getMarginRight($part)
        ];
    }
    
    protected function isSection($part) {
        $excludes = $this->config['excludes'] ?? [];
        return ($part['epub'] ?? false) && 
               !in_array($part['name'], $excludes) &&
               !in_array($this->book.'/'.$part['name'], $excludes);
    }
    
    protected function isRotated($part) {
        return in_array($part['name'], $this->config['layouts'][$this->layout]['rotated'] ?? []);
    }
    
    protected function getPageHeight($part) {
        return $this->getSize($part, self::$HEIGHT);
    }
    
    protected function getPageWidth($part) {
        return $this->getSize($part, self::$WIDTH);
    }
    
    protected function getMarginTop($part) {
        return $this->getSize($part, self::$MARGIN.'.top');
    }
    
    protected function getMarginBottom($part) {
        return $this->getSize($part, self::$MARGIN.'.bottom');
    }
    
    protected function getMarginLeft($part) {
        return $this->getSize($part, self::$MARGIN.'.left');
    }
    
    protected function getMarginRight($part) {
        return $this->getSize($part, self::$MARGIN.'.right');
    }
    
    protected function dressSection(&$section, $part) {
        if ($this->hasHeader($part)) {
            $this->dressHeader($section, $part);
        }
        if ($this->hasFooter($part)) {
            $this->dressFooter($section, $part);
        }
    }
    
    protected function hasHeader($part) {
        return $part['name'] !== 'home';
    }
    
    protected function hasFooter($part) {
        return $part['name'] !== 'home';
    }
    
    protected function dressHeader(&$section, $part) {
        list($fontStyle) = $this->getFontStyle(null, 'header', [], []);
        list($paragraphStyle) = $this->getParagraphStyle(null, 'header', [], []);
        $header = $section->addHeader();
        $header->addText($part['title'], $fontStyle, $paragraphStyle);
        $evenHeader = $section->addHeader(Header::EVEN);
        $evenHeader->addText($this->metadata['title'], $fontStyle, $paragraphStyle);
        $firstHeader = $section->addHeader(Header::FIRST);
        $firstHeader->addText('');
    }
    
    protected function dressFooter(&$section, $part) {
        list($fontStyle) = $this->getFontStyle(null, 'footer', [], []);
        list($paragraphStyle) = $this->getParagraphStyle(null, 'footer', [], []);
        $footer = $section->addFooter();
        $footer->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
        $evenfooter = $section->addFooter(Header::EVEN);
        $evenfooter->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
        $firstFooter = $section->addFooter(Header::FIRST);
        $firstFooter->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
    }
    
    protected function handleNode(&$section, $paragraph, $node, $footnotes, $context, $styles, $done) {
        list($fontStyle, $done) = $this->getFontStyle($node, $context, $styles, $done);
        list($paragraphStyle, $done) = $this->getParagraphStyle($node, $context, $styles, $done);
        $paragraph = $paragraph ?? ($this->isParagraph($node) ? $section->addTextRun($paragraphStyle) : null);
        $container = $paragraph ?? $section;
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $content = $this->textContent($child, !isset($paragraph));
                if (!empty($content)) {
                    $container->addText($content, $fontStyle, $paragraphStyle);
                }
            } else if ($child->nodeType === XML_ELEMENT_NODE) {
                if ($this->isTeiElement($child, 'note')) {
                    $note = null;
                    $id = null;
                    if ($child->hasAttribute('id')) {
                        if (!$child->hasAttribute('data-place') || $child->getAttribute('data-place') === 'foot') {
                            $note = $container->addFootNote();
                            $id = 'footref_'.$child->getAttribute('id');
                        } else if ($child->hasAttribute('data-place') && $child->getAttribute('data-place') === 'end') {
                            $note = $container->addEndNote();
                            $id = 'endref_'.$child->getAttribute('id');
                        }
                    }
                    if ($note && $id && isset($footnotes[$id])) {
                        $this->handleNode($section, $note, $footnotes[$id], $footnotes, 'note', [], []);
                    }
                } else if ($this->isTeiElement($child, 'graphic') && $child->hasAttribute('data-url')) {
                    $url = $child->getAttribute('data-url');
                    if (substr($url, 0, 1) == '/') {
                        $url = substr($url, 1);
                    } else {
                        $url = $this->book.DS.$url;
                    }
                    $dimension = 'height';
                    $size = 150;
                    /*
                    $loading = Zord::firstElementChild($child);
                    if ($loading->hasAttribute('style')) {
                        $matches = [];
                        if (preg_match('/^(height|width):([0-9]+)?px;$/i', $loading->getAttribute('style'), $matches)) {
                            $dimension = $matches[1];
                            $size = Converter::pixelToPoint($matches[2]);
                        }
                    }
                    */
                    $file = STORE_FOLDER.'medias'.DS.$url;
                    if (file_exists($file)) {
                        $container->addImage(STORE_FOLDER.'medias'.DS.$url, [
                            'alignment' => 'center',
                            $dimension  => $size
                        ]);
                    }
                } else if ($child->localName === 'br') {
                    $container->addTextBreak();
                } else if ($child->localName === 'table') {
                    $table = $container->addTable();
                    $row = Zord::firstElementChild($child);
                    while ($row) {
                        $table->addRow();
                        $cell = Zord::firstElementChild($row);
                        while ($cell) {
                            $this->handleNode($section, $table->addCell(), $cell, $footnotes, $context, $styles, $done);
                            $cell = Zord::nextElementSibling($cell);
                        }
                        $row = Zord::nextElementSibling($row);
                    }
                } else if ($this->isTeiElement($child, 'pb') && $this->hasAttribute($child, 'data-n')) {
                    $container->addText('{'.$child->getAttribute('data-n').'}', $fontStyle, $paragraphStyle);
                } else {
                    $this->handleNode($section, $paragraph, $child, $footnotes, $context, [
                        self::$FONT      => $fontStyle,
                        self::$PARAGRAPH => $paragraphStyle
                    ], $done);
                }
            }
        }
    }
    
    protected function textContent($node, $trim = false) {
        $content = preg_replace('#\s+#s', ' ', htmlspecialchars($node->textContent));
        return $trim ? trim($content) : $content;
    }
    
    protected function getFontStyle($node, $context, $styles, $done) {
        return $this->getStyle($node, $context, self::$FONT, $styles, $done);
    }
    
    protected function getParagraphStyle($node, $context, $styles, $done) {
        return $this->getStyle($node, $context, self::$PARAGRAPH, $styles, $done);
    }
    
    protected function isTeiElement($node, $values = null) {
        return isset($node) &&
               $node->nodeType === XML_ELEMENT_NODE &&
               $node->localName === 'div' && (
                   $this->hasAttribute($node, 'class', $values) ||
                   $this->hasAttribute($node, 'data-type', $values)
               );
    }
    
    protected function getStyle($node, $context, $type, $styles, $done) {
        $isTEI = $this->isTeiElement($node);
        $class = $isTEI ? $node->getAttribute('class') : '*';
        $rend = null;
        if ($isTEI) {
            if ($node->hasAttribute('data-rendition')) {
                $rend = $node->getAttribute('data-rendition');
            }
            if ($node->hasAttribute('data-rend')) {
                $rend = $node->getAttribute('data-rend');
            }
        }
        $name = $type.'$'.md5(($styles[$type] ?? 'root').'+'.$class.($rend ? '.'.$rend : '').'@'.$context);
        if (!isset($this->styles[$name])) {
            list($style, $done) = $this->mergeStyles($type, $class, $rend, $context, $styles, $done);
            switch ($type) {
                case self::$FONT: {
                    $this->document->addFontStyle($name, $style);
                    break;
                }
                case self::$PARAGRAPH: {
                    $this->document->addParagraphStyle($name, $style);
                    break;
                }
            }
            $this->styles[$name] = $style;
        }
        return [$name, $done];
    }
        
    protected function isParagraph($node) {
        return $this->isTeiElement($node, $this->config[self::$PARAGRAPH] ?? []);
    }
    
    protected function filename() {
        return '/tmp/'.$this->book.'.'.$this->config['extensions'][$this->format];
    }
    
    private function convert($value) {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->convert($item);
            }
        } else if (is_string($value)) {
            $matches = [];
            if (preg_match('/^[+-]?([0-9]+\.?[0-9]*)?(px|in|cm|pt)$/i', $value, $matches)) {
                switch ($matches[2]) {
                    case 'cm': {
                        $value = Converter::cmToTwip($matches[1]);
                        break;
                    }
                    case 'in': {
                        $value = Converter::inchToTwip($matches[1]);
                        break;
                    }
                    case 'pt': {
                        $value = Converter::pointToTwip($matches[1]);
                        break;
                    }
                    case 'px': {
                        $value = Converter::pixelToTwip($matches[1]);
                        break;
                    }
                }
            }
        }
        return $value;
    }
    
    private function getSize($part, $property) {
        if (in_array($property, [self::$HEIGHT,self::$WIDTH]) && $this->isRotated($part)) {
            if ($property === self::$HEIGHT) {
                $property = self::$WIDTH;
            } else if ($property === self::$WIDTH) {
                $property = self::$HEIGHT;
            }
        }
        $value = $this->config['layouts'][$this->layout] ?? [];
        foreach (explode('.', $property) as $token) {
            $value = $value[$token] ?? null;
            if (!isset($value)) {
                break;
            }
        }
        return $this->convert($value ?? 0);
    }
    
    private function mergeStyles($type, $class, $rend, $context, $styles, $done) {
        $style = [];
        foreach ($class ? ['*', $class] : ['*'] as $_class) {
            foreach ($rend ? ['', '.'.$rend] : [''] as $_rend) {
                foreach ($context ? ['', '@'.$context] : [''] as $_context) {
                    $selector = $_class.$_rend.$_context;
                    if ((!in_array($selector, $done[$type] ?? []))) {
                        $done[$type][] = $selector;
                        $style = Zord::array_merge($style, $this->config['styles'][$type][$selector] ?? []);
                    }
                }
            }
        }
        $parent = $this->styles[$styles[$type] ?? 'none'] ?? [];
        $style = $this->convert(Zord::array_merge($parent, $style));
        return [$style, $done];
    }
    
    private function hasAttribute($node, $name, $values = null) {
        return $node->hasAttribute($name) && (!isset($values) || in_array($node->getAttribute($name), is_array($values) ? $values : [$values]));
    }
}

?>