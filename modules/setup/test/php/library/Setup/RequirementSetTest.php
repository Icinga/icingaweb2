<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Module\Setup;

use Icinga\Test\BaseTestCase;
use Icinga\Module\Setup\Requirement;
use Icinga\Module\Setup\RequirementSet;

class TrueRequirement extends Requirement
{
    protected function evaluate()
    {
        return true;
    }
}

class FalseRequirement extends Requirement
{
    protected function evaluate()
    {
        return false;
    }
}

class RequirementSetTest extends BaseTestCase
{
    public function testFlatMandatoryRequirementsOfTypeAnd()
    {
        $emptySet = new RequirementSet();
        $this->assertFalse($emptySet->fulfilled(), 'A empty mandatory set of type and is fulfilled');

        $singleTrueSet = new RequirementSet();
        $singleTrueSet->add(new TrueRequirement());
        $this->assertTrue(
            $singleTrueSet->fulfilled(),
            'A mandatory set of type and with a single TrueRequirement is not fulfilled'
        );

        $singleFalseSet = new RequirementSet();
        $singleFalseSet->add(new FalseRequirement());
        $this->assertFalse(
            $singleFalseSet->fulfilled(),
            'A mandatory set of type and with a single FalseRequirement is fulfilled'
        );

        $mixedSet = new RequirementSet();
        $mixedSet->add(new TrueRequirement());
        $mixedSet->add(new FalseRequirement());
        $this->assertFalse(
            $mixedSet->fulfilled(),
            'A mandatory set of type and with one True- and one FalseRequirement is fulfilled'
        );
    }

    public function testFlatOptionalRequirementsOfTypeAnd()
    {
        $emptySet = new RequirementSet(true);
        $this->assertTrue($emptySet->fulfilled(), 'A empty optional set of type and is not fulfilled');

        $singleTrueSet = new RequirementSet(true);
        $singleTrueSet->add(new TrueRequirement());
        $this->assertTrue(
            $singleTrueSet->fulfilled(),
            'A optional set of type and with a single TrueRequirement is not fulfilled'
        );

        $singleFalseSet = new RequirementSet(true);
        $singleFalseSet->add(new FalseRequirement());
        $this->assertTrue(
            $singleFalseSet->fulfilled(),
            'A optional set of type and with a single FalseRequirement is not fulfilled'
        );

        $mixedSet = new RequirementSet(true);
        $mixedSet->add(new TrueRequirement());
        $mixedSet->add(new FalseRequirement());
        $this->assertTrue(
            $mixedSet->fulfilled(),
            'A optional set of type and with one True- and one FalseRequirement is not fulfilled'
        );
    }

    public function testFlatMixedRequirementsOfTypeAnd()
    {
        $mandatoryOptionalTrueSet = new RequirementSet();
        $mandatoryOptionalTrueSet->add(new TrueRequirement(array('optional' => true)));
        $mandatoryOptionalTrueSet->add(new FalseRequirement());
        $this->assertFalse(
            $mandatoryOptionalTrueSet->fulfilled(),
            'A mandatory set of type and with one optional True- and one mandatory FalseRequirement is fulfilled'
        );

        $mandatoryOptionalFalseSet = new RequirementSet();
        $mandatoryOptionalFalseSet->add(new TrueRequirement());
        $mandatoryOptionalFalseSet->add(new FalseRequirement(array('optional' => true)));
        $this->assertTrue(
            $mandatoryOptionalFalseSet->fulfilled(),
            'A mandatory set of type and with one mandatory True- and one optional FalseRequirement is not fulfilled'
        );

        $optionalOptionalTrueSet = new RequirementSet(true);
        $optionalOptionalTrueSet->add(new TrueRequirement(array('optional' => true)));
        $optionalOptionalTrueSet->add(new FalseRequirement());
        $this->assertTrue(
            $optionalOptionalTrueSet->fulfilled(),
            'A optional set of type and with one optional True- and one mandatory FalseRequirement is not fulfilled'
        );

        $optionalOptionalFalseSet = new RequirementSet(true);
        $optionalOptionalFalseSet->add(new TrueRequirement());
        $optionalOptionalFalseSet->add(new FalseRequirement(array('optional' => true)));
        $this->assertTrue(
            $optionalOptionalFalseSet->fulfilled(),
            'A optional set of type and with one mandatory True- and one optional FalseRequirement is not fulfilled'
        );
    }

