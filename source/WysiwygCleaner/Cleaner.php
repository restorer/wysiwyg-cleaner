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
     * @param array[] $userStyleRules
     * @param array[] $userClassesRules
     * @param array[] $userAttributesRules
     * @param string $userStyleSheet
     */
    public function __construct(
        array $userStyleRules = [],
        array $userClassesRules = [],
        array $userAttributesRules = [],
        string $userStyleSheet = ''
    ) {
        $cssParser = new CssParser();

        try {
            $styleSheet = $cssParser->parseStyleSheet(CleanerDefaults::USER_AGENT_STYLESHEET . "\n" . $userStyleSheet);
        } catch (CleanerException $e) {
            throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        $this->htmlParser = new HtmlParser();

        $this->styleBuilder = new StyleBuilder(
            $cssParser,
            $styleSheet,
            \array_merge($userStyleRules, CleanerDefaults::STYLE_RULES)
        );

        $this->reworkFlattener = new ReworkFlattener(
            CleanerDefaults::FLATTEN_TAGS_RULES,
            \array_merge($userClassesRules, CleanerDefaults::FLATTEN_CLASSES_RULES),
            \array_merge($userAttributesRules, CleanerDefaults::FLATTEN_ATTRIBUTES_RULES)
        );

        $this->reworkCleaner = new ReworkCleaner(
            CleanerDefaults::CLEAN_WHITESPACE_STYLE_RULES,
            CleanerDefaults::CLEAN_TAGS_RULES
        );

        $this->reworkReconstructor = new ReworkReconstructor(
            CleanerDefaults::RECONSTRUCT_TAGS,
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
