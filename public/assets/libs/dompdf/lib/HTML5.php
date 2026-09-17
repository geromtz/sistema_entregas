<?php
namespace Masterminds;

class HTML5 {
    public function loadHTML($html) {
        $dom = new \DOMDocument();
        // Usamos únicamente las banderas nativas de PHP para evitar errores de constantes indefinidas
        $options = LIBXML_HTML_NOIMPLIED;
        if (defined('LIBXML_HTML_NODEFECT')) {
            $options |= LIBXML_HTML_NODEFECT;
        }
        @$dom->loadHTML($html, $options);
        return $dom;
    }
    
    public function loadHTMLFile($file) {
        return $this->loadHTML(file_get_contents($file));
    }
    
    public function saveHTML($dom) {
        return $dom->saveHTML();
    }
}