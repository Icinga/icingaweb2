/*global Icinga:false, $:true, document: false, define:false require:false base_url:false console:false */
define(['logging','raphael'], function(log,raphael) {
    "use strict";
    var rad = Math.PI / 180;

    var getPaper = function(el,width,height) {
        if (el[0]) {
            el = el[0];
        }
        this.paper = raphael(el,width, height);
        this.paper.customAttributes.arc = function (xloc, yloc, value, total, R) {
            var alpha = 360 / total * value,
                a = (90 - alpha) * Math.PI / 180,
                x = xloc + R * Math.cos(a),
                y = yloc - R * Math.sin(a),
                path;
            if (total === value) {
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
    };

    var drawStatusArc = function(color,percentage,width,anim) {
        anim = anim || 500;
        // how much percentage this sub arc requires
        var alpha = this.rot + percentage / 100 * 180;           // this is the end angle for the arc

        var coords = getCoordsForAngle.call(this,alpha);
        var pos = getSection(alpha);

        var subArc = this.paper.path().attr({
            "stroke": color,
            "stroke-width": width || (this.radius/4)+"px",
            arc: [this.x, this.y, 0, 100, this.radius]
        });

        subArc.data("percentage",percentage);
        subArc.transform("r" + this.rot + "," + this.x + "," + this.y).animate({
            arc: [this.x, this.y, percentage, 100, this.radius]
        }, anim, "easeOut");

        //subArc.hover(indicateMouseOver,indicateMouseOut);
        this.rot += percentage / 100 * 360;
    };

    var getSection = function(alpha) {
        return {
            right: alpha < 180,
            left: alpha > 180,
            top: alpha < 90 || alpha > 270,
            bottom: alpha > 90 && alpha < 270
        };
    };

    var getCoordsForAngle = function(alpha) {
        var a = (90 - alpha) * Math.PI / 180;
        return {
            tx : this.x + (this.radius) * Math.cos(a),
            ty : this.y - (this.radius) * Math.sin(a)
        };
    };


    var sector = function(startAngle, endAngle) {
        var cx = this.x, cy = this.y, r = this.radius;
        var x1 = cx + r * Math.cos(-startAngle * rad),
            x2 = cx + r * Math.cos(-endAngle * rad),
            y1 = cy + r * Math.sin(-startAngle * rad),
            y2 = cy + r * Math.sin(-endAngle * rad);

        return ["M", cx, cy, "L", x1, y1, "A", r, r, 0, +(endAngle - startAngle > 180), 0, x2, y2, "z"];
    };



    var inlinePie = function(targetEl,h,w) {
        var colors = {
            0: '#11ee11',
            1: '#ffff00',
            2: '#ff2222',
            3: '#acacac'
        };
        targetEl = $(targetEl);
        var height = h || targetEl.height();
        var width =  w || targetEl.width();
        this.x = width/2;
        this.y = height/2;
        this.radius = Math.min(width,height)/2.5;
        var values = $(targetEl).html().split(",");
        targetEl.html("");

        getPaper.call(this,targetEl.first(),width,height);

        var total = 0;
        for(var i=0;i<values.length;i++) {
            total += parseInt(values[i],10);
            values[i] = parseInt(values[i],10);
        }
        var degStart = 0;
        var degEnd = 0;

        for(i=0;i<values.length;i++) {
            degEnd = degStart+(parseInt(values[i],10)/total*360);
            if(degEnd >= 360) {
                degEnd = 359.9;
            }
            log.debug(degStart,degEnd);
            this.paper.path(sector.call(this,degStart,degEnd)).attr({
                fill: colors[i],
                "stroke-width": '0.5px'
            });
            degStart = degEnd;
        }

    };

    var diskStatus = function(targetEl,full,ok,warning,critical) {
        targetEl = $(targetEl);
        this.rot = 0;
        var height = targetEl.height();
        var width = targetEl.width();
        this.x = width/2;
        this.y = height/2;
        this.radius = Math.min(width,height)/3;
        getPaper.call(this,targetEl.first(),width,height);
        for(var i = 0;i<5;i++) {
            this.paper
                .ellipse(this.x,this.y+(height/10)-i*(height/20),width/5,height/10)
                .attr({'fill' : '90-#ddd-#666'});
        }
        this.radius -= 7;
        drawStatusArc.call(this,'#000000',100,1,1);
        this.radius += 2;
        drawStatusArc.call(this,'#00ff00',ok,3 );
        drawStatusArc.call(this,'#ffff00',warning,3);
        drawStatusArc.call(this,'#ff0000',critical,3);
        this.radius += 2;
        drawStatusArc.call(this,'#000000',100,2,1);

        this.radius+=4;
        this.rot = 0;
        drawStatusArc.call(this,'#ff0000',full);
        drawStatusArc.call(this,'#0f0',100-full);
        this.rot = 0;
        this.radius += 5;
        drawStatusArc.call(this,'#000000',100,2,1);

    };

    return {
        inlinePie: inlinePie,
        diskStatus: diskStatus
    };

});


