define(['jquery','raphael'], function($,Raphael) {
    return function(el) {
        this.el = $(el);

        this.height = this.el.height();
        this.width = this.el.width();
        this.dataSet = {};
        this.paper= null;
        this.radius = 0;
        this.total =  0;

        var construct = (function(el,cfg) {
            cfg = cfg || {}
            this.radius = cfg.radius || Math.min(this.width,this.height)/4
            this.x = cfg.x || this.width/2;
            this.y = cfg.y || this.height/2;
            this.paper = getPaper();
            console.log(this.el);
        }).bind(this);

        var getSection = function(alpha) {
            return {
                right: alpha < 180,
                left: alpha > 180,
                top: alpha < 90 || alpha > 270,
                bottom: alpha > 90 && alpha < 270
            }
        }

        var getCoordsForAngle = (function(alpha) {
            var a = (90 - alpha) * Math.PI / 180;
            return {
                tx : this.x + (this.radius) * Math.cos(a),
                ty : this.y - (this.radius) * Math.sin(a)
            }
        }).bind(this);

        var drawSubCircle = (function(coords, color,percentage) {
            this.paper.circle(coords.tx,coords.ty,0).attr({
                fill: color,
                stroke: 'none'
            }).animate({r: this.radius/2},500, "easeOut");
        }).bind(this);

        var indicateMouseOver = function() {
            this.animate({"stroke-width": 30 }, 400, "bounce");
        }

        var indicateMouseOut = function() {
            this.animate({"stroke-width":  18}, 400, "bounce");
        }

        var drawSubArcFor = (function(elem,rot) {
            var percentage = elem.items.length/this.total*100;       // how much percentage this sub arc requires
            var alpha = rot + percentage / 100 * 180;           // this is the end angle for the arc

            var coords = getCoordsForAngle(alpha);
            var pos = getSection(alpha);
            if(elem.items.length > 10)
                drawSubCircle(coords,elem.color,percentage/100);

            var subArc = this.paper.path().attr({
                "stroke": elem.color,
                "stroke-width": 18,
                arc: [this.x, this.y, 0, 100, this.radius]
            });

            subArc.data("percentage",percentage);
            subArc.data("item",elem);
            subArc.transform("r" + rot + "," + this.x + "," + this.y).animate({
                arc: [this.x, this.y, percentage, 100, this.radius]
            }, 500, "easeOut");

            var text = this.paper
                .text(coords.tx,coords.ty,elem.items.length)
                .attr({'text-anchor': alpha  < 180  ? 'start' : 'end'});

            subArc.hover(indicateMouseOver,indicateMouseOut);
            return percentage / 100 * 360;
        }).bind(this);

        var drawContainerCircle = (function() {

            var rot = 0;
            this.total = 0;
            for (var i = 0;i<this.dataSet.childs.length;i++) {
                this.total += this.dataSet.childs[i].items.length
            }

            for (var i = 0;i<this.dataSet.childs.length;i++) {
                rot += drawSubArcFor(this.dataSet.childs[i],rot);
            }
            var innerCircleShadow = this.paper.circle(this.x, this.y + 1, this.radius - 3).attr({
                fill: 'black',
                stroke: 'none',
                opacity: 0.4
            });
            var innerCircle = this.paper.circle(this.x, this.y, this.radius - 4).attr({
                fill: this.dataSet.color,
                stroke: 'none'
            });
            this.paper.text(this.x, this.y,this.dataSet.label);
        }).bind(this);

        var getPaper = (function() {
            var paper = Raphael(this.el.first(),this.width, this.height);

            paper.customAttributes.arc = function (xloc, yloc, value, total, R) {
                var alpha = 360 / total * value,
                    a = (90 - alpha) * Math.PI / 180,
                    x = xloc + R * Math.cos(a),
                    y = yloc - R * Math.sin(a),
                    path;
                if (total == value) {
                    path = [
                        ["M", xloc, yloc - R],
                        ["A", R, R, 0, 1, 1, xloc - 0.01, yloc - R]
                    ];
                } else {
                    path = [
                        ["M", xloc, yloc - R],
                        ["A", R, R, 0, +(alpha > 180), 1, x, y]
                    ];
                }
                return {
                    path: path
                };
            };
            return paper;
        }).bind(this);

        this.drawFor = function(dataSet) {
            this.dataSet = dataSet;
            drawContainerCircle();
        }

        construct.apply(this,arguments);
    }
});