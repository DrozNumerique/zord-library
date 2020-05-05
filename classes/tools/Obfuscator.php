<?php

class Obfuscator {
    
    private static $CLEAR_IDS = null;
    private static $ALPHABET  = 'abcdefghijklmnopqrstuvwxyz';
    
    private $ids = null;
    private $prefix = null;
    private $elementMap = null;
    private $attributeMap = null;
    private $filename = null;
    
    public function __construct() {
        $sources = [];
        $medias = Zord::value('TEI', 'medias');
        foreach (COMPONENT_FOLDERS as $tier) {
            $sources[] = $tier.'config'.DS.'TEI.json';
            foreach($medias as $media) {
                $sources[] = $tier.'web'.DS.'css'.DS.'book'.DS.$media.'.css';
            }
        }
        $folder = Zord::liveFolder('config'.DS.'obf');
        $mappings = glob($folder.'*.json');
        foreach($mappings as $mapping) {
            $targets = [$mapping];
            foreach($medias as $media) {
                $css = BUILD_FOLDER.pathinfo($mapping, PATHINFO_FILENAME).'_'.$media.'.css';
                if (file_exists($css)) {
                    $targets[] = $css;
                }
            }
            if (Zord::needsUpdate($targets, $sources)) {
                foreach($targets as $build) {
                    unlink($build);
                }
            }
        }
        $mappings = glob($folder.'*.json');
        if (count($mappings) < OBFUSCATION_MODELS_MAX) {
            $this->prefix = self::$ALPHABET[rand(0, 25)];
            $elements = Zord::value('TEI', 'elements');
            shuffle($elements);
            $this->elementMap = array();
            $index = 0;
            foreach ($elements as $element) {
                $this->elementMap[$element] = $this->num2alpha($index++);
            }
            $attributes = Zord::value('TEI', 'attributes');
            shuffle($attributes);
            $this->attributeMap = array();
            $index = 0;
            foreach ($attributes as $attribute) {
                if (strpos($attribute, ':') === false) {
                    $this->attributeMap[$attribute] = $this->num2alpha($index++);
                }
            }
            $this->ids = self::buildIDS(
                $this->prefix,
                $this->elementMap,
                $this->attributeMap
                );
            $content = [];
            $content['ids'] = $this->ids;
            $content['prefix'] = $this->prefix;
            $content['elements'] = $this->elementMap;
            $content['attributes'] = $this->attributeMap;
            $this->filename = md5(json_encode($this->elementMap).json_encode($this->attributeMap));
            file_put_contents($folder.$this->filename.'.json', Zord::json_encode($content));
        } else {
            $this->filename = pathinfo($mappings[rand(0, count($mappings) - 1)], PATHINFO_FILENAME);
            $obfuscator = Zord::arrayFromJSONFile($folder.$this->filename.'.json');
            $this->ids = $obfuscator['ids'];
            $this->prefix = $obfuscator['prefix'];
            $this->elementMap = $obfuscator['elements'];
            $this->attributeMap = $obfuscator['attributes'];
        }
    }
    
    public function getIDS() {
        return $this->ids;
    }
    
    public function getXML($xml) {
        $xml = preg_replace_callback(
            '#class="(\w+)"#si',
            function($matches) {
                if (isset($this->elementMap[$matches[1]])) {
                    return 'class="'.$this->elementMap[$matches[1]].'"';
                } else {
                    return $matches[0];
                }
            },
            $xml
            );
        $xml = preg_replace_callback(
            '#data-(\w+)#si',
            function($matches) {
                if (isset($this->attributeMap[$matches[1]])) {
                    return 'data-'.$this->attributeMap[$matches[1]];
                } else {
                    return $matches[0];
                }
            },
            $xml
            );
        return $xml;
    }
    
    public function getCSS($media = 'screen') {
        $file = BUILD_FOLDER.$this->filename.'_'.$media.'.css';
        if (!file_exists($file)) {
            $CSS = file_get_contents(Zord::getComponentPath('web'.DS.'css'.DS.'book'.DS.$media.'.css'));
            $CSS = preg_replace_callback(
                '#div\.(\w+)#si',
                function($matches) {
                    if (isset($this->elementMap[$matches[1]])) {
                        return 'div.'.$this->elementMap[$matches[1]];
                    } else {
                        return $matches[0];
                    }
                },
                $CSS
                );
            $CSS = preg_replace_callback(
                '#data-(\w+)#si',
                function($matches) {
                    if (isset($this->attributeMap[$matches[1]])) {
                        return 'data-'.$this->attributeMap[$matches[1]];
                    } else {
                        return $matches[0];
                    }
                },
                $CSS
                );
            file_put_contents($file, $CSS);
        }
        return pathinfo($file, PATHINFO_BASENAME);
    }
    
    public static function buildIDS($prefix, $elementMap, $attributeMap) {
        $els = array('nspace' => $prefix);
        foreach (Zord::value('TEI', 'obfuscated') as $tag => $attributes) {
            $els[$tag] = array('elm' => $elementMap[$tag]);
            foreach ($attributes as $attribute) {
                $els[$tag][$attribute] = $attributeMap[$attribute];
            }
        }
        return bin2hex(substr(json_encode($els), strlen('{"nspace":"'), -strlen('"}}')));
    }
    
    public static function clearIDS() {
        if (!self::$CLEAR_IDS) {
            $elements = Zord::value('TEI', 'elements');
            $attributes = Zord::value('TEI', 'attributes');
            self::$CLEAR_IDS = self::buildIDS(
                'tei',
                array_combine($elements, $elements),
                array_combine($attributes, $attributes)
                );
        }
        return self::$CLEAR_IDS;
    }
    
    private function num2alpha($index) {
        $n = $index;
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1) {
            $r = chr($n%26 + 0x41) . $r;
        }
        if ($r == 'ID') {
            $index++;
            $r = $this->num2alpha($index);
        }
        return strtolower($r);
    }
    
}

?>