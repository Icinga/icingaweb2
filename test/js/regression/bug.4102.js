describe('regression test for bug #4102', function(){
    it('Object.create', function(){
        Object.create.should.be.a('function');

        var t = {
            name: 'test123'
        };

        t.should.have.property('name').and.equal('test123');
    });
});
