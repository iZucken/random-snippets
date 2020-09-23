Fall = (function (window) {
    let context = null;
    let log = function () {
        window.console.log(...arguments);
    };
    let pi = Math.PI;
    let pi2 = Math.PI * 2;
    let pi20 = Math.PI * 20;
    function sin01 (v) {
        return (Math.sin(v)+1)/2;
    }
    function cos01 (v) {
        return (Math.cos(v)+1)/2;
    }
    let line = function (from, to) {
        context.moveTo(from.x, from.y);
        context.lineTo(to.x, to.y);
        context.stroke();
    };
    // createLinearGradient
    // createRadialGradient
    // createPattern

    // fill clip isPointInPath nonzero evenodd

    // context.shadowBlur = 5;
    // context.shadowColor = 'black';
    // context.shadowOffsetX
    // context.shadowOffsetY

    let t = 0;
    let dur = pi2 * 1000;
	
	let arc = function (x,y,r,a,aTo) {
        context.beginPath();
        context.arc(x, y, r, a, aTo);
        context.stroke();
        context.closePath();
	}
	
	let stroke = function (r, g, b, a) {
        context.strokeStyle = `rgba( ${r*255}, ${g*255}, ${b*255}, ${a} )`;
	}

    let render = function () {
        t += pi2 * 4;
        let ts = Math.sin(t / 777);
        let tc = Math.cos(t / 777);
        let step = (50 * pi2)/6;
        context.lineWidth = 10;
        context.lineCap = 'round';
        context.setLineDash([step,step * 5]);
        context.lineDashOffset = t / 5;
        stroke(.2+.8*sin01((t+0) / 999), 0, 1 - .8 * sin01((t+1000) / 1333),1);
        arc(250 + ts * 150, 250 + tc * 150, 50, 0, pi2);
        context.lineDashOffset = (t / 5) + step * 2;
        stroke(.2+.8*sin01((t+1000) / 1111), 0, 1 - .8 * sin01((t+2000) / 777),1);
        arc(250 + ts * 150, 250 + tc * 150, 50, 0, pi2);
        context.lineDashOffset = (t / 5) + step * 4;
        stroke(.2+.8*sin01((t+2000) / 887), 0, 1 - .8*sin01((t+3000) / 1223),1);
        arc(250 + ts * 150, 250 + tc * 150, 50, 0, pi2);
        window.requestAnimationFrame(render);
    };
    return {
        attach (canvasElement) {
            context = canvasElement.getContext('2d',{
                // alpha: false,
                // desynchronized: true,
                willReadFrequently: false,
                storage: false,
            });
            // context.lineWidth
            // context.lineCap
            // context.lineJoin
            // context.lineDashOffset
            // context.miterLimit
            // context.setLineDash
            // context.strokeStyle
            // context.fillStyle
            // context.bezierCurveTo
            // context.quadraticCurveTo()
            // context.arcTo()
            // context.rect()
            // context.ellipse()
            render();
        }
    }
})(window);