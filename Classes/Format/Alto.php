<?php

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Kitodo\Dlf\Format;

/**
 * Fulltext ALTO format class for the 'dlf' extension
 *
 * ** This currently supports only ALTO 2.x **
 *
 * @author Sebastian Meyer <sebastian.meyer@slub-dresden.de>
 * @package TYPO3
 * @subpackage dlf
 * @access public
 */
class Alto implements \Kitodo\Dlf\Common\FulltextInterface
{
    /**
     * This extracts the fulltext data from ALTO XML
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml: The XML to extract the raw text from
     *
     * @return string The raw unformatted fulltext
     */
    public function getRawText(\SimpleXMLElement $xml)
    {
        $rawText = '';
        $xml->registerXPathNamespace('alto', 'http://www.loc.gov/standards/alto/ns-v2#');
        // Get all (presumed) words of the text.
        $words = $xml->xpath('./alto:Layout/alto:Page/alto:PrintSpace//alto:TextBlock/alto:TextLine/alto:String/@CONTENT');
        if (!empty($words)) {
            $rawText = implode(' ', $words);
        }
        return $rawText;
    }

    /**
     * This extracts the fulltext data from ALTO XML and returns it in MiniOCR format
     *
     * @access public
     *
     * @param \SimpleXMLElement $xml: The XML to extract the raw text from
     *
     * @return string The unformatted fulltext in MiniOCR format
     */
    public function getTextAsMiniOcr(\SimpleXMLElement $xml)
    {
        $xml->registerXPathNamespace('alto', 'http://www.loc.gov/standards/alto/ns-v2#');

        // get all text blocks
        $blocks = $xml->xpath('./alto:Layout/alto:Page/alto:PrintSpace//alto:TextBlock');

        if (empty($blocks)) {
            return '';
        }

        $miniOcr = new \SimpleXMLElement("<ocr></ocr>");

        foreach ($blocks as $block) {
            $newBlock = $miniOcr->addChild('b');
            foreach ($block->children() as $key => $value) {
                if ($key === "TextLine") {
                    $newLine = $newBlock->addChild('l');
                    foreach ($value->children() as $wordKey => $word) {
                        if ($wordKey == "String") {
                            $attributes = $word->attributes();
                            $newWord = $newLine->addChild('w', $this->getWord($attributes));
                            $newWord->addAttribute('x', $this->getCoordinates($attributes));
                        }
                    }
                }
            }
        }

        $miniOcrXml = $miniOcr->asXml();
        if (\is_string($miniOcrXml)) {
            return $miniOcrXml;
        }
        return '';
    }

    /**
     * This extracts and parses the word from attribute
     *
     * @access private
     *
     * @param \SimpleXMLElement $attributes: The XML to extract the word
     *
     * @return string The parsed word extracted from attribute
     */
    private function getWord($attributes)
    {
        return htmlspecialchars((string) $attributes['CONTENT']) . ' ';
    }

    /**
     * This extracts and parses the word coordinates from attributes
     *
     * @access private
     *
     * @param \SimpleXMLElement $attributes: The XML to extract the word coordinates
     *
     * @return string The parsed word coordinates extracted from attribute
     */
    private function getCoordinates($attributes)
    {
        return (string) $attributes['HPOS'] . ' ' . (string) $attributes['VPOS'] . ' ' . (string) $attributes['WIDTH'] . ' ' . (string) $attributes['HEIGHT'];
    }
}
