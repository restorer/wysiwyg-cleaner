<?php

namespace WysiwygCleaner;

class Defaults
{
    const FLATTEN_TAGS = ['b', 'em', 'i', 'small', 'span', 'strong'];
    const KEEP_ATTRIBUTES = ['id', 'class'];
    const KEEP_WHITESPACE_PROPS = ['/background*/'];
    const PREFERRABLE_TAGS = ['strong', 'em', 'small', 'span'];

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

        a { display: inline; color: var(--cleaner-link); }
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

        script { display: none; }
    ';
}
