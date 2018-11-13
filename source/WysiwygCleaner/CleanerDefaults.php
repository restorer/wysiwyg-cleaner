<?php

namespace WysiwygCleaner;

class CleanerDefaults
{
    const STYLE_RULES = [
        [false, '-webkit-text-stroke-width'],
        [false, 'font-style', 'normal'],
        [false, 'font-variant-caps'],
        [false, 'font-variant-ligatures'],
        [false, 'font-variant', 'normal'],
        [false, 'font-weight', '/^(?:normal|400)$/'],
        [false, 'letter-spacing'],
        [false, 'line-height', 'normal'],
        [false, 'orphans'],
        [false, 'text-align', '/^(?:start|left)$/'],
        [false, 'text-decoration-color'],
        [false, 'text-decoration-style'],
        [false, 'text-indent'],
        [false, 'text-transform', 'none'],
        [false, 'white-space', 'normal'],
        [false, 'widows'],
        [false, 'word-spacing'],
        [false, 'color', 'tag' => 'img'],
        [false, 'color', 'inline' => false],
        [false, '/^font/', 'tag' => 'img'],
        [false, '/^font/', 'inline' => false],
        [false, 'cursor'],
        [false, 'top'],
        [false, 'left'],
        [false, '/^margin/', '0px'],
        [false, '/^padding/', '0px'],
        [false, '/^border/', '0px'],
        [false, '/^border/', 'none'],
        [false, 'vertical-align', 'baseline'],
        [false, 'font-stretch', 'normal'],
        [true],
    ];

    const FLATTEN_TAGS_RULES = [
        [false, 'b', 'inline' => true],
        [false, 'em', 'inline' => true],
        [false, 'i', 'inline' => true],
        [false, 'small', 'inline' => true],
        [false, 'span', 'inline' => true],
        [false, 'strong', 'inline' => true],
        [true],
    ];

    const FLATTEN_CLASSES_RULES = [
        [false, '/^ng\-/'],
        [false, '/^mce\-/'],
        [true],
    ];

    const FLATTEN_ATTRIBUTES_RULES = [
        [true, 'href'],
        [true, '_target'],
        [true, 'src'],
        [true, 'alt'],
        [true, 'width'],
        [true, 'height'],
        [true, 'frameborder'],
        [true, 'allowfullscreen'],
        [false, 'id', '/^_?mce/'],
        [true, 'id'],
        [false],
    ];

    const CLEAN_WHITESPACE_STYLE_RULES = [
        [true, 'display'],
        [true, '/^background/'],
        [false],
    ];

    const CLEAN_TAGS_RULES = [
        [false, 'div', 'blocky' => true],
        [false, 'p', 'blocky' => true],
        [true],
    ];

    const RECONSTRUCT_TAGS = ['strong', 'em', 'small', 'span'];

    const USER_AGENT_STYLESHEET = '
        /* Block (block, list-item, table, etc.) */

        address { display: block; font-style: italic; }
        article { display: block; }
        aside { display: block; }
        blockquote { display: block; }
        caption { display: table-caption; }
        col { display: table-column; }
        colgroup { display: table-column-group; }
        dd { display: block; }
        div { display: block; }
        dl { display: block; }
        dt { display: block; }
        fieldset { display: block; }
        figcaption { display: block; }
        figure { display: block; }
        footer { display: block; }
        form { display: block; }
        h1 { display: block; font-size: 2em; font-weight: bold; }
        h2 { display: block; font-size: 1.5em; font-weight: bold; }
        h3 { display: block; font-size: 1.17em; font-weight: bold; }
        h4 { display: block; font-weight: bold; }
        h5 { display: block; font-size: 0.83em; font-weight: bold; }
        h6 { display: block; font-weight: 0.67em; font-weight: bold; }
        header { display: block; }
        hr { display: block; }
        li { display: list-item; }
        main { display: block; }
        nav { display: block; }
        noscript { display: block; }
        ol { display: block; }
        p { display: block; }
        pre { display: block; }
        section { display: block; }
        table { display: table; }
        tbody { display: table-row-group; }
        td { display: table-cell; }
        tfoot { display: table-footer-group; }
        th { display: table-cell; font-weight: bold; }
        thead { display: table-header-group; }
        tr { display: table-row; }
        ul { display: block; }

        /* Inline-Block */

        button { display: inline-block; }
        input { display: inline-block; }
        select { display: inline-block; }
        textarea { display: inline-block; }

        /* Inline */

        a { display: inline; color: var(--cleaner-a-color); }
        abbr { display: inline; }
        acronym { display: inline; }
        b { display: inline; font-weight: bold; }
        bdo { display: inline; }
        big { display: inline; font-size: larger; }
        br { display: inline; }
        canvas { display: inline; }
        cite { display: inline; font-style: italic; }
        code { display: inline; font-family: monospace; }
        dfn { display: inline; font-style: italic; }
        em { display: inline; font-style: italic; }
        i { display: inline; font-style: italic; }
        iframe { display: inline; }
        img { display: inline; }
        kbd { display: inline; font-family: monospace; }
        label { display: inline; }
        map { display: inline; }
        object { display: inline; }
        output { display: inline; }
        q { display: inline; }
        samp { display: inline; font-family: monospace; }
        small { display: inline; font-size: smaller; }
        span { display: inline; }
        strong { display: inline; font-weight: bold; }
        sub { display: inline; font-size: smaller; }
        sup { display: inline; font-size: smaller; }
        time { display: inline; }
        tt { display: inline; font-family: monospace; }
        var { display: inline; font-style: italic; }
        video { display: inline; }

        /* None */

        script { display: var(--cleaner-script-display); }
    ';
}
