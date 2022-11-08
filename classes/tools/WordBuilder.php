<?php

use \PhpOffice\PhpWord\PhpWord;
use \PhpOffice\PhpWord\Element\Header;
use \PhpOffice\PhpWord\Shared\Converter;
use \PhpOffice\PhpWord\Style\Language;
use \PhpOffice\PhpWord\IOFactory;

class WordBuilder {
    
    protected $book;
    protected $size;
    protected $format;
    protected $metadata;
    protected $parts;
    protected $document;
    
    protected static $WIDTH = 'width';
    protected static $HEIGHT = 'height';
    protected static $MARGIN = 'margin';
    
    public function __construct($book, $size, $format) {
        $this->book = $book;
        $this->size = $size;
        $this->format = $format;
    }
    
    public function process() {
        $this->document = new PhpWord();
        $this->loadData();
        $this->setSettings();
        $this->addStyles();
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
                $this->handleNode($section, null, $part, $text, $footnotes);
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
    
    protected function setSettings() {
        $this->document->getSettings()->setThemeFontLang(new Language($this->metadata['language'] ?? 'fr'));
        $this->document->getSettings()->setEvenAndOddHeaders(true);
    }
    
    protected function addStyles() {
        foreach (Zord::value('word', 'styles') as $type => $styles) {
            foreach ($styles as $name => $style) {
                $style = $this->convert($style);
                switch ($type) {
                    case 'font': {
                        $this->document->addFontStyle($name, $style);
                        break;
                    }
                    case 'paragraph': {
                        $this->document->addParagraphStyle($name, $style);
                        break;
                    }
                }
            }
        }
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
        $excludes = Zord::value('word', 'excludes') ?? [];
        return ($part['epub'] ?? false) && 
               !in_array($part['name'], $excludes) &&
               !in_array($this->book.'/'.$part['name'], $excludes);
    }
    
    protected function isRotated($part) {
        return false;
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
        $header = $section->addHeader();
        $header->addText($part['title'], 'header', 'center');
        $evenHeader = $section->addHeader(Header::EVEN);
        $evenHeader->addText($this->metadata['title'], 'header','center');
        $firstHeader = $section->addHeader(Header::FIRST);
        $firstHeader->addText('');
    }
    
    protected function dressFooter(&$section, $part) {
        $footer = $section->addFooter();
        $footer->addPreserveText('{PAGE}', 'footer', 'center');
        $evenfooter = $section->addFooter(Header::EVEN);
        $evenfooter->addPreserveText('{PAGE}', 'footer', 'center');
        $firstFooter = $section->addFooter(Header::FIRST);
        $firstFooter->addPreserveText('{PAGE}', 'footer', 'center');
    }
    
    protected function handleNode(&$section, $textrun, $part, $node, $footnotes) {
        $fontStyle = $this->getFontStyle($part, $node);
        $paragraphStyle = $this->getParagraphStyle($part, $node);
        $textrun = $textrun ?? ($this->isTextRun($part, $node) ? $section->addTextRun($paragraphStyle) : null);
        $container = $textrun ?? $section;
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $content = $this->textContent($child, !isset($textrun));
                if (!empty($content)) {
                    $container->addText($content, $fontStyle, $paragraphStyle);
                }
            } else if ($this->isStyled($part, $child, 'note')) {
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
                    $this->handleNode($section, $note, $part, $footnotes[$id], $footnotes);
                }
            } else if ($child->nodeType === XML_ELEMENT_NODE) {
                $this->handleNode($section, $textrun, $part, $child, $footnotes);
            }
        }
    }
    
    protected function textContent($node, $trim = false) {
        $content = preg_replace('#\s+#s', ' ', htmlspecialchars($node->textContent));
        return $trim ? trim($content) : $content;
    }
    
    protected function getFontStyle($part, $node) {
        return $this->getStyle($part, $node, 'font');
    }
    
    protected function getParagraphStyle($part, $node) {
        return $this->getStyle($part, $node, 'paragraph');
    }
    
    protected function isStyled($part, $node, $class = null) {
        return $node->nodeType === XML_ELEMENT_NODE &&
               $node->localName === 'div' &&
               $node->hasAttribute('class') &&
               (!isset($class)
                   || (is_string($class) && $node->getAttribute('class') === $class)
                   || (is_array($class) && in_array($node->getAttribute('class'), $class)));
    }
    
    protected function getStyle($part, $node, $type) {
        if ($this->isStyled($part, $node)) {
            $class = $node->getAttribute('class');
            $rend = null;
            if ($node->hasAttribute('data-rendition')) {
                $rend = $node->getAttribute('data-rendition');
            }
            if ($node->hasAttribute('data-rend')) {
                $rend = $node->getAttribute('data-rend');
            }
            if (isset($rend) && Zord::value('word', ['styles',$type,$class.'.'.$rend])) {
                return $class.'.'.$rend;
            }
            if (Zord::value('word', ['styles',$type,$class])) {
                return $class;
            }
        }
        return "default";
    }
        
    protected function isTextRun($part, $node) {
        return $this->isStyled($part, $node, Zord::value('word', 'textrun'));
    }
    
    protected function filename() {
        return '/tmp/'.$this->book.'.'.Zord::value('word', ['extensions',$this->format]);
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
        if ($this->isRotated($part)) {
            if ($property === self::$HEIGHT) {
                $property = self::$WIDTH;
            } else if ($property === self::$WIDTH) {
                $property = self::$HEIGHT;
            }
        }
        $tokens = explode('.', $property);
        $key = ['sizes', $this->size];
        foreach ($tokens as $token) {
            $key[] = $token;
        }
        return $this->convert(Zord::value('word', $key) ?? 0);
    }
    
}

?>