    public function testFlatMandatoryRequirementsOfTypeOr()
    {
        $emptySet = new RequirementSet(false, RequirementSet::MODE_OR);
        $this->assertFalse($emptySet->fulfilled(), 'A empty mandatory set of type or is fulfilled');

        $singleTrueSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $singleTrueSet->add(new TrueRequirement());
        $this->assertTrue(
            $singleTrueSet->fulfilled(),
            'A mandatory set of type or with a single TrueRequirement is not fulfilled'
        );

        $singleFalseSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $singleFalseSet->add(new FalseRequirement());
        $this->assertFalse(
            $singleFalseSet->fulfilled(),
            'A mandatory set of type or with a single FalseRequirement is fulfilled'
        );

        $mixedSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mixedSet->add(new TrueRequirement());
        $mixedSet->add(new FalseRequirement());
        $this->assertTrue(
            $mixedSet->fulfilled(),
            'A mandatory set of type or with one True- and one FalseRequirement is not fulfilled'
        );
    }

    public function testFlatOptionalRequirementsOfTypeOr()
    {
        $emptySet = new RequirementSet(true, RequirementSet::MODE_OR);
        $this->assertTrue($emptySet->fulfilled(), 'A empty optional set of type or is not fulfilled');

        $singleTrueSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $singleTrueSet->add(new TrueRequirement());
        $this->assertTrue(
            $singleTrueSet->fulfilled(),
            'A optional set of type or with a single TrueRequirement is not fulfilled'
        );

        $singleFalseSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $singleFalseSet->add(new FalseRequirement());
        $this->assertTrue(
            $singleFalseSet->fulfilled(),
            'A optional set of type or with a single FalseRequirement is not fulfilled'
        );

        $mixedSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $mixedSet->add(new TrueRequirement());
        $mixedSet->add(new FalseRequirement());
        $this->assertTrue(
            $mixedSet->fulfilled(),
            'A optional set of type or with one True- and one FalseRequirement is not fulfilled'
        );
    }

    public function testFlatMixedRequirementsOfTypeOr()
    {
        $mandatoryOptionalTrueSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mandatoryOptionalTrueSet->add(new TrueRequirement(array('optional' => true)));
        $mandatoryOptionalTrueSet->add(new FalseRequirement());
        $this->assertTrue(
            $mandatoryOptionalTrueSet->fulfilled(),
            'A mandatory set of type or with one optional True- and one mandatory FalseRequirement is not fulfilled'
        );

        $mandatoryOptionalFalseSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mandatoryOptionalFalseSet->add(new TrueRequirement());
        $mandatoryOptionalFalseSet->add(new FalseRequirement(array('optional' => true)));
        $this->assertTrue(
            $mandatoryOptionalFalseSet->fulfilled(),
            'A mandatory set of type or with one mandatory True- and one optional FalseRequirement is not fulfilled'
        );

        $optionalOptionalTrueSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $optionalOptionalTrueSet->add(new TrueRequirement(array('optional' => true)));
        $optionalOptionalTrueSet->add(new FalseRequirement());
        $this->assertTrue(
            $optionalOptionalTrueSet->fulfilled(),
            'A optional set of type or with one optional True- and one mandatory FalseRequirement is not fulfilled'
        );

        $optionalOptionalFalseSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $optionalOptionalFalseSet->add(new TrueRequirement());
        $optionalOptionalFalseSet->add(new FalseRequirement(array('optional' => true)));
        $this->assertTrue(
            $optionalOptionalFalseSet->fulfilled(),
            'A optional set of type or with one mandatory True- and one optional FalseRequirement is not fulfilled'
        );
    }

    public function testNestedMandatoryRequirementsOfTypeAnd()
    {
        $trueSet = new RequirementSet();
        $trueSet->add(new TrueRequirement());
        $falseSet = new RequirementSet();
        $falseSet->add(new FalseRequirement());

        $nestedTrueSet = new RequirementSet();
        $nestedTrueSet->merge($trueSet);
        $this->assertTrue(
            $nestedTrueSet->fulfilled(),
            'A nested mandatory set of type and with one mandatory TrueRequirement is not fulfilled'
        );

        $nestedFalseSet = new RequirementSet();
        $nestedFalseSet->merge($falseSet);
        $this->assertFalse(
            $nestedFalseSet->fulfilled(),
            'A nested mandatory set of type and with one mandatory FalseRequirement is fulfilled'
        );

        $nestedMixedSet = new RequirementSet();
        $nestedMixedSet->merge($trueSet);
        $nestedMixedSet->merge($falseSet);
        $this->assertFalse(
            $nestedMixedSet->fulfilled(),
            'Two nested mandatory sets of type and with one mandatory True- and'
            . ' one mandatory FalseRequirement respectively are fulfilled'
        );
    }

