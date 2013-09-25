// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

var should  = require('should');
var rjsmock = require('requiremock.js');

GLOBAL.document = $('body');

var registry;
var setUp = function() {
    requireNew('icinga/componentRegistry.js');
    registry = rjsmock.getDefine();
};

var cleanTestDom = function() {
    $('body').empty();
};


describe('Component registry',function() {
    it('Ids are created automatically in the form "icinga-component-<id>"', function() {
        setUp();

        registry.add({}, null, null).should.equal('icinga-component-0');
        registry.add({}, null, null).should.equal('icinga-component-1');
        registry.add({}, null, null).should.equal('icinga-component-2');

        cleanTestDom();
    });

    xit('Existing ids are preserved', function() {
        setUp();

        registry.add({}, 'user-defined-id', null).should.equal('user-defined-id');

        cleanTestDom();
    });

    it('Components are correctly added to the library', function() {
        setUp();

        var cmp2 = { component: "cmp2" };
        registry.add(cmp2, null, null);
        registry.getById('icinga-component-0').should.equal(cmp2);

        cleanTestDom();
    });

    /**
     * Not supported anymore
     */
    xit('getId(component) should return the components assigned id.', function() {
        setUp();

        var cmp1 = { component: "cmp1" };
        registry.add(cmp1, 'user-defined-id', null);
        registry.getId(cmp1).should.equal('user-defined-id');

        var cmp2 = { component: "cmp2" };
        registry.add(cmp2, 'user-defined-id-2',null);
        registry.getId(cmp2).should.equal('user-defined-id-2');

        should.not.exist(registry.getId({}));

        cleanTestDom();
    });

    it('getByType() should return all components of a certain type', function() {
        setUp();

        var cmp1 = { component: "some/type" };
        registry.add(cmp1,'some/type');

        var cmp2 = { component: "some/type" };
        registry.add(cmp2, "some/type");

        var cmp3 = { component: "other/type" };
        registry.add(cmp3, "other/type");

        var cmps = registry.getByType('some/type');
        cmps.length.should.equal(2);
        cmps[0].component.should.equal('some/type');
        cmps[1].component.should.equal('some/type');

        cleanTestDom();
    });

    it('getComponents() should return all components', function() {
        setUp();

        var cmp1 = { component: "cmp1" };
        registry.add(cmp1, null, null);

        var cmp2 = { component: "cmp2" };
        registry.add(cmp2, null, null);

        var cmp3 = { component: "cmp3" };
        registry.add(cmp3, null, null);

        var cmps = registry.getComponents();
        cmps.length.should.equal(3);
        cmps[0].should.equal(cmp1);
        cmps[1].should.equal(cmp2);
        cmps[2].should.equal(cmp3);

        cleanTestDom();
    });

    /*
     * NOTE: The functionality of the garbage collection of this class is
     * tested in the componentTest.js
     */
});

