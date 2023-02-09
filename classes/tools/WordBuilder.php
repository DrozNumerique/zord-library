<?php

use \PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Element\Endnote;
use PhpOffice\PhpWord\Element\Footnote;
use \PhpOffice\PhpWord\Element\Header;
use \PhpOffice\PhpWord\Shared\Converter;
use \PhpOffice\PhpWord\Style\Language;
use \PhpOffice\PhpWord\Style\Tab;
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
    protected $footnotes = [];
    
    public static $WIDTH = 'width';
    public static $HEIGHT = 'height';
    public static $MARGIN = 'margin';
    public static $MARGIN_TOP = 'margin.top';
    public static $MARGIN_BOTTOM = 'margin.bottom';
    public static $MARGIN_LEFT = 'margin.left';
    public static $MARGIN_RIGHT = 'margin.right';
    public static $FONT = 'font';
    public static $PARAGRAPH = 'paragraph';
    public static $NONE = 'none';
    
    public function __construct($book, $layout = 'default', $format = 'Word2007') {
        $this->book = $book;
        $this->layout = $layout;
        $this->format = $format;
        $this->config = Zord::array_merge(Zord::getConfig('word'), Zord::getConfig('word'.DS.$book));
        foreach ([self::$FONT,self::$PARAGRAPH] as $type) {
            $styles = $this->config[$type] ?? [];
            foreach ($styles as $selectors => $style) {
                foreach(explode(',', $selectors) as $selector) {
                    $this->rules[$type][trim($selector)] = Zord::array_merge($this->rules[$type][trim($selector)] ?? [], $style);
                }
            }
        }
    }
    
    public static function isTeiElement($node, $values = null) {
        return isset($node) &&
        $node->nodeType === XML_ELEMENT_NODE &&
        $node->localName === 'div' && (
            self::hasAttribute($node, 'class', $values) ||
            (self::hasAttribute($node, 'class', 'div') && self::hasAttribute($node, 'data-type', $values))
            );
    }
    
    public static function isTeiGroup($node) {
        return self::isTeiElement($node, Zord::value('word', ['fragment','group']) ?? []);
    }
        
    public static function isHtmlElement($node, $values = null) {
        $values = $values ?? (Zord::value('word', ['fragment','html']) ?? []);
        if (!is_array($values)) {
            $values = [$values];
        }
        return isset($node) &&
            $node->nodeType === XML_ELEMENT_NODE &&
            in_array($node->localName, $values);
    }
    
    public static function isParagraph($node) {
        $paragraphs = Zord::value('word', ['fragment','paragraph']) ?? [];
        return self::isTeiElement($node, $paragraphs) ||
            (self::isTeiElement($node) && in_array('*.'.$node->getAttribute('data-rend'), $paragraphs)) ||
            (self::isTeiElement($node) && in_array('*.'.$node->getAttribute('data-rendition'), $paragraphs)) ||
            (self::isTeiElement($node, 'div') && in_array('*.'.$node->getAttribute('data-type'), $paragraphs));
    }
    
    public static function hasAttribute($node, $name, $values = null) {
        return $node->hasAttribute($name) && (!isset($values) || in_array($node->getAttribute($name), is_array($values) ? $values : [$values]));
    }
    
    public static function textContent($node, $trim = false) {
        $content = preg_replace('#\s+#s', ' ', htmlspecialchars($node->textContent));
        return $trim ? trim($content) : $content;
    }
    
    public function process() {
        $this->document = new PhpWord();
        $this->loadData();
        $this->loadSettings();
        foreach ($this->parts as $part) {
            if ($this->isSection($part)) {
                $section = $this->document->addSection($this->getSectionStyle($part));
                $text = Zord::firstElementChild($part['dom']->documentElement);
                $this->dressSection($section, $part);
                $this->footnotes = [];
                foreach (Zord::nextElementSibling($text)->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE
                        && $child->localName === 'div'
                        && $child->hasAttribute('id')) {
                        $this->footnotes[$child->getAttribute('id')] = Zord::firstElementChild(Zord::nextElementSibling(Zord::firstElementChild($child)));
                    }
                }
                $fragment = Zord::getInstance('WordFragment', $this, $part, 'text', $text, $section);
                $this->handleNode($fragment);
            }
        }
        $writer = IOFactory::createWriter($this->document, $this->format);
        $file = $this->filename();
        $writer->save($file);
        return $file;
    }
    
    public function getFontStyle(&$fragment) {
        return $this->getStyle($fragment, self::$FONT);
    }
    
    public function getParagraphStyle(&$fragment) {
        return $this->getStyle($fragment, self::$PARAGRAPH);
    }
    
    protected function handleNode(&$fragment) {
        $paragraph = (isset($fragment->paragraph) && !self::isTeiGroup($fragment->node)) ? $fragment->paragraph : (self::isParagraph($fragment->node) ? $fragment->section->addTextRun($fragment->getParagraphStyle()) : null);
        $container = $paragraph ?? $fragment->section;
        $this->addBeforeText($fragment, $container);
        foreach ($fragment->node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $content = self::textContent($child, !isset($paragraph));
                if (!empty($content)) {
                    $container->addText($content, $fragment->getFontStyle(), $fragment->getParagraphStyle());
                }
            } else if ($child->nodeType === XML_ELEMENT_NODE) {
                if (self::isTeiElement($child, 'note')) {
                    $note = null;
                    if ($child->hasAttribute('id') && $child->hasAttribute('data-n')) {
                        if (!$child->hasAttribute('data-place') || $child->getAttribute('data-place') === 'foot') {
                            while ($container instanceof Footnote && $container->getParent() !== null) {
                                $container = $container->getParent();
                            }
                            $note = $container->addFootNote();
                        } else if ($child->hasAttribute('data-place') && $child->getAttribute('data-place') === 'end') {
                            while (($container instanceof Endnote || $container instanceof Footnote) && $container->getParent() !== null) {
                                $container = $container->getParent();
                            }
                            $note = $container->addEndNote();
                        }
                    }
                    if ($note) {
                        $id = 'footref_'.$child->getAttribute('id');
                        if (isset($this->footnotes[$id])) {
                            $noteFragment = $fragment->attach([
                                'node'      => $this->footnotes[$id],
                                'paragraph' => $note,
                                'context'   => 'note'
                            ]);
                            $this->handleNode($noteFragment);
                        }
                    }
                } else if (self::isTeiElement($child, 'graphic') && $child->hasAttribute('data-url')) {
                    list($file, $style) = $this->getImageFileAndStyle($fragment->attach([
                        'node' => $child
                    ]));
                    if (file_exists($file)) {
                        $container->addImage($file, $style);
                    }
                } else if (self::isHtmlElement($child, 'br')) {
                    if (isset($paragraph) && $this->styles[$fragment->getParagraphStyle()]['alignment'] === 'both') {
                        $container->addText("\t");
                    }
                    $container->addTextBreak();
                } else if (self::isHtmlElement($child, 'table')) {
                    $first = Zord::firstElementChild($child);
                    $firstFragment = $fragment->attach(['node' => $first]);
                    if (self::isHtmlElement($first, 'caption')) {
                        $this->handleNode($firstFragment);
                        $row = Zord::nextElementSibling($first);
                    } else {
                        $row = $first;
                    }
                    if (!self::isHtmlElement(Zord::previousElementSibling($child), 'table')) {
                        $container->addTextBreak();
                    }
                    $table = $fragment->section->addTable($this->convert($this->getTableStyle($fragment->part, $child)));
                    if (!self::isHtmlElement(Zord::nextElementSibling($child), 'table')) {
                        $container->addTextBreak();
                    }
                    $rowIndex = 0;
                    while ($row) {
                        $rowFragment = $fragment->attach(['node' => $row]);
                        if (self::isHtmlElement($row, 'caption')) {
                            $this->handleNode($rowFragment);
                            break;
                        }
                        $rowHeight = $this->getRowHeight($rowFragment, $rowIndex);
                        $rowStyle = $this->getRowStyle($rowFragment, $child, $rowIndex);
                        $table->addRow($rowHeight, $rowStyle);
                        $cell = Zord::firstElementChild($row);
                        $cellIndex = 0;
                        while ($cell) {
                            $cellFragment = $fragment->attach(['node' => $cell]);
                            $cellWidth = $this->getCellWidth($cellFragment, $cellIndex);
                            $cellStyle = $this->getCellStyle($cellFragment, $rowIndex, $cellIndex);
                            $cellFragment->paragraph = $table->addCell($cellWidth, $cellStyle)->addTextRun($cellFragment->getParagraphStyle());
                            $cellFragment->inherits = [
                                self::$FONT      => $cellFragment->getFontStyle(),
                                self::$PARAGRAPH => $cellFragment->getParagraphStyle()
                            ];
                            $this->handleNode($cellFragment);
                            $cell = Zord::nextElementSibling($cell);
                            $cellIndex++;
                        }
                        $row = Zord::nextElementSibling($row);
                        $rowIndex++;
                    }
                } else {
                    $childFragment = $fragment->attach([
                        'node'      => $child,
                        'paragraph' => $paragraph,
                        'inherits'  => [
                            self::$FONT      => $fragment->getFontStyle(),
                            self::$PARAGRAPH => $fragment->getParagraphStyle()
                        ]
                    ]);
                    $this->handleNode($childFragment);
                }
            }
        }
        $this->addAfterText($fragment, $container);
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
               !in_array($part['name'], $excludes);
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
    
    protected function dressSection($section, $part) {
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
    
    protected function dressHeader($section, $part) {
        $fragment = Zord::getInstance('WordFragment', $this, $part, 'header', null, $section);
        $fontStyle = $this->getFontStyle($fragment);
        $paragraphStyle = $this->getParagraphStyle($fragment);
        $header = $section->addHeader();
        $header->addText($part['flat'], $fontStyle, $paragraphStyle);
        $evenHeader = $section->addHeader(Header::EVEN);
        $evenHeader->addText($this->metadata['title'], $fontStyle, $paragraphStyle);
        $firstHeader = $section->addHeader(Header::FIRST);
        $firstHeader->addText(''); 
    }
    
    protected function dressFooter($section, $part) {
        $fragment = Zord::getInstance('WordFragment', $this, $part, 'header', null, $section);
        $fontStyle = $this->getFontStyle($fragment);
        $paragraphStyle = $this->getParagraphStyle($fragment);
        $footer = $section->addFooter();
        $footer->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
        $evenfooter = $section->addFooter(Header::EVEN);
        $evenfooter->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
        $firstFooter = $section->addFooter(Header::FIRST);
        $firstFooter->addPreserveText('{PAGE}', $fontStyle, $paragraphStyle);
    }
    
    protected function addPseudoText($fragment, $container, $position) {
        $pseudoFragment = $fragment->attach(['position' => $position]);
        $content = $this->styles[$pseudoFragment->getparagraphStyle()]['content'] ?? null;
        if ($content) {
            for ($index = 0; $index < $fragment->node->attributes->length; $index++) {
                $name = $fragment->node->attributes->item($index)->name;
                $content = str_replace('attr('.$name.')', $fragment->node->getAttribute($name), $content);
            }
            $container->addText($content, $pseudoFragment->getFontStyle(), $pseudoFragment->getParagraphStyle());
        }
    }
    
    protected function addBeforeText($fragment, $container) {
        $this->addPseudoText($fragment, $container, 'before');
    }
    
    protected function addAfterText($fragment, $container) {
        $this->addPseudoText($fragment, $container, 'after');
    }
    
    protected function getTableStyle($fragment) {
        return $this->_getTableStyle($fragment);
    }
    
    protected function getRowStyle($fragment, $rowIndex) {
        return $this->_getTableStyle($fragment, $rowIndex);
    }
    
    protected function getCellStyle($fragment, $rowIndex, $cellIndex) {
        return $this->_getTableStyle($fragment, $rowIndex, $cellIndex);
    }
    
    protected function getTableStyleName($fragment, $rowIndex = null, $cellIndex = null) {
        return 'default';
    }
    
    protected function getRowHeight($fragment, $rowIndex) {
        return $this->config['row']['default']['height'] ?? null;
    }
    
    protected function getCellWidth($fragment, $cellIndex) {
        return $this->config['cell']['default']['width'] ?? null;
    }
    
    protected function getImageFileAndStyle($fragment) {
        $url = $fragment->node->getAttribute('data-url');
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
                    $frameHeight = $this->getPageHeight($fragment->part) - $this->getMarginTop($fragment->part) - $this->getMarginBottom($fragment->part) - 2 * $margin;
                    $frameWidth = $this->getPageWidth($fragment->part) - $this->getMarginLeft($fragment->part) - $this->getMarginRight($fragment->part) - 2 * $margin;
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
    
    protected function filename() {
        return '/tmp/'.$this->book.'.'.$this->config['extension'][$this->format];
    }
    
    protected function getStyle(&$fragment, $type) {
        $selector = $fragment->class.($fragment->type ? '['.$fragment->type.']' : '').($fragment->rend ? '.'.$fragment->rend : '').($fragment->position ? ':'.$fragment->position : '').'@'.$fragment->context;
        $name = $type.'$'.md5(($fragment->inherits[$type] ?? self::$NONE).$selector);
        if (!isset($this->styles[$name])) {
            $style = $this->mergeStyles($fragment, $type);
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
        return $name;
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
    
    private function mergeStyles(&$fragment, $type) {
        $style = [];
        foreach ($fragment->class ? ['*', $fragment->class] : ['*'] as $_class) {
            foreach ($fragment->type ? ['', '['.$fragment->type.']'] : [''] as $_type) {
                foreach ($fragment->rend ? ['', '.'.$fragment->rend] : [''] as $_rend) {
                    foreach ($fragment->position ? ['', ':'.$fragment->position] : [''] as $_position) {
                        foreach ($fragment->context ? ['', '@'.$fragment->context] : [''] as $_context) {
                            $_selector = $_class.$_type.$_rend.$_position.$_context;
                            $style = $this->_mergeStyle($style, $fragment, $type, $_selector, []);
                            foreach ($fragment->variants as $variants) {
                                $style = $this->_mergeStyle($style, $fragment, $type, $_selector, $variants);
                            }
                            if (count($fragment->variants) > 0) {
                                $style = $this->_mergeStyle($style, $fragment, $type, $_selector, end($fragment->variants), true);
                            }
                        }
                    }
                }
            }
        }
        if ($style['inherits'] ?? true) {
            $parent = $this->styles[$fragment->inherits[$type] ?? self::$NONE] ?? [];
            $style = Zord::array_merge($parent, $style);
        }
        $style = $this->convert($style);
        if ($type === self::$PARAGRAPH && isset($style['tabs']) && is_array($style['tabs']) && !Zord::is_associative($style['tabs'])) {
            foreach ($style['tabs'] as &$tab) {
                if (is_array($tab) && count($tab) > 0 && in_array($tab[0], $this->config['tab'] ?? [])) {
                    $tab = new Tab($tab[0], $tab[1] ?? 0, $tab[2] ?? null);
                }
            }
        }
        return $style;
    }
    
    private function _mergeStyle($style, &$fragment, $type, $selector, $variants, $last = false) {
        $parents = $last ? $variants : array_merge([''], $variants);
        $separator = $last ? ' > ' : (!empty($variants) ? ' ' : '');
        foreach ($parents as $parent) {
            $_selector = $parent.$separator.$selector;
            $rule = $this->rules[$type][$_selector] ?? [];
            if (!empty($rule) && !in_array($_selector, $fragment->done[$type] ?? [])) {
                $fragment->done[$type][] = $_selector;
                $style = Zord::array_merge($style, $rule);
            }
        }
        return $style;
    }
    
    private function _getTableStyle($fragment, $rowIndex = null, $cellIndex = null) {
        $element = isset($rowIndex) ? (isset($cellIndex) ? 'cell' : 'row') : 'table';
        return $this->config[$element][$this->getTableStyleName($fragment, $rowIndex, $cellIndex)] ?? [];
    }
    
}

class WordFragment {
    
    public $builder;
    public $part;
    public $context;
    public $node;
    public $section;
    public $paragraph = null;
    public $inherits = [];
    public $variants = [];
    public $done = [];
    public $class;
    public $rend;
    public $type;
    public $styles;
    public $position = null;
    
    public function __construct($builder, $part, $context, $node, $section, $paragraph = null, $inherits = [], $variants = [], $done = []) {
        $this->builder = $builder;
        $this->part = $part;
        $this->context = $context;
        $this->node = $node;
        $this->section = $section;
        $this->paragraph = $paragraph;
        $this->inherits = $inherits;
        $this->variants = $variants;
        $this->done = $done;
        list($this->class, $this->rend, $this->type) = $this->getTokens();
    }
    
    public function attach($set = []) {
        $fragment = clone($this);
        foreach ($set as $property => $value) {
            $fragment->$property = $value;
        }
        list($fragment->class, $fragment->rend, $fragment->type) = $fragment->getTokens();
        if (!isset($fragment->position)) {
            $fragment->addVariants($this);
        }
        $fragment->getStyles();
        return $fragment;
    }
    
    protected function addVariants($parent) {
        $_variants = [$parent->class];
        if ($parent->rend) {
            $_variants[] = $parent->class.'.'.$parent->rend;
        }
        if ($parent->type) {
            $_variants[] = $parent->class.'['.$parent->type.']';
        }
        if ($parent->rend && $parent->type) {
            $_variants[] = $parent->class.'['.$parent->type.']'.'.'.$parent->rend;
        }
        $this->variants[] = $_variants;
    }
    
    
    protected function getTokens() {
        $isTEI = WordBuilder::isTeiElement($this->node);
        $isHTML = WordBuilder::isHtmlElement($this->node);
        $class = $isTEI ? $this->node->getAttribute('class') : ($isHTML ? $this->node->localName : '*');
        $type = null;
        if ($isTEI && $this->node->hasAttribute('data-type')) {
            $type = $this->node->getAttribute('data-type');
        }
        $rend = null;
        if ($isTEI || $isHTML) {
            if ($this->node->hasAttribute('data-rendition')) {
                $rend = $this->node->getAttribute('data-rendition');
            }
            if ($this->node->hasAttribute('data-rend')) {
                $rend = $this->node->getAttribute('data-rend');
            }
        }
        return [$class, $rend, $type];
    }
    
    protected function getStyles() {
        $this->styles = [
            'font'      => $this->builder->getFontStyle($this),
            'paragraph' => $this->builder->getParagraphStyle($this)
        ];
    }
    
    public function getFontStyle() {
        return $this->styles[WordBuilder::$FONT] ?? 'root';
    }
    
    public function getParagraphStyle() {
        return $this->styles[WordBuilder::$PARAGRAPH] ?? 'root';
    }
    
}

?>