    public function testNestedOptionalRequirementsOfTypeAnd()
    {
        $trueSet = new RequirementSet(true);
        $trueSet->add(new TrueRequirement());
        $falseSet = new RequirementSet(true);
        $falseSet->add(new FalseRequirement());

        $nestedTrueSet = new RequirementSet(true);
        $nestedTrueSet->merge($trueSet);
        $this->assertTrue(
            $nestedTrueSet->fulfilled(),
            'A nested optional set of type and with one mandatory TrueRequirement is not fulfilled'
        );

        $nestedFalseSet = new RequirementSet(true);
        $nestedFalseSet->merge($falseSet);
        $this->assertTrue(
            $nestedFalseSet->fulfilled(),
            'A nested optional set of type and with one mandatory FalseRequirement is not fulfilled'
        );

        $nestedMixedSet = new RequirementSet(true);
        $nestedMixedSet->merge($trueSet);
        $nestedMixedSet->merge($falseSet);
        $this->assertTrue(
            $nestedMixedSet->fulfilled(),
            'Two nested optional sets of type and with one mandatory True- and'
            . ' one mandatory FalseRequirement respectively are not fulfilled'
        );
    }

    public function testNestedMixedRequirementsOfTypeAnd()
    {
        $mandatoryMandatoryTrueSet = new RequirementSet();
        $mandatoryMandatoryTrueSet->add(new TrueRequirement());
        $mandatoryOptionalTrueSet = new RequirementSet();
        $mandatoryOptionalTrueSet->add(new TrueRequirement(array('optional' => true)));
        $mandatoryMandatoryFalseSet = new RequirementSet();
        $mandatoryMandatoryFalseSet->add(new FalseRequirement());
        $mandatoryOptionalFalseSet = new RequirementSet();
        $mandatoryOptionalFalseSet->add(new FalseRequirement(array('optional' => true)));
        $optionalMandatoryTrueSet = new RequirementSet(true);
        $optionalMandatoryTrueSet->add(new TrueRequirement());
        $optionalOptionalTrueSet = new RequirementSet(true);
        $optionalOptionalTrueSet->add(new TrueRequirement(array('optional' => true)));
        $optionalMandatoryFalseSet = new RequirementSet(true);
        $optionalMandatoryFalseSet->add(new FalseRequirement());
        $optionalOptionalFalseSet = new RequirementSet(true);
        $optionalOptionalFalseSet->add(new FalseRequirement(array('optional' => true)));

        $mandatoryMandatoryOptionalTrueSet = new RequirementSet();
        $mandatoryMandatoryOptionalTrueSet->merge($mandatoryOptionalTrueSet);
        $mandatoryMandatoryOptionalTrueSet->merge($mandatoryMandatoryFalseSet);
        $this->assertFalse(
            $mandatoryMandatoryOptionalTrueSet->fulfilled(),
            'A mandatory set of type and with two nested mandatory sets of type and where one has a optional'
            . ' TrueRequirement and the other one has a mandatory FalseRequirement is fulfilled'
        );

        $mandatoryMandatoryOptionalFalseSet = new RequirementSet();
        $mandatoryMandatoryOptionalFalseSet->merge($mandatoryOptionalFalseSet);
        $mandatoryMandatoryOptionalFalseSet->merge($mandatoryMandatoryTrueSet);
        $this->assertTrue(
            $mandatoryMandatoryOptionalFalseSet->fulfilled(),
            'A mandatory set of type and with two nested mandatory sets of type and where one has a mandatory'
            . ' TrueRequirement and the other one has a optional FalseRequirement is not fulfilled'
        );

        $optionalOptionalOptionalTrueSet = new RequirementSet(true);
        $optionalOptionalOptionalTrueSet->merge($optionalOptionalTrueSet);
        $optionalOptionalOptionalTrueSet->merge($optionalMandatoryFalseSet);
        $this->assertTrue(
            $optionalOptionalOptionalTrueSet->fulfilled(),
            'A optional set of type and with two nested optional sets of type and where one has a optional'
            . ' TrueRequirement and the other one has a mandatory FalseRequirement is not fulfilled'
        );

        $optionalOptionalOptionalFalseSet = new RequirementSet(true);
        $optionalOptionalOptionalFalseSet->merge($optionalOptionalFalseSet);
        $optionalOptionalOptionalFalseSet->merge($optionalMandatoryTrueSet);
        $this->assertTrue(
            $optionalOptionalOptionalFalseSet->fulfilled(),
            'A optional set of type and with two nested optional sets of type and where one has a mandatory'
            . ' TrueRequirement and the other one has a optional FalseRequirement is not fulfilled'
        );
    }

