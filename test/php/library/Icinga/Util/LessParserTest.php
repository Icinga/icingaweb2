<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Test\BaseTestCase;
use Icinga\Util\LessParser;

class LessParserTest extends BaseTestCase
{
    protected $lessc;

    public function setUp(): void
    {
        parent::setUp();

        $this->lessc = new LessParser();
    }

    protected function compileLess($less)
    {
        return $this->lessc->compile($less);
    }

    public function testSimpleVariables()
    {
        $this->assertEquals(
            <<<CSS
.black {
  color: var(--black, #000000);
}
.notBlack {
  color: var(--notBlack, #ffffff);
}
.alsoNotBlack {
  color: var(--also-not-black, #008000);
}

CSS
            ,
            $this->compileLess(<<<LESS
@black: black;
@notBlack: white;
@also-not-black: green;

.black {
  color: @black;
}

.notBlack {
  color: @notBlack;
}

.alsoNotBlack {
  color: @also-not-black;
}
LESS
            )
        );
    }

    public function testVariablesUsedInFunctions()
    {
        $this->assertEquals(
            <<<CSS
.light-black {
  color: #808080;
}
.dark-white {
  color: var(--dark-white, #808080);
}

CSS
            ,
            $this->compileLess(<<<LESS
@black: black;
@dark-white: darken(white, 50%);

.light-black {
  color: lighten(@black, 50%);
}

.dark-white {
  color: @dark-white;
}

LESS
            )
        );
    }

    public function testVariableInterpolation()
    {
        $this->assertEquals(
            <<<CSS
.a-rule {
  width: calc(1337px - 50%);
  color: var(--property-value, #ffa500);
}
.another-rule {
  font-size: 1em;
}

CSS
            ,
            $this->compileLess(<<<LESS
@pixels: 1337px;
@property: color;
@property-value: orange;
@selector: another-rule;

.a-rule {
  width: ~"calc(@{pixels} - 50%)";
  @{property}: @property-value;
}

.@{selector} {
  font-size: 1em;
}
LESS
            )
        );
    }

    public function testVariableVariables()
    {
        $this->assertEquals(
            <<<CSS
.section .element {
  color: var(--primary, #008000);
}
.lazy-eval {
  color: var(--a, #000000);
}

CSS
            ,
            $this->compileLess(<<<LESS
@primary: green;

.section {
  @color: primary;

  .element {
    color: @@color;
  }
}

.lazy-eval {
  color: @@var;
}

@var: a;
@a: black;
LESS
            )
        );
    }

    public function testVariablesInsideMediaQueries()
    {
        $this->assertEquals(
            <<<CSS
@media screen {
  .link {
    color: var(--link-color, #000000);
  }
}

CSS
            ,
            $this->compileLess(<<<LESS
@link-color: black;

@media screen {
  .link {
    color: @link-color;
  }
}
LESS
            )
        );
    }

    public function testVariablesInsideMixins()
    {
        $this->assertEquals(
            <<<CSS
.mixin2 {
  color: var(--link-color, #000000);
}
.mixin-user {
  color: var(--link-color, #000000);
}
.mixin-user .nested {
  color: var(--link-color, #000000) !important;
}
.mixin2-user {
  color: var(--link-color, #000000);
}

CSS
            ,
            $this->compileLess(<<<LESS
@link-color: black;

.mixin() {
  color: @link-color;
  
  .nested {
    color: @link-color !important;
  }
}

.mixin2 {
  color: @link-color;
}

.mixin-user {
  .mixin();
}

.mixin2-user {
  .mixin2();
}
LESS
            )
        );
    }

    public function testVariablesInsideNamespacedMixins()
    {
        $this->assertEquals(
            <<<CSS
.mixin-user {
  color: var(--link-color, #000000);
}

CSS
            ,
            $this->compileLess(<<<LESS
@link-color: black;

#namespace {
  .mixin() {
    color: @link-color;
  }
}

.mixin-user {
  #namespace.mixin();
}
LESS
            )
        );
    }

    public function testVariablesInsideMixinsAndGuardedNamespaces()
    {
        $this->assertEquals(
            <<<CSS
.mixin-user {
  color: var(--link-color, #000000);
}

CSS
            ,
            $this->compileLess(<<<LESS
@mode: huge;
@link-color: black;

#namespace when (@mode = huge) {
  .mixin() {
    color: @link-color;
  }
}

.mixin-user {
  #namespace.mixin();
}
LESS
            )
        );
    }

    public function testVariablesInsideParametricMixins()
    {
        $this->assertEquals(
            <<<CSS
.button {
  background-color: var(--button-bg-color, #000000);
}
.light-button {
  background-color: var(--base-bg-color, #ffffff);
}
.very-special-button {
  background-color: var(--special-bg-color, #ff0000);
  color: var(--special-fg-color, #4169e1);
}

CSS
            ,
            $this->compileLess(<<<LESS
@base-bg-color: white;
@base-fg-color: black;
@button-bg-color: black;
@special-bg-color: red;
@special-fg-color: royalblue;

.button(@bg-color) {
  background-color: @bg-color;
}

.button-with-defaults(@bg-color: @base-bg-color) {
  background-color: @bg-color;
}

.special-button(@bg-color: @base-bg-color, @fg-color: @base-fg-color) {
  background-color: @bg-color;
  color: @fg-color;
}

.button {
  .button(@button-bg-color);
}

.light-button {
  .button-with-defaults();
}

.very-special-button {
  .special-button(@fg-color: @special-fg-color, @bg-color: @special-bg-color);
}
LESS
            )
        );
    }

    public function testArgumentsParameterOfMixins()
    {
        $this->assertEquals(
            <<<CSS
.big-block {
  -webkit-box-shadow: 2px 5px 1px var(--shadow-color, #000000);
  -moz-box-shadow: 2px 5px 1px var(--shadow-color, #000000);
  box-shadow: 2px 5px 1px var(--shadow-color, #000000);
}

CSS
            ,
            $this->compileLess(<<<LESS
@shadow-color: black;

.box-shadow(@x: 0, @y: 0, @blur: 1px, @color: #fff) {
  -webkit-box-shadow: @arguments;
     -moz-box-shadow: @arguments;
          box-shadow: @arguments;
}
.big-block {
  .box-shadow(2px, 5px, @color: @shadow-color);
}
LESS
            )
        );
    }

    public function testRestParameterOfMixins()
    {
        $this->assertEquals(
            <<<CSS
.my-button {
  color: var(--button-fg-color, #000000);
  background-color: white;
  box-shadow: 0 0 1px var(--shadow-color, #000000);
}

CSS
            ,
            $this->compileLess(<<<LESS
@button-fg-color: black;
@shadow-color: black;

.button(@fg-color, @box-shadow...) {
  color: @fg-color;
  background-color: white;
  box-shadow: @box-shadow;
}

.my-button {
  .button(@button-fg-color, 0, 0, 1px, @shadow-color);
}
LESS
            )
        );
    }

    public function testVariablesInsideDetachedRulesets()
    {
        $this->assertEquals(
            <<<CSS
.ruleset-user {
  background-color: var(--base-bg-color, #000000);
}

CSS
            ,
            $this->compileLess(<<<LESS
@base-bg-color: black;

@detached-ruleset: {
  background-color: @base-bg-color;
};

.ruleset-user {
  @detached-ruleset();
}
LESS
            )
        );
    }
}
