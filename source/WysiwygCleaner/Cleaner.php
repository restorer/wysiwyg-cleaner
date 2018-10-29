<?php

namespace WysiwygCleaner;

use WysiwygCleaner\Css\CssParser;
use WysiwygCleaner\Css\CssRenderer;
use WysiwygCleaner\Html\HtmlParser;
use WysiwygCleaner\Html\HtmlRenderer;
use WysiwygCleaner\Rework\ReworkCleaner;
use WysiwygCleaner\Rework\ReworkFlattener;
use WysiwygCleaner\Rework\ReworkReconstructor;
use WysiwygCleaner\Style\StyleBuilder;

class Cleaner
{
    /** @var HtmlParser */
    private $htmlParser;

    /** @var StyleBuilder */
    private $styleBuilder;

    /** @var ReworkFlattener */
    private $reworkFlattener;

    /** @var ReworkCleaner */
    private $reworkCleaner;

    /** @var ReworkReconstructor */
    private $reworkReconstructor;

    /** @var HtmlRenderer */
    private $htmlRenderer;

    /**
     * @param array $removeClassesRegexps
     * @param array $removeStylesRegexps
     * @param array $removeIdsRegexps
     */
    public function __construct(
        array $removeClassesRegexps = [],
        array $removeStylesRegexps = [],
        array $removeIdsRegexps = []
    ) {
        $cssParser = new CssParser();

        try {
            $styleSheet = $cssParser->parseStyleSheet(CleanerDefaults::USER_AGENT_STYLESHEET);
        } catch (CleanerException $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $this->htmlParser = new HtmlParser();
        $this->styleBuilder = new StyleBuilder($cssParser, $styleSheet);

        $this->reworkFlattener = new ReworkFlattener(
            CleanerDefaults::FLATTEN_INLINE_TAGS,
            \array_merge(CleanerDefaults::REMOVE_IDS, $removeIdsRegexps),
            \array_merge(CleanerDefaults::REMOVE_CLASSES, $removeClassesRegexps),
            CleanerDefaults::KEEP_ATTRIBUTES
        );

        $this->reworkCleaner = new ReworkCleaner(
            CleanerDefaults::KEEP_WHITESPACE_PROPS,
            CleanerDefaults::FLATTEN_BLOCK_TAGS,
            $removeStylesRegexps,
            CleanerDefaults::REMOVE_BLOCK_STYLES
        );

        $this->reworkReconstructor = new ReworkReconstructor(
            CleanerDefaults::PREFERABLE_TAGS,
            CleanerDefaults::KEEP_WHITESPACE_PROPS,
            new CssRenderer(),
            $styleSheet
        );

        $this->htmlRenderer = new HtmlRenderer();
    }

    /**
     * @param string $html
     *
     * @return string
     * @throws CleanerException
     */
    public function clean(string $html) : string
    {
        $document = $this->htmlParser->parse($html);

        $this->styleBuilder->build($document);
        $this->reworkFlattener->flatten($document);
        $this->reworkCleaner->cleanup($document);
        $this->reworkReconstructor->reconstruct($document);

        return $this->htmlRenderer->render($document);
    }
}