    public function testNestedMandatoryRequirementsOfTypeOr()
    {
        $trueSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $trueSet->add(new TrueRequirement());
        $falseSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $falseSet->add(new FalseRequirement());

        $nestedTrueSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $nestedTrueSet->merge($trueSet);
        $this->assertTrue(
            $nestedTrueSet->fulfilled(),
            'A nested mandatory set of type or with one mandatory TrueRequirement is not fulfilled'
        );

        $nestedFalseSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $nestedFalseSet->merge($falseSet);
        $this->assertFalse(
            $nestedFalseSet->fulfilled(),
            'A nested mandatory set of type or with one mandatory FalseRequirement is fulfilled'
        );

        $nestedMixedSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $nestedMixedSet->merge($trueSet);
        $nestedMixedSet->merge($falseSet);
        $this->assertTrue(
            $nestedMixedSet->fulfilled(),
            'Two nested mandatory sets of type or with one mandatory True- and'
            . ' one mandatory FalseRequirement respectively are not fulfilled'
        );
    }

    public function testNestedOptionalRequirementsOfTypeOr()
    {
        $trueSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $trueSet->add(new TrueRequirement());
        $falseSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $falseSet->add(new FalseRequirement());

        $nestedTrueSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $nestedTrueSet->merge($trueSet);
        $this->assertTrue(
            $nestedTrueSet->fulfilled(),
            'A nested optional set of type or with one mandatory TrueRequirement is not fulfilled'
        );

        $nestedFalseSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $nestedFalseSet->merge($falseSet);
        $this->assertTrue(
            $nestedFalseSet->fulfilled(),
            'A nested optional set of type or with one mandatory FalseRequirement is not fulfilled'
        );

        $nestedMixedSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $nestedMixedSet->merge($trueSet);
        $nestedMixedSet->merge($falseSet);
        $this->assertTrue(
            $nestedMixedSet->fulfilled(),
            'Two nested optional sets of type or with one mandatory True- and'
            . ' one mandatory FalseRequirement respectively are not fulfilled'
        );
    }

    public function testNestedMixedRequirementsOfTypeOr()
    {
        $mandatoryMandatoryTrueSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mandatoryMandatoryTrueSet->add(new TrueRequirement());
        $mandatoryOptionalTrueSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mandatoryOptionalTrueSet->add(new TrueRequirement(array('optional' => true)));
        $mandatoryMandatoryFalseSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mandatoryMandatoryFalseSet->add(new FalseRequirement());
        $mandatoryOptionalFalseSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mandatoryOptionalFalseSet->add(new FalseRequirement(array('optional' => true)));
        $optionalMandatoryTrueSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $optionalMandatoryTrueSet->add(new TrueRequirement());
        $optionalOptionalTrueSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $optionalOptionalTrueSet->add(new TrueRequirement(array('optional' => true)));
        $optionalMandatoryFalseSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $optionalMandatoryFalseSet->add(new FalseRequirement());
        $optionalOptionalFalseSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $optionalOptionalFalseSet->add(new FalseRequirement(array('optional' => true)));

        $mandatoryMandatoryOptionalTrueSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mandatoryMandatoryOptionalTrueSet->merge($mandatoryOptionalTrueSet);
        $mandatoryMandatoryOptionalTrueSet->merge($mandatoryMandatoryFalseSet);
        $this->assertTrue($mandatoryMandatoryOptionalTrueSet->fulfilled());

        $mandatoryMandatoryOptionalFalseSet = new RequirementSet(false, RequirementSet::MODE_OR);
        $mandatoryMandatoryOptionalFalseSet->merge($mandatoryOptionalFalseSet);
        $mandatoryMandatoryOptionalFalseSet->merge($mandatoryMandatoryTrueSet);
        $this->assertTrue($mandatoryMandatoryOptionalFalseSet->fulfilled());

        $optionalOptionalOptionalTrueSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $optionalOptionalOptionalTrueSet->merge($optionalOptionalTrueSet);
        $optionalOptionalOptionalTrueSet->merge($optionalMandatoryFalseSet);
        $this->assertTrue($optionalOptionalOptionalTrueSet->fulfilled());

        $optionalOptionalOptionalFalseSet = new RequirementSet(true, RequirementSet::MODE_OR);
        $optionalOptionalOptionalFalseSet->merge($optionalOptionalFalseSet);
        $optionalOptionalOptionalFalseSet->merge($optionalMandatoryTrueSet);
        $this->assertTrue($optionalOptionalOptionalFalseSet->fulfilled());
    }

