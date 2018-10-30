<?php

use PHPUnit\Framework\TestCase;
use WysiwygCleaner\Cleaner;

class IntegrationTest extends TestCase
{
    /** @var Cleaner */
    private $cleaner;

    /**
     */
    protected function setUp()
    {
        $this->cleaner = new Cleaner();
    }

    /**
     * @throws \WysiwygCleaner\CleanerException
     */
    public function testBasic()
    {
        static::assertEquals(
            '<p style="color: #f00; font-weight: bold;">AB</p>',
            $this->cleaner->clean(
                '<p><span style="color: #f00;"><strong>A</strong></span><strong><span style="color: #f00;">B</span></strong></p>'
            )
        );
    }

    /**
     * @throws \WysiwygCleaner\CleanerException
     */
    public function testReconstruct()
    {
        static::assertEquals(
            '<p><strong>A</strong> <span style="color: #f00;">B</span></p>',
            $this->cleaner->clean(
                '<p data-mce="a"><span style="font-weight: bold;">A</span> <span style="color: #f00;">B</span></p>'
            )
        );
    }

    /**
     * @throws \WysiwygCleaner\CleanerException
     */
    public function testClean()
    {
        // Ideally should be:
        // '<p style="color: #00f;">A<br /><img src="about:blank" alt="" width="42" height="42" /><br />B</p>'

        static::assertEquals(
            '<p><span style="color: #00f;">A</span><br /><img src="about:blank" alt="" width="42" height="42" /><br /><span style="color: #00f;">B</span></p>',
            $this->cleaner->clean(
                '<div class="ng-binding" style="letter-spacing: normal; color: #f00; font-variant-ligatures: normal; font-variant-caps: normal; orphans: 2; text-align: start; text-indent: 0; text-transform: none; white-space: normal; widows: 2; word-spacing: 0; -webkit-text-stroke-width: 0; text-decoration-style: initial; text-decoration-color: initial;">
                <p style="letter-spacing: normal; color: #00f; font-variant-ligatures: normal; font-variant-caps: normal; orphans: 2; text-align: start; text-indent: 0; text-transform: none; white-space: normal; widows: 2; word-spacing: 0; -webkit-text-stroke-width: 0; text-decoration-style: initial; text-decoration-color: initial;">
                A<br />
                <img class="" src="about:blank" alt="" width="42" height="42" style="letter-spacing: normal; color: #0f0; font-variant-ligatures: normal; font-variant-caps: normal; orphans: 2; text-align: start; text-indent: 0; text-transform: none; white-space: normal; widows: 2; word-spacing: 0; -webkit-text-stroke-width: 0; text-decoration-style: initial; text-decoration-color: initial; font: 400 16px arial,sans-serif;" /><br />
                B<br />
                </p>
                </div>'
            )
        );
    }

    /**
     * @throws \WysiwygCleaner\CleanerException
     */
    public function testQuotes()
    {
        static::assertEquals(
            '<p id="&quot;&apos;">"A"</p>',
            $this->cleaner->clean('<p id="&quot;\'">"A"</p>')
        );
    }

    /**
     * @throws \WysiwygCleaner\CleanerException
     */
    public function testReconstructWhitespaces()
    {
        static::assertEquals(
            '<p style="font-style: italic;">A <strong>B</strong> C</p>',
            $this->cleaner->clean('<p><em>A <strong>B</strong> C</em></p>')
        );
    }
}
