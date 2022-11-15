<?php

use \PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Element\Endnote;
use PhpOffice\PhpWord\Element\Footnote;
use \PhpOffice\PhpWord\Element\Header;
use \PhpOffice\PhpWord\Shared\Converter;
use \PhpOffice\PhpWord\Style\Language;
use \PhpOffice\PhpWord\IOFactory;

class WordBuilder {
    
    protected $book;
    protected $layout;
    protected $format;
    protected $config;
    protected $rules = [];
    protected $metadata;
    protected $parts;
    protected $document;
    protected $styles = [];
    
    protected static $WIDTH = 'width';
    protected static $HEIGHT = 'height';
    protected static $MARGIN = 'margin';
    protected static $MARGIN_TOP = 'margin.top';
    protected static $MARGIN_BOTTOM = 'margin.bottom';
    protected static $MARGIN_LEFT = 'margin.left';
    protected static $MARGIN_RIGHT = 'margin.right';
    protected static $FONT = 'font';
    protected static $PARAGRAPH = 'paragraph';
    protected static $NONE = 'none';
    
    public function __construct($book, $layout = 'default', $format = 'Word2007') {
        $this->book = $book;
        $this->layout = $layout;
        $this->format = $format;
        $this->config = Zord::array_merge(Zord::getConfig('word'), Zord::getConfig('word'.DS.$book));
        foreach (['font','paragraph'] as $type) {
            $styles = $this->config[$type] ?? [];
            foreach ($styles as $selectors => $style) {
                foreach(explode(',', $selectors) as $selector) {
                    $this->rules[$type][trim($selector)] = Zord::array_merge($this->rules[$type][trim($selector)] ?? [], $style);
                }
            }
        }
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
                        $note = Zord::firstElementChild(Zord::nextElementSibling(Zord::firstElementChild($child)));
                        $number = $part['dom']->createTextNode('{'.$note->getAttribute('data-n').'} ');
                        $first = Zord::firstElementChild($note);
                        if ($note->hasChildNodes()) {
                            $note->insertBefore($number, $first);
                        } else {
                            $note->appendChild($number);
                        }
                        $footnotes[$child->getAttribute('id')] = $note;
                    }
                }
                $this->handleNode($part, $section, null, Zord::firstElementChild($text), $footnotes, 'text', [], [], []);
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
    
    protected function getRotation($part) {
        return $this->config['layout'][$this->layout]['rotation'][$part['name']] ?? self::$NONE;
    }
    
    protected function getPageHeight($part) {
        return $this->getLayoutProperty($part, self::$HEIGHT);
    }
    
    protected function getPageWidth($part) {
        return $this->getLayoutProperty($part, self::$WIDTH);
    }
    
    protected function getMarginTop($part) {
        return $this->getLayoutProperty($part, self::$MARGIN_TOP);
    }
    
    protected function getMarginBottom($part) {
        return $this->getLayoutProperty($part, self::$MARGIN_BOTTOM);
    }
    
    protected function getMarginLeft($part) {
        return $this->getLayoutProperty($part, self::$MARGIN_LEFT);
    }
    
    protected function getMarginRight($part) {
        return $this->getLayoutProperty($part, self::$MARGIN_RIGHT);
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
        list($fontStyle) = $this->getFontStyle(null, 'header', [], [], []);
        list($paragraphStyle) = $this->getParagraphStyle(null, 'header', [], [], []);
        $header = $section->addHeader();
        $header->addText($part['title'], $fontStyle, $paragraphStyle);
        $evenHeader = $section->addHeader(Header::EVEN);
        $evenHeader->addText($this->metadata['title'], $fontStyle, $paragraphStyle);
        $firstHeader = $section->addHeader(Header::FIRST);
        $firstHeader->addText('');
    }
    
    protected function dressFooter(&$section, $part) {
        list($fontStyle) = $this->getFontStyle(null, 'footer', [], [], []);
        list($paragraphStyle) = $this->getParagraphStyle(null, 'footer', [], [], []);
        $footer = $section->addFooter();
        $footer->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
        $evenfooter = $section->addFooter(Header::EVEN);
        $evenfooter->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
        $firstFooter = $section->addFooter(Header::FIRST);
        $firstFooter->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
    }
    
    protected function addParents($parents, $node) {
        list($class, $rend, $div) = $this->getTokens($node);
        $_parents = [$class];
        if ($rend) {
            $_parents[] = $class.'.'.$rend;
        }
        if ($div) {
            $_parents[] = $class.'['.$div.']';
        }
        if ($rend && $div) {
            $_parents[] = $class.'['.$div.']'.'.'.$rend;
        }
        $parents[] = $_parents;
        return $parents;
    }
    
    protected function addBeforeText($part, $node, $container, $fontStyle, $paragraphStyle) {
        if ($this->isTeiElement($node, 'l') && $this->hasAttribute($node, 'data-n') && $this->hasAttribute($node, 'data-rend', 'margin')) {
            $fontStyle = Zord::array_merge($this->styles[$fontStyle], $this->rules[self::$FONT]['l.margin:before'] ?? []);
            $paragraphStyle = Zord::array_merge($this->styles[$paragraphStyle], $this->rules[self::$PARAGRAPH]['l.margin:before'] ?? []);
            $container->addText('{v.'.$node->getAttribute('data-n').'}', $fontStyle, $paragraphStyle);
        }
    }
    
    protected function addAfterText($part, $node, $container, $fontStyle, $paragraphStyle) {
        
    }
    
    protected function handleNode($part, &$section, $paragraph, $node, $footnotes, $context, $styles, $done, $parents) {
        list($fontStyle, $done) = $this->getFontStyle($node, $context, $styles, $done, $parents);
        list($paragraphStyle, $done) = $this->getParagraphStyle($node, $context, $styles, $done, $parents);
        $paragraph = (isset($paragraph) && !$this->isTeiElement($node, 'list')) ? $paragraph : ($this->isParagraph($node) ? $section->addTextRun($paragraphStyle) : null);
        $container = $paragraph ?? $section;
        $parents = $this->addParents($parents, $node);
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $this->addBeforeText($part, $node, $container, $fontStyle, $paragraphStyle);
                $content = $this->textContent($child, !isset($paragraph));
                if (!empty($content) && !empty(trim($content))) {
                    $container->addText($content, $fontStyle, $paragraphStyle);
                }
                $this->addAfterText($part, $node, $container, $fontStyle, $paragraphStyle);
            } else if ($child->nodeType === XML_ELEMENT_NODE) {
                if ($this->isTeiElement($child, 'note')) {
                    $note = null;
                    if ($child->hasAttribute('id') && $child->hasAttribute('data-n')) {
                        if (!$child->hasAttribute('data-place') || $child->getAttribute('data-place') === 'foot') {
                            while ($container instanceof Footnote && $container->getParent() !== null) {
                                $container = $container->getParent();
                            }
                            $note = $container->addFootNote();
                        } else if ($child->hasAttribute('data-place') && $child->getAttribute('data-place') === 'end') {
                            while ($container instanceof Endnote && $container->getParent() !== null) {
                                $container = $container->getParent();
                            }
                            $note = $container->addEndNote();
                        }
                    }
                    if ($note) {
                        $id = 'footref_'.$child->getAttribute('id');
                        if (isset($footnotes[$id])) {
                            $this->handleNode($part, $section, $note, $footnotes[$id], $footnotes, 'note', [], [], []);
                        }
                    }
                } else if ($this->isTeiElement($child, 'graphic') && $child->hasAttribute('data-url')) {
                    list($file, $style) = $this->getImageFileAndStyle($part, $child);
                    if (file_exists($file)) {
                        $container->addImage($file, $style);
                    }
                } else if ($this->isHtmlElement($child, 'br')) {
                    $container->addTextBreak();
                } else if ($this->isHtmlElement($child, 'table')) {
                    $first = Zord::firstElementChild($child);
                    if ($this->isHtmlElement($first, 'caption')) {
                        $this->handleNode($part, $section, $paragraph, $first, $footnotes, $context, $styles, $done, $parents);
                        $row = Zord::nextElementSibling($first);
                    } else {
                        $row = $first;
                    }
                    if (!$this->isHtmlElement(Zord::previousElementSibling($child), 'table')) {
                        $container->addTextBreak();
                    }
                    $table = $section->addTable($this->convert($this->getTableStyle($part, $child)));
                    if (!$this->isHtmlElement(Zord::nextElementSibling($child), 'table')) {
                        $container->addTextBreak();
                    }
                    $rowIndex = 0;
                    while ($row) {
                        if ($this->isHtmlElement($row, 'caption')) {
                            $this->handleNode($part, $section, $paragraph, $row, $footnotes, $context, $styles, $done, $parents);
                            break;
                        }
                        $rowHeight = $this->getRowHeight($part, $child, $rowIndex);
                        $rowStyle = $this->getRowStyle($part, $child, $rowIndex);
                        $table->addRow($rowHeight, $rowStyle);
                        $cell = Zord::firstElementChild($row);
                        $cellIndex = 0;
                        while ($cell) {
                            $cellWidth = $this->getCellWidth($part, $child, $cellIndex);
                            $cellStyle = $this->getCellStyle($part, $child, $rowIndex, $cellIndex);
                            list($cellFontStyle, $done) = $this->getFontStyle($cell, $context, $styles, $done, $parents);
                            list($cellParagraphStyle, $done) = $this->getParagraphStyle($cell, $context, $styles, $done, $parents);
                            $this->handleNode($part, $section, $table->addCell($cellWidth, $cellStyle)->addTextRun($cellParagraphStyle), $cell, $footnotes, $context, [
                                self::$FONT      => $cellFontStyle,
                                self::$PARAGRAPH => $cellParagraphStyle
                            ], $done, $parents);
                            $cell = Zord::nextElementSibling($cell);
                            $cellIndex++;
                        }
                        $row = Zord::nextElementSibling($row);
                        $rowIndex++;
                    }
                } else if ($this->isTeiElement($child, 'pb') &&  $this->hasAttribute($child, 'data-n')) {
                    list($pbFontStyle, $done) = $this->getFontStyle($child, $context, $styles, $done, $parents);
                    list($pbParagraphStyle, $done) = $this->getParagraphStyle($child, $context, $styles, $done, $parents);
                    $content =  $this->hasAttribute($child, 'data-rend', 'temoin') ? '['.$child->getAttribute('data-n').']' : '{p.'.$child->getAttribute('data-n').'}';
                    $container->addText($content, $pbFontStyle, $pbParagraphStyle);
                } else {
                    $this->handleNode($part, $section, $paragraph, $child, $footnotes, $context, [
                        self::$FONT      => $fontStyle,
                        self::$PARAGRAPH => $paragraphStyle
                    ], $done, $parents);
                }
            }
        }
    }
    
    protected function textContent($node, $trim = false) {
        $content = preg_replace('#\s+#s', ' ', htmlspecialchars($node->textContent));
        return $trim ? trim($content) : $content;
    }
    
    protected function getFontStyle($node, $context, $styles, $done, $parents) {
        return $this->getStyle($node, $context, self::$FONT, $styles, $done, $parents);
    }
    
    protected function getParagraphStyle($node, $context, $styles, $done, $parents) {
        return $this->getStyle($node, $context, self::$PARAGRAPH, $styles, $done, $parents);
    }
    
    protected function getTableStyle($part, $node) {
        return $this->_getTableStyle($part, $node, 'table');
    }
    
    protected function getRowStyle($part, $node, $rowIndex) {
        return $this->_getTableStyle($part, $node, 'row', $rowIndex);
    }
    
    protected function getCellStyle($part, $node, $rowIndex, $cellIndex) {
        return $this->_getTableStyle($part, $node, 'cell', $rowIndex, $cellIndex);
    }
    
    protected function getTableStyleName($part, $node, $element, $rowIndex = null, $cellIndex = null) {
        return 'default';
    }
    
    protected function getRowHeight($part, $node, $rowIndex) {
        return $this->config['row']['default']['height'] ?? null;
    }
    
    protected function getCellWidth($part, $node, $cellIndex) {
        return $this->config['cell']['default']['width'] ?? null;
    }
    
    protected function getImageFileAndStyle($part, $node) {
        $url = $node->getAttribute('data-url');
        $style = $this->config['image'][$url] ?? null;
        if (!isset($style)) {
            foreach ($this->config['image'] as $pattern => $_style) {
                if (substr($pattern, 0, 1) === '#' && preg_match($pattern, $url)) {
                    $style = $_style;
                }
            }
        }
        $style = $this->convert($style ?? $this->config['image']['default'], true);
        if (substr($url, 0, 1) == '/') {
            $url = substr($url, 1);
        } else {
            $url = $this->book.DS.$url;
        }
        $file = STORE_FOLDER.'medias'.DS.$url;
        if (file_exists($file)) {
            list($width, $height) = getimagesize($file);
            $scale = $style['scale'] ?? false;
            $strech = $style['strech'] ?? false;
            $margin = ($style['margin'] ?? 0) * 20;
            if ($scale) {
                $matches = [];
                $ratio = 1;
                if ($scale === 'fit') {
                    $frameHeight = $this->getPageHeight($part) - $this->getMarginTop($part) - $this->getMarginBottom($part) - 2 * $margin;
                    $frameWidth = $this->getPageWidth($part) - $this->getMarginLeft($part) - $this->getMarginRight($part) - 2 * $margin;
                    $ratio = min([
                        $frameHeight / $this->convert($height."px"),
                        $frameWidth / $this->convert($width."px")
                    ]);
                } else if (preg_match('/^([0-9]+\.?[0-9]*)%$/', $scale, $matches)) {
                    $ratio = floatval($matches[1]) / 100;
                }
                if ($ratio !== 1) {
                    $style['width'] = $this->convert(($width * $ratio)."px", true);
                    $style['height'] = $this->convert(($height * $ratio)."px", true);
                }
            }
            if (!$strech && isset($style['width']) && isset($style['height'])) {
                unset($style[($width * $this->convert($style['height']) / ($height * $this->convert($style['width']))) > 1 ? 'width' : 'height']);
            }
        }
        return [$file, $style];
    }
    
    protected function isTeiElement($node, $values = null) {
        return isset($node) &&
               $node->nodeType === XML_ELEMENT_NODE &&
               $node->localName === 'div' && (
                   $this->hasAttribute($node, 'class', $values) ||
                   ($this->hasAttribute($node, 'class', 'div') && $this->hasAttribute($node, 'data-type', $values))
               );
    }
    
    protected function isHtmlElement($node, $values = null) {
        $values = $values ?? ($this->config['html'] ?? []);
        if (!is_array($values)) {
            $values = [$values];
        }
        return isset($node) && 
               $node->nodeType === XML_ELEMENT_NODE && 
               in_array($node->localName, $values);
    }
    
    protected function getTokens($node) {
        $isTEI = $this->isTeiElement($node);
        $isHTML = $this->isHtmlElement($node);
        $class = $isTEI ? $node->getAttribute('class') : ($isHTML ? $node->localName : '*');
        $div = null;
        if ($isTEI && $node->hasAttribute('data-type')) {
            $div = $node->getAttribute('data-type');
        }
        $rend = null;
        if ($isTEI || $isHTML) {
            if ($node->hasAttribute('data-rendition')) {
                $rend = $node->getAttribute('data-rendition');
            }
            if ($node->hasAttribute('data-rend')) {
                $rend = $node->getAttribute('data-rend');
            }
        }
        return [$class, $rend, $div];
    }
    
    protected function getStyle($node, $context, $type, $styles, $done, $parents) {
        list($class, $rend, $div) = $this->getTokens($node);
        $name = $type.'$'.md5(($styles[$type] ?? 'root').'+'.$class.($div ? '['.$div.']' : '').($rend ? '.'.$rend : '').'@'.$context);
        if (!isset($this->styles[$name])) {
            list($style, $done) = $this->mergeStyles($type, $class, $rend, $div, $context, $styles, $done, $parents);
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
        return $this->isTeiElement($node, $this->config[self::$PARAGRAPH]['names'] ?? []);
    }
    
    protected function filename() {
        return '/tmp/'.$this->book.'.'.$this->config['extension'][$this->format];
    }
    
    private function convert($value, $point = false) {
        if (is_array($value)) {
            foreach ($value as &$item) {
                $item = $this->convert($item, $point);
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
                $value = $value / ($point ? 20 : 1);
            }
        }
        return $value;
    }
    
    private function getLayoutProperty($part, $property) {
        $rotation = $this->getRotation($part);
        if (in_array($property, [self::$HEIGHT,self::$WIDTH]) && $rotation !== self::$NONE) {
            if ($property === self::$HEIGHT) {
                $property = self::$WIDTH;
            } else if ($property === self::$WIDTH) {
                $property = self::$HEIGHT;
            }
        }
        $value = $this->config['layout'][$this->layout] ?? [];
        foreach (explode('.', $property) as $token) {
            $value = $value[$token] ?? null;
            if (!isset($value)) {
                break;
            }
        }
        return $this->convert($value ?? 0);
    }
    
    private function mergeStyles($type, $class, $rend, $div, $context, $styles, $done, $parents) {
        $style = [];
        foreach ($class ? ['*', $class] : ['*'] as $_class) {
            foreach ($div ? ['', '['.$div.']'] : [''] as $_div) {
                foreach ($rend ? ['', '.'.$rend] : [''] as $_rend) {
                    foreach ($context ? ['', '@'.$context] : [''] as $_context) {
                        $_selector = $_class.$_div.$_rend.$_context;
                        list($style, $done) = $this->_mergeStyle($type, $style, $done, [], $_selector);
                        foreach ($parents as $_parents) {
                            list($style, $done) = $this->_mergeStyle($type, $style, $done, $_parents, $_selector);
                        }
                        if (count($parents) > 0) {
                            list($style, $done) = $this->_mergeStyle($type, $style, $done, $parents[count($parents) - 1], $_selector, true);
                        }
                    }
                }
            }
        }
        $parent = $this->styles[$styles[$type] ?? self::$NONE] ?? [];
        $style = $this->convert(Zord::array_merge($parent, $style));
        return [$style, $done];
    }
    
    private function _mergeStyle($type, $style, $done, $parents, $selector, $last = false) {
        $separator = $last ? ' > ' : (!empty($parents) ? ' ' : '');
        foreach (array_merge([''], $parents) as $parent) {
            $_selector = $parent.$separator.$selector;
            if ((!in_array($_selector, $done[$type] ?? []))) {
                $done[$type][] = $_selector;
                $style = Zord::array_merge($style, $this->rules[$type][$_selector] ?? []);
            }
        }
        return [$style, $done];
    }
    
    private function hasAttribute($node, $name, $values = null) {
        return $node->hasAttribute($name) && (!isset($values) || in_array($node->getAttribute($name), is_array($values) ? $values : [$values]));
    }
    
    private function _getTableStyle($part, $node, $element, $rowIndex = null, $cellIndex = null) {
        return $this->config[$element][$this->getTableStyleName($part, $node, $element, $rowIndex, $cellIndex)] ?? [];
    }
}

?>