    public function testNestedMandatoryRequirementsOfDifferentTypes()
    {
        $true = new TrueRequirement();
        $false = new FalseRequirement();

        $level1And = new RequirementSet();
        $level2FirstOr = new RequirementSet(false, RequirementSet::MODE_OR);
        $level2SecondOr = new RequirementSet(false, RequirementSet::MODE_OR);
        $level1And->merge($level2FirstOr)->merge($level2SecondOr);
        $level3FirstAnd = new RequirementSet();
        $level3SecondAnd = new RequirementSet();
        $level2FirstOr->merge($level3FirstAnd)->merge($level3SecondAnd);
        $level2SecondOr->merge($level3FirstAnd)->merge($level3SecondAnd);
        $level3FirstAnd->add($true)->add($true);
        $level3SecondAnd->add($false)->add($true);
        $this->assertTrue($level1And->fulfilled());

        $level1Or = new RequirementSet(false, RequirementSet::MODE_OR);
        $level2FirstAnd = new RequirementSet();
        $level2SecondAnd = new RequirementSet();
        $level1Or->merge($level2FirstAnd)->merge($level2SecondAnd);
        $level3FirstOr = new RequirementSet(false, RequirementSet::MODE_OR);
        $level3SecondOr = new RequirementSet(false, RequirementSet::MODE_OR);
        $level2FirstAnd->merge($level3FirstOr)->merge($level3SecondOr);
        $level2SecondAnd->merge($level3FirstOr)->merge($level3SecondOr);
        $level3FirstOr->add($false);
        $level3SecondOr->add($true);
        $this->assertFalse($level1Or->fulfilled());
    }

    public function testNestedOptionalRequirementsOfDifferentTypes()
    {
        $true = new TrueRequirement();
        $false = new FalseRequirement();

        $level1And = new RequirementSet();
        $level2FirstAnd = new RequirementSet(true);
        $level2SecondAnd = new RequirementSet(true);
        $level1And->merge($level2FirstAnd)->merge($level2SecondAnd);
        $level3FirstOr = new RequirementSet(true, RequirementSet::MODE_OR);
        $level3SecondOr = new RequirementSet(true, RequirementSet::MODE_OR);
        $level2FirstAnd->merge($level3FirstOr)->merge($level3SecondOr);
        $level2SecondAnd->merge($level3FirstOr)->merge($level3SecondOr);
        $level3FirstOr->add($false);
        $level3SecondOr->add($false);
        $this->assertFalse($level1And->fulfilled());
        $this->assertTrue($level2FirstAnd->fulfilled());
        $this->assertTrue($level2SecondAnd->fulfilled());

        $level1Or = new RequirementSet(false, RequirementSet::MODE_OR);
        $level2FirstOr = new RequirementSet(true, RequirementSet::MODE_OR);
        $level2SecondOr = new RequirementSet(true, RequirementSet::MODE_OR);
        $level1Or->merge($level2FirstOr)->merge($level2SecondOr);
        $level3FirstAnd = new RequirementSet(true);
        $level3SecondAnd = new RequirementSet(true);
        $level2FirstOr->merge($level3FirstAnd)->merge($level3SecondAnd);
        $level2SecondOr->merge($level3FirstAnd)->merge($level3SecondAnd);
        $level3FirstAnd->add($true)->add($true);
        $level3SecondAnd->add($false)->add($true);
        $this->assertTrue($level1Or->fulfilled());
    }

    public function testNestedMixedRequirementsOfDifferentTypes()
    {
        $this->markTestIncomplete();
    }
}
